<?php
session_start();
require "../../backend/auth/guard.php";
require "../../backend/config/db.php";

// Obtenemos el nombre y el rol desde la sesión
$usuario = $_SESSION['user_name'];
$rol_real = $_SESSION['rol'] ?? 'tecnico'; // Lee el rol que configuramos en login_process.php

$empresas = $conn->query("SELECT * FROM empresas");

// Arreglo con logos por ID de empresa
$logos = [
    1 => 'h_vozandes.png',
    2 => 'c_internacional.png',
    3 => 'c_cruzmedic.png',
    4 => 'empresa4.png',
    5 => 'FGDE.png',
    6 => 'SD.png',
    7 => 'A_SJDD.png',
    8 => 'LA_Y.png',
    9 => 'HSJDD.png',
    10 => 'F_VOZANDES.png'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICV - Inicio</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/custom.css">

    <style>
        .search-container {
            max-width: 600px;
            margin: 0 auto 30px auto;
        }
        
        .search-wrapper {
            display: flex;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            overflow: hidden;
            background-color: white;
            transition: all 0.3s ease;
        }

        .search-wrapper:focus-within {
            border-color: #0A2540;
            box-shadow: 0 0 10px rgba(10, 37, 64, 0.1);
        }

        .search-icon-box {
            background-color: white;
            border: none;
            padding-left: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-input {
            border: none !important;
            border-radius: 0 50px 50px 0 !important;
            padding: 12px 20px 12px 10px;
            height: 50px;
            box-shadow: none !important;
        }

        .tarjeta-empresa {
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .no-results-msg {
            display: none;
            padding: 40px;
            text-align: center;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<div id="particles-js"></div>

<nav class="navbar navbar-dark px-4 shadow-sm" style="background-color: #0A2540;">
    <span class="navbar-brand fw-bold">ICV</span>
    
    <div class="d-flex gap-2">
        <?php if ($rol_real === 'admin'): ?>
            <a href="usuarios.php" class="btn btn-primary btn-sm fw-bold shadow-sm">
                <i class="bi bi-people-fill"></i> Usuarios
            </a>
        <?php endif; ?>

        <?php if ($rol_real === 'admin' || $rol_real === 'supervisor'): ?>
            <a href="auditoria.php" class="btn btn-warning btn-sm fw-bold shadow-sm text-dark">
                <i class="bi bi-eye-fill"></i> Monitoreo
            </a>
        <?php endif; ?>

        <a href="../../backend/auth/logout.php" class="btn btn-danger btn-sm shadow-sm">
            <i class="bi bi-box-arrow-right"></i> Cerrar sesión
        </a>
    </div>
</nav>

<section class="container my-4">
    <div class="card shadow welcome-card border-0 p-3" style="border-radius: 15px;">
        <div class="d-flex align-items-center">
            <img src="../assets/images/user.png" width="80" class="me-3 rounded-circle border border-2 border-primary">
            <div>
                <h5 class="mb-0 text-muted small">Bienvenido</h5>
                <strong class="text-primary fs-5 d-block"><?= htmlspecialchars($usuario) ?></strong>
                
                <?php if ($rol_real === 'admin'): ?>
                    <span class="badge bg-primary mt-1">Administrador</span>
                <?php elseif ($rol_real === 'supervisor'): ?>
                    <span class="badge bg-info text-dark mt-1">Supervisor</span>
                <?php else: ?>
                    <span class="badge bg-secondary mt-1">Técnico</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="container mb-4">
    <div class="search-container">
        <div class="search-wrapper shadow-sm">
            <div class="search-icon-box">
                <i class="bi bi-search text-primary"></i>
            </div>
            <input type="text" id="inputBuscador" class="form-control search-input" placeholder="Escribe el nombre del cliente...">
        </div>
    </div>
</section>

<section class="container flex-grow-1 mb-5">
    <h4 class="mb-4 text-primary fw-bold"><i class="bi bi-building me-2"></i>Nuestros Clientes</h4>

    <div id="mensajeVacio" class="no-results-msg shadow-sm">
        <i class="bi bi-search-heart fs-1 text-muted"></i>
        <p class="mt-3 fs-5 text-muted">No encontramos ningun cliente que coincida con tu búsqueda.</p>
        <button onclick="limpiarBuscador()" class="btn btn-outline-primary btn-sm">Ver todos los clientes</button>
    </div>

    <div class="row g-3" id="listaEmpresas">
        <?php while ($empresa = $empresas->fetch_assoc()): ?>
            <?php 
            $logo = $logos[$empresa['id']] ?? 'empresa.png';
            ?>
            <div class="col-md-6 col-lg-4 tarjeta-empresa">
                <a href="empresa.php?empresa_id=<?= $empresa['id'] ?>" class="text-decoration-none">
                    <div class="card shadow empresa-card h-100 border-0" style="border-radius: 15px;">
                        <div class="card-body d-flex align-items-center">
                            <img src="../assets/images/<?= $logo ?>" width="60" height="60" class="me-3 rounded-circle object-fit-cover shadow-sm">
                            <h6 class="mb-0 text-dark fw-bold nombre-empresa">
                                <?= strtoupper(htmlspecialchars($empresa['nombre'])) ?>
                            </h6>
                        </div>
                    </div>
                </a>
            </div>
        <?php endwhile; ?>
    </div>
</section>

<footer class="text-white text-center py-3 mt-auto" style="background-color: #0A2540;">
    <small>© 2026 ICV - Todos los derechos reservados</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/particles.js"></script>

<script>
document.getElementById('inputBuscador').addEventListener('input', function(e) {
    let busqueda = e.target.value.toLowerCase().trim();
    let tarjetas = document.querySelectorAll('.tarjeta-empresa');
    let contadorResultados = 0;

    tarjetas.forEach(tarjeta => {
        let nombre = tarjeta.querySelector('.nombre-empresa').textContent.toLowerCase();
        
        if (nombre.includes(busqueda)) {
            tarjeta.style.display = 'block';
            tarjeta.style.opacity = '1';
            contadorResultados++;
        } else {
            tarjeta.style.display = 'none';
            tarjeta.style.opacity = '0';
        }
    });

    const mensaje = document.getElementById('mensajeVacio');
    mensaje.style.display = (contadorResultados === 0) ? 'block' : 'none';
});

function limpiarBuscador() {
    let input = document.getElementById('inputBuscador');
    input.value = '';
    input.dispatchEvent(new Event('input'));
    input.focus();
}

particlesJS("particles-js", {
    particles: {
        number: { value: 60, density: { enable: true, value_area: 800 } },
        color: { value: "#0A2540" },
        shape: { type: "circle" },
        opacity: { value: 0.3 },
        size: { value: 3 },
        line_linked: {
            enable: true,
            distance: 150,
            color: "#0A2540",
            opacity: 0.2,
            width: 1
        },
        move: { enable: true, speed: 2 }
    },
    retina_detect: true
});
</script>

</body>
</html>