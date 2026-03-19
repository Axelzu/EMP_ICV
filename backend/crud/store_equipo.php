<?php
require "../config/db.php";
require "../auth/guard.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_id = $_POST['empresa_id'];
    $dep    = $_POST['dependencia'];
    $mod    = $_POST['marca_modelo'];
    $ser    = $_POST['serie'];
    $tipo   = $_POST['tipo_color'];

    // 1. PRIMERO: Verificamos si la serie ya existe para no lanzar el error fatal
    $check_sql = "SELECT serie FROM equipos WHERE serie = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $ser);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Si ya existe, regresamos con un mensaje de error amigable
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&error=serie_duplicada");
        exit;
    }

    // 2. SI NO EXISTE: Procedemos con el INSERT (tus 5 parámetros corregidos)
    $sql = "INSERT INTO equipos (empresa_id, dependencia, marca_modelo, serie, tipo_color) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Error en la preparación: " . $conn->error);
    }

    $stmt->bind_param("issss", $emp_id, $dep, $mod, $ser, $tipo);

    if ($stmt->execute()) {
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&msg=equipo_creado");
        exit;
    } else {
        echo "Error al registrar: " . $stmt->error;
    }
}