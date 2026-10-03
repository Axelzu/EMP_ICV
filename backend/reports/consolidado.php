<?php
// RF-04 / CU-09: informe de consumo mensual consolidado por cliente (Excel).
// Solo incluye equipos con estado "Reportado" (lectura real) o "Estimado" (proyección autorizada). Las cifras estimadas se marcan.
require "../config/db.php";
require "../auth/guard.php";
require "../security/functions.php";
require "../lib/lecturas.php";
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if (!in_array($_SESSION['rol'] ?? '', ['admin', 'supervisor'], true)) die("Acceso no autorizado");

$periodo = $_GET['periodo'] ?? date('Y-m');
if (!periodoValido($periodo)) die("Periodo inválido");
$empresa_id = (int)($_GET['empresa_id'] ?? 0);

$equipos = estadoEquipos($conn, $periodo, $empresa_id);

// Cierre del periodo anterior (última lectura real previa al primer día del periodo) para calcular el consumo del mes
$inicio = $periodo . '-01 00:00:00';
$stPrev = $conn->prepare("SELECT contador_bn, contador_color FROM lecturas
                          WHERE equipo_id = ? AND origen <> 'ESTIMADO' AND fecha < ? ORDER BY fecha DESC, id DESC LIMIT 1");

$ss = new Spreadsheet();
$sh = $ss->getActiveSheet();
$sh->setTitle('Consumo ' . $periodo);
$sh->setCellValue('A1', "Informe de consumo mensual - $periodo");
$sh->mergeCells('A1:K1');
$sh->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sh->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i') . ' · Las filas en amarillo corresponden a cifras ESTIMADAS por el motor predictivo (respaldo de contingencia).');
$sh->mergeCells('A2:K2');

$enc = ['Cliente', 'Dependencia', 'Marca / Modelo', 'Serie', 'Origen del dato', 'Contador B/N (cierre)', 'Contador Color (cierre)',
        'Consumo B/N del mes', 'Consumo Color del mes', 'Total del mes', 'Observación'];
$sh->fromArray($enc, null, 'A4');
$sh->getStyle('A4:K4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$sh->getStyle('A4:K4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0A2540');

$fila = 5; $tBn = 0; $tCol = 0; $omitidos = [];
foreach ($equipos as $e) {
    if ($e['estado'] === 'Pendiente') { $omitidos[] = $e['serie']; continue; }
    $esEst = $e['estado'] === 'Estimado';
    $bn  = (int)($esEst ? $e['contador_bn_estimado'] : $e['real_bn']);
    $col = (int)($esEst ? $e['contador_color_estimado'] : $e['real_color']);

    $eid = (int)$e['id'];
    $stPrev->bind_param("is", $eid, $inicio);
    $stPrev->execute();
    $p = $stPrev->get_result()->fetch_assoc();
    $cBn  = $p ? max(0, $bn - (int)$p['contador_bn']) : null;
    $cCol = $p ? max(0, $col - (int)$p['contador_color']) : null;

    $obs = $esEst ? 'Cifra estimada (' . $e['modelo'] . ($e['preliminar'] ? ', preliminar' : '') . ')' : '';
    if (!$p) $obs .= ($obs ? ' · ' : '') . 'Sin lectura previa: consumo no calculable';
    $sh->fromArray([
        $e['empresa'], $e['dependencia'], $e['marca_modelo'], $e['serie'],
        $esEst ? 'Estimado (ML)' : 'Lectura real (' . $e['origen'] . ')',
        $bn, $col, $cBn, $cCol, ($cBn !== null ? $cBn + $cCol : null), $obs
    ], null, "A$fila");
    if ($esEst) {
        $sh->getStyle("A$fila:K$fila")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF3CD');
    }
    $tBn += (int)$cBn; $tCol += (int)$cCol;
    $fila++;
}
$sh->setCellValue("A$fila", 'TOTAL');
$sh->setCellValue("H$fila", $tBn);
$sh->setCellValue("I$fila", $tCol);
$sh->setCellValue("J$fila", $tBn + $tCol);
$sh->getStyle("A$fila:K$fila")->getFont()->setBold(true);
if ($omitidos) {
    $sh->setCellValue("A" . ($fila + 2), 'Equipos excluidos por no tener lectura ni estimación autorizada: ' . implode(', ', $omitidos));
}
foreach (range('A', 'K') as $c) $sh->getColumnDimension($c)->setAutoSize(true);
$sh->getStyle("F5:J$fila")->getNumberFormat()->setFormatCode('#,##0');

registrarLog($conn, "REPORTE", "Informe consolidado $periodo (empresa_id=$empresa_id)");

while (ob_get_level()) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="consumo_' . $periodo . ($empresa_id ? "_cliente$empresa_id" : '') . '.xlsx"');
header('Cache-Control: max-age=0');
(new Xlsx($ss))->save('php://output');
exit;
