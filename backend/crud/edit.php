<?php
require "../config/db.php";
require "../auth/guard.php";
// --- NUEVO: CARGAR APARTADO DE SEGURIDAD ---
require '../security/functions.php'; 

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Copiadora no seleccionada");
}

/* Buscar copiadora */
$sql = "SELECT * FROM impresoras_formulario WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$copiadora = $result->fetch_assoc();

if (!$copiadora) {
    die("Copiadora no encontrada");
}

$empresa_id = $copiadora['empresa_id'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Copiadora</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../frontend/assets/css/custom.css">
</head>

<body class="d-flex flex-column min-vh-100">

<div id="particles-js"></div>

<nav class="navbar navbar-dark px-4">
    <a class="navbar-brand d-flex align-items-center" href="../../frontend/pages/inicio.php">
        <img src="../../frontend/assets/images/foto.png" width="40" class="me-2">
        ICV
    </a>
</nav>

<main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="card shadow form-card">

        <h4 class="text-center text-primary mb-4">✏️ Editar Copiadora</h4>

        <form action="update.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF(); ?>">

            <input type="hidden" name="id" value="<?= $copiadora['id'] ?>">
            <input type="hidden" name="empresa_id" value="<?= $empresa_id ?>">

            <div class="mb-3">
                <label class="form-label">Marca</label>
                <input type="text" name="marca_impresora" 
                       class="form-control" 
                       value="<?= htmlspecialchars($copiadora['marca_impresora']) ?>" 
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Número de serie</label>
                <input type="text" name="numero_serie" 
                       class="form-control" 
                       value="<?= htmlspecialchars($copiadora['numero_serie']) ?>" 
                       required>
            </div>

            <div class="mb-4">
                <label class="form-label">Contador general</label>
                <input type="number" name="contador_general" 
                       class="form-control" 
                       value="<?= $copiadora['contador_general'] ?>" 
                       required>
            </div>

            <div class="d-flex justify-content-between">
                <a href="../../frontend/pages/empresa.php?empresa_id=<?= $empresa_id ?>" 
                   class="btn btn-secondary">⬅ Volver</a>

                <button class="btn btn-info text-white">💾 Actualizar</button>
            </div>
        </form>

    </div>
</main>

<footer class="text-white text-center py-3">
    <small>© 2026 ICV</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/particles.js"></script>
<script>
particlesJS("particles-js", {
  particles: { number: { value: 60 }, size: { value: 3 }, move: { speed: 2 } }
});
</script>

</body>
</html>