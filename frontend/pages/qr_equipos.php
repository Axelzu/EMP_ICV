<?php
session_start();
require "../../backend/auth/guard.php";
require "../../backend/config/db.php";

if (!in_array($_SESSION['rol'] ?? '', ['admin', 'supervisor'], true)) {
    header("Location: inicio.php?error=acceso_restringido_equipos");
    exit;
}

// Garantiza que todos los equipos tengan su identificador QR
$conn->query("UPDATE equipos SET qr_token = MD5(CONCAT(serie, RAND(), NOW())) WHERE qr_token IS NULL OR qr_token = ''");

$empresa_id = (int)($_GET['empresa_id'] ?? 0);
$sql = "SELECT e.serie, e.dependencia, e.marca_modelo, e.qr_token, emp.nombre AS empresa
        FROM equipos e JOIN empresas emp ON emp.id = e.empresa_id" . ($empresa_id ? " WHERE e.empresa_id = $empresa_id" : "") . "
        ORDER BY emp.nombre, e.dependencia";
$equipos = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$empresas = $conn->query("SELECT id, nombre FROM empresas ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas QR | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        .etiqueta { border: 1px dashed #999; border-radius: 8px; padding: 10px; text-align: center; background: #fff; break-inside: avoid; }
        .etiqueta .qr { display: flex; justify-content: center; margin-bottom: 6px; }
        @media print { .no-print { display: none !important; } body { background: #fff !important; } }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
        <h4 class="text-primary fw-bold mb-0">Etiquetas QR de equipos</h4>
        <form class="d-flex gap-2" method="GET">
            <select name="empresa_id" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="0">Todos los clientes</option>
                <?php while ($em = $empresas->fetch_assoc()): ?>
                    <option value="<?= $em['id'] ?>" <?= $empresa_id === (int)$em['id'] ? 'selected' : '' ?>><?= htmlspecialchars($em['nombre']) ?></option>
                <?php endwhile; ?>
            </select>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">Imprimir</button>
            <a href="mantenimiento.php" class="btn btn-outline-secondary btn-sm">⬅ Volver</a>
        </form>
    </div>
    <div class="row row-cols-2 row-cols-md-4 row-cols-xl-6 g-3">
        <?php foreach ($equipos as $i => $e): ?>
            <div class="col"><div class="etiqueta">
                <div class="qr" id="qr<?= $i ?>" data-token="<?= htmlspecialchars($e['qr_token']) ?>"></div>
                <div class="small fw-bold"><?= htmlspecialchars($e['marca_modelo']) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($e['empresa']) ?> · <?= htmlspecialchars($e['dependencia']) ?></div>
                <div class="small">S/N <?= htmlspecialchars($e['serie']) ?></div>
            </div></div>
        <?php endforeach; ?>
    </div>
</div>
<script>
document.querySelectorAll('.qr').forEach(el => {
    new QRCode(el, { text: 'ICV:' + el.dataset.token, width: 130, height: 130, correctLevel: QRCode.CorrectLevel.M });
});
</script>
</body>
</html>
