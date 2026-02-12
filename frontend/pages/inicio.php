<?php
session_start();
require "../../backend/auth/guard.php";
require "../../backend/config/db.php";

$usuario = $_SESSION['user_name'];
$empresas = $conn->query("SELECT * FROM empresas");

// Arreglo con logos por ID de empresa
$logos = [
    1 => 'h_vozandes.png',
    2 => 'empresa2.png',
    3 => 'empresa3.png',
    4 => 'empresa4.png',
    5 => 'empresa5.png'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ICV - Inicio</title>

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS PROPIO -->
    <link rel="stylesheet" href="../assets/css/custom.css">
</head>
<body class="d-flex flex-column min-vh-100">

<!-- PARTICULAS -->
<div id="particles-js"></div>

<!-- NAVBAR -->
<nav class="navbar navbar-dark px-4">
    <span class="navbar-brand">ICV</span>
    <a href="../../backend/auth/logout.php" class="btn btn-danger btn-sm">
        Cerrar sesión
    </a>
</nav>

<!-- HEADER BIENVENIDA -->
<section class="container my-4">
    <div class="card shadow welcome-card">
        <div class="d-flex align-items-center">
            <img src="../assets/images/user.png" width="80" class="me-3 rounded-circle">
            <div>
                <h5 class="mb-0">Bienvenido</h5>
                <strong class="text-primary fs-5"><?= htmlspecialchars($usuario) ?></strong>
            </div>
        </div>
    </div>
</section>

<!-- LISTADO DE EMPRESAS -->
<section class="container flex-grow-1">
    <h4 class="mb-4 text-primary fw-bold">Clientes</h4>

    <div class="row g-3">
        <?php while ($empresa = $empresas->fetch_assoc()): ?>
            <?php 
            // Determinar qué logo usar
            $logo = $logos[$empresa['id']] ?? 'empresa.png';
            ?>
            <div class="col-md-6 col-lg-4">
                <a href="empresa.php?empresa_id=<?= $empresa['id'] ?>" class="text-decoration-none">
                    <div class="card shadow empresa-card h-100">
                        <div class="card-body d-flex align-items-center">
                            <img src="../assets/images/<?= $logo ?>" width="60" class="me-3 rounded-circle">
                            <h6 class="mb-0 text-dark">
                                <?= htmlspecialchars($empresa['nombre']) ?>
                            </h6>
                        </div>
                    </div>
                </a>
            </div>
        <?php endwhile; ?>
    </div>
</section>

<!-- FOOTER -->
<footer class="text-white text-center py-3 mt-auto">
    <small>© 2026 ICV - Todos los derechos reservados</small>
</footer>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/particles.js"></script>

<script>
particlesJS("particles-js", {
  particles: {
    number: { value: 80 },
    color: { value: "#0A2540" },
    shape: { type: "circle" },
    opacity: { value: 0.5 },
    size: { value: 3 },
    line_linked: {
      enable: true,
      distance: 150,
      color: "#0A2540",
      opacity: 0.4,
      width: 1
    },
    move: { enable: true, speed: 3 }
  },
  retina_detect: true
});
</script>

</body>
</html>
