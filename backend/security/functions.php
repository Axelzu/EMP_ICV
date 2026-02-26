<?php
// 1. Iniciar sesión de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Genera un token aleatorio para proteger los formularios
 */
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida que el token enviado coincida con el de la sesión
 */
function validarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Limpia los textos para evitar ataques XSS (Seguridad de visualización)
 */
function sanear($dato) {
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

/**
 * REGISTRAR LOG: Guarda la actividad en la tabla 'auditoria'
 */
function registrarLog($conn, $accion, $detalle) {
    // A SEGURAR CONEXIÓN: Si $conn no llega, intentamos usar la conexión global
    if (!$conn) {
        global $conn;
    }

    // Si después de intentar la global sigue sin haber conexión, cancelamos
    if (!$conn) {
        error_log("Error crítico: No hay conexión a la base de datos en registrarLog.");
        return false;
    }

    // Capturar datos de sesión e IP
    $user_id = $_SESSION['user_id'] ?? 0; // Si no hay sesión, guarda 0
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    try {
        // Preparamos la consulta exactamente como están tus columnas en phpMyAdmin
        $sql = "INSERT INTO auditoria (user_id, accion, detalle, ip) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("isss", $user_id, $accion, $detalle, $ip);
            $resultado = $stmt->execute();
            $stmt->close();
            return $resultado;
        } else {
            // Esto guarda el error en el log de cPanel si la tabla o columnas están mal escritas
            error_log("Error preparando SQL de auditoría: " . $conn->error);
            return false;
        }
    } catch (Exception $e) {
        error_log("Excepción en registrarLog: " . $e->getMessage());
        return false;
    }
}