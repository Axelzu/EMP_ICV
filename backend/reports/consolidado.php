<?php
// RF-04 / CU-09: informe de consumo consolidado por cliente (Excel).
// Dos modos: mensual (periodo AAAA-MM, con cifras estimadas autorizadas) y por rango de fechas (desde / hasta, solo lecturas reales).
// Filtro opcional por departamento (dependencia). Solo incluye equipos con lectura real o, en modo mensual, con estimación autorizada.
require "../config/db.php";
require "../auth/guard.php";
require "../security/functions.php";
require "../lib/lecturas.php";
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if (!in_array($_SESSION['rol'] ?? '', ['admin', 'supervisor'], true)) die("Acceso no autorizado");

$inicioEjecucion = microtime(true);
$empresa_id = (int)($_GET['empresa_id'] ?? 0);
$departamento = trim((string)($_GET['departamento'] ?? ''));
$desde = trim((string)($_GET['desde'] ?? ''));
$hasta = trim((string)($_GET['hasta'] ?? ''));

$modoRango = ($desde !== '' || $hasta !== '');
if ($modoRango) {
    if (!fechaValida($desde) || !fechaValida($hasta)) die("Rango de fechas inválido: use ambas fechas (desde y hasta).");
    if ($desde > $hasta) die("La fecha inicial no puede ser posterior a la final.");
    if ((strtotime($hasta) - strtotime($desde)) > 366 * 86400) die("El rango no puede superar un año.");
    $etiqueta = "$desde al $hasta";
    $titulo = "Informe de consumo del $desde al $hasta";
    $colConsumo = 'del periodo';
} else {
    $periodo = $_GET['periodo'] ?? date('Y-m');
    if (!periodoValido($periodo)) die("Periodo inválido");
    $etiqueta = $periodo;
    $titulo = "Informe de consumo mensual - $periodo";
    $colConsumo = 'del mes';
}

