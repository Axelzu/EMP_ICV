<?php
session_start();
require "../../backend/auth/guard.php";
require "../../backend/config/db.php";
require "../../backend/lib/lecturas.php";

$rol = $_SESSION['rol'] ?? 'tecnico';
if ($rol === 'tecnico') { header("Location: inicio.php?error=acceso_restringido_equipos"); exit; }

$equipo_id = (int)($_GET['equipo_id'] ?? 0);
$st = $conn->prepare("SELECT e.*, emp.nombre AS empresa FROM equipos e JOIN empresas emp ON emp.id = e.empresa_id WHERE e.id = ?");
$st->bind_param("i", $equipo_id);
$st->execute();
$eq = $st->get_result()->fetch_assoc();
if (!$eq) die("Equipo no encontrado");

// Último valor de cada mes (lecturas reales)
$st = $conn->prepare("SELECT DATE_FORMAT(fecha,'%Y-%m') AS ym, contador_bn, contador_color
                      FROM lecturas WHERE equipo_id = ? AND origen <> 'ESTIMADO' ORDER BY fecha ASC, id ASC");
$st->bind_param("i", $equipo_id);
$st->execute();
$porMes = [];
foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $porMes[$r['ym']] = $r; // el último de cada mes sobrescribe
$meses = array_values($porMes);

$labels = []; $real = []; $consumo = [];
$prev = null;
foreach ($meses as $m) {
    $labels[] = $m['ym'];
    $tot = (int)$m['contador_bn'];
    $real[] = $tot;
    $consumo[] = $prev === null ? null : max(0, $tot - $prev);
    $prev = $tot;
}

// Proyección del periodo actual
$periodo = date('Y-m');
$st = $conn->prepare("SELECT * FROM proyecciones WHERE equipo_id = ? AND periodo = ?");
$st->bind_param("is", $equipo_id, $periodo);
$st->execute();
$proy = $st->get_result()->fetch_assoc();

$estimado = array_fill(0, count($labels), null);
$consEst = array_fill(0, count($labels), null);
if ($proy) {
    $idx = array_search($periodo, $labels, true);
    if ($idx === false) { $labels[] = $periodo; $real[] = null; $consumo[] = null; $estimado[] = null; $consEst[] = null; $idx = count($labels) - 1; }
    if ($idx > 0 || $idx === count($labels) - 1) {
        // Empalma la curva punteada con el último punto real anterior
        for ($i = $idx - 1; $i >= 0; $i--) { if ($real[$i] !== null) { $estimado[$i] = $real[$i]; $baseIdx = $i; break; } }
        $estimado[$idx] = (int)$proy['contador_bn_estimado'];
        if (isset($baseIdx)) $consEst[$idx] = max(0, (int)$proy['contador_bn_estimado'] - $real[$baseIdx]);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tendencia | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark px-4" style="background-color:#0A2540">
    <a class="navbar-brand fw-bold" href="inicio.php">ICV</a>
    <a href="dashboard.php" class="btn btn-outline-light btn-sm">⬅ Volver al panel</a>
</nav>
<main class="container my-4">
    <h4 class="text-primary fw-bold"><?= htmlspecialchars($eq['marca_modelo']) ?> · <?= htmlspecialchars($eq['dependencia']) ?></h4>
    <p class="text-muted"><?= htmlspecialchars($eq['empresa']) ?> · Serie <?= htmlspecialchars($eq['serie']) ?></p>

    <div class="row g-3 mb-3">
        <div class="col-md-8"><div class="card shadow-sm border-0"><div class="card-body">
            <h6 class="fw-bold">Contador B/N acumulado (real y proyectado)</h6>
            <canvas id="c1" height="130"></canvas>
        </div></div></div>
        <div class="col-md-4"><div class="card shadow-sm border-0 h-100"><div class="card-body">
            <h6 class="fw-bold">Motor predictivo</h6>
            <?php if ($proy): ?>
                <ul class="list-unstyled small mb-0">
                    <li><b>Periodo:</b> <?= htmlspecialchars($proy['periodo']) ?></li>
                    <li><b>Cierre estimado B/N:</b> <?= number_format($proy['contador_bn_estimado']) ?></li>
                    <?php if ($proy['contador_color_estimado'] > 0): ?><li><b>Cierre estimado Color:</b> <?= number_format($proy['contador_color_estimado']) ?></li><?php endif; ?>
                    <li><b>Modelo:</b> <?= htmlspecialchars($proy['modelo']) ?></li>
                    <li><b>MAE (backtest):</b> <?= $proy['mae_pct'] !== null ? $proy['mae_pct'] . '%' : 'n/d' ?></li>
                    <li><b>Historial:</b> <?= (int)$proy['meses_historial'] ?> meses <?= $proy['preliminar'] ? '<span class="badge bg-secondary">preliminar (&lt;6)</span>' : '' ?></li>
                    <li><b>Tóner al cierre:</b> <?= $proy['toner_pct_estimado'] !== null ? round($proy['toner_pct_estimado']) . '%' : 'n/d' ?></li>
                    <li><b>Umbral de mantenimiento:</b> <?= htmlspecialchars($proy['fecha_umbral_mantenimiento'] ?? 'n/d') ?></li>
                    <li><b>Estado:</b> <?= $proy['aplicada'] ? 'Aplicada al informe' : 'Solo respaldo (no aplicada)' ?></li>
                </ul>
            <?php else: ?><p class="text-muted small mb-0">Aún no hay proyección. Ejecute el motor predictivo (<code>npm run ml</code> en <code>servicios/</code>).</p><?php endif; ?>
        </div></div></div>
    </div>
    <div class="card shadow-sm border-0"><div class="card-body">
        <h6 class="fw-bold">Consumo mensual B/N</h6>
        <canvas id="c2" height="90"></canvas>
    </div></div>
</main>
<script>
const labels = <?= json_encode($labels) ?>;
new Chart(document.getElementById('c1'), {
    type: 'line',
    data: { labels, datasets: [
        { label: 'Lectura real', data: <?= json_encode($real) ?>, borderColor: '#0A2540', backgroundColor: '#0A2540', tension: .25, spanGaps: false },
        { label: 'Proyección (respaldo)', data: <?= json_encode($estimado) ?>, borderColor: '#f59f00', backgroundColor: '#f59f00', borderDash: [6, 5], tension: .25 }
    ]},
    options: { responsive: true, scales: { y: { ticks: { callback: v => v.toLocaleString() } } } }
});
new Chart(document.getElementById('c2'), {
    type: 'bar',
    data: { labels, datasets: [
        { label: 'Consumo real', data: <?= json_encode($consumo) ?>, backgroundColor: '#0A2540' },
        { label: 'Consumo estimado', data: <?= json_encode($consEst) ?>, backgroundColor: '#f59f00' }
    ]},
    options: { responsive: true }
});
</script>
</body>
</html>
