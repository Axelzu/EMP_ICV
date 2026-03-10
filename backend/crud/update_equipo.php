<?php
require "../config/db.php";
require "../auth/guard.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_id = $_POST['empresa_id'];
    $s_orig = $_POST['serie_original'];
    $dep    = $_POST['dependencia'];
    $mod    = $_POST['marca_modelo'];
    $s_nuev = $_POST['serie_nueva'];

    $sql = "UPDATE equipos SET dependencia = ?, marca_modelo = ?, serie = ? WHERE serie = ? AND empresa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $dep, $mod, $s_nuev, $s_orig, $emp_id);

    if ($stmt->execute()) {
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&msg=actualizado");
    } else { echo "Error al actualizar"; }
}