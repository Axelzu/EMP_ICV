<?php
require "../config/db.php";
require "../auth/guard.php";

$serie = $_GET['serie'];
$emp_id = $_GET['empresa_id'];

$sql = "DELETE FROM equipos WHERE serie = ? AND empresa_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $serie, $emp_id);

if ($stmt->execute()) {
    header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&msg=eliminado");
} else { echo "Error al eliminar"; }