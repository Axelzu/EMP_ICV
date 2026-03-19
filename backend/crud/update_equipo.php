<?php
require "../config/db.php";
require "../auth/guard.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Recibimos los 6 datos del formulario
    $emp_id = $_POST['empresa_id'];
    $s_orig = $_POST['serie_original'];
    $dep    = $_POST['dependencia'];
    $mod    = $_POST['marca_modelo'];
    $s_nuev = $_POST['serie_nueva'];
    $tipo   = $_POST['tipo_color']; // El nuevo campo que agregamos

    // 2. SQL con 6 parámetros (?)
    // Actualizamos: dependencia(1), marca_modelo(2), serie(3), tipo_color(4)
    // Filtramos por: serie_original(5) y empresa_id(6)
    $sql = "UPDATE equipos SET dependencia = ?, marca_modelo = ?, serie = ?, tipo_color = ? WHERE serie = ? AND empresa_id = ?";
    
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error al preparar la consulta: " . $conn->error);
    }

    // 3. BIND_PARAM con 6 variables
    // "sssssi" -> s (dep), s (mod), s (s_nuev), s (tipo), s (s_orig), i (emp_id)
    $stmt->bind_param("sssssi", $dep, $mod, $s_nuev, $tipo, $s_orig, $emp_id);

    if ($stmt->execute()) {
        // Si todo sale bien, regresamos a la página del cliente
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&msg=actualizado");
        exit;
    } else {
        echo "Error al ejecutar la actualización: " . $stmt->error;
    }
} else {
    echo "Método no permitido.";
}