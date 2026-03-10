<?php
require '../config/db.php';
require '../auth/guard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/security/functions.php';

$empresa_id = $_GET['empresa_id'] ?? null;
$serie_url = $_GET['serie'] ?? null; // Recibimos la serie desde el cuadro rojo

if (!$empresa_id) { die("Error: Empresa no seleccionada"); }

$dep_f = ""; $mod_f = ""; $ser_f = "";

// SOLUCIÓN: Buscamos los datos en la tabla maestra 'equipos'
if ($serie_url) {
    $stmt = $conn->prepare("SELECT dependencia, marca_modelo, serie FROM equipos WHERE serie = ? AND empresa_id = ? LIMIT 1");
    $stmt->bind_param("si", $serie_url, $empresa_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    
    if ($res) {
        $dep_f = $res['dependencia'];
        $mod_f = $res['marca_modelo'];
        $ser_f = $res['serie'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Lectura | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .static-input { background-color: #e9ecef !important; font-weight: bold; border: 1px solid #ced4da; }
        .form-card { max-width: 600px; margin: 40px auto; border-radius: 15px; border-top: 5px solid #dc3545; }
    </style>
</head>
<body class="bg-light">
<div class="container">
    <div class="card shadow form-card">
        <div class="card-body p-4">
            <h4 class="text-center mb-4 text-danger fw-bold">➕ REGISTRAR LECTURA</h4>
            
            <form action="store.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF(); ?>">
                <input type="hidden" name="empresa_id" value="<?= htmlspecialchars($empresa_id) ?>">

                <div class="mb-3">
                    <label class="small fw-bold">Dependencia / Departamento</label>
                    <input type="text" name="dependencia" class="form-control static-input" value="<?= htmlspecialchars($dep_f) ?>" readonly required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Marca y Modelo</label>
                    <input type="text" name="marca_modelo" class="form-control static-input" value="<?= htmlspecialchars($mod_f) ?>" readonly required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Número de Serie</label>
                    <input type="text" name="serie" class="form-control static-input" value="<?= htmlspecialchars($ser_f) ?>" readonly required>
                </div>

                <hr class="my-4">
                
                <div class="row g-2 mb-3 text-center">
                    <div class="col-6">
                        <label class="small fw-bold text-secondary">Fecha Inicial</label>
                        <input type="date" name="fecha_inicial" class="form-control border-danger" required>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold text-secondary">Fecha Final</label>
                        <input type="date" name="fecha_final" class="form-control border-danger" required>
                    </div>
                </div>

                <div class="row g-3 mb-4 text-center">
                    <div class="col-6"><label class="small">Copias B/N</label><input type="number" name="copias_bn" class="form-control" value="0" min="0"></div>
                    <div class="col-6"><label class="small">Copias Color</label><input type="number" name="copias_color" class="form-control" value="0" min="0"></div>
                    <div class="col-6"><label class="small">Impresiones B/N</label><input type="number" name="impresiones_bn" class="form-control" value="0" min="0"></div>
                    <div class="col-6"><label class="small">Impresiones Color</label><input type="number" name="impresiones_color" class="form-control" value="0" min="0"></div>
                </div>

                <div class="d-flex justify-content-between pt-2">
                    <a href="../../frontend/pages/empresa.php?empresa_id=<?= $empresa_id ?>" class="btn btn-secondary px-4">Volver</a>
                    <button type="submit" class="btn btn-danger px-4 fw-bold shadow">GUARDAR DATOS</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>