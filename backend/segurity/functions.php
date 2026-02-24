<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generar el Token
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Limpiar datos
function sanear($dato) {
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

// Validar el Token
function validarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Registrar movimientos
function registrarLog($conn, $accion, $detalle) {
    $user_id = $_SESSION['user_id'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO auditoria (user_id, accion, detalle, ip) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $accion, $detalle, $ip);
    $stmt->execute();
}