<?php
session_start();
require "../../backend/auth/guard.php";
require "../../backend/config/db.php";
require "../../backend/security/functions.php";
require "../../backend/lib/lecturas.php";

// RNF-04: el técnico no accede al panel administrativo
$rol = $_SESSION['rol'] ?? 'tecnico';
if ($rol === 'tecnico') {
    header("Location: inicio.php?error=acceso_restringido_equipos");
    exit;
}

$periodo = $_GET['periodo'] ?? date('Y-m');
if (!periodoValido($periodo)) $periodo = date('Y-m');
$empresa_id = (int)($_GET['empresa_id'] ?? 0);

$equipos = estadoEquipos($conn, $periodo, $empresa_id);
$empresas = $conn->query("SELECT id, nombre FROM empresas ORDER BY nombre");

$cont = ['Reportado' => 0, 'Estimado' => 0, 'Pendiente' => 0];
foreach ($equipos as $e) $cont[$e['estado']]++;

$notifs = $conn->query("SELECT id, tipo, mensaje, creada_en FROM notificaciones WHERE leida = 0 ORDER BY id DESC LIMIT 8");
$tickets_pend = (int)$conn->query("SELECT COUNT(*) n FROM tickets_mantenimiento WHERE estado = 'Pendiente'")->fetch_assoc()['n'];
$token = generarTokenCSRF();

