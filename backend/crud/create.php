<?php
// Reporte de errores para saber qué falla exactamente
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../config/db.php';
require '../auth/guard.php';

// Intentar cargar la seguridad de forma absoluta
$rutaSeguridad = __DIR__ . '/../security/functions.php';
if (file_exists($rutaSeguridad)) {
    require_once $rutaSeguridad;
} else {
    die("Error: No se encuentra el archivo de seguridad en: " . $rutaSeguridad);
}

$empresa_id = $_GET['empresa_id'] ?? null;
if (!$empresa_id) {
    die("Empresa no seleccionada");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Copiadora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../frontend/assets/css/custom.css">
</head>
<body class="d-flex flex-column min-vh-100">

<div id="particles-js"></div>

<nav class="navbar navbar-dark px-4" style="background-color: #0A2540;">
    <a class="navbar-brand d-flex align-items-center" href="../../frontend/pages/inicio.php">
        <img src="../../frontend/assets/images/foto.png" width="40" class="me-2">
        ICV
    </a>
</nav>

<main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="card shadow form-card" style="width: 400px; background-color: white; padding: 20px; border-radius: 10px; z-index: 100;">
        <h4 class="text-center text-primary mb-4">➕ Registrar Copiadora</h4>

        <form action="store.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
            <input type="hidden" name="empresa_id" value="<?= htmlspecialchars($empresa_id) ?>">

            <div class="mb-3">
                <label class="form-label fw-bold">Marca de la impresora</label>
                <input type="text" name="marca_impresora" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Número de serie</label>
                <input type="text" name="numero_serie" class="form-control" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Contador general</label>
                <input type="number" name="contador_general" class="form-control" required>
            </div>

            <div class="d-flex justify-content-between">
                <a href="../../frontend/pages/empresa.php?empresa_id=<?= $empresa_id ?>" class="btn btn-secondary">⬅ Cancelar</a>
                <button type="submit" class="btn btn-info text-white">💾 Guardar</button>
            </div>
        </form>
    </div>
</main>

<footer class="text-white text-center py-3 mt-auto" style="background-color: #0A2540;">
    <small>© 2026 ICV - Todos los derechos reservados</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>