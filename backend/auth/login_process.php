<?php
session_start();
require __DIR__ . "/../config/db.php";

$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user['password'])) {

        // LOGIN CORRECTO
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['user_email'] = $user['email'];

        header("Location: /ICV/frontend/pages/inicio.php");
        exit;
    }
}

// ❌ LOGIN INCORRECTO
$_SESSION['login_error'] = "Usuario o contraseña incorrectos";
header("Location: /ICV/frontend/pages/login.php");
exit;
