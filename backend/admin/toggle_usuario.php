<?php
// HU-09: desactivar / reactivar cuentas (no se borran para conservar la trazabilidad de auditoría).
require "../config/db.php";
require "../auth/guard.php";
require "../security/functions.php";

if (($_SESSION['rol'] ?? '') !== 'admin') die("Acceso no autorizado");
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarTokenCSRF($_POST['csrf_token'] ?? '')) die("Error de seguridad: petición no válida.");

$id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$id) die("Datos inválidos");
if ($id === (int)$_SESSION['user_id']) {
    header("Location: ../../frontend/pages/usuarios.php?error=" . urlencode("No puede desactivar su propia cuenta."));
    exit;
}

$st = $conn->prepare("UPDATE users SET activo = 1 - activo WHERE id = ?");
$st->bind_param("i", $id);
$st->execute();
registrarLog($conn, "USUARIO_ESTADO", "Se cambió el estado de la cuenta ID $id");
header("Location: ../../frontend/pages/usuarios.php?mensaje=" . urlencode("Estado de la cuenta actualizado."));
exit;
