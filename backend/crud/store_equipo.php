<?php
require "../config/db.php";
require "../auth/guard.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_id = $_POST['empresa_id'];
    $dep    = $_POST['dependencia'];
    $mod    = $_POST['marca_modelo'];
    $ser    = $_POST['serie'];

    // Insertar en la tabla maestra EQUIPOS
    $sql = "INSERT INTO equipos (empresa_id, dependencia, marca_modelo, serie) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $emp_id, $dep, $mod, $ser);

    if ($stmt->execute()) {
        // Al tener éxito, regresamos a empresa.php donde ya aparecerá el cuadro rojo
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&msg=equipo_creado");
    } else {
        echo "Error al registrar el equipo: " . $conn->error;
    }
}