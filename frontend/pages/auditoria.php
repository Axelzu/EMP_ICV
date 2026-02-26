<?php
require "../../backend/config/db.php";
require "../../backend/auth/guard.php";

// 🛡️ SEGURIDAD: Solo permitir si el usuario es administrador
// (Asumiendo que guardas el rol en la sesión)
if ($_SESSION['user_rol'] !== 'admin') {
    die("Acceso denegado: No tienes permisos para ver esta sección.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Auditoría | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .table-container { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .badge-update { background-color: #ffc107; color: black; }
        .badge-create { background-color: #28a745; }
        .badge-delete { background-color: #dc3545; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
    <a class="navbar-brand" href="inicio.php">ICV - Panel Administrativo</a>
    <span class="navbar-text text-white">Historial de Movimientos</span>
</nav>

<div class="container mt-5">
    <div class="table-container">
        <h3 class="mb-4 text-primary">Log de Actividades del Sistema</h3>
        
        <table class="table table-hover">
            <thead class="table-dark">
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

                while ($row = $result->fetch_assoc()):
                    // Color del badge según la acción
                    $badgeClass = 'bg-primary';
                    if (strpos($row['accion'], 'ACTUALIZAR') !== false) $badgeClass = 'badge-update';
                    if (strpos($row['accion'], 'REGISTRO') !== false) $badgeClass = 'bg-success';
                ?>
                <tr>
                    <td><small><?= date('d/m/Y H:i', strtotime($row['fecha'])) ?></small></td>
                    <td><strong><?= htmlspecialchars($row['nombre']) ?></strong></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= $row['accion'] ?></span></td>
                    <td><?= htmlspecialchars($row['detalle']) ?></td>
                    <td><code class="text-muted"><?= $row['ip'] ?></code></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>