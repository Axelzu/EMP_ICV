<?php
// Credenciales: si existe db.local.php (ignorado por git) se usa ese archivo; si no, los valores de producción.
$host = "localhost";
$user = "icvcom_admin_icv";
$pass = "icv-vc.2117856@";
$db   = "icvcom_icv_empresa";

if (file_exists(__DIR__ . '/db.local.php')) {
    require __DIR__ . '/db.local.php';
}

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión");
}
$conn->set_charset("utf8mb4");