// Filas comunes a los dos modos: cliente, dependencia, marca/modelo, serie, estado (Reportado|Estimado), origen, cierre y consumo
$filas = []; $omitidos = [];
if ($modoRango) {
    foreach (lecturasRango($conn, $desde, $hasta, $empresa_id, $departamento) as $e) {
        if ($e['cierre_bn'] === null) { $omitidos[] = $e['serie']; continue; }
        $tiene = $e['ap_bn'] !== null;
        $filas[] = [
            'empresa' => $e['empresa'], 'dependencia' => $e['dependencia'], 'marca_modelo' => $e['marca_modelo'], 'serie' => $e['serie'],
            'estimado' => false, 'origen' => 'Lectura real (' . $e['origen'] . ')',
            'bn' => $e['cierre_bn'], 'col' => $e['cierre_color'],
            'cBn' => $tiene ? max(0, $e['cierre_bn'] - $e['ap_bn']) : null,
            'cCol' => $tiene ? max(0, $e['cierre_color'] - $e['ap_color']) : null,
            'obs' => $tiene ? '' : 'Sin lectura previa al rango: consumo no calculable',
        ];
    }
} else {
    // Cierre del periodo anterior (última lectura real previa al primer día del periodo) para calcular el consumo del mes
    $inicio = $periodo . '-01 00:00:00';
    $stPrev = $conn->prepare("SELECT contador_bn, contador_color FROM lecturas
                              WHERE equipo_id = ? AND origen <> 'ESTIMADO' AND fecha < ? ORDER BY fecha DESC, id DESC LIMIT 1");
    foreach (estadoEquipos($conn, $periodo, $empresa_id, $departamento) as $e) {
        if ($e['estado'] === 'Pendiente') { $omitidos[] = $e['serie']; continue; }
        $esEst = $e['estado'] === 'Estimado';
        $bn  = (int)($esEst ? $e['contador_bn_estimado'] : $e['real_bn']);
        $col = (int)($esEst ? $e['contador_color_estimado'] : $e['real_color']);
        $eid = (int)$e['id'];
        $stPrev->bind_param("is", $eid, $inicio);
        $stPrev->execute();
        $p = $stPrev->get_result()->fetch_assoc();
        $obs = $esEst ? 'Cifra estimada (' . $e['modelo'] . ($e['preliminar'] ? ', preliminar' : '') . ')' : '';
        if (!$p) $obs .= ($obs ? ' · ' : '') . 'Sin lectura previa: consumo no calculable';
        $filas[] = [
            'empresa' => $e['empresa'], 'dependencia' => $e['dependencia'], 'marca_modelo' => $e['marca_modelo'], 'serie' => $e['serie'],
            'estimado' => $esEst, 'origen' => $esEst ? 'Estimado (ML)' : 'Lectura real (' . $e['origen'] . ')',
            'bn' => $bn, 'col' => $col,
            'cBn' => $p ? max(0, $bn - (int)$p['contador_bn']) : null,
            'cCol' => $p ? max(0, $col - (int)$p['contador_color']) : null,
            'obs' => $obs,
        ];
    }
}

$ss = new Spreadsheet();
$sh = $ss->getActiveSheet();
$sh->setTitle(substr('Consumo ' . $etiqueta, 0, 31));
$sh->setCellValue('A1', $titulo);
$sh->mergeCells('A1:K1');
$sh->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$subtitulo = 'Generado: ' . date('Y-m-d H:i') . ($departamento !== '' ? ' · Departamento: ' . $departamento : ' · Todos los departamentos');
$subtitulo .= $modoRango ? ' · Solo lecturas reales del rango.' : ' · Las filas en amarillo corresponden a cifras ESTIMADAS por el motor predictivo (respaldo de contingencia).';
$sh->setCellValue('A2', $subtitulo);
$sh->mergeCells('A2:K2');

$enc = ['Cliente', 'Dependencia', 'Marca / Modelo', 'Serie', 'Origen del dato', 'Contador B/N (cierre)', 'Contador Color (cierre)',
        "Consumo B/N $colConsumo", "Consumo Color $colConsumo", "Total $colConsumo", 'Observación'];
$sh->fromArray($enc, null, 'A4');
$sh->getStyle('A4:K4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$sh->getStyle('A4:K4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0A2540');

$fila = 5; $tBn = 0; $tCol = 0;
foreach ($filas as $f) {
    $sh->fromArray([
        $f['empresa'], $f['dependencia'], $f['marca_modelo'], $f['serie'], $f['origen'],
        $f['bn'], $f['col'], $f['cBn'], $f['cCol'], ($f['cBn'] !== null ? $f['cBn'] + $f['cCol'] : null), $f['obs']
    ], null, "A$fila");
    if ($f['estimado']) {
        $sh->getStyle("A$fila:K$fila")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF3CD');
    }
    $tBn += (int)$f['cBn']; $tCol += (int)$f['cCol'];
    $fila++;
}
$sh->setCellValue("A$fila", 'TOTAL');
$sh->setCellValue("H$fila", $tBn);
$sh->setCellValue("I$fila", $tCol);
$sh->setCellValue("J$fila", $tBn + $tCol);
$sh->getStyle("A$fila:K$fila")->getFont()->setBold(true);
if ($omitidos) {
    $motivo = $modoRango ? 'no tener lectura real en el rango' : 'no tener lectura ni estimación autorizada';
    $sh->setCellValue("A" . ($fila + 2), "Equipos excluidos por $motivo: " . implode(', ', $omitidos));
}
foreach (range('A', 'K') as $c) $sh->getColumnDimension($c)->setAutoSize(true);
$sh->getStyle("F5:J$fila")->getNumberFormat()->setFormatCode('#,##0');

$segundos = round(microtime(true) - $inicioEjecucion, 2);
registrarLog($conn, "REPORTE", "Informe consolidado $etiqueta (empresa_id=$empresa_id" . ($departamento !== '' ? ", departamento=$departamento" : '') . ", {$segundos}s)");

$nombre = 'consumo_' . ($modoRango ? "{$desde}_a_{$hasta}" : $periodo) . ($empresa_id ? "_cliente$empresa_id" : '');
if ($departamento !== '') {
    $sinTildes = strtr($departamento, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
                                       'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N']);
    $nombre .= '_' . trim(preg_replace('/[^A-Za-z0-9]+/', '-', $sinTildes), '-');
}

while (ob_get_level()) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nombre . '.xlsx"');
header('Cache-Control: max-age=0');
(new Xlsx($ss))->save('php://output');
exit;
