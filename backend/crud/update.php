<?php
// 1. Forzar errores para ver qué falla si vuelve a pasar
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "../config/db.php";
require "../config/excel.php";
require "../security/functions.php"; 

if (!isset($_POST['csrf_token']) || !validarTokenCSRF($_POST['csrf_token'])) {
    die("Error de seguridad: Petición no autorizada.");
}

$id         = filter_var($_POST['id'], FILTER_VALIDATE_INT);
$empresa_id = filter_var($_POST['empresa_id'], FILTER_VALIDATE_INT);
$marca      = sanear($_POST['marca_impresora']);
$serie      = sanear($_POST['numero_serie']);
$contador   = filter_var($_POST['contador_general'], FILTER_VALIDATE_INT);

if (!$id || !$empresa_id || !$marca || !$serie || !$contador) {
    die("Datos incompletos o inválidos");
}

// 2. Actualizar Base de Datos
$sql = "UPDATE impresoras_formulario SET marca_impresora = ?, numero_serie = ?, contador_general = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssii", $marca, $serie, $contador, $id);
$stmt->execute();

registrarLog($conn, "ACTUALIZACIÓN", "El usuario editó la copiadora ID: $id (Empresa: $empresa_id)");

// 3. Obtener el nombre del archivo guardado y el nombre de la empresa
$sql_info = "SELECT f.nombre_archivo, e.nombre AS nombre_empresa 
             FROM impresoras_formulario f 
             JOIN empresas e ON f.empresa_id = e.id 
             WHERE f.id = ?";
$stmt_info = $conn->prepare($sql_info);
$stmt_info->bind_param("i", $id);
$stmt_info->execute();
$res_info = $stmt_info->get_result();
$data_info = $res_info->fetch_assoc();

$nombreExcel = $data_info['nombre_archivo'];
$nombreEmpresaReal = $data_info['nombre_empresa'] ?? 'Empresa';

// 4. Si el registro viejo no tenía nombre de archivo, le creamos uno nuevo
if (empty($nombreExcel)) {
    $nombreLimpio = str_replace([' ', '.', ','], '_', $nombreEmpresaReal);
    $nombreExcel = $nombreLimpio . "_" . date('d_m_y_H_i') . ".xlsx";
    
    // Guardamos este nuevo nombre para que la próxima vez sí lo encuentre
    $upd_name = $conn->prepare("UPDATE impresoras_formulario SET nombre_archivo = ? WHERE id = ?");
    $upd_name->bind_param("si", $nombreExcel, $id);
    $upd_name->execute();
}

// 5. Preparar datos para el Excel
$datos = [[
    'ID' => $id, 
    'Empresa' => $nombreEmpresaReal, 
    'Marca' => $marca, 
    'Serie' => $serie, 
    'Contador' => $contador
]];

$rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/" . $nombreExcel;

// 6. Generar/Sobrescribir el archivo
if (function_exists('generarExcel')) {
    generarExcel($datos, $rutaPublica);
}

header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id");
exit;