#!/usr/local/bin/php -q
<?php
/**
 * Entrada de correos desde cPanel (RF-02 / CU-02 / HU-02).
 *
 * En cPanel > Correo > Reenviadores, el buzón que reciben las impresoras se configura con
 * "Canalizar a un programa" apuntando a este archivo. Cada correo entrante llega por la entrada estándar (STDIN);
 * aquí se identifica el equipo por su número de serie y se guarda el contador en `lecturas`, sin intervención humana.
 *
 * Prueba local:  php backend/cron/recibir_correo.php < ejemplo.eml
 *
 * Termina con código 0 (correo procesado o descartado, sin rebotes). Solo si la base de datos no responde devuelve 75
 * para que el servidor de correo reintente más tarde. Los eventos se registran en backend/storage/ingesta.log.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require __DIR__ . '/../lib/mime.php';
require __DIR__ . '/../lib/parser_reporte.php';
require __DIR__ . '/../lib/ingesta.php';
require __DIR__ . '/../lib/aviso_equipo.php';

$cfg = require __DIR__ . '/../config/ingesta.php';
$dirStorage = __DIR__ . '/../storage';

function ingesta_log($dir, $msg) {
    @file_put_contents($dir . '/ingesta.log', '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
}

$crudo = stream_get_contents(STDIN);
if ($crudo === false || trim($crudo) === '') { ingesta_log($dirStorage, 'Correo vacío'); exit(0); }

try {
    if (!empty($cfg['guardar_correos'])) {
        @mkdir($dirStorage . '/correos', 0755, true);
        @file_put_contents($dirStorage . '/correos/' . date('Ymd-His') . '-' . substr(md5($crudo), 0, 6) . '.eml', $crudo);
    }

    $mail = mime_parsear($crudo);

    if (!empty($cfg['remitentes_permitidos'])) {
        $permitido = false;
        foreach ($cfg['remitentes_permitidos'] as $r) {
            if (stripos($mail['remitente'], $r) !== false) { $permitido = true; break; }
        }
        if (!$permitido) { ingesta_log($dirStorage, 'Remitente no permitido: ' . $mail['remitente']); exit(0); }
    }

    // 1) Reporte de contadores (serie + contador)   2) Aviso de alerta del equipo (MAC / host, sin contadores)
    $rep = parsearReporte($mail['asunto'] . "\n" . $mail['texto']);
    $aviso = $rep ? null : parsearAvisoEquipo($mail['asunto'], $mail['texto']);
    if (!$rep && !$aviso) {
        ingesta_log($dirStorage, 'Sin serie/contador reconocibles. Remitente: ' . $mail['remitente'] . ' | Asunto: ' . $mail['asunto']);
        exit(0);
    }

    try {
        require __DIR__ . '/../config/db.php';
    } catch (Throwable $e) {
        // Base de datos no disponible: código 75 (fallo temporal) para que el servidor de correo reintente el mensaje.
        ingesta_log($dirStorage, 'Base de datos no disponible, se reintentará: ' . $e->getMessage());
        exit(75);
    }

    if ($rep) {
        $r = registrarLecturaSmtp($conn, $rep);
        ingesta_log($dirStorage, $rep['serie'] . ': ' . ($r['ok'] ? 'lectura guardada' : 'rechazada - ' . $r['motivo']));
    } else {
        $r = registrarAvisoEquipo($conn, $aviso);
        $quien = $aviso['mac'] ?: $aviso['hostname'];
        if (!$r['ok'])                   ingesta_log($dirStorage, "Aviso de $quien rechazado - " . $r['motivo']);
        elseif (!empty($r['duplicado'])) ingesta_log($dirStorage, "Aviso de $quien repetido (ignorado): " . $aviso['resumen']);
        else                             ingesta_log($dirStorage, "Aviso de $quien guardado [" . $aviso['categoria'] . ']: ' . $aviso['resumen']
                                                          . (!empty($r['ticket']) ? ' (ticket de mantenimiento creado)' : ''));
    }
} catch (Throwable $e) {
    ingesta_log($dirStorage, 'Error: ' . $e->getMessage());
}
exit(0);
