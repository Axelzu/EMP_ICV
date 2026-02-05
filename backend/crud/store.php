<?php
require "../config/db.php";
require "../config/excel.php";
require "../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/* =========================
   1. DATOS DEL FORMULARIO
   ========================= */
$empresa_id = $_POST['empresa_id'] ?? null;
$marca      = $_POST['marca_impresora'] ?? null;
$serie      = $_POST['numero_serie'] ?? null;
$contador   = $_POST['contador_general'] ?? null;

if (!$empresa_id || !$marca || !$serie || !$contador) {
    die("Datos incompletos");
}

/* =========================
   2. GUARDAR EN BD
   ========================= */
$sql = "INSERT INTO impresoras_formulario 
        (empresa_id, marca_impresora, numero_serie, contador_general)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("issi", $empresa_id, $marca, $serie, $contador);
$stmt->execute();

/* ID ÚNICO DE LA COPIADORA */
$id_impresora = $conn->insert_id;

/* =========================
   3. CREAR EXCEL ÚNICO
   ========================= */

/* Nombre ÚNICO del archivo */
$archivo = EXCEL_PATH . "empresa_{$empresa_id}_copiadora_{$id_impresora}.xlsx";

/* Crear Excel */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

/* Encabezados */
$sheet->fromArray(
    ['ID Copiadora', 'Empresa', 'Marca', 'Serie', 'Contador'],
    NULL,
    'A1'
);

/* Datos */
$sheet->setCellValue('A2', $id_impresora);
$sheet->setCellValue('B2', $empresa_id);
$sheet->setCellValue('C2', $marca);
$sheet->setCellValue('D2', $serie);
$sheet->setCellValue('E2', $contador);

/* Guardar archivo */
$writer = new Xlsx($spreadsheet);
$writer->save($archivo);

/* =========================
   4. REDIRECCIONAR
   ========================= */
header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id");
exit;
