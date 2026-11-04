<?php
/**
 * Recepción de lecturas del colector de contadores (RF-02).
 *
 * Las impresoras que no reportan contadores por correo (p. ej. Ricoh MP C2503) se leen desde la red del cliente con
 * colector/colector_ricoh.php, que envía aquí por HTTPS (conexión solo saliente) un JSON:
 *   {"token": "...", "serie": "E214MB60667", "contador_bn": 448877, "contador_color": 347713}
 *
 * El token se define en backend/config/colector.local.php (ignorado por git). Sin ese archivo el endpoint queda desactivado.
 */
header('Content-Type: application/json; charset=utf-8');

function responder($codigo, $datos) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}
function api_log($msg) {
    @file_put_contents(__DIR__ . '/../storage/ingesta.log', '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(405, ['ok' => false, 'error' => 'Método no permitido']);

$archivo = __DIR__ . '/../config/colector.local.php';
$cfg = file_exists($archivo) ? require $archivo : [];
$tokenEsperado = (string)($cfg['token'] ?? '');
if (strlen($tokenEsperado) < 16) responder(503, ['ok' => false, 'error' => 'Colector no configurado']);

$crudo = file_get_contents('php://input', false, null, 0, 4096);
$d = json_decode((string)$crudo, true);
if (!is_array($d)) responder(400, ['ok' => false, 'error' => 'JSON inválido']);

if (!hash_equals($tokenEsperado, (string)($d['token'] ?? ''))) {
    api_log('Colector: token inválido desde ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    responder(401, ['ok' => false, 'error' => 'Token inválido']);
}

$serie = trim((string)($d['serie'] ?? ''));
$bn = $d['contador_bn'] ?? null;
$color = $d['contador_color'] ?? null;
if ($serie === '' || strlen($serie) > 100 || !is_int($bn) || !is_int($color) || $bn < 0 || $color < 0) {
    responder(422, ['ok' => false, 'error' => 'Datos inválidos']);
}

try {
    require __DIR__ . '/../config/db.php';
    require __DIR__ . '/../lib/ingesta.php';
} catch (Throwable $e) {
    api_log('Colector: base de datos no disponible: ' . $e->getMessage());
    responder(503, ['ok' => false, 'error' => 'Base de datos no disponible']);
}

$r = registrarLecturaSmtp($conn, ['serie' => $serie, 'contador_bn' => $bn, 'contador_color' => $color, 'toner_pct' => null, 'fecha' => null], 'COLECTOR');
api_log("Colector $serie: " . ($r['ok'] ? "lectura guardada (B/N $bn, color $color)" : 'rechazada - ' . $r['motivo']));
responder($r['ok'] ? 200 : 422, $r['ok'] ? ['ok' => true] : ['ok' => false, 'error' => $r['motivo']]);
