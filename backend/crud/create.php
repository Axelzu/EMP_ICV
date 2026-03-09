<?php
require '../config/db.php';
require '../auth/guard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/security/functions.php';

$empresa_id = $_GET['empresa_id'] ?? null;
if (!$empresa_id) {
    die("Error: Empresa no seleccionada");
}

// 1. Buscamos los equipos registrados para que el técnico elija UNO
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
    <title>Registrar Lectura | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .form-card { max-width: 600px; margin: 50px auto; border-radius: 15px; border-top: 5px solid #dc3545; }
        .static-info { background-color: #e9ecef; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 5px solid #0A2540; }
    </style>
</head>
<body>

<div class="container">
    <div class="card shadow form-card">
        <div class="card-body p-4">
            <h4 class="text-center text-primary mb-4">➕ NUEVA LECTURA</h4>

            <form action="store.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF(); ?>">
                <input type="hidden" name="empresa_id" value="<?= htmlspecialchars($empresa_id) ?>">

                <div class="mb-4">
                    <label class="form-label fw-bold">Seleccione el Equipo</label>
                    <select id="selector_equipo" class="form-select form-select-lg border-danger" onchange="actualizarDatos()" required>
                        <option value="">-- Seleccione una máquina --</option>
                        <?php while ($row = $equipos->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['serie']) ?>" 
                                    data-dep="<?= htmlspecialchars($row['dependencia']) ?>" 
                                    data-mod="<?= htmlspecialchars($row['marca_modelo']) ?>">
                                <?= htmlspecialchars($row['dependencia']) ?> - <?= htmlspecialchars($row['serie']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="static-info">
                    <p class="mb-1 small text-muted">Información del Equipo:</p>
                    <div id="display_info"><strong>Seleccione un equipo para ver los detalles...</strong></div>
                    
                    <input type="hidden" name="dependencia" id="hidden_dep">
                    <input type="hidden" name="marca_modelo" id="hidden_mod">
                    <input type="hidden" name="serie" id="hidden_ser">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="small fw-bold">Fecha Inicial</label>
                        <input type="date" name="fecha_inicial" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold">Fecha Final</label>
                        <input type="date" name="fecha_final" class="form-control" required>
                    </div>
                </div>

                <hr>
                <h6 class="fw-bold text-danger">🔢 CONTADORES</h6>
                <div class="row g-2">
                    <div class="col-6 mb-2">
                        <label class="small">Copias B/N</label>
                        <input type="number" name="copias_bn" class="form-control" value="0">
                    </div>
                    <div class="col-6 mb-2">
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

                <div class="d-flex justify-content-between mt-4">
                    <a href="../../frontend/pages/empresa.php?empresa_id=<?= $empresa_id ?>" class="btn btn-secondary">⬅ Volver</a>
                    <button type="submit" class="btn btn-danger px-4 shadow">💾 GUARDAR LECTURA</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function actualizarDatos() {
    const select = document.getElementById('selector_equipo');
    const option = select.options[select.selectedIndex];
    
    if (option.value !== "") {
        const dep = option.getAttribute('data-dep');
        const mod = option.getAttribute('data-mod');
        const ser = option.value;

        // Mostrar al usuario
        document.getElementById('display_info').innerHTML = `
            <b>Depto:</b> ${dep} <br>
            <b>Modelo:</b> ${mod} <br>
            <b>Serie:</b> ${ser}
        `;

        // Llenar los campos ocultos para el store.php
        document.getElementById('hidden_dep').value = dep;
        document.getElementById('hidden_mod').value = mod;
        document.getElementById('hidden_ser').value = ser;
    } else {
        document.getElementById('display_info').innerHTML = "Seleccione un equipo...";
    }
}
</script>

</body>
</html>