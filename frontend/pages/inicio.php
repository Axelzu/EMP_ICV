<?php
session_start();
require "../../backend/auth/guard.php";
require "../../backend/config/db.php";

$usuario = $_SESSION['user_name'];
$empresas = $conn->query("SELECT * FROM empresas");

// Arreglo con logos por ID de empresa
$logos = [
    1 => 'h_vozandes.png',
    2 => 'c_internacional.png',
    3 => 'c_cruzmedic.png',
    4 => 'empresa4.png',
    5 => 'empresa5.png'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ICV - Inicio</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="../assets/css/custom.css">
</head>
<body class="d-flex flex-column min-vh-100">

<div id="particles-js"></div>

<nav class="navbar navbar-dark px-4 shadow-sm">
    <span class="navbar-brand fw-bold">ICV - Gestión</span>
    
    <div class="d-flex gap-2">
        <?php if (isset($_SESSION['user_name']) && $_SESSION['user_name'] === 'Admin'): ?>
            <a href="auditoria.php" class="btn btn-warning btn-sm fw-bold shadow-sm">
                <i class="bi bi-eye-fill"></i> Monitoreo
            </a>
        <?php endif; ?>

        <a href="../../backend/auth/logout.php" class="btn btn-danger btn-sm shadow-sm">
            <i class="bi bi-box-arrow-right"></i> Cerrar sesión
        </a>
    </div>
</nav>

<section class="container my-4">
    <div class="card shadow welcome-card">
        <div class="d-flex align-items-center">
            <img src="../assets/images/user.png" width="80" class="me-3 rounded-circle border border-2 border-primary">
            <div>
                <h5 class="mb-0">Bienvenido</h5>
                <strong class="text-primary fs-5"><?= htmlspecialchars($usuario) ?></strong>
                <?php if ($_SESSION['user_name'] === 'Admin'): ?>
                    <span class="badge bg-primary d-block mt-1">Administrador</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

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
                    <div class="card shadow empresa-card h-100 border-0">
                        <div class="card-body d-flex align-items-center">
                            <img src="../assets/images/<?= $logo ?>" width="60" height="60" class="me-3 rounded-circle object-fit-cover shadow-sm">
                            <h6 class="mb-0 text-dark fw-bold">
                                <?= strtoupper(htmlspecialchars($empresa['nombre'])) ?>
                            </h6>
                        </div>
                    </div>
                </a>
            </div>
        <?php endwhile; ?>
    </div>
</section>

<footer class="text-white text-center py-3 mt-auto" style="background-color: #0A2540;">
    <small>© 2026 ICV - Todos los derechos reservados</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/particles.js"></script>

<script>
particlesJS("particles-js", {
  particles: {
    number: { value: 80, density: { enable: true, value_area: 800 } },
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