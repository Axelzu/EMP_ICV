<?php
session_start();
require "../../backend/auth/guard.php";
require "../../backend/config/db.php";

$rol = $_SESSION['rol'] ?? 'tecnico';
$uid = (int)$_SESSION['user_id'];
$esAdmin = in_array($rol, ['admin', 'supervisor'], true);

// RNF-04: el técnico solo ve los mantenimientos que tiene asignados
$sql = "SELECT t.*, e.serie, e.dependencia, e.marca_modelo, emp.nombre AS empresa, u.nombre AS tecnico
        FROM tickets_mantenimiento t
        JOIN equipos e ON e.id = t.equipo_id
        JOIN empresas emp ON emp.id = e.empresa_id
        LEFT JOIN users u ON u.id = t.tecnico_id";
if (!$esAdmin) $sql .= " WHERE t.tecnico_id = " . $uid;
$sql .= " ORDER BY (t.estado = 'Pendiente') DESC, t.fecha_agendada ASC, t.id DESC LIMIT 200";
$tickets = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento Preventivo | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark px-4" style="background-color:#0A2540">
    <a class="navbar-brand fw-bold" href="inicio.php">ICV</a>
    <div class="d-flex gap-2">
        <a href="escanear.php" class="btn btn-success btn-sm fw-bold"><i class="bi bi-qr-code-scan"></i> Escanear QR</a>
        <?php if ($esAdmin): ?>
            <a href="qr_equipos.php" class="btn btn-outline-light btn-sm"><i class="bi bi-printer"></i> Etiquetas QR</a>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">Panel</a>
        <?php endif; ?>
        <a href="inicio.php" class="btn btn-outline-light btn-sm">⬅ Volver</a>
    </div>
</nav>
<main class="container my-4">
    <h3 class="text-primary fw-bold mb-1"><i class="bi bi-tools"></i> Mantenimiento preventivo</h3>
    <p class="text-muted">Los tickets los agenda automáticamente el motor predictivo y se cierran al escanear el código QR del equipo en sitio.</p>
    <div class="card shadow-sm border-0" style="border-radius:15px"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
        <thead class="table-dark"><tr>
            <th>#</th><th>Estado</th><th>Agendado</th><th>Cliente</th><th>Equipo</th><th>Serie</th><th>Técnico</th><th>Motivo</th><th>Completado</th>
        </tr></thead>
        <tbody>
        <?php while ($t = $tickets->fetch_assoc()):
            $cls = ['Pendiente' => 'danger', 'Completado' => 'success', 'Cancelado' => 'secondary'][$t['estado']] ?? 'secondary'; ?>
            <tr>
                <td><?= $t['id'] ?></td>
                <td><span class="badge bg-<?= $cls ?>"><?= htmlspecialchars($t['estado']) ?></span></td>
                <td><?= htmlspecialchars($t['fecha_agendada']) ?></td>
                <td><?= htmlspecialchars($t['empresa']) ?></td>
                <td><?= htmlspecialchars($t['marca_modelo']) ?><br><span class="text-muted"><?= htmlspecialchars($t['dependencia']) ?></span></td>
                <td><?= htmlspecialchars($t['serie']) ?></td>
                <td><?= htmlspecialchars($t['tecnico'] ?? 'Sin asignar') ?></td>
                <td style="max-width:320px"><?= htmlspecialchars($t['motivo']) ?></td>
                <td><?= htmlspecialchars($t['fecha_completado'] ?? '—') ?></td>
            </tr>
        <?php endwhile; ?>
        <?php if ($tickets->num_rows === 0): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No hay tickets de mantenimiento.</td></tr>
        <?php endif; ?>
        </tbody>
    </table></div></div>
</main>
</body>
</html>
