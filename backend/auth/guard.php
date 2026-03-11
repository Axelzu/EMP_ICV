<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verificar si está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: /frontend/pages/login.php");
    exit;
}

$rol_actual = $_SESSION['rol'] ?? 'tecnico';
$url_solicitada = $_SERVER['REQUEST_URI'];

// 2. PROTECCIÓN DE RUTAS SEGÚN ROL

// Regla para GESTIÓN DE USUARIOS: Solo entra el Admin
if (strpos($url_solicitada, '/usuarios/') !== false && $rol_actual !== 'admin') {
    header("Location: /frontend/pages/inicio.php?error=acceso_denegado_admin");
    exit;
}

// Regla para MONITOREO/LOGS: No entra el Técnico
if (strpos($url_solicitada, '/monitoreo/') !== false && $rol_actual === 'tecnico') {
    header("Location: /frontend/pages/inicio.php?error=acceso_denegado_monitoreo");
    exit;
}

// Regla para GESTIÓN DE EQUIPOS (crear/borrar/editar): No entra el Técnico
// Si el técnico intenta entrar por URL directa a estas acciones, lo rebota
if ((strpos($url_solicitada, 'nuevo_equipo.php') !== false || 
     strpos($url_solicitada, 'editar_equipo.php') !== false || 
     strpos($url_solicitada, 'delete_equipo.php') !== false) && $rol_actual === 'tecnico') {
    header("Location: /frontend/pages/inicio.php?error=acceso_restringido_equipos");
    exit;
}
?>