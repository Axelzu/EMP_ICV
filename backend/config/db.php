<?php
$host = "localhost";
$user = "root";
$pass = ""; // En XAMPP normalmente está vacío
$db   = "icv_empresa";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
