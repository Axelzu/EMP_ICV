<?php
/**
 * Colector de contadores para impresoras Ricoh (RF-02).
 *
 * Se ejecuta en un computador de la red del cliente que esté encendido (o en el de un técnico durante la visita).
 * Lee de cada impresora su número de serie y sus contadores desde la página de Web Image Monitor y los envía por HTTPS al
 * sistema (tráfico solo saliente: el sistema nunca entra a la red del cliente).
 *
 * Uso:   php colector/colector_ricoh.php
 * Programarlo: Programador de tareas de Windows (una vez al día o al cierre de mes) ejecutando el comando anterior.
 * Configuración: copiar colector/config.example.php a colector/config.php y completar la URL, el token y las IP.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require __DIR__ . '/../backend/lib/ricoh_contador.php';

$archivo = __DIR__ . '/config.php';
if (!file_exists($archivo)) { fwrite(STDERR, "Falta colector/config.php (copie config.example.php).\n"); exit(2); }
$cfg = require $archivo;

function http($url, $opciones) {
    $ctx = stream_context_create(['http' => $opciones + ['timeout' => 10, 'ignore_errors' => true]]);
    $cuerpo = @file_get_contents($url, false, $ctx);
    $codigo = 0;
    foreach ($http_response_header ?? [] as $h) if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) $codigo = (int)$m[1];
    return [$codigo, $cuerpo];
}

$fallos = 0;
foreach ($cfg['impresoras'] as $ip) {
    echo "[$ip] ";
    [$c1, $htmlEquipo] = http("http://$ip/web/guest/en/websys/status/configuration.cgi", ['method' => 'GET']);
    [$c2, $htmlCont]   = http("http://$ip/web/guest/en/websys/status/getUnificationCounter.cgi", ['method' => 'GET']);
    $equipo = $c1 === 200 ? ricoh_parsear_equipo($htmlEquipo) : null;
    $cont   = $c2 === 200 ? ricoh_parsear_contadores($htmlCont) : null;
    if (!$equipo || !$cont) { echo "no se pudo leer la impresora\n"; $fallos++; continue; }
    if (!$cont['consistente']) echo "(aviso: B/N + color no suma el total) ";

    $json = json_encode(['token' => $cfg['token'], 'serie' => $equipo['serie'],
                         'contador_bn' => $cont['contador_bn'], 'contador_color' => $cont['contador_color']]);
    [$codigo, $resp] = http($cfg['url'], ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $json]);
    $ok = $codigo === 200;
    echo "{$equipo['serie']} B/N {$cont['contador_bn']} color {$cont['contador_color']} -> " . ($ok ? 'enviado' : "error $codigo $resp") . "\n";
    if (!$ok) $fallos++;
}
exit($fallos ? 1 : 0);
