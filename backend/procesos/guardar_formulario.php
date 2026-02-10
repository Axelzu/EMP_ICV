<?php
require '../config/db.php';
require '../excel.php/generar_excel.php';

// 1️⃣ Guardar datos en BD (ejemplo)
$nombre = $_POST['nombre'];
$correo = $_POST['correo'];

mysqli_query($conn, "INSERT INTO registros VALUES (NULL,'$nombre','$correo')");

// 2️⃣ Obtener datos para el Excel
$result = mysqli_query($conn, "SELECT nombre, correo FROM registros");
$datos = mysqli_fetch_all($result, MYSQLI_ASSOC);

// 3️⃣ Generar Excel
$archivoExcel = generarExcel($datos);

// 4️⃣ Conexión FTP
$ftp_server = "192.168.100.98";
$ftp_user   = "alex";
$ftp_pass   = "alex3102255";

$conn_ftp = ftp_connect($ftp_server);
if (!$conn_ftp) die("Error conectando al FTP");

if (!ftp_login($conn_ftp, $ftp_user, $ftp_pass)) {
    die("Error login FTP");
}

ftp_pasv($conn_ftp, true);

// 5️⃣ Subir archivo
$nombreRemoto = "/webpage/" . basename($archivoExcel);

if (ftp_put($conn_ftp, $nombreRemoto, $archivoExcel, FTP_BINARY)) {
    echo "Excel enviado correctamente";
} else {
    echo "Error al enviar el Excel";
}

ftp_close($conn_ftp);
