<?php
require '../config/db.php';
require '../auth/guard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/security/functions.php';

$empresa_id = $_GET['empresa_id'] ?? null;
if (!$empresa_id) {
    die("Empresa no seleccionada");
}

// Buscamos los equipos únicos de esta empresa
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
    <style>
        body { background-color: #f8f9fa; }
        .form-card-wide { border-radius: 15px; border-top: 5px solid #dc3545; background: white; }
        .static-cell { background-color: #f1f3f5 !important; font-size: 0.85rem; font-weight: 600; }
        .input-number-icv { text-align: center; font-weight: bold; width: 100px; }
    </style>
</head>
<body class="py-4">

<div class="container-fluid px-4">
    <div class="card shadow form-card-wide">
        <div class="card-body p-4">
            <h4 class="text-center text-danger fw-bold mb-4">📝 INGRESO DE CONTADORES</h4>
            
            <p class="text-center text-muted small">Ingrese las fechas y luego guarde el equipo correspondiente.</p>

            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                    <thead class="table-dark small">
                        <tr>
                            <th>DEPENDENCIA</th>
                            <th>MARCA / MODELO</th>
                            <th>SERIE</th>
                            <th>FECHA INICIAL / FINAL</th>
                            <th class="table-danger text-dark">B/N (COP/IMP)</th>
                            <th class="table-primary text-dark">COL (COP/IMP)</th>
                            <th>ACCIÓN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($equipos->num_rows > 0): ?>
                            <?php while ($row = $equipos->fetch_assoc()): ?>
                                <form action="store.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF(); ?>">
                                    <input type="hidden" name="empresa_id" value="<?= htmlspecialchars($empresa_id) ?>">
                                    <input type="hidden" name="dependencia" value="<?= htmlspecialchars($row['dependencia']) ?>">
                                    <input type="hidden" name="marca_modelo" value="<?= htmlspecialchars($row['marca_modelo']) ?>">
                                    <input type="hidden" name="serie" value="<?= htmlspecialchars($row['serie']) ?>">

                                    <tr>
                                        <td class="static-cell"><?= htmlspecialchars($row['dependencia']) ?></td>
                                        <td class="static-cell"><?= htmlspecialchars($row['marca_modelo']) ?></td>
                                        <td class="static-cell small"><?= htmlspecialchars($row['serie']) ?></td>
                                        
                                        <td>
                                            <input type="date" name="fecha_inicial" class="form-control form-control-sm mb-1" required>
                                            <input type="date" name="fecha_final" class="form-control form-control-sm" required>
                                        </td>

                                        <td>
                                            <input type="number" name="copias_bn" class="form-control form-control-sm input-number-icv mb-1" value="0" placeholder="Cop">
                                            <input type="number" name="impresiones_bn" class="form-control form-control-sm input-number-icv" value="0" placeholder="Imp">
                                        </td>

                                        <td>
                                            <input type="number" name="copias_color" class="form-control form-control-sm input-number-icv mb-1" value="0" placeholder="Cop">
                                            <input type="number" name="impresiones_color" class="form-control form-control-sm input-number-icv" value="0" placeholder="Imp">
                                        </td>

                                        <td>
                                            <button type="submit" class="btn btn-sm btn-danger shadow-sm fw-bold">💾 GUARDAR</button>
                                        </td>
                                    </tr>
                                </form>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="p-4 text-muted">No hay equipos registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3 text-center">
                <a href="../../frontend/pages/empresa.php?empresa_id=<?= $empresa_id ?>" class="btn btn-secondary">⬅ Volver al Panel</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>