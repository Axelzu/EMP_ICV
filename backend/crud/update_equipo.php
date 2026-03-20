<?php
require "../config/db.php";
require "../auth/guard.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Capturamos los datos que vienen del formulario
    $emp_id    = $_POST['empresa_id'];
    $dep       = $_POST['dependencia'];
    $mod       = $_POST['marca_modelo'];
    $ser_nueva = $_POST['serie']; // La serie que el usuario pudo haber editado
    $ser_old   = $_POST['serie_original']; // Necesitamos la serie anterior para el WHERE
    $tipo      = $_POST['tipo_color'];

    // 2. Preparamos el SQL de ACTUALIZACIÓN (UPDATE)
    // Cambiamos los datos donde la serie coincida con la original
    $sql = "UPDATE equipos 
            SET dependencia = ?, marca_modelo = ?, serie = ?, tipo_color = ? 
            WHERE serie = ? AND empresa_id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Error en la preparación de la base de datos: " . $conn->error);
    }

    // 3. Vinculamos los 6 parámetros:
    // "sssssi" -> 5 Strings (dep, mod, ser_nueva, tipo, ser_old) y 1 Entero (emp_id)
    $stmt->bind_param("sssssi", $dep, $mod, $ser_nueva, $tipo, $ser_old, $emp_id);

    if ($stmt->execute()) {
        // Si todo sale bien, regresamos a la página de la empresa con mensaje de éxito
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&status=updated");
        exit;
    } else {
        echo "Error al actualizar el equipo: " . $stmt->error;
    }
} else {
    echo "Acceso no permitido.";
}