<?php
require '../../backend/auth/guard.php';
require '../../backend/config/db.php';
require '../../backend/lib/lecturas.php';

$empresa_id = (int)($_GET['empresa_id'] ?? 0);
$rol_usuario = $_SESSION['rol'] ?? 'tecnico';
$puede_gestionar = in_array($rol_usuario, ['admin', 'supervisor'], true);

if (!$empresa_id) { die("Empresa no seleccionada"); }

// 1. Obtener nombre de la empresa
$stmt = $conn->prepare("SELECT nombre FROM empresas WHERE id = ?");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$empresa = $stmt->get_result()->fetch_assoc();

if (!$empresa) { die("Empresa no encontrada"); }

// 2. Periodo a consultar (por defecto el mes actual)
$periodo = $_GET['periodo'] ?? date('Y-m');
if (!periodoValido($periodo)) $periodo = date('Y-m');

// 3. Estado y contadores de cada equipo del cliente en el periodo
$equipos = estadoEquipos($conn, $periodo, $empresa_id);
$semaforo = ['Reportado' => 'success', 'Estimado' => 'warning', 'Pendiente' => 'danger'];

// 4. Últimas lecturas recibidas de los equipos del cliente
$stmt = $conn->prepare("SELECT l.fecha, l.contador_bn, l.contador_color, l.toner_pct, l.origen, e.dependencia, e.marca_modelo, e.serie
                        FROM lecturas l JOIN equipos e ON e.id = l.equipo_id
                        WHERE e.empresa_id = ? AND l.origen <> 'ESTIMADO'
                        ORDER BY l.fecha DESC, l.id DESC LIMIT 40");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$historial = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipos - <?= htmlspecialchars($empresa['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        #particles-js {
            position: fixed; width: 100%; height: 100%; z-index: -1; top: 0; left: 0;
            background-color: #f8f9fa;
        }
        .dot { display:inline-block; width:12px; height:12px; border-radius:50%; }
        .dot-success { background:#198754; } .dot-warning { background:#ffc107; } .dot-danger { background:#dc3545; }
        .panel { border-radius: 15px; background: rgba(255,255,255,0.95); }
        td, th { white-space: nowrap; }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

<div id="particles-js"></div>

<nav class="navbar navbar-dark px-4" style="background-color: #0A2540;">
    <a class="navbar-brand d-flex align-items-center" href="inicio.php">
        <img src="../assets/images/foto.png" width="40" class="me-2">
        ICV
    </a>
    <div class="d-flex gap-2">
        <?php if ($puede_gestionar): ?>
            <a href="nuevo_equipo.php?empresa_id=<?= $empresa_id ?>" class="btn btn-info btn-sm text-white shadow-sm">+ Agregar Máquina</a>
        <?php endif; ?>
        <a href="inicio.php" class="btn btn-outline-light btn-sm">⬅ Volver</a>
    </div>
</nav>

<main class="container-fluid px-4 my-4 flex-grow-1">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-primary mb-0"><?= strtoupper(htmlspecialchars($empresa['nombre'])) ?></h2>
            <p class="text-muted mb-0">Contadores recibidos automáticamente desde los equipos (SMTP).</p>
        </div>
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <input type="hidden" name="empresa_id" value="<?= $empresa_id ?>">
            <input type="month" name="periodo" value="<?= htmlspecialchars($periodo) ?>" class="form-control form-control-sm" style="width:auto">
            <button class="btn btn-primary btn-sm">Ver</button>
            <?php if ($puede_gestionar): ?>
                <a class="btn btn-success btn-sm"
                   href="../../backend/reports/consolidado.php?periodo=<?= urlencode($periodo) ?>&empresa_id=<?= $empresa_id ?>">
                    <i class="bi bi-file-earmark-excel"></i> Descargar Excel
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="container mb-3">
        <?php if (isset($_GET['error']) && $_GET['error'] === 'serie_duplicada'): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm text-center mx-auto" role="alert" style="max-width: 600px; border-radius: 12px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>¡Atención!</strong> El número de serie que intentas registrar ya existe.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'equipo_creado'): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm text-center mx-auto" role="alert" style="max-width: 600px; border-radius: 12px;">
                <i class="bi bi-check-circle-fill me-2"></i> Equipo registrado correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <div class="card panel shadow border-0 mb-4">
        <div class="card-header bg-white fw-bold">Equipos · periodo <?= htmlspecialchars($periodo) ?></div>
        <?php if ($equipos): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-dark">
                    <tr>
                        <th></th><th>Dependencia</th><th>Marca / Modelo</th><th>Serie</th><th>Tipo</th><th>Estado</th>
                        <th class="text-end">Contador B/N</th><th class="text-end">Contador Color</th><th>Última lectura</th>
                        <?php if ($puede_gestionar): ?><th>Acciones</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($equipos as $e):
                    $cls = $semaforo[$e['estado']];
                    $bn  = $e['estado'] === 'Reportado' ? $e['real_bn'] : ($e['estado'] === 'Estimado' ? $e['contador_bn_estimado'] : null);
                    $col = $e['estado'] === 'Reportado' ? $e['real_color'] : ($e['estado'] === 'Estimado' ? $e['contador_color_estimado'] : null);
                ?>
                    <tr>
                        <td><span class="dot dot-<?= $cls ?>"></span></td>
                        <td class="fw-bold"><?= htmlspecialchars($e['dependencia']) ?></td>
                        <td><?= htmlspecialchars($e['marca_modelo']) ?></td>
                        <td><?= htmlspecialchars($e['serie']) ?></td>
                        <td><span class="badge rounded-pill <?= ($e['tipo_color'] ?? '') === 'Color' ? 'bg-info text-dark' : 'bg-secondary' ?>"><?= ($e['tipo_color'] ?? '') === 'Color' ? 'COLOR' : 'B/N' ?></span></td>
                        <td>
                            <span class="badge bg-<?= $cls ?> <?= $cls === 'warning' ? 'text-dark' : '' ?>"><?= $e['estado'] ?></span>
                            <?php if ($e['estado'] === 'Reportado'): ?><span class="text-muted">(<?= htmlspecialchars($e['origen']) ?>)</span><?php endif; ?>
                        </td>
                        <td class="text-end"><?= $bn !== null ? number_format($bn) : '—' ?></td>
                        <td class="text-end"><?= ($col !== null && $col > 0) ? number_format($col) : '—' ?></td>
                        <td><?= $e['ultima_fecha'] ? date('d/m/Y H:i', strtotime($e['ultima_fecha'])) : 'Sin lecturas' ?></td>
                        <?php if ($puede_gestionar): ?>
                        <td>
                            <a href="editar_equipo.php?serie=<?= urlencode($e['serie']) ?>&empresa_id=<?= $empresa_id ?>" class="btn btn-warning btn-sm" title="Editar">✏️</a>
                            <a href="../../backend/crud/delete_equipo.php?serie=<?= urlencode($e['serie']) ?>&empresa_id=<?= $empresa_id ?>"
                               class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que quieres borrar este equipo?')" title="Eliminar">🗑️</a>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="card-body text-center py-5">
                <h5 class="fw-bold">No hay equipos registrados</h5>
                <p class="text-muted">Registre la primera máquina para comenzar.</p>
                <?php if ($puede_gestionar): ?>
                    <a href="nuevo_equipo.php?empresa_id=<?= $empresa_id ?>" class="btn btn-danger shadow px-4">Registrar Máquina</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <p class="text-muted small">
        <span class="dot dot-success"></span> Reportado: lectura recibida en el periodo ·
        <span class="dot dot-warning"></span> Estimado: cifra del motor predictivo autorizada ·
        <span class="dot dot-danger"></span> Pendiente: aún sin lectura.
    </p>

    <div class="card panel shadow border-0">
        <div class="card-header bg-white fw-bold">Últimas lecturas recibidas</div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 small text-center">
                <thead class="table-light">
                    <tr><th>Fecha / Hora</th><th>Dependencia</th><th>Equipo</th><th>Serie</th>
                        <th class="text-end">Contador B/N</th><th class="text-end">Contador Color</th><th>Tóner</th><th>Origen</th></tr>
                </thead>
                <tbody>
                <?php while ($h = $historial->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($h['fecha'])) ?></td>
                        <td><?= htmlspecialchars($h['dependencia']) ?></td>
                        <td><?= htmlspecialchars($h['marca_modelo']) ?></td>
                        <td><?= htmlspecialchars($h['serie']) ?></td>
                        <td class="text-end"><?= number_format($h['contador_bn']) ?></td>
                        <td class="text-end"><?= $h['contador_color'] > 0 ? number_format($h['contador_color']) : '—' ?></td>
                        <td><?= $h['toner_pct'] !== null ? (int)$h['toner_pct'] . '%' : '—' ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($h['origen']) ?></span></td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($historial->num_rows === 0): ?>
                    <tr><td colspan="8" class="text-muted py-4">Todavía no se han recibido lecturas de este cliente.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
