<?php
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function generarExcel($datos, $nombreArchivo = null)
{
    // Ruta donde se guardarán los excel
    $ruta = __DIR__ . '/../../excel_generados/';

    // Crear carpeta si no existe
    if (!file_exists($ruta)) {
        mkdir($ruta, 0777, true);
    }

    if (!$nombreArchivo) {
        $nombreArchivo = "reporte_" . time() . ".xlsx";
    }

    $archivoCompleto = $ruta . $nombreArchivo;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Encabezados automáticos
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
    $writer->save($archivoCompleto);

    return $archivoCompleto;
}
?>
