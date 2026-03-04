<?php
require '../../backend/auth/guard.php';
require '../../backend/config/db.php';

$empresa_id = $_GET['empresa_id'] ?? null;
if (!$empresa_id) {
    die("Empresa no seleccionada");
}

$stmt = $conn->prepare("SELECT nombre FROM empresas WHERE id = ?");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$empresa = $stmt->get_result()->fetch_assoc();

if (!$empresa) {
    die("Empresa no encontrada");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Copiadoras - <?= htmlspecialchars($empresa['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        /* Ajuste para que la tabla sea legible en pantallas pequeñas */
        .table-card {
            border-radius: 15px;
            overflow: hidden;
        }
        .table th {
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        .table td {
            font-size: 0.85rem;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

<div id="particles-js"></div>

<nav class="navbar navbar-dark px-4">
    <a class="navbar-brand d-flex align-items-center" href="inicio.php">
        <img src="../assets/images/foto.png" width="40" class="me-2">
        ICV
    </a>
    <a href="inicio.php" class="btn btn-outline-light">⬅ Volver</a>
</nav>

<main class="container-fluid px-4 my-5 flex-grow-1">

    <div class="text-center mb-4">
        <h2 class="fw-bold text-primary">
            COPIADORAS – <?= strtoupper(htmlspecialchars($empresa['nombre'])) ?>
        </h2>
        <p class="text-muted small">Lectura y control de contadores desglosados</p>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <a href="../../backend/crud/create.php?empresa_id=<?= $empresa_id ?>"
           class="btn btn-info text-white shadow-sm">
            ➕ Registrar nueva lectura
        </a>
    </div>

    <div class="card shadow table-card">
        <div class="card-body p-0">
            <?php include "../../backend/crud/list.php"; ?>
        </div>
    </div>

</main>

<footer class="text-white text-center py-3">
    <small>© 2026 ICV - Gestión Técnica Profesional</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/particles.js"></script>
<script>
particlesJS("particles-js", {
  particles: {
    number: { value: 60 },
    color: { value: "#0A2540" },
    size: { value: 3 },
    line_linked: { enable: true, distance: 150, color: "#0A2540" },
    move: { enable: true, speed: 2 }
  }
});
</script>
</body>
</html>