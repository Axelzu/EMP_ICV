<?php
require "../config/db.php";
require "../config/excel.php";

/* ======================
   1. DATOS DEL FORMULARIO
====================== */
$id         = $_POST['id'] ?? null;
$empresa_id = $_POST['empresa_id'] ?? null;
$marca      = $_POST['marca_impresora'] ?? null;
$serie      = $_POST['numero_serie'] ?? null;
$contador   = $_POST['contador_general'] ?? null;

if (!$id || !$empresa_id) {
    die("Datos incompletos");
}

/* ======================
   2. ACTUALIZAR EN BD
====================== */
$sql = "UPDATE impresoras_formulario
        SET marca_impresora = ?, numero_serie = ?, contador_general = ?
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssii", $marca, $serie, $contador, $id);
$stmt->execute();

/* ======================
   3. CREAR/ACTUALIZAR EXCEL
====================== */
$datos = [[
    'ID Copiadora' => $id,
    'Empresa'      => $empresa_id,
    'Marca'        => $marca,
    'Serie'        => $serie,
    'Contador'     => $contador
]];

$nombreArchivo = "empresa_{$empresa_id}_copiadora_{$id}.xlsx";
$rutaLocal = sys_get_temp_dir() . "/" . $nombreArchivo;

generarExcel($datos, $nombreArchivo);
$archivoGenerado = __DIR__ . '/../../excel_generados/' . $nombreArchivo;

/* ======================
   4. ENVIAR A API INTERMEDIA
====================== */
$api_url = 'https://api-intermedia.com/upload';
$ch = curl_init($api_url);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'file'  => new CURLFile($archivoGenerado),
    'token' => 'TU_API_KEY_SECRETA'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    error_log('Error enviando Excel a API: ' . curl_error($ch));
} else {
    error_log('Excel enviado correctamente: ' . $response);
}

curl_close($ch);

/* ======================
   5. BORRAR TEMPORAL
====================== */
if (file_exists($archivoGenerado)) {
    unlink($archivoGenerado);
}

/* ======================
   6. REDIRECCIONAR
====================== */
header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id");
exit;
