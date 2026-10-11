<?php
/**
 * Pruebas unitarias sin base de datos ni dependencias: lector de correos, parser de reportes y motor predictivo.
 * Uso: php backend/tests/run.php   (devuelve código distinto de 0 si algo falla)
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require __DIR__ . '/../lib/parser_reporte.php';
require __DIR__ . '/../lib/mime.php';
require __DIR__ . '/../lib/motor.php';

$fallos = 0; $total = 0;
function prueba($nombre, $cond) {
    global $fallos, $total;
    $total++;
    if ($cond) { echo "  ok   $nombre\n"; return; }
    $fallos++; echo "  FALLA $nombre\n";
}

echo "Parser de reportes\n";
$r = parsearReporte("Serial Number: KM-A1001\nTotal Black: 120,500\nTotal Color: 31.200\nToner Level: 55%");
prueba('reporte en inglés', $r && $r['serie'] === 'KM-A1001' && $r['contador_bn'] === 120500 && $r['contador_color'] === 31200 && $r['toner_pct'] === 55);
$r = parsearReporte("Número de serie: XR-B2002\nB/N: 98000\nColor: 0");
prueba('reporte en español', $r && $r['serie'] === 'XR-B2002' && $r['contador_bn'] === 98000);
$r = parsearReporte("Serial Number,KM-G7007\nTotal Black,116700\nTotal Color,0\nToner Level,70%");
prueba('reporte CSV con comas', $r && $r['serie'] === 'KM-G7007' && $r['contador_bn'] === 116700 && $r['toner_pct'] === 70);
prueba('sin serie => null', parsearReporte("Total Black: 100") === null);
prueba('"Number" no es una serie', parsearReporte("Serial Number: \nTotal Black: 100") === null);
prueba('sin contador => null', parsearReporte("Serial Number: AB-1234") === null);

echo "Lector de correo (MIME)\n";
$csv = base64_encode("Serial Number,KM-G7007\nTotal Black,117000");
$eml = "From: copiadora@cliente.local\r\nSubject: =?UTF-8?B?UmVwb3J0ZSBkZSBjb250YWRvcmVz?=\r\nMIME-Version: 1.0\r\n"
     . "Content-Type: multipart/mixed; boundary=\"XYZ\"\r\n\r\n--XYZ\r\nContent-Type: text/plain; charset=utf-8\r\n\r\nReporte adjunto\r\n"
     . "--XYZ\r\nContent-Type: text/csv; name=\"c.csv\"\r\nContent-Disposition: attachment; filename=\"c.csv\"\r\nContent-Transfer-Encoding: base64\r\n\r\n$csv\r\n--XYZ--\r\n";
$m = mime_parsear($eml);
prueba('decodifica el asunto', $m['asunto'] === 'Reporte de contadores');
prueba('lee el adjunto CSV', strpos($m['texto'], 'KM-G7007') !== false);
$qp = "From: a@b.c\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\nN=C3=BAmero de serie: RC-H8008\r\nB/N: 90.500\r\n";
$r = parsearReporte(mime_parsear($qp)['texto']);
prueba('quoted-printable con acentos', $r && $r['serie'] === 'RC-H8008' && $r['contador_bn'] === 90500);

echo "Motor predictivo\n";
function lineales($meses, $base, $porMes) {
    $o = [];
    for ($i = 0; $i < $meses; $i++) $o[] = ['fecha' => sprintf('2025-%02d-28 08:00:00', $i + 1), 'valor' => $base + $i * $porMes];
    return $o;
}
$s = motor_serie_mensual([['fecha' => '2025-01-28 00:00:00', 'valor' => 1000], ['fecha' => '2025-04-28 00:00:00', 'valor' => 4000]]);
prueba('interpola meses sin lectura', array_map(fn($p) => (int)$p['valor'], $s) === [1000, 2000, 3000, 4000]);
prueba('el consumo nunca es negativo', motor_consumos([['ym' => '2025-01', 'valor' => 100], ['ym' => '2025-02', 'valor' => 90]]) === [0]);
$p = motor_proyectar(lineales(9, 10000, 3000), '2025-10');
$esperado = 10000 + 9 * 3000;
prueba('proyecta un consumo constante con error < 2%', $p && abs($p['estimado'] - $esperado) / $esperado < 0.02);
prueba('con 9 meses no es preliminar', $p && $p['preliminar'] === false);
$p = motor_proyectar(lineales(4, 10000, 3000), '2025-05');
prueba('menos de 6 meses => preliminar', $p && $p['preliminar'] === true);
prueba('sin historial suficiente no proyecta', motor_proyectar(lineales(1, 10000, 3000), '2025-03') === null);

echo "\n$total pruebas, $fallos fallas\n";
exit($fallos ? 1 : 0);
