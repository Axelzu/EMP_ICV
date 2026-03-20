<?php
require '../../backend/auth/guard.php';
require '../../backend/config/db.php';

$empresa_id = $_GET['empresa_id'] ?? null;
$rol_usuario = $_SESSION['rol'] ?? 'tecnico'; // Obtenemos el rol de la sesión

if (!$empresa_id) { die("Empresa no seleccionada"); }

// 1. Obtener nombre de la empresa
$stmt = $conn->prepare("SELECT nombre FROM empresas WHERE id = ?");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$empresa = $stmt->get_result()->fetch_assoc();

if (!$empresa) { die("Empresa no encontrada"); }

// 2. BUSCAR EN LA TABLA MAESTRA Y VERIFICAR SI TIENE REGISTRO HOY (Incluimos tipo_color)
$hoy = date('Y-m-d');
$sql_maq = "SELECT e.dependencia, e.marca_modelo, e.serie, e.tipo_color, 
            (SELECT COUNT(*) FROM impresoras_formulario 
             WHERE serie = e.serie AND DATE(fecha_registro) = ?) as ya_registrado
            FROM equipos e 
            WHERE e.empresa_id = ? 
            ORDER BY e.dependencia ASC";

$stmt_maq = $conn->prepare($sql_maq);
$stmt_maq->bind_param("si", $hoy, $empresa_id);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        #particles-js {
            position: fixed; width: 100%; height: 100%; z-index: -1; top: 0; left: 0;
            background-color: #f8f9fa;
        }
        .card-container {
            position: relative;
            height: 100%;
        }
        .custom-card-btn {
            transition: all 0.3s ease;
            border-radius: 15px;
            background-color: rgba(255, 255, 255, 0.9);
            height: 100%;
            cursor: pointer;
        }
        .card-pendiente { border: 2px solid #dc3545 !important; }
        .card-pendiente:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.3) !important;
        }
        .card-completado {
            border: 2px solid #198754 !important;
            background-color: #f0fff4 !important;
        }
        .card-completado:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(25, 135, 84, 0.3) !important;
        }
        .action-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
            display: flex;
            gap: 5px;
        }
        .btn-action-sm {
            padding: 2px 6px;
            font-size: 0.75rem;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            transition: 0.2s;
        }
        .btn-edit-sm { background-color: #ffc107; color: #000; }
        .btn-delete-sm { background-color: #dc3545; }
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
        <?php if ($rol_usuario === 'admin' || $rol_usuario === 'supervisor'): ?>
            <a href="nuevo_equipo.php?empresa_id=<?= $empresa_id ?>" class="btn btn-info btn-sm text-white shadow-sm">+ Agregar Máquina</a>
        <?php endif; ?>
        <a href="inicio.php" class="btn btn-outline-light btn-sm">⬅ Volver</a>
    </div>
</nav>

<main class="container-fluid px-4 my-5 flex-grow-1">

    <div class="text-center mb-5">
        <h2 class="fw-bold text-primary"><?= strtoupper(htmlspecialchars($empresa['nombre'])) ?></h2>
        <p class="text-muted">Los equipos en <b class="text-success">Verde</b> ya tienen lectura hoy.</p>
    </div>

    <div class="container mb-4">
        <?php if (isset($_GET['error']) && $_GET['error'] === 'serie_duplicada'): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm text-center mx-auto" role="alert" style="max-width: 600px; border-radius: 12px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>¡Atención!</strong> El número de serie que intentas registrar ya existe.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm text-center mx-auto" role="alert" style="max-width: 600px; border-radius: 12px;">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>¡Excelente!</strong> La lectura se ha registrado correctamente con la hora actual.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($maquinas->num_rows > 0): ?>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4 mb-5 justify-content-center">
            <?php while($m = $maquinas->fetch_assoc()): 
                $esta_listo = ($m['ya_registrado'] > 0);
                $clase_status = $esta_listo ? 'card-completado' : 'card-pendiente';
                $texto_status = $esta_listo ? 'text-success' : 'text-danger';
                $tipo_impresion = $m['tipo_color'] ?? 'Blanco y Negro';
            ?>
                <div class="col">
                    <div class="card-container">
                        <?php if ($rol_usuario === 'admin' || $rol_usuario === 'supervisor'): ?>
                        <div class="action-overlay">
                            <a href="editar_equipo.php?serie=<?= urlencode($m['serie']) ?>&empresa_id=<?= $empresa_id ?>" class="btn-action-sm btn-edit-sm" title="Editar">✏️</a>
                            <a href="../../backend/crud/delete_equipo.php?serie=<?= urlencode($m['serie']) ?>&empresa_id=<?= $empresa_id ?>" 
                               class="btn-action-sm btn-delete-sm" 
                               onclick="return confirm('¿Seguro que quieres borrar este equipo?')" title="Eliminar">🗑️</a>
                        </div>
                        <?php endif; ?>

                        <a href="../../backend/crud/create.php?empresa_id=<?= $empresa_id ?>&serie=<?= urlencode($m['serie']) ?>" class="text-decoration-none text-center">
                            <div class="card shadow-sm custom-card-btn p-3 <?= $clase_status ?>">
                                <div class="card-body d-flex flex-column align-items-center">
                                    <img src="../assets/images/lectura.png" class="card-icon" 
                                         style="<?= $esta_listo ? 'filter: grayscale(100%) sepia(100%) hue-rotate(70deg) saturate(3);' : '' ?>">
                                    
                                    <h6 class="fw-bold <?= $texto_status ?> mb-1"><?= strtoupper(htmlspecialchars($m['dependencia'])) ?></h6>
                                    <small class="text-dark d-block"><?= htmlspecialchars($m['marca_modelo']) ?></small>
                                    
                                    <div class="mt-2">
                                        <?php if ($tipo_impresion === 'Color'): ?>
                                            <span class="badge rounded-pill bg-info text-dark" style="font-size: 0.65rem;">COLOR</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-secondary" style="font-size: 0.65rem;">B/N</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if($esta_listo): ?>
                                        <span class="badge bg-success mt-2">✓ COMPLETADO</span>
                                    <?php else: ?>
                                        <span class="badge bg-dark mt-2">S/N: <?= htmlspecialchars($m['serie']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state-wrapper">
            <div class="card p-5 shadow-sm text-center border-0" style="border-radius: 20px; max-width: 500px;">
                <h5 class="fw-bold">No hay equipos registrados</h5>
                <p class="text-muted">Registre la primera máquina para comenzar.</p>
                <?php if ($rol_usuario === 'admin' || $rol_usuario === 'supervisor'): ?>
                    <a href="nuevo_equipo.php?empresa_id=<?= $empresa_id ?>" class="btn btn-danger shadow px-4">Registrar Máquina</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="text-center mb-3">
        <h5 class="fw-bold text-secondary">HISTORIAL DE REGISTROS</h5>
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