$semaforo = ['Reportado' => 'success', 'Estimado' => 'warning', 'Pendiente' => 'danger'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Sincronización | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .dot { display:inline-block; width:14px; height:14px; border-radius:50%; }
        .dot-success { background:#198754; } .dot-warning { background:#ffc107; } .dot-danger { background:#dc3545; }
        .kpi { border-radius: 15px; border: 0; }
        td, th { white-space: nowrap; }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark px-4" style="background-color: #0A2540;">
    <a class="navbar-brand fw-bold" href="inicio.php">ICV</a>
    <div class="d-flex gap-2">
        <a href="mantenimiento.php" class="btn btn-outline-light btn-sm"><i class="bi bi-tools"></i> Mantenimiento
            <?php if ($tickets_pend): ?><span class="badge bg-danger"><?= $tickets_pend ?></span><?php endif; ?></a>
        <a href="inicio.php" class="btn btn-outline-light btn-sm">⬅ Volver</a>
    </div>
</nav>

<main class="container-fluid px-4 my-4">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_GET['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h3 class="text-primary fw-bold mb-0"><i class="bi bi-traffic-light"></i> Semáforo de sincronización</h3>
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <input type="month" name="periodo" value="<?= htmlspecialchars($periodo) ?>" class="form-control form-control-sm" style="width:auto">
            <select name="empresa_id" class="form-select form-select-sm" style="width:auto">
                <option value="0">Todos los clientes</option>
                <?php while ($em = $empresas->fetch_assoc()): ?>
                    <option value="<?= $em['id'] ?>" <?= $empresa_id === (int)$em['id'] ? 'selected' : '' ?>><?= htmlspecialchars($em['nombre']) ?></option>
                <?php endwhile; ?>
            </select>
            <button class="btn btn-primary btn-sm">Filtrar</button>
            <a class="btn btn-success btn-sm"
               href="../../backend/reports/consolidado.php?periodo=<?= urlencode($periodo) ?>&empresa_id=<?= $empresa_id ?>">
                <i class="bi bi-file-earmark-excel"></i> Informe consolidado (Excel)</a>
        </form>
    </div>

    <div class="row g-3 mb-4">
        <?php foreach ($cont as $est => $n): ?>
            <div class="col-12 col-md-4">
                <div class="card kpi shadow-sm"><div class="card-body d-flex align-items-center gap-3">
                    <span class="dot dot-<?= $semaforo[$est] ?>"></span>
                    <div><div class="fs-3 fw-bold"><?= $n ?></div><div class="text-muted small"><?= $est ?></div></div>
                </div></div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($notifs->num_rows > 0): ?>
    <div class="card shadow-sm mb-4 border-0" style="border-radius:15px">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-bell-fill text-warning"></i> Alertas sin leer</strong>
            <form method="POST" action="../../backend/admin/marcar_notificaciones.php" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= $token ?>">
                <input type="hidden" name="volver" value="dashboard.php?periodo=<?= htmlspecialchars($periodo) ?>&empresa_id=<?= $empresa_id ?>">
                <button class="btn btn-outline-secondary btn-sm">Marcar como leídas</button>
            </form>
        </div>
        <ul class="list-group list-group-flush small">
            <?php while ($n = $notifs->fetch_assoc()): ?>
                <li class="list-group-item"><span class="badge bg-secondary me-2"><?= htmlspecialchars($n['tipo']) ?></span>
                    <?= htmlspecialchars($n['mensaje']) ?> <span class="text-muted">(<?= htmlspecialchars($n['creada_en']) ?>)</span></li>
            <?php endwhile; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0" style="border-radius:15px">
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-dark">
                <tr>
                    <th></th><th>Cliente</th><th>Dependencia</th><th>Equipo</th><th>Serie</th><th>Estado</th>
                    <th class="text-end">Contador B/N</th><th class="text-end">Contador Color</th>
                    <th>Modelo / MAE</th><th>Tóner cierre</th><th>Umbral mant.</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($equipos as $e):
                $cls = $semaforo[$e['estado']];
                $bn  = $e['estado'] === 'Reportado' ? $e['real_bn'] : ($e['proy_id'] ? $e['contador_bn_estimado'] : null);
                $col = $e['estado'] === 'Reportado' ? $e['real_color'] : ($e['proy_id'] ? $e['contador_color_estimado'] : null);
            ?>
                <tr>
                    <td><span class="dot dot-<?= $cls ?>"></span></td>
                    <td><?= htmlspecialchars($e['empresa']) ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($e['dependencia']) ?></td>
                    <td><?= htmlspecialchars($e['marca_modelo']) ?></td>
                    <td><?= htmlspecialchars($e['serie']) ?></td>
                    <td>
                        <span class="badge bg-<?= $cls ?> <?= $cls === 'warning' ? 'text-dark' : '' ?>"><?= $e['estado'] ?></span>
                        <?php if ($e['estado'] === 'Reportado'): ?><span class="text-muted">(<?= htmlspecialchars($e['origen']) ?>)</span><?php endif; ?>
                    </td>
                    <td class="text-end"><?= $bn !== null ? number_format($bn) : '—' ?></td>
                    <td class="text-end"><?= $col !== null && ($col > 0) ? number_format($col) : '—' ?></td>
                    <td>
                        <?php if ($e['proy_id']): ?>
                            <?= htmlspecialchars($e['modelo']) ?>
                            <?= $e['mae_pct'] !== null ? '· MAE ' . $e['mae_pct'] . '%' : '' ?>
                            <?php if ($e['preliminar']): ?><span class="badge bg-secondary">preliminar</span><?php endif; ?>
                        <?php else: ?><span class="text-muted">sin proyección</span><?php endif; ?>
                    </td>
                    <td><?= $e['toner_pct_estimado'] !== null ? round($e['toner_pct_estimado']) . '%' : '—' ?></td>
                    <td><?= htmlspecialchars($e['fecha_umbral_mantenimiento'] ?? '—') ?></td>
                    <td>
                        <a class="btn btn-outline-primary btn-sm" title="Ver tendencia" href="tendencia.php?equipo_id=<?= $e['id'] ?>"><i class="bi bi-graph-up"></i></a>
                        <?php if ($e['proy_id'] && $e['estado'] === 'Pendiente'): ?>
                            <form method="POST" action="../../backend/admin/aplicar_proyeccion.php" class="d-inline"
                                  onsubmit="return confirm('¿Usar la cifra estimada como respaldo para el informe?')">
                                <input type="hidden" name="csrf_token" value="<?= $token ?>">
                                <input type="hidden" name="proyeccion_id" value="<?= $e['proy_id'] ?>">
                                <input type="hidden" name="accion" value="aplicar">
                                <input type="hidden" name="volver" value="dashboard.php?periodo=<?= htmlspecialchars($periodo) ?>&empresa_id=<?= $empresa_id ?>">
                                <button class="btn btn-warning btn-sm">Aplicar estimación</button>
                            </form>
                        <?php elseif ($e['estado'] === 'Estimado'): ?>
                            <form method="POST" action="../../backend/admin/aplicar_proyeccion.php" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= $token ?>">
                                <input type="hidden" name="proyeccion_id" value="<?= $e['proy_id'] ?>">
                                <input type="hidden" name="accion" value="revertir">
                                <input type="hidden" name="volver" value="dashboard.php?periodo=<?= htmlspecialchars($periodo) ?>&empresa_id=<?= $empresa_id ?>">
                                <button class="btn btn-outline-secondary btn-sm">Revertir</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$equipos): ?><tr><td colspan="12" class="text-center text-muted py-4">No hay equipos registrados.</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <p class="text-muted small mt-3 mb-0">
        <span class="dot dot-success"></span> Reportado: lectura real (SMTP o manual) en el periodo ·
        <span class="dot dot-warning"></span> Estimado: cifra del motor predictivo autorizada para el informe ·
        <span class="dot dot-danger"></span> Pendiente: sin lectura. La cifra estimada es solo un respaldo de contingencia, no una lectura física.
    </p>
</main>
</body>
</html>
