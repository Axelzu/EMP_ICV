<?php
// RF-03 / CU-03: el técnico escanea el QR del equipo y confirma la visita de mantenimiento preventivo.
// Regla: el escaneo solo es válido si existe un ticket "Pendiente" para ese equipo (y, siendo técnico, asignado a él).
require "../config/db.php";
require "../auth/guard.php";
require "../security/functions.php";

header('Content-Type: application/json; charset=utf-8');

function responder($ok, $mensaje, $extra = []) {
    echo json_encode(array_merge(['ok' => $ok, 'mensaje' => $mensaje], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarTokenCSRF($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    responder(false, 'Sesión expirada o petición no válida. Recargue la página.');
}

$token = trim($_POST['qr'] ?? '');
if (stripos($token, 'ICV:') === 0) $token = substr($token, 4);
if (!preg_match('/^[a-f0-9]{32}$/i', $token)) responder(false, 'Código QR no reconocido.');

$st = $conn->prepare("SELECT id, serie, dependencia, marca_modelo FROM equipos WHERE qr_token = ? LIMIT 1");
$st->bind_param("s", $token);
$st->execute();
$eq = $st->get_result()->fetch_assoc();
if (!$eq) responder(false, 'El código QR no corresponde a ningún equipo registrado.');

$uid = (int)$_SESSION['user_id'];
$rol = $_SESSION['rol'] ?? 'tecnico';

$st = $conn->prepare("SELECT id, tecnico_id, motivo FROM tickets_mantenimiento
                      WHERE equipo_id = ? AND estado = 'Pendiente' ORDER BY fecha_agendada ASC, id ASC LIMIT 1");
$st->bind_param("i", $eq['id']);
$st->execute();
$tk = $st->get_result()->fetch_assoc();

if (!$tk) {
    registrarLog($conn, "QR_RECHAZADO", "Escaneo sin ticket pendiente: equipo {$eq['serie']}");
    responder(false, "El equipo {$eq['marca_modelo']} ({$eq['serie']}) no tiene un mantenimiento preventivo agendado.", ['equipo' => $eq]);
}
if ($rol === 'tecnico' && $tk['tecnico_id'] !== null && (int)$tk['tecnico_id'] !== $uid) {
    responder(false, 'Este mantenimiento está asignado a otro técnico.', ['equipo' => $eq]);
}

$st = $conn->prepare("UPDATE tickets_mantenimiento SET estado = 'Completado', fecha_completado = NOW(), completado_por = ?
                      WHERE id = ? AND estado = 'Pendiente'");
$st->bind_param("ii", $uid, $tk['id']);
$st->execute();
if ($st->affected_rows < 1) responder(false, 'El ticket ya había sido cerrado.', ['equipo' => $eq]);

registrarLog($conn, "MANTENIMIENTO", "Ticket #{$tk['id']} completado por QR: equipo {$eq['serie']}");
responder(true, "Mantenimiento confirmado: {$eq['marca_modelo']} · {$eq['dependencia']} ({$eq['serie']}).", ['equipo' => $eq, 'ticket' => (int)$tk['id']]);
