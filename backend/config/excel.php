<?php
require __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function generarExcel($datos, $rutaCompletaArchivo) {
    $carpeta = dirname($rutaCompletaArchivo);
    if (!file_exists($carpeta)) {
        mkdir($carpeta, 0777, true);
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    if (!empty($datos)) {
        $encabezados = array_keys($datos[0]);
        $sheet->fromArray($encabezados, NULL, 'A1');
        $fila = 2;
        foreach ($datos as $row) {
            $sheet->fromArray(array_values($row), NULL, "A$fila");
            $fila++;
        }
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($rutaCompletaArchivo);
    return $rutaCompletaArchivo;
}