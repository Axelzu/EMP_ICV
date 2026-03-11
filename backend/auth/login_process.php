<?php
session_start();
require __DIR__ . "/../config/db.php";
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
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['user_email'] = $user['email'];
        
        // CORRECCIÓN AQUÍ: 
        // Guardamos 'rol' y 'user_rol' para que sea compatible con todos tus archivos
        $_SESSION['rol']      = $user['rol']; 
        $_SESSION['user_rol'] = $user['rol']; 

        // 🛡️ REGISTRAR EN AUDITORÍA
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