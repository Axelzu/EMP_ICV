<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICV - Inicio</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="frontend/assets/css/custom.css">

    <style>
        /* Ajuste rápido para que las flechas del carrusel se vean sobre fondo blanco */
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            filter: invert(1) grayscale(100) brightness(0.5);
        }
    </style>
</head>
<body>

<div id="particles-js"></div>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
    <a class="navbar-brand d-flex align-items-center" href="#">
        <img src="frontend/assets/images/foto.png" width="40" class="me-2" alt="ICV Logo">
        ICV
    </a>

    <div class="ms-auto">
        <a href="frontend/pages/login.php" class="btn btn-info text-white">
            Iniciar Sesión
        </a>
    </div>
</nav>

<section class="container my-5 hero-section">
    <div class="row align-items-center">
        <div class="col-md-6 hero-text">
            <h1 class="fw-bold text-primary">Innovación y Confianza</h1>
            <p class="lead">
                En ICV brindamos soluciones tecnológicas modernas, seguras
                y eficientes para la gestión empresarial.
            </p>
        </div>

        <div class="col-md-6 text-center">
            <div class="hero-card shadow rounded">
                <div id="carouselICV" 
                     class="carousel slide carousel-fade" 
                     data-bs-ride="carousel"
                     data-bs-interval="5000">

                    <div class="carousel-indicators">
                        <button type="button" data-bs-target="#carouselICV" data-bs-slide-to="0" class="active"></button>
                        <button type="button" data-bs-target="#carouselICV" data-bs-slide-to="1"></button>
                        <button type="button" data-bs-target="#carouselICV" data-bs-slide-to="2"></button>
                    </div>

                    <div class="carousel-inner rounded">

                        <div class="carousel-item active">
                            <img src="frontend/assets/images/foto.png" 
                                 class="carousel-img"
                                 alt="Imagen 1">
                        </div>

                        <div class="carousel-item">
                            <img src="frontend/assets/images/foto2.png" 
                                 class="carousel-img"
                                 alt="Imagen 2">
                        </div>

                        <div class="carousel-item">
                            <img src="frontend/assets/images/foto3.png" 
                                 class="carousel-img"
                                 alt="Imagen 3">
                        </div>

                    </div>

                    <button class="carousel-control-prev" 
                            type="button" 
                            data-bs-target="#carouselICV" 
                            data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>

                    <button class="carousel-control-next" 
                            type="button" 
                            data-bs-target="#carouselICV" 
                            data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Siguiente</span>
                    </button>

                </div> </div> </div>
    </div>
</section>

<footer class="bg-dark text-white text-center py-4">
    <p class="mb-1"><strong>ICV S.A.</strong></p>
    <p class="mb-1">Soluciones Tecnológicas</p>
    <p class="mb-1">Email: contacto@icv.com</p>
    <p class="mb-1">Tel: +593 99 999 9999</p>
    <small>© 2026 ICV - Todos los derechos reservados</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/particles.js"></script>

<script>
// Configuración Particles.js
particlesJS("particles-js", {
  "particles": {
    "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
    "color": { "value": "#0A2540" },
    "shape": { "type": "circle" },
    "opacity": { "value": 0.5 },
    "size": { "value": 3 },
    "line_linked": { "enable": true, "distance": 150, "color": "#0A2540", "opacity": 0.4, "width": 1 },
    "move": { "enable": true, "speed": 3, "direction": "none", "out_mode": "out" }
  },
  "interactivity": {
    "events": {
      "onhover": { "enable": true, "mode": "grab" },
      "onclick": { "enable": true, "mode": "push" }
    }
  },
  "retina_detect": true
});
</script>

</body>
</html>