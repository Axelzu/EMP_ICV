<?php
// HU-09: el administrador elimina definitivamente una cuenta de usuario.
// Reglas: solo administrador, petición POST con token CSRF, no puede eliminarse a sí mismo
// y no puede quedar el sistema sin ningún administrador activo.
require "../config/db.php";
require "../auth/guard.php";
require "../security/functions.php";

if (($_SESSION['rol'] ?? '') !== 'admin') die("Acceso no autorizado");
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarTokenCSRF($_POST['csrf_token'] ?? '')) die("Error de seguridad: petición no válida.");

function volver($tipo, $texto) {
    header("Location: ../../frontend/pages/usuarios.php?$tipo=" . urlencode($texto));
    exit;
}

$id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$id) die("Datos inválidos");
if ($id === (int)$_SESSION['user_id']) volver('error', 'No puede eliminar su propia cuenta.');

$st = $conn->prepare("SELECT nombre, email, rol, activo FROM users WHERE id = ?");
$st->bind_param("i", $id);
$st->execute();
$u = $st->get_result()->fetch_assoc();
if (!$u) volver('error', 'El usuario no existe.');

// No dejar el sistema sin administradores activos
if ($u['rol'] === 'admin' && (int)$u['activo'] === 1) {
    $r = $conn->query("SELECT COUNT(*) n FROM users WHERE rol = 'admin' AND activo = 1")->fetch_assoc();
    if ((int)$r['n'] <= 1) volver('error', 'No se puede eliminar al único administrador activo.');
}

$conn->begin_transaction();
try {
    // Los mantenimientos pendientes de un técnico eliminado quedan sin asignar (los puede cerrar cualquier técnico)
    $st = $conn->prepare("UPDATE tickets_mantenimiento SET tecnico_id = NULL WHERE tecnico_id = ? AND estado = 'Pendiente'");
    $st->bind_param("i", $id);
    $st->execute();

    $st = $conn->prepare("DELETE FROM users WHERE id = ?");
    $st->bind_param("i", $id);
    $st->execute();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    volver('error', 'No se pudo eliminar el usuario.');
}

// El detalle conserva el nombre y el correo: la auditoría sigue siendo legible aunque la cuenta ya no exista
registrarLog($conn, "USUARIO_ELIMINADO", "Se eliminó la cuenta ID $id ({$u['nombre']}, {$u['email']}, rol {$u['rol']})");
volver('mensaje', "Usuario {$u['email']} eliminado.");
