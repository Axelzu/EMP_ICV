<?php
require '../config/db.php';
require '../auth/guard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/security/functions.php';

$empresa_id = $_GET['empresa_id'] ?? null;
if (!$empresa_id) {
    die("Empresa no seleccionada");
}

// 1. Buscamos los equipos registrados para esta empresa
// Usamos DISTINCT por si hay varios registros de la misma máquina, solo mostrar una fila por serie
$sql_equipos = "SELECT dependencia, marca_modelo, serie 
                FROM impresoras_formulario 
                WHERE empresa_id = ? 
                GROUP BY serie 
                ORDER BY dependencia ASC";
                
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
    <title>Registro de Lecturas | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../frontend/assets/css/custom.css">
    <style>
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            background-color: #f8f9fa;
            z-index: -1;
            top: 0; left: 0;
        }
        .form-card-wide {
            z-index: 10;
            background-color: rgba(255, 255, 255, 0.98) !important;
            border-radius: 15px;
            width: 95%;
            max-width: 1200px;
            border-top: 5px solid #dc3545; /* Detalle rojo */
        }
        /* Celdas estáticas grisadas */
        .static-cell {
            background-color: #f1f3f5 !important;
            color: #495057;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .input-number-icv {
            border: 1px solid #ced4da;
            text-align: center;
            font-weight: bold;
        }
        .input-number-icv:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

<div id="particles-js"></div>

<nav class="navbar navbar-dark px-4" style="background-color: #0A2540;">
    <a class="navbar-brand d-flex align-items-center" href="../../frontend/pages/inicio.php">
        <img src="../../frontend/assets/images/foto.png" width="40" class="me-2">
        ICV - PANEL TÉCNICO
    </a>
</nav>

<main class="flex-grow-1 d-flex align-items-center justify-content-center py-4">
    <div class="card shadow form-card-wide">
        <div class="card-body p-4">
            <h4 class="text-center text-danger fw-bold mb-4">📝 INGRESO DE CONTADORES</h4>

            <form action="store_masivo.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF(); ?>">
                <input type="hidden" name="empresa_id" value="<?= htmlspecialchars($empresa_id) ?>">

                <div class="row g-3 mb-4 justify-content-center">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary">FECHA INICIAL</label>
                        <input type="date" name="fecha_inicial" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary">FECHA FINAL</label>
                        <input type="date" name="fecha_final" class="form-control" required>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-dark text-center">
                            <tr class="small">
                                <th width="20%">DEPENDENCIA</th>
                                <th width="20%">MARCA / MODELO</th>
                                <th width="15%">SERIE</th>
                                <th class="table-danger text-dark">COP. B/N</th>
                                <th class="table-danger text-dark">IMP. B/N</th>
                                <th class="table-primary text-dark">COP. COL</th>
                                <th class="table-primary text-dark">IMP. COL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 0;
                            if ($equipos->num_rows > 0): 
                                while ($row = $equipos->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td class="static-cell"><?= htmlspecialchars($row['dependencia']) ?></td>
                                    <td class="static-cell"><?= htmlspecialchars($row['marca_modelo']) ?></td>
                                    <td class="static-cell"><?= htmlspecialchars($row['serie']) ?></td>

                                    <input type="hidden" name="equipos[<?= $i ?>][dependencia]" value="<?= $row['dependencia'] ?>">
                                    <input type="hidden" name="equipos[<?= $i ?>][marca_modelo]" value="<?= $row['marca_modelo'] ?>">
                                    <input type="hidden" name="equipos[<?= $i ?>][serie]" value="<?= $row['serie'] ?>">

                                    <td>
                                        <input type="number" name="equipos[<?= $i ?>][copias_bn]" class="form-control form-control-sm input-number-icv" value="0" min="0">
                                    </td>
                                    <td>
                                        <input type="number" name="equipos[<?= $i ?>][impresiones_bn]" class="form-control form-control-sm input-number-icv" value="0" min="0">
                                    </td>
                                    <td>
                                        <input type="number" name="equipos[<?= $i ?>][copias_color]" class="form-control form-control-sm input-number-icv" value="0" min="0">
                                    </td>
                                    <td>
                                        <input type="number" name="equipos[<?= $i ?>][impresiones_color]" class="form-control form-control-sm input-number-icv" value="0" min="0">
                                    </td>
                                </tr>
                            <?php 
                                $i++;
                                endwhile; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="7" class="text-center p-4">
                                        <span class="text-muted">No hay equipos registrados para este cliente.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="../../frontend/pages/empresa.php?empresa_id=<?= $empresa_id ?>" class="btn btn-secondary">⬅ Volver</a>
                    <?php if ($i > 0): ?>
                        <button type="submit" class="btn btn-danger px-5 shadow fw-bold">💾 GUARDAR TODAS LAS LECTURAS</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/particles.js"></script>
<script>
    particlesJS("particles-js", {
        "particles": {
            "number": { "value": 50 },
            "color": { "value": "#0A2540" },
            "shape": { "type": "circle" },
            "opacity": { "value": 0.3 },
            "size": { "value": 3 },
            "line_linked": { "enable": true, "distance": 150, "color": "#0A2540", "opacity": 0.2, "width": 1 },
            "move": { "enable": true, "speed": 2 }
        }
    });
</script>

</body>
</html>