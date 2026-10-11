<?php
// Botón "Recalcular proyecciones" del panel: ejecuta el mismo ciclo que la tarea diaria.
require "../config/db.php";
require "../auth/guard.php";
require "../security/functions.php";
require "../lib/motor.php";

if (!in_array($_SESSION['rol'] ?? '', ['admin', 'supervisor'], true)) die("Acceso no autorizado");
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarTokenCSRF($_POST['csrf_token'] ?? '')) die("Error de seguridad: petición no válida.");

@set_time_limit(120);
$r = motor_ejecutar($conn);
$ok = count(array_filter($r['equipos'], fn($e) => isset($e['estimado_bn'])));
registrarLog($conn, "PROYECCION", "Recálculo manual: $ok equipos proyectados");

$msg = "Proyecciones recalculadas: $ok equipos. Alertas nuevas: {$r['omisiones']['alertas']}.";
header("Location: ../../frontend/pages/dashboard.php?msg=" . urlencode($msg));
exit;
