<?php
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function generarExcel($datos) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Cabeceras
    $sheet->setCellValue('A1', 'Nombre');
    $sheet->setCellValue('B1', 'Correo');
    $sheet->setCellValue('C1', 'Fecha');

    // Datos
    $fila = 2;
    foreach ($datos as $dato) {
        $sheet->setCellValue("A$fila", $dato['nombre']);
        $sheet->setCellValue("B$fila", $dato['correo']);
        $sheet->setCellValue("C$fila", date('Y-m-d'));
        $fila++;
    }

    $nombreArchivo = 'formulario_' . time() . '.xlsx';
    $rutaLocal = __DIR__ . "/$nombreArchivo";

    $writer = new Xlsx($spreadsheet);
    $writer->save($rutaLocal);

    return $rutaLocal;
}
