<?php
require "../config/db.php";
require "../auth/guard.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Capturamos los 5 datos que vienen del formulario
    $emp_id = $_POST['empresa_id'];
    $dep    = $_POST['dependencia'];
    $mod    = $_POST['marca_modelo'];
    $ser    = $_POST['serie'];
    $tipo   = $_POST['tipo_color']; // El nuevo campo

    // 2. Preparamos el SQL con 5 columnas y 5 signos de interrogación (?)
    $sql = "INSERT INTO equipos (empresa_id, dependencia, marca_modelo, serie, tipo_color) VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Error en la preparación de la base de datos: " . $conn->error);
    }

    // 3. Vinculamos los 5 parámetros: 1 entero (i) y 4 strings (ssss)
    // Total: "issss" = 5 parámetros
    $stmt->bind_param("issss", $emp_id, $dep, $mod, $ser, $tipo);

    if ($stmt->execute()) {
        // Si todo sale bien, regresamos a la página de la empresa
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&msg=equipo_creado");
        exit;
    } else {
        echo "Error al registrar el equipo: " . $stmt->error;
    }
} else {
    echo "Acceso no permitido.";
}