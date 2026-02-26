<?php
require "../../backend/config/db.php";
require "../../backend/auth/guard.php";

// 🛡️ SEGURIDAD PERSONALIZADA: 
// Permitir el paso si el nombre de usuario es 'Admin'
if (!isset($_SESSION['user_name']) || $_SESSION['user_name'] !== 'Admin') {
    die("Acceso denegado: Solo el administrador (" . $_SESSION['user_name'] . ") no tiene permisos. Contacte al soporte.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Auditoría | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .table-container { 
            background: white; 
            border-radius: 15px; 
            padding: 30px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            margin-top: 30px;
        }
        .navbar { background-color: #0A2540 !important; }
        .badge-update { background-color: #ffc107; color: black; font-size: 0.85em; }
        .badge-create { background-color: #28a745; font-size: 0.85em; }
        .table thead { background-color: #0A2540; color: white; }
        .user-name { color: #007bff; font-weight: bold; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark px-4 shadow-sm">
    <a class="navbar-brand d-flex align-items-center" href="inicio.php">
        <img src="../assets/images/foto.png" width="35" class="me-2" alt="ICV">
        ICV - Panel de Control
    </a>
    <div class="navbar-text text-white d-none d-md-block">
        Bienvenido, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong> (Administrador)
    </div>
</nav>

<div class="container mb-5">
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-primary m-0">📜 Historial de Actividades</h3>
            <a href="inicio.php" class="btn btn-outline-secondary btn-sm">⬅ Volver al Inicio</a>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Detalle del Movimiento</th>
                        <th>Dirección IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // 🔍 CONSULTA CON JOIN: Traemos el nombre del usuario desde la tabla usuarios
                    $sql = "SELECT a.*, u.nombre 
                            FROM auditoria a 
                            INNER JOIN usuarios u ON a.user_id = u.id 
                            ORDER BY a.fecha DESC LIMIT 100";
                    
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                            // Color del badge según la acción
                            $badgeClass = 'bg-primary';
                            if (strpos($row['accion'], 'ACTUALIZAR') !== false) $badgeClass = 'badge-update';
                            if (strpos($row['accion'], 'REGISTRO') !== false) $badgeClass = 'badge-create text-white';
                            if (strpos($row['accion'], 'ELIMINAR') !== false) $badgeClass = 'bg-danger';
                    ?>
                    <tr>
                        <td class="text-nowrap">
                            <span class="text-muted"><?= date('d/m/Y', strtotime($row['fecha'])) ?></span><br>
                            <strong><?= date('H:i:s', strtotime($row['fecha'])) ?></strong>
                        </td>
                        <td><span class="user-name"><?= htmlspecialchars($row['nombre']) ?></span></td>
                        <td><span class="badge <?= $badgeClass ?> p-2 px-3"><?= $row['accion'] ?></span></td>
                        <td style="max-width: 300px;"><small class="text-dark"><?= htmlspecialchars($row['detalle']) ?></small></td>
                        <td><code class="bg-light p-1 rounded"><?= $row['ip'] ?></code></td>
                    </tr>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No hay movimientos registrados todavía.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>