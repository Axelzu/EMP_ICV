<?php
include "../config/db.php";

$id = $_GET['id'] ?? null;
$empresa_id = $_GET['empresa_id'] ?? null;

if (!$id || !$empresa_id) {
    die("Empresa no seleccionada");
}

$stmt = $conn->prepare("DELETE FROM impresoras_formulario WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id");
exit;
