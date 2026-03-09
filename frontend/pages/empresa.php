<?php
require '../../backend/auth/guard.php';
require '../../backend/config/db.php';

$empresa_id = $_GET['empresa_id'] ?? null;
if (!$empresa_id) { die("Empresa no seleccionada"); }

// 1. Nombre de la empresa
$stmt = $conn->prepare("SELECT nombre FROM empresas WHERE id = ?");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$empresa = $stmt->get_result()->fetch_assoc();

// 2. BUSCAR EN LA NUEVA TABLA MAESTRA DE EQUIPOS
$sql_maq = "SELECT dependencia, marca_modelo, serie FROM equipos WHERE empresa_id = ? ORDER BY dependencia ASC";
$stmt_maq = $conn->prepare($sql_maq);
$stmt_maq->bind_param("i", $empresa_id);
$stmt_maq->execute();
$maquinas = $stmt_maq->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Copiadoras - <?= htmlspecialchars($empresa['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .custom-card-btn {
            transition: all 0.3s ease;
            border: 2px solid #dc3545 !important;
            border-radius: 15px;
            background-color: white;
            height: 100%;
        }
        .custom-card-btn:hover { transform: translateY(-10px); box-shadow: 0 10px 20px rgba(220, 53, 69, 0.2); }
        .card-icon { width: 50px; margin-bottom: 10px; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark px-4" style="background-color: #0A2540;">
    <a class="navbar-brand" href="inicio.php">ICV</a>
    <div class="d-flex gap-2">
        <a href="nuevo_equipo.php?empresa_id=<?= $empresa_id ?>" class="btn btn-sm btn-info text-white">+ Agregar Máquina</a>
        <a href="inicio.php" class="btn btn-sm btn-outline-light">⬅ Volver</a>
    </div>
</nav>

<main class="container py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-primary"><?= strtoupper(htmlspecialchars($empresa['nombre'])) ?></h2>
        <p class="text-muted">Seleccione un equipo para registrar su contador</p>
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php if ($maquinas->num_rows > 0): ?>
            <?php while($m = $maquinas->fetch_assoc()): ?>
                <div class="col">
                    <a href="../../backend/crud/create.php?empresa_id=<?= $empresa_id ?>&serie=<?= urlencode($m['serie']) ?>" class="text-decoration-none">
                        <div class="card shadow-sm custom-card-btn p-3 text-center">
                            <img src="../assets/images/lectura.png" class="card-icon mx-auto">
                            <h6 class="fw-bold text-danger mb-1"><?= strtoupper(htmlspecialchars($m['dependencia'])) ?></h6>
                            <small class="text-muted"><?= htmlspecialchars($m['marca_modelo']) ?></small>
                            <div class="badge bg-dark mt-2">S/N: <?= htmlspecialchars($m['serie']) ?></div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <p class="text-muted">No hay equipos registrados para esta empresa.</p>
                <a href="nuevo_equipo.php?empresa_id=<?= $empresa_id ?>" class="btn btn-danger">Registrar Primera Máquina</a>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>