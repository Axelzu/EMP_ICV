<?php
require "../config/db.php";
require "../config/excel.php";
require "../config/remote_storage.php"; // NUEVO

$id         = $_POST['id'] ?? null;
$empresa_id = $_POST['empresa_id'] ?? null;
$marca      = $_POST['marca_impresora'] ?? null;
$serie      = $_POST['numero_serie'] ?? null;
$contador   = $_POST['contador_general'] ?? null;

if (!$id || !$empresa_id || !$marca || !$serie || !$contador) {
    die("Datos incompletos");
}

// 1. ACTUALIZAR EN BD
$sql = "UPDATE impresoras_formulario
        SET marca_impresora = ?, numero_serie = ?, contador_general = ?
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssii", $marca, $serie, $contador, $id);
$stmt->execute();

// 2. PREPARAR DATOS
$datos = [[
    'ID Copiadora' => $id,
    'Empresa'      => $empresa_id,
    'Marca'        => $marca,
    'Serie'        => $serie,
    'Contador'     => $contador
]];

$nombreExcel = "empresa_{$empresa_id}_copiadora_{$id}.xlsx";
$rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/" . $nombreExcel;

// 3. ACTUALIZAR EXCEL LOCAL
generarExcel($datos, $rutaPublica);

// 4. ACTUALIZAR EN SERVIDOR PRIVADO (NUEVO)
// Al usar el mismo nombre de archivo, el servidor remoto lo sobrescribe.
enviarServidorPrivado($rutaPublica, $nombreExcel);

header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id");
exit;