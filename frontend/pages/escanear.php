<?php
session_start();
require "../../backend/auth/guard.php";
require "../../backend/security/functions.php";
$token = generarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escanear QR | ICV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark px-3" style="background-color:#0A2540">
    <a class="navbar-brand fw-bold" href="inicio.php">ICV</a>
    <a href="mantenimiento.php" class="btn btn-outline-light btn-sm">⬅ Mantenimientos</a>
</nav>
<main class="container my-3" style="max-width:560px">
    <h4 class="text-primary fw-bold text-center"><i class="bi bi-qr-code-scan"></i> Confirmar mantenimiento</h4>
    <p class="text-center text-muted small">Apunte la cámara al código QR pegado en el equipo. Solo se confirma si el sistema agendó un mantenimiento preventivo para ese equipo.</p>

    <div id="resultado" class="alert d-none" role="alert"></div>
    <div id="lector" class="rounded overflow-hidden shadow-sm bg-white"></div>
    <div id="aviso-camara" class="alert alert-warning small mt-3 d-none">
        No se pudo activar la cámara. Debe permitir el acceso y abrir el sitio por HTTPS. Mientras tanto puede ingresar el código manualmente.
    </div>

    <div class="card border-0 shadow-sm mt-3"><div class="card-body">
        <label class="small fw-bold">Código manual (plan B)</label>
        <div class="input-group">
            <input id="manual" class="form-control" placeholder="ICV:xxxxxxxx… o token de 32 caracteres" autocomplete="off">
            <button class="btn btn-primary" id="btn-manual">Confirmar</button>
        </div>
    </div></div>
</main>
<script>
const CSRF = <?= json_encode($token) ?>;
const caja = document.getElementById('resultado');
let procesando = false;

function mostrar(ok, texto) {
    caja.className = 'alert ' + (ok ? 'alert-success' : 'alert-danger');
    caja.textContent = texto;
}

async function enviar(codigo) {
    if (procesando) return;
    procesando = true;
    try {
        const body = new URLSearchParams({ qr: codigo, csrf_token: CSRF });
        const r = await fetch('../../backend/mantenimiento/confirmar.php', { method: 'POST', body, credentials: 'same-origin' });
        const j = await r.json();
        mostrar(j.ok, j.mensaje);
        if (j.ok && navigator.vibrate) navigator.vibrate(200);
    } catch (e) {
        mostrar(false, 'Error de conexión. Reintente cuando tenga señal.');
    }
    setTimeout(() => { procesando = false; }, 3000);
}

document.getElementById('btn-manual').addEventListener('click', () => {
    const v = document.getElementById('manual').value.trim();
    if (v) enviar(v);
});

const lector = new Html5Qrcode('lector');
lector.start({ facingMode: 'environment' }, { fps: 10, qrbox: 240 }, (texto) => enviar(texto), () => {})
      .catch(() => document.getElementById('aviso-camara').classList.remove('d-none'));
</script>
</body>
</html>
