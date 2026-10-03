<?php
require "../../backend/config/db.php";
require "../../backend/auth/guard.php";
require "../../backend/security/functions.php";

// 🛡️ SOLO ADMINS ENTRAN AQUÍ
// Usamos 'rol' para ser consistentes con el guard.php y login_process.php corregidos
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: inicio.php?error=acceso_denegado");
    exit;
}

$csrf = generarTokenCSRF();
$usuarios = $conn->query("SELECT id, nombre, email, rol, activo FROM users ORDER BY nombre ASC");
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
        
        /* Colores de Badges por Rol */
        .badge-admin { background-color: #0A2540; color: white; }
        .badge-supervisor { background-color: #0dcaf0; color: #000; }
        .badge-tecnico { background-color: #6c757d; color: white; }
        
        .avatar-circle { width: 45px; height: 45px; background: #e9ecef; display: flex; align-items: center; justify-content: center; border-radius: 50%; color: #0A2540; font-weight: bold; }
        .dropdown-menu { border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
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
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars(['ok' => 'Rol actualizado con éxito.', 'actualizado' => 'Rol actualizado con éxito.'][$_GET['mensaje']] ?? $_GET['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary fw-bold">👥 Gestión de Usuarios</h3>
    </div>

    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="user-card mb-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-person-plus-fill text-primary"></i> Crear usuario</h6>
        <form method="POST" action="../../backend/admin/crear_usuario.php" class="row g-2">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="col-md-3"><input name="nombre" class="form-control" placeholder="Nombre completo" required maxlength="100"></div>
            <div class="col-md-3"><input name="email" type="email" class="form-control" placeholder="Correo" required maxlength="100"></div>
            <div class="col-md-2"><input name="password" type="password" class="form-control" placeholder="Contraseña (mín. 8)" required minlength="8"></div>
            <div class="col-md-2">
                <select name="rol" class="form-select">
                    <option value="tecnico">Técnico</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary">Crear</button></div>
        </form>
    </div>

    <div class="user-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol Actual</th>
                        <th class="text-end">Cambiar Rol</th>
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
                            <?php 
                                $rol = $user['rol'] ?? 'tecnico';
                                $badge_class = 'badge-tecnico';
                                if($rol === 'admin') $badge_class = 'badge-admin';
                                if($rol === 'supervisor') $badge_class = 'badge-supervisor';
                            ?>
                            <span class="badge <?= $badge_class ?> px-3 py-2">
                                <?= strtoupper($rol) ?>
                            </span>
                            <?php if (isset($user['activo']) && (int)$user['activo'] === 0): ?>
                                <span class="badge bg-dark px-3 py-2">INACTIVO</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-shield-lock"></i> Gestionar
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><h6 class="dropdown-header">Asignar nuevo rol:</h6></li>
                                    <li><a class="dropdown-item" href="../../backend/admin/actualizar_rol.php?id=<?= $user['id'] ?>&rol=admin" onclick="return confirm('¿Hacer Administrador?')"><i class="bi bi-person-fill-check text-primary"></i> Administrador</a></li>
                                    <li><a class="dropdown-item" href="../../backend/admin/actualizar_rol.php?id=<?= $user['id'] ?>&rol=supervisor" onclick="return confirm('¿Hacer Supervisor?')"><i class="bi bi-eye-fill text-info"></i> Supervisor</a></li>
                                    <li><a class="dropdown-item" href="../../backend/admin/actualizar_rol.php?id=<?= $user['id'] ?>&rol=tecnico" onclick="return confirm('¿Hacer Técnico?')"><i class="bi bi-person-gear text-secondary"></i> Técnico</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="../../backend/admin/toggle_usuario.php" class="m-0"
                                              onsubmit="return confirm('¿Cambiar el estado de esta cuenta?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                            <button class="dropdown-item">
                                                <?php if ((int)($user['activo'] ?? 1) === 1): ?><i class="bi bi-person-x text-danger"></i> Desactivar cuenta
                                                <?php else: ?><i class="bi bi-person-check text-success"></i> Reactivar cuenta<?php endif; ?>
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
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