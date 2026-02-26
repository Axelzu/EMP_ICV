<?php
require "../config/db.php";
session_start();

// 🛡️ SEGURIDAD: Solo el admin real puede ejecutar esto
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    die("Acceso no autorizado");
}

// Borrar todos los registros de la tabla auditoria
$sql = "DELETE FROM auditoria";

if ($conn->query($sql)) {
    // Redirigir con un mensaje de éxito
    header("Location: ../../frontend/pages/auditoria.php?mensaje=limpiado");
} else {
    echo "Error al limpiar el historial: " . $conn->error;
}
?>