<?php
require '../../backend/auth/guard.php';
require '../../backend/config/db.php';

$empresa_id = $_GET['empresa_id'] ?? null;
if (!$empresa_id) { die("Empresa no seleccionada"); }

// 1. Obtener nombre de la empresa
$stmt = $conn->prepare("SELECT nombre FROM empresas WHERE id = ?");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$empresa = $stmt->get_result()->fetch_assoc();

if (!$empresa) { die("Empresa no encontrada"); }

// 2. BUSCAR EN LA TABLA MAESTRA DE EQUIPOS
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Copiadoras - <?= htmlspecialchars($empresa['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        #particles-js {
            position: fixed; width: 100%; height: 100%; z-index: -1; top: 0; left: 0;
            background-color: #f8f9fa;
        }
        .custom-card-btn {
            transition: all 0.3s ease;
            border: 2px solid #dc3545 !important;
            border-radius: 15px;
            background-color: rgba(255, 255, 255, 0.9);
            height: 100%;
            cursor: pointer;
        }
        .custom-card-btn:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.3) !important;
        }
        .card-icon { width: 60px; height: 60px; margin-bottom: 15px; }
        .empty-state-wrapper {
            min-height: 40vh; display: flex; align-items: center; justify-content: center;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

<div id="particles-js"></div>

<nav class="navbar navbar-dark px-4" style="background-color: #0A2540;">
    <a class="navbar-brand d-flex align-items-center" href="inicio.php">
        <img src="../assets/images/foto.png" width="40" class="me-2">
        ICV
    </a>
    <div class="d-flex gap-2">
        <a href="nuevo_equipo.php?empresa_id=<?= $empresa_id ?>" class="btn btn-info btn-sm text-white shadow-sm">+ Agregar Máquina</a>
        <a href="inicio.php" class="btn btn-outline-light btn-sm">⬅ Volver</a>
    </div>
</nav>

<main class="container-fluid px-4 my-5 flex-grow-1">

    <div class="text-center mb-5">
        <h2 class="fw-bold text-primary"><?= strtoupper(htmlspecialchars($empresa['nombre'])) ?></h2>
        <p class="text-muted">Seleccione un equipo para registrar lectura</p>
    </div>

    <?php if ($maquinas->num_rows > 0): ?>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4 mb-5 justify-content-center">
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
        </div>
    <?php else: ?>
        <div class="empty-state-wrapper">
            <div class="card p-5 shadow-sm text-center border-0" style="border-radius: 20px; max-width: 500px;">
                <h5 class="fw-bold">No hay equipos registrados</h5>
                <p class="text-muted">Registre la primera máquina para comenzar.</p>
                <a href="nuevo_equipo.php?empresa_id=<?= $empresa_id ?>" class="btn btn-danger shadow px-4">Registrar Máquina</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="text-center mb-3">
        <h5 class="fw-bold text-secondary">📄 HISTORIAL DE REGISTROS</h5>
    </div>
    
    <div class="table-card shadow border-0">
        <div class="card-body p-0">
            <?php include "../../backend/crud/list.php"; ?>
        </div>
    </div>

</main>

<footer class="text-white text-center py-3 mt-auto" style="background-color: #0A2540;">
    <small>© 2026 ICV - Gestión Técnica Profesional</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/particles.js"></script>
<script>
    particlesJS("particles-js", {
        particles: {
            number: { value: 50 },
            color: { value: "#0A2540" },
            opacity: { value: 0.2 },
            size: { value: 3 },
            line_linked: { enable: true, distance: 150, color: "#0A2540", opacity: 0.2, width: 1 },
            move: { enable: true, speed: 2 }
        }
    });
</script>
</body>
</html>