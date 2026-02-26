<?php
require "../../backend/config/db.php";
require "../../backend/auth/guard.php";

// 🛡️ SEGURIDAD POR ROL
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    die("Acceso denegado: Se requieren permisos de administrador.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Monitoreo | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #0A2540 !important; }
        
        .table-container { 
            background: white; 
            border-radius: 15px; 
            padding: 30px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
        }

        /* ✨ EFECTO DE TRANSICIÓN SUAVE */
        .tab-pane {
            transition: all 0.4s ease-in-out;
        }

        .fade {
            opacity: 0;
            transform: translateX(15px);
        }
        .fade.show {
            opacity: 1;
            transform: translateX(0);
        }

        .nav-pills .nav-link {
            color: #0A2540;
            font-weight: 600;
            border: 1px solid #dee2e6;
            margin: 0 5px;
            transition: 0.3s;
        }

        .nav-pills .nav-link.active {
            background-color: #0A2540 !important;
            border-color: #0A2540;
            transform: scale(1.05);
        }

        .user-name { color: #007bff; font-weight: bold; }
        .table thead { background-color: #f8f9fa; }
        code { color: #d63384; font-size: 0.85em; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark px-4 shadow-sm">
    <a class="navbar-brand d-flex align-items-center" href="inicio.php">
        <i class="bi bi-arrow-left-circle me-2"></i> ICV - Monitoreo
    </a>
</nav>

<div class="container mt-4 mb-5">

    <?php if(isset($_GET['mensaje']) && $_GET['mensaje'] == 'limpiado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="transform: none; opacity: 1;">
            <i class="bi bi-check-circle-fill me-2"></i> El historial ha sido vaciado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-primary fw-bold mb-0">📜 Centro de Monitoreo</h3>
            <p class="text-muted small">Rastreo detallado de accesos y cambios en el sistema</p>
        </div>
        
        <button onclick="confirmarLimpieza()" class="btn btn-outline-danger shadow-sm fw-bold">
            <i class="bi bi-trash3-fill me-1"></i> Limpiar Historial
        </button>
    </div>

    <ul class="nav nav-pills justify-content-center mb-4" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4" id="tab-login" data-bs-toggle="pill" data-bs-target="#content-login" type="button" role="tab">
                <i class="bi bi-person-lock me-2"></i> Accesos (Login)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4" id="tab-forms" data-bs-toggle="pill" data-bs-target="#content-forms" type="button" role="tab">
                <i class="bi bi-file-earmark-diff me-2"></i> Movimientos
            </button>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
        
        <div class="tab-pane fade show active" id="content-login" role="tabpanel">
            <div class="table-container shadow-sm">
                <h5 class="text-info mb-4 border-bottom pb-2"><i class="bi bi-clock-history me-2"></i>Registro de Entradas</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Dirección IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sqlLog = "SELECT a.*, u.nombre FROM auditoria a LEFT JOIN users u ON a.user_id = u.id WHERE a.accion = 'LOGIN' ORDER BY a.fecha DESC LIMIT 50";
                            $resLog = $conn->query($sqlLog);
                            if ($resLog && $resLog->num_rows > 0):
                                while ($row = $resLog->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= date('d/m/Y H:i:s', strtotime($row['fecha'])) ?></td>
                                <td><span class="user-name"><?= htmlspecialchars($row['nombre'] ?? "ID: ".$row['user_id']) ?></span></td>
                                <td><span class="badge bg-info text-dark">LOGIN</span></td>
                                <td><code><?= $row['ip'] ?></code></td>
                            </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No hay registros de login.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="content-forms" role="tabpanel">
            <div class="table-container shadow-sm">
                <h5 class="text-success mb-4 border-bottom pb-2"><i class="bi bi-pencil-square me-2"></i>Actividad en Formularios</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Detalle</th>
                                <th>Dirección IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sqlForms = "SELECT a.*, u.nombre FROM auditoria a LEFT JOIN users u ON a.user_id = u.id WHERE a.accion != 'LOGIN' ORDER BY a.fecha DESC LIMIT 50";
                            $resForms = $conn->query($sqlForms);
                            if ($resForms && $resForms->num_rows > 0):
                                while ($row = $resForms->fetch_assoc()):
                                    $badge = 'bg-primary';
                                    if (strpos($row['accion'], 'REGISTRO') !== false) $badge = 'bg-success';
                                    if (strpos($row['accion'], 'ACTUALIZAR') !== false) $badge = 'bg-warning text-dark';
                                    if (strpos($row['accion'], 'ELIMINAR') !== false) $badge = 'bg-danger';
                            ?>
                            <tr>
                                <td class="text-nowrap small"><?= date('d/m/Y H:i:s', strtotime($row['fecha'])) ?></td>
                                <td><span class="user-name"><?= htmlspecialchars($row['nombre'] ?? "ID: ".$row['user_id']) ?></span></td>
                                <td><span class="badge <?= $badge ?>"><?= $row['accion'] ?></span></td>
                                <td style="max-width: 250px;"><small class="text-dark"><?= htmlspecialchars($row['detalle']) ?></small></td>
                                <td><code><?= $row['ip'] ?></code></td>
                            </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No hay movimientos de formularios registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function confirmarLimpieza() {
    if (confirm("¿Estás 100% seguro? Esta acción borrará TODO el historial de auditoría y no se puede recuperar.")) {
        // Redirige al archivo que ejecuta el DELETE
        window.location.href = "../../backend/admin/limpiar_auditoria.php";
    }
}
</script>

</body>
</html>