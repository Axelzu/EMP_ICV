<?php
// 1. Forzar la visualización de errores por si algo falla internamente
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "../config/db.php";
session_start();

// 🛡️ SEGURIDAD: Solo el admin real puede ejecutar esto
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    die("Acceso no autorizado: No eres administrador.");
}

// 2. Capturar datos con validación básica
$id_usuario = isset($_GET['id']) ? intval($_GET['id']) : 0;
$nuevo_rol  = isset($_GET['rol']) ? $_GET['rol'] : 'tecnico';

// 3. Evitar que te quites el admin a ti mismo (Auto-bloqueo)
if ($id_usuario == $_SESSION['user_id'] && $nuevo_rol !== 'admin') {
    header("Location: ../../frontend/pages/usuarios.php?error=auto_proteccion");
    exit();
}

if ($id_usuario > 0) {
    // 4. Ejecutar la actualización
    $sql = "UPDATE users SET rol = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("si", $nuevo_rol, $id_usuario);
        
        if ($stmt->execute()) {
            // ✅ TODO BIEN: Redirigir de vuelta con mensaje
            header("Location: ../../frontend/pages/usuarios.php?mensaje=actualizado");
            exit();
        } else {
            echo "Error al ejecutar la consulta: " . $stmt->error;
        }
    } else {
        echo "Error al preparar la consulta: " . $conn->error;
    }
} else {
    // Si no llegó un ID válido, regresamos por seguridad
    header("Location: ../../frontend/pages/usuarios.php?error=id_invalido");
    exit();
}