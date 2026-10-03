<?php
// HU-09: el administrador crea cuentas de usuario.
require "../config/db.php";
require "../auth/guard.php";
require "../security/functions.php";

if (($_SESSION['rol'] ?? '') !== 'admin') die("Acceso no autorizado");
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarTokenCSRF($_POST['csrf_token'] ?? '')) die("Error de seguridad: petición no válida.");

function volver($tipo, $texto) {
    header("Location: ../../frontend/pages/usuarios.php?$tipo=" . urlencode($texto));
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$email  = strtolower(trim($_POST['email'] ?? ''));
$pass   = $_POST['password'] ?? '';
$rol    = $_POST['rol'] ?? 'tecnico';

if ($nombre === '' || mb_strlen($nombre) > 100)            volver('error', 'Nombre inválido.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))             volver('error', 'Correo inválido.');
if (strlen($pass) < 8)                                      volver('error', 'La contraseña debe tener al menos 8 caracteres.');
if (!in_array($rol, ['admin', 'supervisor', 'tecnico'], true)) volver('error', 'Rol inválido.');

$st = $conn->prepare("SELECT id FROM users WHERE email = ?");
$st->bind_param("s", $email);
$st->execute();
if ($st->get_result()->num_rows > 0) volver('error', 'Ya existe un usuario con ese correo.');

$hash = password_hash($pass, PASSWORD_DEFAULT);
$st = $conn->prepare("INSERT INTO users (nombre, email, password, rol, activo) VALUES (?, ?, ?, ?, 1)");
$st->bind_param("ssss", $nombre, $email, $hash, $rol);
if (!$st->execute()) volver('error', 'No se pudo crear el usuario.');

registrarLog($conn, "USUARIO_CREADO", "Se creó el usuario $email con rol $rol");
volver('mensaje', "Usuario $email creado.");
