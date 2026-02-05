<?php
require "../config/db.php";
require "../config/excel.php";
require "../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/* ======================
   1. DATOS
====================== */
$id         = $_POST['id'] ?? null;
$empresa_id = $_POST['empresa_id'] ?? null;
$marca      = $_POST['marca_impresora'] ?? null;
$serie      = $_POST['numero_serie'] ?? null;
$contador   = $_POST['contador_general'] ?? null;

if (!$id || !$empresa_id) {
    die("Empresa no seleccionada");
}

/* ======================
   2. ACTUALIZAR BD
====================== */
$sql = "UPDATE impresoras_formulario
        SET marca_impresora = ?, numero_serie = ?, contador_general = ?
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssii", $marca, $serie, $contador, $id);
$stmt->execute();

/* ======================
   3. RUTA EXCEL
====================== */
$archivo = EXCEL_PATH . "empresa_{$empresa_id}_copiadora_{$id}.xlsx";

/* ======================
   4. CARGAR O CREAR
====================== */
try {
    // Intentar cargar
    $spreadsheet = IOFactory::load($archivo);
    $sheet = $spreadsheet->getActiveSheet();

} catch (Exception $e) {
    // Si NO existe → crear
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->fromArray(
        ['ID', 'Empresa', 'Marca', 'Serie', 'Contador'],
        NULL,
        'A1'
    );
}

/* ======================
   5. ESCRIBIR DATOS
====================== */
$sheet->setCellValue('A2', $id);
$sheet->setCellValue('B2', $empresa_id);
$sheet->setCellValue('C2', $marca);
$sheet->setCellValue('D2', $serie);
$sheet->setCellValue('E2', $contador);

/* ======================
   6. GUARDAR
====================== */
$writer = new Xlsx($spreadsheet);
$writer->save($archivo);

/* ======================
   7. REDIRECCIÓN
====================== */
header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id");
exit;
