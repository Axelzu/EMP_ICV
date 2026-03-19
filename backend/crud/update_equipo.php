<?php
require "../config/db.php";
require "../auth/guard.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_id = $_POST['empresa_id'];
    $s_orig = $_POST['serie_original'];
    $dep    = $_POST['dependencia'];
    $mod    = $_POST['marca_modelo'];
    $s_nuev = $_POST['serie_nueva'];
    $tipo   = $_POST['tipo_color']; // El nuevo dato que causaba el error

    // EXPLICACIÓN DEL ERROR:
    // Tu SQL tiene 6 signos de interrogación (?), por lo tanto bind_param NECESITA 6 valores.
    $sql = "UPDATE equipos SET dependencia = ?, marca_modelo = ?, serie = ?, tipo_color = ? WHERE serie = ? AND empresa_id = ?";
    
    $stmt = $conn->prepare($sql);
    
    // "sssssi" significa: 5 Strings (dep, mod, s_nuev, tipo, s_orig) y 1 Integer (emp_id)
    $stmt->bind_param("sssssi", $dep, $mod, $s_nuev, $tipo, $s_orig, $emp_id);

    if ($stmt->execute()) {
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&msg=actualizado");
        exit;
    } else { 
        echo "Error al actualizar: " . $conn->error; 
    }
}