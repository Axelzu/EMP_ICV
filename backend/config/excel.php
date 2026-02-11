<?php
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Genera un archivo Excel con los datos proporcionados
 * y lo guarda en la ruta completa que se le pase.
 *
 * @param array $datos Array de datos a guardar (asociativo)
 * @param string $rutaCompletaArchivo Ruta completa donde se guardará el Excel
 * @return string Ruta del archivo generado
 */
function generarExcel($datos, $rutaCompletaArchivo)
{
    // Crear carpeta si no existe
    $carpeta = dirname($rutaCompletaArchivo);
    if (!file_exists($carpeta)) {
        mkdir($carpeta, 0777, true);
    }

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
    $writer->save($rutaCompletaArchivo);

    return $rutaCompletaArchivo;
}
?>
