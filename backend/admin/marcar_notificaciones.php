<?php
require "../config/db.php";
require "../auth/guard.php";
require "../security/functions.php";

if (!in_array($_SESSION['rol'] ?? '', ['admin', 'supervisor'], true)) die("Acceso no autorizado");
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarTokenCSRF($_POST['csrf_token'] ?? '')) die("Error de seguridad: petición no válida.");

$conn->query("UPDATE notificaciones SET leida = 1 WHERE leida = 0");

$volver = $_POST['volver'] ?? 'dashboard.php';
if (!preg_match('/^dashboard\.php(\?[A-Za-z0-9_=&%\-]*)?$/', $volver)) $volver = 'dashboard.php';
header("Location: ../../frontend/pages/$volver");
exit;
