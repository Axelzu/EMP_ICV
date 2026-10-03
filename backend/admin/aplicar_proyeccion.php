<?php
// CU-08: el personal administrativo autoriza (o revierte) el uso de la cifra predictiva como respaldo del informe.
require "../config/db.php";
require "../auth/guard.php";
require "../security/functions.php";

if (!in_array($_SESSION['rol'] ?? '', ['admin', 'supervisor'], true)) {
    die("Acceso no autorizado");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarTokenCSRF($_POST['csrf_token'] ?? '')) {
    die("Error de seguridad: petición no válida.");
}

$id = filter_var($_POST['proyeccion_id'] ?? 0, FILTER_VALIDATE_INT);
$accion = $_POST['accion'] ?? '';
$volver = $_POST['volver'] ?? 'dashboard.php';
if (!preg_match('/^dashboard\.php(\?[A-Za-z0-9_=&%\-]*)?$/', $volver)) $volver = 'dashboard.php';
if (!$id || !in_array($accion, ['aplicar', 'revertir'], true)) die("Datos inválidos");

$uid = (int)$_SESSION['user_id'];
if ($accion === 'aplicar') {
    $st = $conn->prepare("UPDATE proyecciones SET aplicada = 1, aplicada_por = ?, aplicada_en = NOW() WHERE id = ?");
    $st->bind_param("ii", $uid, $id);
    $msg = "Estimación aplicada al informe.";
} else {
    $st = $conn->prepare("UPDATE proyecciones SET aplicada = 0, aplicada_por = NULL, aplicada_en = NULL WHERE id = ?");
    $st->bind_param("i", $id);
    $msg = "Estimación revertida.";
}
$st->execute();
registrarLog($conn, $accion === 'aplicar' ? "CONTINGENCIA" : "CONTINGENCIA_REVERTIDA", "Proyección ID $id: $accion");

header("Location: ../../frontend/pages/$volver" . (strpos($volver, '?') === false ? '?' : '&') . "msg=" . urlencode($msg));
exit;
