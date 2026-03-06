<?php
// 1. Forzar errores para ver qué falla si vuelve a pasar (útil en cPanel)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "../config/db.php";
require "../config/excel.php";
require "../security/functions.php"; 

// 🛡️ Verificar Token CSRF para seguridad
if (!isset($_POST['csrf_token']) || !validarTokenCSRF($_POST['csrf_token'])) {
    die("Error de seguridad: Petición no autorizada.");
}

// --- CAPTURA DE LOS NUEVOS DATOS ---
$id           = filter_var($_POST['id'], FILTER_VALIDATE_INT);
$empresa_id   = filter_var($_POST['empresa_id'], FILTER_VALIDATE_INT);
$dependencia  = sanear($_POST['dependencia']);
$marca_modelo = sanear($_POST['marca_modelo']);
$serie        = sanear($_POST['serie']);
$c_bn         = filter_var($_POST['copias_bn'] ?? 0, FILTER_VALIDATE_INT);
$c_col        = filter_var($_POST['copias_color'] ?? 0, FILTER_VALIDATE_INT);
$i_bn         = filter_var($_POST['impresiones_bn'] ?? 0, FILTER_VALIDATE_INT);
$i_col        = filter_var($_POST['impresiones_color'] ?? 0, FILTER_VALIDATE_INT);
$f_ini        = $_POST['fecha_inicial'] ?? '';
$f_fin        = $_POST['fecha_final'] ?? '';

// Validación de seguridad básica
if (!$id || !$empresa_id || !$marca_modelo || !$serie) {
    die("Error: Datos incompletos o inválidos.");
}

// 2. Actualizar Base de Datos (Con nombres exactos de tu tabla)
$sql = "UPDATE impresoras_formulario SET 
        dependencia = ?, 
        marca_modelo = ?, 
        serie = ?, 
        copias_bn = ?, 
        copias_color = ?, 
        impresiones_bn = ?, 
        impresiones_color = ?, 
        contador_fecha_inicial = ?, 
        contador_fecha_final = ? 
        WHERE id = ?";

$stmt = $conn->prepare($sql);
// i=int, s=string. Total: 10 parámetros
$stmt->bind_param("sssiiiissi", $dependencia, $marca_modelo, $serie, $c_bn, $c_col, $i_bn, $i_col, $f_ini, $f_fin, $id);
$stmt->execute();

// Registrar la acción en el Log
registrarLog($conn, "ACTUALIZACIÓN", "El usuario editó la copiadora ID: $id (Empresa: $empresa_id)");

// 3. Obtener el nombre del archivo guardado y el nombre de la empresa para regenerar el Excel
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

// 4. Si por alguna razón el registro no tiene nombre de archivo, creamos uno nuevo
if (empty($nombreExcel)) {
    $nombreLimpio = str_replace([' ', '.', ','], '_', $nombreEmpresaReal);
    $nombreExcel = $nombreLimpio . "_" . date('d_m_y_H_i') . ".xlsx";
    
    $upd_name = $conn->prepare("UPDATE impresoras_formulario SET nombre_archivo = ? WHERE id = ?");
    $upd_name->bind_param("si", $nombreExcel, $id);
    $upd_name->execute();
}

// --- CÁLCULO DE TOTALES PARA EL REPORTE EXCEL ---
$subtotal_bn    = $c_bn + $i_bn;
$subtotal_color = $c_col + $i_col;
$total_general  = $subtotal_bn + $subtotal_color;

// 5. Preparar datos para el Excel con los TOTALES AL FINAL (como en list.php)
$datos = [[
    'ID'            => $id, 
    'DEPTO'         => $dependencia,
    'MARCA/MODELO'  => $marca_modelo, 
    'SERIE'         => $serie, 
    'FECHA INI'     => $f_ini,
    'FECHA FIN'     => $f_fin,
    'COP B/N'       => $c_bn,
    'IMP B/N'       => $i_bn,
    'COP COL'       => $c_col,
    'IMP COL'       => $i_col,
    'TOTAL B/N'     => $subtotal_bn,    // Total de Negro al final
    'TOTAL COL'     => $subtotal_color, // Total de Color al final
    'TOTAL GENERAL' => $total_general   // Suma total al final
]];

// Ruta física en el servidor
$rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/" . $nombreExcel;

// 6. Generar/Sobrescribir el archivo Excel físico
if (function_exists('generarExcel')) {
    generarExcel($datos, $rutaPublica);
}

// Redireccionar a la página de la empresa
header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id");
exit;