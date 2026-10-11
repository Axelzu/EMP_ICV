<?php
/**
 * Tarea diaria (cron de cPanel): proyecciones del motor predictivo, tickets de mantenimiento y alertas de omisión.
 * Comando sugerido en cPanel > Tareas Cron (una vez al día):
 *     /usr/local/bin/php /home/USUARIO_CPANEL/public_html/backend/cron/motor.php
 * Prueba: php backend/cron/motor.php --hoy=2026-10-08
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/motor.php';

$hoy = null;
foreach ($argv as $a) {
    if (strpos($a, '--hoy=') === 0) $hoy = new DateTimeImmutable(substr($a, 6) . ' 12:00:00');
}
$r = motor_ejecutar($conn, $hoy);
foreach ($r['equipos'] as $e) echo json_encode($e, JSON_UNESCAPED_UNICODE) . "\n";
echo '[' . date('Y-m-d H:i:s') . '] Equipos: ' . count($r['equipos']) . ' | alertas nuevas: ' . $r['omisiones']['alertas']
   . ' | contingencias aplicadas: ' . $r['omisiones']['aplicadas'] . "\n";
