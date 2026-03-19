<?php
require "../config/db.php";
require "../auth/guard.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_id = $_POST['empresa_id'];
    $s_orig = $_POST['serie_original'];
    $dep    = $_POST['dependencia'];
    $mod    = $_POST['marca_modelo'];
    $s_nuev = $_POST['serie_nueva'];
    $tipo   = $_POST['tipo_color']; // <--- Nueva variable para el tipo de impresión (Color/BN)

    // Actualizamos el SQL para incluir la columna tipo_color
    $sql = "UPDATE equipos SET dependencia = ?, marca_modelo = ?, serie = ?, tipo_color = ? WHERE serie = ? AND empresa_id = ?";
    $stmt = $conn->prepare($sql);
    
    // Ajustamos el bind_param: ahora son 5 strings ("sssss") y 1 entero ("i") al final
    $stmt->bind_param("sssssi", $dep, $mod, $s_nuev, $tipo, $s_orig, $emp_id);

    if ($stmt->execute()) {
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&msg=actualizado");
    } else { 
        echo "Error al actualizar: " . $conn->error; 
    }
}