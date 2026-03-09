<?php
require '../config/db.php';
require '../auth/guard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/security/functions.php';

$empresa_id = $_GET['empresa_id'] ?? null;
if (!$empresa_id) { die("Empresa no seleccionada"); }

// Buscamos equipos existentes para que el técnico no tenga que escribir
$sql = "SELECT DISTINCT dependencia, marca_modelo, serie FROM impresoras_formulario WHERE empresa_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$equipos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Lectura | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .static-input { background-color: #e9ecef !important; font-weight: bold; }
        .form-card { max-width: 600px; margin: 40px auto; border-radius: 15px; border-top: 5px solid #dc3545; }
    </style>
</head>
<body class="bg-light">

<div class="container">
    <div class="card shadow form-card">
        <div class="card-body p-4">
            <h4 class="text-center mb-4 text-primary">➕ REGISTRAR LECTURA</h4>

            <div class="mb-4">
                <label class="form-label fw-bold text-danger">1. Seleccione la Máquina</label>
                <select id="maquina_select" class="form-select border-danger" onchange="cargarDatos()">
                    <option value="">-- Seleccionar Equipo --</option>
                    <?php while($row = $equipos->fetch_assoc()): ?>
                        <option value="<?= $row['serie'] ?>" data-dep="<?= $row['dependencia'] ?>" data-mod="<?= $row['marca_modelo'] ?>">
                            <?= $row['dependencia'] ?> | <?= $row['serie'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <small class="text-muted">Si no aparece, regístrela manualmente primero.</small>
            </div>

            <form action="store.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF(); ?>">
                <input type="hidden" name="empresa_id" value="<?= $empresa_id ?>">

                <div class="mb-3">
                    <label class="small fw-bold">Dependencia / Departamento</label>
                    <input type="text" name="dependencia" id="f_dep" class="form-control static-input" readonly required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Marca y Modelo</label>
                    <input type="text" name="marca_modelo" id="f_mod" class="form-control static-input" readonly required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Número de Serie</label>
                    <input type="text" name="serie" id="f_ser" class="form-control static-input" readonly required>
                </div>

                <hr>
                <div class="row g-2 mb-3 text-center">
                    <div class="col-6">
                        <label class="small fw-bold text-secondary">Fecha Inicial</label>
                        <input type="date" name="fecha_inicial" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold text-secondary">Fecha Final</label>
                        <input type="date" name="fecha_final" class="form-control" required>
                    </div>
                </div>

                <div class="row g-2 mb-4">
                    <div class="col-6">
                        <label class="small">Copias B/N</label>
                        <input type="number" name="copias_bn" class="form-control" value="0">
                    </div>
                    <div class="col-6">
                        <label class="small">Copias Color</label>
                        <input type="number" name="copias_color" class="form-control" value="0">
                    </div>
                    <div class="col-6">
                        <label class="small">Impresiones B/N</label>
                        <input type="number" name="impresiones_bn" class="form-control" value="0">
                    </div>
                    <div class="col-6">
                        <label class="small">Impresiones Color</label>
                        <input type="number" name="impresiones_color" class="form-control" value="0">
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="../../frontend/pages/empresa.php?empresa_id=<?= $empresa_id ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-danger px-4">Guardar Lectura</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function cargarDatos() {
    const select = document.getElementById('maquina_select');
    const option = select.options[select.selectedIndex];
    
    document.getElementById('f_dep').value = option.getAttribute('data-dep') || '';
    document.getElementById('f_mod').value = option.getAttribute('data-mod') || '';
    document.getElementById('f_ser').value = option.value || '';
}
</script>
</body>
</html>