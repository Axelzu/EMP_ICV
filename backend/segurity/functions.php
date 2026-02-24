<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Generar Token CSRF (Evita ataques de formularios falsos)
function generarToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 2. Validar Token CSRF
function validarToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// 3. Saneamiento XSS (Limpia lo que el usuario escribe para que no inyecte scripts)
function limpiar($dato) {
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

// 4. Registro de Auditoría (Para ver quién hizo qué en tu panel)
function registrarLog($conn, $accion, $detalle) {
    $user_id = $_SESSION['user_id'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO auditoria (user_id, accion, detalle, ip) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $accion, $detalle, $ip);
    $stmt->execute();
}