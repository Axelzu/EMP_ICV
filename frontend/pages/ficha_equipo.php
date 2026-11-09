<?php
// RF-01: ficha técnica por equipo (datos, estado operativo, lecturas, proyección, mantenimientos y alertas).
require '../../backend/auth/guard.php';
require '../../backend/config/db.php';

$serie = $_GET['serie'] ?? '';
$empresa_id = (int)($_GET['empresa_id'] ?? 0);
$rol = $_SESSION['rol'] ?? 'tecnico';
$puede_gestionar = in_array($rol, ['admin', 'supervisor'], true);
if ($serie === '' || !$empresa_id) die("Datos insuficientes para mostrar la ficha.");

$st = $conn->prepare("SELECT e.*, emp.nombre AS empresa FROM equipos e JOIN empresas emp ON emp.id = e.empresa_id
                      WHERE e.serie = ? AND e.empresa_id = ?");
$st->bind_param("si", $serie, $empresa_id);
$st->execute();
$eq = $st->get_result()->fetch_assoc();
if (!$eq) die("El equipo no existe en el sistema.");
$eid = (int)$eq['id'];

$consulta = function ($sql) use ($conn, $eid) {
    $q = $conn->prepare($sql);
    $q->bind_param("i", $eid);
    $q->execute();
    return $q->get_result()->fetch_all(MYSQLI_ASSOC);
};
$lecturas = $consulta("SELECT fecha, contador_bn, contador_color, toner_pct, origen FROM lecturas
                       WHERE equipo_id = ? AND origen <> 'ESTIMADO' ORDER BY fecha DESC, id DESC LIMIT 12");
$proy = $consulta("SELECT periodo, contador_bn_estimado, contador_color_estimado, mae_pct, meses_historial, preliminar, toner_pct_estimado,
                          fecha_umbral_mantenimiento, modelo, aplicada
                   FROM proyecciones WHERE equipo_id = ? ORDER BY periodo DESC LIMIT 1");
$proy = $proy[0] ?? null;
$tickets = $consulta("SELECT t.estado, t.motivo, t.fecha_agendada, t.fecha_completado, u.nombre AS tecnico, c.nombre AS completado_por
                      FROM tickets_mantenimiento t
                      LEFT JOIN users u ON u.id = t.tecnico_id
                      LEFT JOIN users c ON c.id = t.completado_por
                      WHERE t.equipo_id = ? ORDER BY t.id DESC LIMIT 10");
$alertas = $consulta("SELECT categoria, resumen, recibido_en FROM alertas_equipo WHERE equipo_id = ? ORDER BY recibido_en DESC, id DESC LIMIT 10");

$colorEstado = ['Operativo' => 'success', 'En mantenimiento' => 'warning text-dark', 'Fuera de servicio' => 'secondary'];
$colorTicket = ['Pendiente' => 'warning text-dark', 'Completado' => 'success', 'Cancelado' => 'secondary'];
$estilo_alerta = ['TONER' => ['warning text-dark', 'Tóner'], 'PAPEL' => ['info text-dark', 'Papel'],
                  'TAPA' => ['secondary', 'Tapa'], 'OTRO' => ['dark', 'Otro']];
$fecha = function ($f) { return $f ? date('d/m/Y H:i', strtotime($f)) : '—'; };
$u = $lecturas[0] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha del equipo <?= htmlspecialchars($eq['serie']) ?> | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        body { background-color: #f8f9fa; }
        .panel { border-radius: 15px; background: #fff; }
        dt { color: #6c757d; font-weight: 600; font-size: .85rem; }
        dd { margin-bottom: .6rem; }
        td, th { white-space: nowrap; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark px-4" style="background-color: #0A2540;">
    <a class="navbar-brand d-flex align-items-center" href="inicio.php">
        <img src="../assets/images/foto.png" width="40" class="me-2"> ICV
    </a>
    <div class="d-flex gap-2">
        <?php if ($puede_gestionar): ?>
            <a href="editar_equipo.php?serie=<?= urlencode($eq['serie']) ?>&empresa_id=<?= $empresa_id ?>" class="btn btn-warning btn-sm">Editar</a>
        <?php endif; ?>
        <a href="empresa.php?empresa_id=<?= $empresa_id ?>" class="btn btn-outline-light btn-sm">⬅ Volver</a>
    </div>
</nav>

<main class="container my-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h3 class="fw-bold mb-0" style="color:#0A2540"><i class="bi bi-card-list"></i> Ficha técnica del equipo</h3>
            <span class="text-muted"><?= htmlspecialchars($eq['marca_modelo']) ?> · <?= htmlspecialchars($eq['serie']) ?></span>
        </div>
        <span class="badge fs-6 bg-<?= $colorEstado[$eq['estado']] ?? 'secondary' ?>"><?= htmlspecialchars($eq['estado']) ?></span>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card panel shadow-sm border-0 h-100"><div class="card-body">
                <h6 class="fw-bold mb-3">Datos del equipo</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Identificador único</dt><dd class="col-sm-8">#<?= $eid ?></dd>
                    <dt class="col-sm-4">Cliente</dt><dd class="col-sm-8"><?= htmlspecialchars($eq['empresa']) ?></dd>
                    <dt class="col-sm-4">Dependencia</dt><dd class="col-sm-8"><?= htmlspecialchars($eq['dependencia']) ?></dd>
                    <dt class="col-sm-4">Marca y modelo</dt><dd class="col-sm-8"><?= htmlspecialchars($eq['marca_modelo']) ?></dd>
                    <dt class="col-sm-4">Número de serie</dt><dd class="col-sm-8"><?= htmlspecialchars($eq['serie']) ?></dd>
                    <dt class="col-sm-4">Tipo de impresión</dt><dd class="col-sm-8"><?= htmlspecialchars($eq['tipo_color'] ?? '—') ?></dd>
                    <dt class="col-sm-4">Dirección MAC</dt><dd class="col-sm-8"><?= htmlspecialchars($eq['mac'] ?: '—') ?></dd>
                    <dt class="col-sm-4">Nombre de host</dt><dd class="col-sm-8"><?= htmlspecialchars($eq['hostname'] ?: '—') ?></dd>
                    <dt class="col-sm-4">Umbral de mantenimiento</dt><dd class="col-sm-8"><?= number_format((int)$eq['umbral_mantenimiento']) ?> páginas</dd>
                    <dt class="col-sm-4">Rendimiento del tóner</dt><dd class="col-sm-8"><?= number_format((int)$eq['rendimiento_toner']) ?> páginas</dd>
                    <dt class="col-sm-4">Última lectura real</dt>
                    <dd class="col-sm-8">
                        <?php if ($u): ?>
                            <?= $fecha($u['fecha']) ?> · B/N <?= number_format((int)$u['contador_bn']) ?> · Color <?= number_format((int)$u['contador_color']) ?>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($u['origen']) ?></span>
                        <?php else: ?>Sin lecturas<?php endif; ?>
                    </dd>
                </dl>
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card panel shadow-sm border-0 h-100 text-center"><div class="card-body">
                <h6 class="fw-bold mb-3">Código QR del equipo</h6>
                <div id="qr" class="d-flex justify-content-center mb-2"></div>
                <small class="text-muted">Se escanea para confirmar el mantenimiento. Etiquetas imprimibles en «Códigos QR».</small>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card panel shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Proyección del motor predictivo</div>
                <div class="card-body">
                <?php if ($proy): ?>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Periodo</dt><dd class="col-sm-7"><?= htmlspecialchars($proy['periodo']) ?></dd>
                        <dt class="col-sm-5">Contador B/N estimado</dt><dd class="col-sm-7"><?= number_format((int)$proy['contador_bn_estimado']) ?></dd>
                        <dt class="col-sm-5">Contador color estimado</dt><dd class="col-sm-7"><?= number_format((int)$proy['contador_color_estimado']) ?></dd>
                        <dt class="col-sm-5">Modelo y error (MAE)</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($proy['modelo']) ?><?= $proy['mae_pct'] !== null ? ' · ' . number_format((float)$proy['mae_pct'], 2) . ' %' : '' ?>
                            <?= $proy['preliminar'] ? '<span class="badge bg-warning text-dark">preliminar</span>' : '' ?></dd>
                        <dt class="col-sm-5">Meses de historial</dt><dd class="col-sm-7"><?= (int)$proy['meses_historial'] ?></dd>
                        <dt class="col-sm-5">Próximo mantenimiento</dt><dd class="col-sm-7"><?= $proy['fecha_umbral_mantenimiento'] ? date('d/m/Y', strtotime($proy['fecha_umbral_mantenimiento'])) : '—' ?></dd>
                    </dl>
                <?php else: ?>
                    <p class="text-muted mb-0">Aún no hay proyección: el motor necesita al menos 2 meses de lecturas reales.</p>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card panel shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Alertas recientes de la impresora</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 small">
                        <tbody>
                        <?php foreach ($alertas as $a): ?>
                            <tr>
                                <td><?= $fecha($a['recibido_en']) ?></td>
                                <td><span class="badge bg-<?= $estilo_alerta[$a['categoria']][0] ?? 'dark' ?>"><?= htmlspecialchars($estilo_alerta[$a['categoria']][1] ?? $a['categoria']) ?></span></td>
                                <td class="text-wrap"><?= htmlspecialchars($a['resumen']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$alertas): ?><tr><td class="text-muted text-center py-3">Sin alertas registradas.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card panel shadow-sm border-0 mb-3">
        <div class="card-header bg-white fw-bold">Historial de mantenimientos</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 small">
                <thead class="table-light"><tr><th>Estado</th><th>Motivo</th><th>Agendado</th><th>Técnico asignado</th><th>Completado</th><th>Confirmado por</th></tr></thead>
                <tbody>
                <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td><span class="badge bg-<?= $colorTicket[$t['estado']] ?? 'secondary' ?>"><?= htmlspecialchars($t['estado']) ?></span></td>
                        <td class="text-wrap"><?= htmlspecialchars($t['motivo']) ?></td>
                        <td><?= $t['fecha_agendada'] ? date('d/m/Y', strtotime($t['fecha_agendada'])) : '—' ?></td>
                        <td><?= htmlspecialchars($t['tecnico'] ?? 'Sin asignar') ?></td>
                        <td><?= $fecha($t['fecha_completado']) ?></td>
                        <td><?= htmlspecialchars($t['completado_por'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$tickets): ?><tr><td colspan="6" class="text-muted text-center py-3">Sin mantenimientos registrados.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card panel shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">Últimas lecturas de contadores</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 small">
                <thead class="table-light"><tr><th>Fecha y hora</th><th class="text-end">B/N</th><th class="text-end">Color</th><th class="text-end">Tóner</th><th>Origen</th></tr></thead>
                <tbody>
                <?php foreach ($lecturas as $l): ?>
                    <tr>
                        <td><?= $fecha($l['fecha']) ?></td>
                        <td class="text-end"><?= number_format((int)$l['contador_bn']) ?></td>
                        <td class="text-end"><?= number_format((int)$l['contador_color']) ?></td>
                        <td class="text-end"><?= $l['toner_pct'] !== null ? (int)$l['toner_pct'] . ' %' : '—' ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($l['origen']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$lecturas): ?><tr><td colspan="5" class="text-muted text-center py-3">Sin lecturas.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
<?php if (!empty($eq['qr_token'])): ?>
new QRCode(document.getElementById('qr'), { text: 'ICV:' + <?= json_encode($eq['qr_token']) ?>, width: 150, height: 150, correctLevel: QRCode.CorrectLevel.M });
<?php else: ?>
document.getElementById('qr').innerHTML = '<span class="text-muted small">Sin código QR asignado.</span>';
<?php endif; ?>
</script>
</body>
</html>
