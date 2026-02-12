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
    <title>Copiadoras - <?= htmlspecialchars($empresa['nombre']) ?></title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS propio -->
    <link rel="stylesheet" href="../assets/css/custom.css">
</head>

<body class="d-flex flex-column min-vh-100">

<!-- PARTICULAS -->
<div id="particles-js"></div>

<!-- NAVBAR -->
<nav class="navbar navbar-dark px-4">
    <a class="navbar-brand d-flex align-items-center" href="inicio.php">
        <img src="../assets/images/foto.png" width="40" class="me-2">
        ICV
    </a>

    <a href="inicio.php" class="btn btn-outline-light">
        ⬅ Volver al inicio
    </a>
</nav>

<!-- CONTENIDO -->
<main class="container my-5 flex-grow-1">

    <!-- TÍTULO -->
    <div class="text-center mb-4">
        <h2 class="fw-bold text-primary">
            COPIADORAS – <?= strtoupper(htmlspecialchars($empresa['nombre'])) ?>
        </h2>
        <p class="text-muted">
            Gestión de impresoras registradas
        </p>
    </div>

    <!-- BOTÓN CREAR -->
    <div class="d-flex justify-content-end mb-3">
        <a href="../../backend/crud/create.php?empresa_id=<?= $empresa_id ?>"
           class="btn btn-info text-white">
            ➕ Registrar impresora
        </a>
    </div>

    <!-- TABLA -->
    <div class="card shadow table-card">
        <div class="card-body">
            <?php include "../../backend/crud/list.php"; ?>
        </div>
    </div>

</main>

<!-- FOOTER -->
<footer class="text-white text-center py-3">
    <small>© 2026 ICV - Todos los derechos reservados</small>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/particles.js"></script>

<script>
particlesJS("particles-js", {
  particles: {
    number: { value: 60 },
    color: { value: "#0A2540" },
    size: { value: 3 },
    line_linked: {
      enable: true,
      distance: 150,
      color: "#0A2540"
    },
    move: { enable: true, speed: 2 }
  }
});
</script>

</body>
</html>
