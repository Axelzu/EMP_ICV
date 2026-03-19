<?php
require "../config/db.php";
require "../auth/guard.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_id = $_POST['empresa_id'];
    $dep    = $_POST['dependencia'];
    $mod    = $_POST['marca_modelo'];
    $ser    = $_POST['serie'];
    $tipo   = $_POST['tipo_color']; // <--- Nueva variable capturada del formulario

    // Insertar en la tabla maestra EQUIPOS incluyendo el nuevo campo tipo_color
    $sql = "INSERT INTO equipos (empresa_id, dependencia, marca_modelo, serie, tipo_color) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // Agregamos una "s" extra en bind_param para el string de tipo_color
    $stmt->bind_param("issss", $emp_id, $dep, $mod, $ser, $tipo);

    if ($stmt->execute()) {
        // Al tener éxito, regresamos a empresa.php donde ya aparecerá el cuadro rojo
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&msg=equipo_creado");
    } else {
        echo "Error al registrar el equipo: " . $conn->error;
    }
}