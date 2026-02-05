<?php
require '../config/db.php';
require '../auth/guard.php';

$empresa_id = $_GET['empresa_id'] ?? null;
if (!$empresa_id) {
    die("Empresa no seleccionada");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Copiadora</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="../../frontend/assets/css/custom.css">
</head>

<body class="d-flex flex-column min-vh-100">

<!-- PARTICULAS -->
<div id="particles-js"></div>

<!-- NAVBAR -->
<nav class="navbar navbar-dark px-4">
    <a class="navbar-brand d-flex align-items-center" href="../../frontend/pages/inicio.php">
        <img src="../../frontend/assets/images/foto.png" width="40" class="me-2">
        ICV
    </a>
</nav>

<!-- CONTENIDO -->
<main class="flex-grow-1 d-flex align-items-center justify-content-center">

    <div class="card shadow form-card" style="width: 400px; background-color: rgba(255,255,255,0.9);">
        <h4 class="text-center text-primary mb-4">
            ➕ Registrar Copiadora
        </h4>

        <form action="store.php" method="POST">
            <!-- input oculto con el id de la empresa -->
            <input type="hidden" name="empresa_id" value="<?= htmlspecialchars($empresa_id) ?>">

            <div class="mb-3">
                <label class="form-label">Marca de la impresora</label>
                <input type="text" name="marca_impresora" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Número de serie</label>
                <input type="text" name="numero_serie" class="form-control" required>
            </div>

            <div class="mb-4">
                <label class="form-label">Contador general</label>
                <input type="number" name="contador_general" class="form-control" required>
            </div>

            <div class="d-flex justify-content-between">
                <a href="../../frontend/pages/empresa.php?empresa_id=<?= $empresa_id ?>" class="btn btn-secondary">
                    ⬅ Cancelar
                </a>

                <button class="btn btn-info text-white">
                    💾 Guardar
                </button>
            </div>
        </form>
    </div>

</main>

<!-- FOOTER -->
<footer class="text-white text-center py-3 mt-auto">
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
      color: "#0A2540",
      opacity: 0.4,
      width: 1
    },
    move: { enable: true, speed: 2 }
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
