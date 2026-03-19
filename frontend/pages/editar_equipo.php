<?php
require '../../backend/auth/guard.php';
require '../../backend/config/db.php';
require_once '../../backend/security/functions.php';

$serie = $_GET['serie'] ?? null;
$empresa_id = $_GET['empresa_id'] ?? null;

if (!$serie || !$empresa_id) {
    die("Datos insuficientes para editar.");
}

// Buscamos los datos actuales del equipo en la tabla maestra
$stmt = $conn->prepare("SELECT * FROM equipos WHERE serie = ? AND empresa_id = ?");
$stmt->bind_param("si", $serie, $empresa_id);
$stmt->execute();
$equipo = $stmt->get_result()->fetch_assoc();

if (!$equipo) {
    die("El equipo no existe en el sistema.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Equipo | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        #particles-js {
            position: fixed; width: 100%; height: 100%; z-index: -1; top: 0; left: 0;
            background-color: #f8f9fa;
        }
        .form-card-edit {
            max-width: 500px;
            width: 100%;
            margin: auto;
            border-radius: 15px;
            border-top: 5px solid #ffc107; /* Color amarillo para edición */
            background: rgba(255, 255, 255, 0.95);
        }
        body { min-height: 100vh; display: flex; align-items: center; }
    </style>
</head>
<body>

<div id="particles-js"></div>

<div class="container">
    <div class="card shadow form-card-edit">
        <div class="card-body p-4">
            <h4 class="text-center fw-bold mb-4" style="color: #0A2540;">✏️ EDITAR EQUIPO</h4>
            <p class="text-center text-muted small">Modifique los datos técnicos del equipo</p>
            <hr>

            <form action="../../backend/equipos/update_equipo.php" method="POST">
                <input type="hidden" name="empresa_id" value="<?= $empresa_id ?>">
                <input type="hidden" name="serie_original" value="<?= $equipo['serie'] ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">Dependencia / Departamento</label>
                    <input type="text" name="dependencia" class="form-control" 
                           value="<?= htmlspecialchars($equipo['dependencia']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Marca y Modelo</label>
                    <input type="text" name="marca_modelo" class="form-control" 
                           value="<?= htmlspecialchars($equipo['marca_modelo']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Número de Serie (Nuevo)</label>
                    <input type="text" name="serie_nueva" class="form-control" 
                           value="<?= htmlspecialchars($equipo['serie']) ?>" required>
                    <small class="text-muted">Si cambia la serie, se actualizará en los cuadros rojos.</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Tipo de Impresión</label>
                    <select name="tipo_color" class="form-select" style="border: 1px solid #00bcd4;" required>
                        <option value="Blanco y Negro" <?= ($equipo['tipo_color'] == 'Blanco y Negro') ? 'selected' : '' ?>>⚪ Blanco y Negro</option>
                        <option value="Color" <?= ($equipo['tipo_color'] == 'Color') ? 'selected' : '' ?>>🌈 A Color</option>
                    </select>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="empresa.php?empresa_id=<?= $empresa_id ?>" class="btn btn-secondary px-4">Cancelar</a>
                    <button type="submit" class="btn btn-warning fw-bold shadow-sm px-4">Actualizar Datos</button>
                </div>
            </form>
        </div>
    </div>
</div>

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