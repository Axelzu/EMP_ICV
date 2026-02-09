<?php
$host = "localhost";
$user = "icvcom_admin_icv";
$pass = "uUuX4iDx,Cr(bELE";
$db   = "icvcom_icv_empresa";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión DB: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
