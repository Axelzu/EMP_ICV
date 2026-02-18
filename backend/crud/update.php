<?php
require "../config/db.php";
require "../config/excel.php";

$id         = $_POST['id'] ?? null;
$empresa_id = $_POST['empresa_id'] ?? null;
$marca      = $_POST['marca_impresora'] ?? null;
$serie      = $_POST['numero_serie'] ?? null;
$contador   = $_POST['contador_general'] ?? null;

if (!$id || !$empresa_id || !$marca || !$serie || !$contador) {
    die("Datos incompletos");
}

// 1. Actualizar Base de Datos
$sql = "UPDATE impresoras_formulario SET marca_impresora = ?, numero_serie = ?, contador_general = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssii", $marca, $serie, $contador, $id);
$stmt->execute();

// 2. Sobrescribir el Excel local
$datos = [['ID' => $id, 'Empresa' => $empresa_id, 'Marca' => $marca, 'Serie' => $serie, 'Contador' => $contador]];
$nombreExcel = "empresa_{$empresa_id}_copiadora_{$id}.xlsx";
$rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/" . $nombreExcel;

generarExcel($datos, $rutaPublica);

// 3. Redireccionar
header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id");
exit;