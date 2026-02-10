<?php
$host = "localhost";
$user = "icvcom_admin_icv";
$pass = "icv-vc.2117856@";
$db   = "icvcom_icv_empresa";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión");
}
