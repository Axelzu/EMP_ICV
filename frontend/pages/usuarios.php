<?php
require "../../backend/config/db.php";
require "../../backend/auth/guard.php";

// 🛡️ SOLO ADMINS ENTRAN AQUÍ
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    die("Acceso denegado.");
}

$usuarios = $conn->query("SELECT id, nombre, email, rol FROM users ORDER BY nombre ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #0A2540 !important; }
        .user-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .badge-admin { background-color: #0A2540; color: white; }
        .badge-tecnico { background-color: #6c757d; color: white; }
        .avatar-circle { width: 45px; height: 45px; background: #e9ecef; display: flex; align-items: center; justify-content: center; border-radius: 50%; color: #0A2540; font-weight: bold; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark px-4 shadow-sm">
    <a class="navbar-brand d-flex align-items-center" href="inicio.php">
        <i class="bi bi-arrow-left-circle me-2"></i> Volver al Inicio
    </a>
</nav>

<div class="container mt-4 mb-5">
    
    <?php if(isset($_GET['mensaje'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Rol actualizado con éxito.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary fw-bold">👥 Gestión de Usuarios</h3>
    </div>

    <div class="user-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol Actual</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $usuarios->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-3">
                                    <?= strtoupper(substr($user['nombre'], 0, 1)) ?>
                                </div>
                                <div>
                                    <span class="fw-bold d-block"><?= htmlspecialchars($user['nombre']) ?></span>
                                    <small class="text-muted">ID: #<?= $user['id'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="badge <?= $user['rol'] === 'admin' ? 'badge-admin' : 'badge-tecnico' ?> px-3 py-2">
                                <?= strtoupper($user['rol'] ?? 'tecnico') ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <?php if($user['rol'] === 'admin'): ?>
                                <a href="../../backend/admin/actualizar_rol.php?id=<?= $user['id'] ?>&rol=tecnico" 
                                   class="btn btn-outline-secondary btn-sm"
                                   onclick="return confirm('¿Bajar a Técnico?')">
                                    <i class="bi bi-person-down"></i> Quitar Admin
                                </a>
                            <?php else: ?>
                                <a href="../../backend/admin/actualizar_rol.php?id=<?= $user['id'] ?>&rol=admin" 
                                   class="btn btn-primary btn-sm"
                                   onclick="return confirm('¿Hacer Administrador?')">
                                    <i class="bi bi-person-up"></i> Hacer Admin
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>