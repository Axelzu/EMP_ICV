<?php
require '../../backend/auth/guard.php';
require '../../backend/config/db.php';

$empresa_id = $_GET['empresa_id'] ?? null;
if (!$empresa_id) {
    die("Empresa no seleccionada");
}

// 1. Obtener nombre de la empresa
$stmt = $conn->prepare("SELECT nombre FROM empresas WHERE id = ?");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$empresa = $stmt->get_result()->fetch_assoc();

if (!$empresa) { die("Empresa no encontrada"); }

// 2. Intentar obtener máquinas. Si la tabla está vacía, no se romperá la página.
$sql_maq = "SELECT dependencia, marca_modelo, serie FROM impresoras_formulario WHERE empresa_id = ? GROUP BY serie ORDER BY dependencia ASC";
$stmt_maq = $conn->prepare($sql_maq);
$stmt_maq->bind_param("i", $empresa_id);
$stmt_maq->execute();
$maquinas = $stmt_maq->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Copiadoras - <?= htmlspecialchars($empresa['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .custom-card-btn {
            transition: all 0.3s ease;
            border: 2px solid #dc3545 !important;
            border-radius: 15px;
            background-color: rgba(255, 255, 255, 0.9);
            height: 100%;
        }
        .custom-card-btn:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.3) !important;
        }
        .card-icon { width: 60px; height: 60px; object-fit: contain; margin-bottom: 10px; }
        #particles-js { position: fixed; width: 100%; height: 100%; z-index: -1; top: 0; left: 0; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
<div id="particles-js"></div>

<nav class="navbar navbar-dark px-4" style="background-color: #0A2540;">
    <a class="navbar-brand d-flex align-items-center" href="inicio.php">
        <img src="../assets/images/foto.png" width="40" class="me-2"> ICV
    </a>
    <a href="inicio.php" class="btn btn-outline-light btn-sm">⬅ Volver</a>
</nav>

<main class="container-fluid px-4 my-5 flex-grow-1">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-primary"><?= strtoupper(htmlspecialchars($empresa['nombre'])) ?></h2>
        <p class="text-muted">Gestión de Lecturas y Equipos</p>
    </div>

    <div class="d-flex justify-content-center mb-5">
        <a href="../../backend/crud/create.php?empresa_id=<?= $empresa_id ?>" class="btn btn-info text-white shadow">
            ➕ Registrar Nueva Máquina / Lectura
        </a>
    </div>

    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4 mb-5">
        <?php if ($maquinas && $maquinas->num_rows > 0): ?>
            <?php while($m = $maquinas->fetch_assoc()): ?>
                <div class="col">
                    <a href="../../backend/crud/create.php?empresa_id=<?= $empresa_id ?>&serie=<?= urlencode($m['serie']) ?>" class="text-decoration-none text-center">
                        <div class="card shadow-sm custom-card-btn p-3">
                            <div class="card-body d-flex flex-column align-items-center">
                                <img src="../assets/images/lectura.png" class="card-icon">
                                <h6 class="fw-bold text-danger mb-1"><?= strtoupper(htmlspecialchars($m['dependencia'])) ?></h6>
                                <small class="text-dark d-block"><?= htmlspecialchars($m['marca_modelo']) ?></small>
                                <span class="badge bg-dark mt-2">S/N: <?= htmlspecialchars($m['serie']) ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-body p-0">
            <?php include "../../backend/crud/list.php"; ?>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/particles.js"></script>
<script>
particlesJS("particles-js", {
    particles: { number: { value: 40 }, color: { value: "#0A2540" }, opacity: { value: 0.2 }, size: { value: 3 }, line_linked: { enable: true, color: "#0A2540" }, move: { speed: 1.5 } }
});
</script>
</body>
</html>