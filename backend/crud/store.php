<?php
// Reporte de errores para ver si algo falla al guardar
ini_set('display_errors', 1);
error_reporting(E_ALL);

require "../config/db.php";
require "../config/excel.php";
require "../auth/guard.php"; // Aseguramos que la sesión esté activa

// Usamos ruta absoluta para la seguridad
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/security/functions.php'; 

// 🛡️ MEDIDA 1: Verificar el Token CSRF
$token = $_POST['csrf_token'] ?? '';
if (!validarTokenCSRF($token)) {
    die("Error de seguridad: Petición no autorizada o sesión expirada.");
}

// 🛡️ MEDIDA 2: Sanear y Validar Entradas
$empresa_id = filter_var($_POST['empresa_id'] ?? 0, FILTER_VALIDATE_INT);
$marca      = isset($_POST['marca_impresora']) ? sanear($_POST['marca_impresora']) : '';
$serie      = isset($_POST['numero_serie']) ? sanear($_POST['numero_serie']) : '';
$contador   = filter_var($_POST['contador_general'] ?? 0, FILTER_VALIDATE_INT);

if (!$empresa_id || empty($marca) || empty($serie)) {
    die("Datos incompletos o inválidos. Por favor regrese y complete el formulario.");
}

// 1. Guardar en Base de Datos
$sql = "INSERT INTO impresoras_formulario (empresa_id, marca_impresora, numero_serie, contador_general) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issi", $empresa_id, $marca, $serie, $contador);

if ($stmt->execute()) {
    $id_impresora = $conn->insert_id;

    // 🛡️ MEDIDA 3: Registrar en Auditoría (Logs)
    // Pasamos $conn para asegurar la conexión a la base de datos
    $detalle_log = "El usuario creó la copiadora: $marca (Serie: $serie) para la empresa ID: $empresa_id";
    registrarLog($conn, "REGISTRO_COPIADORA", $detalle_log);

    // 2. Crear el Excel en la carpeta exports
    $datos = [[
        'ID' => $id_impresora, 
        'Empresa' => $empresa_id, 
        'Marca' => $marca, 
        'Serie' => $serie, 
        'Contador' => $contador
    ]];
    
    $nombreExcel = "empresa_{$empresa_id}_copiadora_{$id_impresora}.xlsx";
    // Asegúrate que la carpeta /exports/ tenga permisos de escritura en cPanel
    $rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/" . $nombreExcel;

    // Generar el archivo
    if (function_exists('generarExcel')) {
        generarExcel($datos, $rutaPublica);
    }

    // 3. Redireccionar al éxito
    header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id&status=success");
    exit;

} else {
    die("Error al guardar en la base de datos: " . $conn->error);
}