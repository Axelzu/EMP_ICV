<?php
require "../config/db.php";
require "../auth/guard.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_id = $_POST['empresa_id'];
    $dep    = $_POST['dependencia'];
    $mod    = $_POST['marca_modelo'];
    $ser    = $_POST['serie'];
    $tipo   = $_POST['tipo_color']; // <--- Capturamos el 5to dato (Color/BN)

    // AHORA EL SQL TIENE 5 COLUMNAS Y 5 SIGNOS DE PREGUNTA (?)
    $sql = "INSERT INTO equipos (empresa_id, dependencia, marca_modelo, serie, tipo_color) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // "issss" -> 1 entero (emp_id) y 4 strings (dep, mod, ser, tipo)
    $stmt->bind_param("issss", $emp_id, $dep, $mod, $ser, $tipo);

    if ($stmt->execute()) {
        // Al tener éxito, regresamos a empresa.php
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&msg=equipo_creado");
        exit;
    } else {
        echo "Error al registrar el equipo: " . $conn->error;
    }
}