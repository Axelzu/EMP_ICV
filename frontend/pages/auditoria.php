<?php
require "../../backend/config/db.php";
require "../../backend/auth/guard.php";

// 🛡️ SEGURIDAD POR ROL: 
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
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .table-container { 
            background: white; 
            border-radius: 15px; 
            padding: 30px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            margin-top: 20px;
        }
        .navbar { background-color: #0A2540 !important; }
        .nav-pills .nav-link.active {
            background-color: #0A2540;
            color: white;
        }
        .nav-link {
            color: #0A2540;
            font-weight: 600;
        }
        .badge-update { background-color: #ffc107; color: black; }
        .badge-create { background-color: #28a745; color: white; }
        .badge-login { background-color: #0dcaf0; color: black; }
        .user-name { color: #007bff; font-weight: bold; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark px-4 shadow-sm">
    <a class="navbar-brand d-flex align-items-center" href="inicio.php">
        <i class="bi bi-arrow-left-circle me-2"></i> ICV - Monitoreo
    </a>
    <div class="navbar-text text-white d-none d-md-block">
        Admin: <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary fw-bold">📜 Historial del Sistema</h3>
        <a href="inicio.php" class="btn btn-outline-secondary btn-sm">Volver al Inicio</a>
    </div>

    <ul class="nav nav-pills nav-fill gap-2 p-1 small bg-white border rounded-5 shadow-sm" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-5" id="tab-login" data-bs-toggle="pill" data-bs-target="#content-login" type="button" role="tab">
                <i class="bi bi-person-lock"></i> Accesos (Login)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-5" id="tab-forms" data-bs-toggle="pill" data-bs-target="#content-forms" type="button" role="tab">
                <i class="bi bi-file-earmark-diff"></i> Movimientos de Formularios
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="pills-tabContent">
        
        <div class="tab-pane fade show active" id="content-login" role="tabpanel">
            <div class="table-container">
                <h5 class="text-info mb-4"><i class="bi bi-clock-history"></i> Registro de Entradas</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
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
                                <td><span class="badge badge-login">INICIO DE SESIÓN</span></td>
                                <td><code class="text-muted"><?= $row['ip'] ?></code></td>
                            </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" class="text-center py-4">No hay registros de login.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="content-forms" role="tabpanel">
            <div class="table-container">
                <h5 class="text-success mb-4"><i class="bi bi-pencil-square"></i> Actividad en Formularios</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sqlForms = "SELECT a.*, u.nombre FROM auditoria a LEFT JOIN users u ON a.user_id = u.id WHERE a.accion != 'LOGIN' ORDER BY a.fecha DESC LIMIT 50";
                            $resForms = $conn->query($sqlForms);
                            if ($resForms && $resForms->num_rows > 0):
                                while ($row = $resForms->fetch_assoc()):
                                    $badge = 'bg-primary';
                                    if (strpos($row['accion'], 'REGISTRO') !== false) $badge = 'badge-create';
                                    if (strpos($row['accion'], 'ACTUALIZAR') !== false) $badge = 'badge-update';
                                    if (strpos($row['accion'], 'ELIMINAR') !== false) $badge = 'bg-danger';
                            ?>
                            <tr>
                                <td><?= date('d/m/Y H:i:s', strtotime($row['fecha'])) ?></td>
                                <td><span class="user-name"><?= htmlspecialchars($row['nombre'] ?? "ID: ".$row['user_id']) ?></span></td>
                                <td><span class="badge <?= $badge ?>"><?= $row['accion'] ?></span></td>
                                <td><small><?= htmlspecialchars($row['detalle']) ?></small></td>
                            </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" class="text-center py-4">No hay movimientos de formularios.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div> </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>