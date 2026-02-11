<?php
require "../config/db.php";
require "../config/excel.php"; // Función generarExcel

/* =========================
   1. DATOS DEL FORMULARIO
========================= */
$empresa_id = $_POST['empresa_id'] ?? null;
$marca      = $_POST['marca_impresora'] ?? null;
$serie      = $_POST['numero_serie'] ?? null;
$contador   = $_POST['contador_general'] ?? null;

if (!$empresa_id || !$marca || !$serie || !$contador) {
    die("Datos incompletos");
}

/* =========================
   2. GUARDAR EN BD
========================= */
$sql = "INSERT INTO impresoras_formulario 
        (empresa_id, marca_impresora, numero_serie, contador_general)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("issi", $empresa_id, $marca, $serie, $contador);
$stmt->execute();
$id_impresora = $conn->insert_id;

/* =========================
   3. CREAR EXCEL CON excel.php
========================= */
$datos = [[
    'ID Copiadora' => $id_impresora,
    'Empresa'      => $empresa_id,
    'Marca'        => $marca,
    'Serie'        => $serie,
    'Contador'     => $contador
]];

$nombreArchivo = "empresa_{$empresa_id}_copiadora_{$id_impresora}.xlsx";
$rutaLocal = sys_get_temp_dir() . "/" . $nombreArchivo;

generarExcel($datos, $nombreArchivo); // Devuelve la ruta
$archivoGenerado = __DIR__ . '/../../excel_generados/' . $nombreArchivo; // Ruta completa del excel.php

/* =========================
   4. ENVIAR A API INTERMEDIA
========================= */
$api_url = 'https://api-intermedia.com/upload'; // Cambia por tu URL real
$ch = curl_init($api_url);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'file'  => new CURLFile($archivoGenerado),
    'token' => 'TU_API_KEY_SECRETA' // opcional para seguridad
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    error_log('Error enviando Excel a API: ' . curl_error($ch));
} else {
    error_log('Excel enviado correctamente: ' . $response);
}

curl_close($ch);

/* =========================
   5. BORRAR TEMPORAL
========================= */
if (file_exists($archivoGenerado)) {
    unlink($archivoGenerado);
}

/* =========================
   6. REDIRECCIONAR
========================= */
header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id");
exit;
