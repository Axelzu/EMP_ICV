<?php
require '../config/db.php';
require '../auth/guard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/security/functions.php';

$empresa_id = $_GET['empresa_id'] ?? null;
if (!$empresa_id) {
    die("Empresa no seleccionada");
}

// 1. Buscamos los equipos que ya existen para esta empresa para mostrarlos estáticamente
$sql_equipos = "SELECT dependencia, marca_modelo, serie 
                FROM impresoras_formulario 
                WHERE empresa_id = ? 
                GROUP BY serie"; // Agrupamos por serie para no repetir la misma máquina
$stmt_eq = $conn->prepare($sql_equipos);
$stmt_eq->bind_param("i", $empresa_id);
$stmt_eq->execute();
$equipos = $stmt_eq->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Masivo | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../frontend/assets/css/custom.css">
    <style>
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            background-color: #f8f9fa;
            z-index: -1;
            top: 0;
            left: 0;
        }
        .form-card-wide {
            z-index: 10;
            background-color: rgba(255, 255, 255, 0.95) !important;
            border-radius: 15px;
            width: 95%;
            max-width: 1100px;
        }
        .static-cell {
            background-color: #e9ecef;
            font-size: 0.85rem;
            font-weight: bold;
        }
        .input-counter {
            min-width: 100px;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

<div id="particles-js"></div>

<nav class="navbar navbar-dark px-4" style="background-color: #0A2540;">
    <a class="navbar-brand d-flex align-items-center" href="../../frontend/pages/inicio.php">
        <img src="../../frontend/assets/images/foto.png" width="40" class="me-2">
        ICV - Gestión
    </a>
</nav>

<main class="flex-grow-1 d-flex align-items-center justify-content-center py-4">
    <div class="card shadow form-card-wide">
        <div class="card-body p-4">
            <h4 class="text-center text-primary mb-4">📝 Registro Masivo de Lecturas</h4>

            <form action="store_masivo.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF(); ?>">
                <input type="hidden" name="empresa_id" value="<?= htmlspecialchars($empresa_id) ?>">

                <div class="row g-3 mb-4 justify-content-center">
                    <div class="col-md-4 text-center">
                        <label class="form-label fw-bold small">Fecha Inicial (Período)</label>
                        <input type="date" name="fecha_inicial" class="form-control text-center" required>
                    </div>
                    <div class="col-md-4 text-center">
                        <label class="form-label fw-bold small">Fecha Final (Período)</label>
                        <input type="date" name="fecha_final" class="form-control text-center" required>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <thead class="table-dark small">
                            <tr>
                                <th>Departamento</th>
                                <th>Marca / Modelo</th>
                                <th>N° Serie</th>
                                <th class="table-secondary text-dark">Copias B/N</th>
                                <th class="table-secondary text-dark">Imp. B/N</th>
                                <th class="table-info text-dark">Copias Col</th>
                                <th class="table-info text-dark">Imp. Col</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 0;
                            if ($equipos->num_rows > 0): 
                                while ($row = $equipos->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td class="static-cell">
                                        <?= htmlspecialchars($row['dependencia']) ?>
                                        <input type="hidden" name="equipos[<?= $i ?>][dependencia]" value="<?= $row['dependencia'] ?>">
                                    </td>
                                    <td class="static-cell">
                                        <?= htmlspecialchars($row['marca_modelo']) ?>
                                        <input type="hidden" name="equipos[<?= $i ?>][marca_modelo]" value="<?= $row['marca_modelo'] ?>">
                                    </td>
                                    <td class="static-cell">
                                        <?= htmlspecialchars($row['serie']) ?>
                                        <input type="hidden" name="equipos[<?= $i ?>][serie]" value="<?= $row['serie'] ?>">
                                    </td>

                                    <td>
                                        <input type="number" name="equipos[<?= $i ?>][copias_bn]" class="form-control form-control-sm input-counter" value="0" min="0">
                                    </td>
                                    <td>
                                        <input type="number" name="equipos[<?= $i ?>][impresiones_bn]" class="form-control form-control-sm input-counter" value="0" min="0">
                                    </td>
                                    <td>
                                        <input type="number" name="equipos[<?= $i ?>][copias_color]" class="form-control form-control-sm input-counter" value="0" min="0">
                                    </td>
                                    <td>
                                        <input type="number" name="equipos[<?= $i ?>][impresiones_color]" class="form-control form-control-sm input-counter" value="0" min="0">
                                    </td>
                                </tr>
                            <?php 
                                $i++;
                                endwhile; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="7" class="text-center p-4">
                                        No se encontraron equipos registrados para esta empresa. 
                                        <br><small class="text-muted">Debe registrar al menos un equipo primero.</small>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="../../frontend/pages/empresa.php?empresa_id=<?= $empresa_id ?>" class="btn btn-secondary">⬅ Cancelar</a>
                    <?php if ($i > 0): ?>
                        <button type="submit" class="btn btn-danger shadow-sm fw-bold">💾 GUARDAR TODAS LAS LECTURAS</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</main>

<footer class="text-white text-center py-3 mt-auto" style="background-color: #0A2540;">
    <small>© 2026 ICV - Gestión de Equipos</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/particles.js"></script>
<script>
    particlesJS("particles-js", {
        "particles": {
            "number": { "value": 60, "density": { "enable": true, "value_area": 800 } },
            "color": { "value": "#0A2540" },
            "shape": { "type": "circle" },
            "opacity": { "value": 0.5 },
            "size": { "value": 3 },
            "line_linked": { "enable": true, "distance": 150, "color": "#0A2540", "opacity": 0.4, "width": 1 },
            "move": { "enable": true, "speed": 2 }
        },
        "retina_detect": true
    });
</script>

</body>
</html>