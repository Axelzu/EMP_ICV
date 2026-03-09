<?php
require '../../backend/auth/guard.php';
require '../../backend/config/db.php';
require_once '../../backend/security/functions.php';

$empresa_id = $_GET['empresa_id'] ?? null;
if (!$empresa_id) {
    die("Error: Empresa no seleccionada.");
}

// Obtener nombre de la empresa para el título
$stmt = $conn->prepare("SELECT nombre FROM empresas WHERE id = ?");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$empresa = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Equipo | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .form-card { max-width: 500px; margin: 50px auto; border-radius: 15px; border-top: 5px solid #0dcaf0; }
    </style>
</head>
<body>

<div class="container">
    <div class="card shadow form-card">
        <div class="card-body p-4">
            <h4 class="text-center text-info fw-bold mb-3">🖥️ REGISTRAR NUEVO EQUIPO</h4>
            <p class="text-center text-muted small">Empresa: <b><?= htmlspecialchars($empresa['nombre']) ?></b></p>
            <hr>

            <form action="../../backend/crud/store_equipo.php" method="POST">
                <input type="hidden" name="empresa_id" value="<?= $empresa_id ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">Dependencia / Departamento</label>
                    <input type="text" name="dependencia" class="form-control" placeholder="Ej: Consulta Externa" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Marca y Modelo</label>
                    <input type="text" name="marca_modelo" class="form-control" placeholder="Ej: Ricoh MP 301" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Número de Serie</label>
                    <input type="text" name="serie" class="form-control" placeholder="Serie única del equipo" required>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="empresa.php?empresa_id=<?= $empresa_id ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-info text-white px-4 shadow-sm">Guardar Equipo</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>