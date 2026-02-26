<?php
session_start();
require __DIR__ . "/../config/db.php";
// Cargamos las funciones de seguridad para poder usar registrarLog
require __DIR__ . "/../security/functions.php"; 

$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// 1. Buscamos al usuario por email
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // 2. Verificamos la contraseña
    if (password_verify($password, $user['password'])) {

        // ✅ LOGIN CORRECTO: Guardamos todo en la sesión
        $_SESSION['user_id']    = $user['id'];     // Crítico para la tabla auditoria
        $_SESSION['user_name']  = $user['nombre']; // Se usará en el LEFT JOIN
        $_SESSION['user_email'] = $user['email'];
        
        // Si tienes una columna 'rol' en tu tabla 'users', guárdala aquí
        // Si no la tienes aún, esto guardará NULL pero no romperá nada
        $_SESSION['user_rol']   = $user['rol'] ?? 'admin'; 

        // 🛡️ REGISTRAR EN AUDITORÍA EL INICIO DE SESIÓN
        // Esto confirma que la conexión entre Login y Auditoría ya funciona
        registrarLog($conn, "LOGIN", "El usuario " . $user['nombre'] . " ha iniciado sesión.");

        // Redirección al éxito
        header("Location: /frontend/pages/inicio.php");
        exit;
    }
}

// ❌ LOGIN INCORRECTO
$_SESSION['login_error'] = "Usuario o contraseña incorrectos";
header("Location: /frontend/pages/login.php");
exit;