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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Copiadoras - <?= htmlspecialchars($empresa['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        /* Estilos para los cuadros visuales */
        .custom-card-btn {
            transition: all 0.3s ease;
            border: 2px solid #dc3545 !important; /* Borde Rojo */
            border-radius: 15px;
            cursor: pointer;
            background-color: rgba(255, 255, 255, 0.9);
        }
        .custom-card-btn:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.3) !important;
            background-color: #fff5f5;
        }
        .card-icon {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 15px;
        }
        
        /* Ajuste de tabla */
        .table-card {
            border-radius: 15px;
            overflow: hidden;
        }
        .table th {
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        .table td {
            font-size: 0.85rem;
        }
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: -1;
            top: 0;
            left: 0;
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
    <a href="inicio.php" class="btn btn-outline-light">⬅ Volver</a>
</nav>

<main class="container-fluid px-4 my-5 flex-grow-1">

    <div class="text-center mb-5">
        <h2 class="fw-bold text-primary">
            <?= strtoupper(htmlspecialchars($empresa['nombre'])) ?>
        </h2>
        <p class="text-muted">Seleccione el tipo de operación a realizar</p>
    </div>

    <div class="row justify-content-center mb-5">
        
        <div class="col-md-3 col-sm-6 mb-4">
            <a href="../../backend/crud/create.php?empresa_id=<?= $empresa_id ?>" class="text-decoration-none text-center">
                <div class="card h-100 shadow-sm custom-card-btn p-3">
                    <div class="card-body d-flex flex-column align-items-center">
                        <img src="../assets/images/lectura.png" class="card-icon" alt="Lectura">
                        <h5 class="fw-bold text-dark">REGISTRO LECTURA</h5>
                        <small class="text-muted">Contadores BN / Color</small>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
            <a href="../../backend/crud/create.php?empresa_id=<?= $empresa_id ?>" class="text-decoration-none text-center">
                <div class="card h-100 shadow-sm custom-card-btn p-3">
                    <div class="card-body d-flex flex-column align-items-center">
                        <img src="../assets/images/mantenimiento.png" class="card-icon" alt="Soporte">
                        <h5 class="fw-bold text-dark">SERVICIO TÉCNICO</h5>
                        <small class="text-muted">Mantenimiento preventivo</small>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
            <a href="../../backend/crud/create.php?empresa_id=<?= $empresa_id ?>" class="text-decoration-none text-center">
                <div class="card h-100 shadow-sm custom-card-btn p-3">
                    <div class="card-body d-flex flex-column align-items-center">
                        <img src="../assets/images/suministros.png" class="card-icon" alt="Toner">
                        <h5 class="fw-bold text-dark">SUMINISTROS</h5>
                        <small class="text-muted">Entrega de Tóner / Repuestos</small>
                    </div>
                </div>
            </a>
        </div>

    </div>

    <div class="text-center mb-3">
        <h5 class="fw-bold text-secondary">📄 ÚLTIMOS REGISTROS</h5>
    </div>
    
    <div class="card shadow table-card">
        <div class="card-body p-0">
            <?php include "../../backend/crud/list.php"; ?>
        </div>
    </div>

</main>

<footer class="text-white text-center py-3" style="background-color: #0A2540;">
    <small>© 2026 ICV - Gestión Técnica Profesional</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/particles.js"></script>
<script>
particlesJS("particles-js", {
  particles: {
    number: { value: 60 },
    color: { value: "#0A2540" },
    size: { value: 3 },
    line_linked: { enable: true, distance: 150, color: "#0A2540" },
    move: { enable: true, speed: 2 }
  }
});
</script>
</body>
</html>