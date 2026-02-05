<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ICV - Iniciar Sesión</title>

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS PROPIO -->
    <link rel="stylesheet" href="../assets/css/custom.css">
</head>
<body class="d-flex flex-column min-vh-100">

<!-- FONDO DE PARTICULAS -->
<div id="particles-js"></div>

<!-- NAVBAR -->
<nav class="navbar navbar-dark px-4">
    <a class="navbar-brand d-flex align-items-center" href="../../index.php">
        <img src="../assets/images/foto.png" width="40" class="me-2" alt="Logo">
        ICV
    </a>
</nav>

<!-- CONTENIDO LOGIN -->
<main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="card shadow login-card">

        <h4 class="text-center mb-4 text-primary fw-bold">
            Iniciar Sesión
        </h4>

        <!-- ALERTA DE ERROR -->
        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="alert alert-danger text-center">
                <?= $_SESSION['login_error']; ?>
            </div>
        <?php unset($_SESSION['login_error']); endif; ?>

        <form action="../../backend/auth/login_process.php" method="POST">
            <div class="mb-3">
                <label class="form-label text-dark">Correo</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label text-dark">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button class="btn btn-info w-100 text-white fw-bold">
                Entrar
            </button>
        </form>
    </div>
</main>

<!-- FOOTER -->
<footer class="text-white text-center py-3">
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
  interactivity: {
    events: {
      onhover: { enable: true, mode: "grab" }
    }
  },
  retina_detect: true
});
</script>

</body>
</html>
