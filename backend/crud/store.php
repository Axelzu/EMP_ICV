<?php
// Reporte de errores para depuración en cPanel
ini_set('display_errors', 1);
error_reporting(E_ALL);

require "../config/db.php";
require "../config/excel.php";
require "../auth/guard.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/security/functions.php'; 

// 🛡️ Verificar Token CSRF para seguridad
$token = $_POST['csrf_token'] ?? '';
if (!validarTokenCSRF($token)) {
    die("Error de seguridad: Sesión expirada.");
}

// --- CAPTURA DE DATOS DESDE EL FORMULARIO ---
$empresa_id   = filter_var($_POST['empresa_id'] ?? 0, FILTER_VALIDATE_INT);
$dependencia  = isset($_POST['dependencia']) ? sanear($_POST['dependencia']) : '';
$marca_modelo = isset($_POST['marca_modelo']) ? sanear($_POST['marca_modelo']) : '';
$serie        = isset($_POST['serie']) ? sanear($_POST['serie']) : '';
$c_bn         = filter_var($_POST['copias_bn'] ?? 0, FILTER_VALIDATE_INT);
$c_col        = filter_var($_POST['copias_color'] ?? 0, FILTER_VALIDATE_INT);
$i_bn         = filter_var($_POST['impresiones_bn'] ?? 0, FILTER_VALIDATE_INT);
$i_col        = filter_var($_POST['impresiones_color'] ?? 0, FILTER_VALIDATE_INT);

// CAPTURA AUTOMÁTICA DE FECHA Y HORA (Quito, Ecuador)
date_default_timezone_set('America/Guayaquil');
$f_actual = date('Y-m-d H:i:s'); 

// Validación rápida
if (!$empresa_id || empty($marca_modelo) || empty($serie)) {
    die("Error: Faltan datos obligatorios (Empresa, Modelo o Serie).");
}

// 1. Obtener nombre de la empresa para el nombre del archivo Excel
$sql_emp = "SELECT nombre FROM empresas WHERE id = ?"; 
$stmt_emp = $conn->prepare($sql_emp);
$stmt_emp->bind_param("i", $empresa_id);
$stmt_emp->execute();
$res_emp = $stmt_emp->get_result();
$emp_data = $res_emp->fetch_assoc();

$nombreOriginal = $emp_data['nombre'] ?? 'Empresa';
$nombreLimpio = str_replace([' ', '.', ','], '_', $nombreOriginal);
$fechaHoraNombre = date('d_m_y_H_i');

// Generamos el nombre del archivo
$nombreExcel = $nombreLimpio . "_" . $fechaHoraNombre . ".xlsx";

// 2. Guardar en Base de Datos (Usamos f_actual en ambos campos de fecha)
$sql = "INSERT INTO impresoras_formulario 
        (empresa_id, dependencia, marca_modelo, serie, copias_bn, copias_color, impresiones_bn, impresiones_color, contador_fecha_inicial, contador_fecha_final, nombre_archivo) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
// i=int, s=string. f_actual se guarda en las dos columnas de fecha para mantener compatibilidad
$stmt->bind_param("isssiiiisss", $empresa_id, $dependencia, $marca_modelo, $serie, $c_bn, $c_col, $i_bn, $i_col, $f_actual, $f_actual, $nombreExcel);

if ($stmt->execute()) {
    $id_impresora = $conn->insert_id;

    // 3. Registrar en el Log del sistema
    registrarLog($conn, "REGISTRO", "Se creó lectura para $marca_modelo (Serie: $serie) - Empresa ID: $empresa_id");

    // --- CÁLCULO DE TOTALES ---
    $subtotal_bn    = $c_bn + $i_bn;
    $subtotal_color = $c_col + $i_col;
    $total_general  = $subtotal_bn + $subtotal_color;

    // 4. Preparar datos para generar el Excel
    $datos = [[
        'ID'            => $id_impresora, 
        'DEPTO'         => $dependencia,
        'MARCA/MODELO'  => $marca_modelo, 
        'SERIE'         => $serie, 
        'FECHA/HORA'    => $f_actual, // Ahora solo enviamos una fecha
        'COP B/N'       => $c_bn,
        'IMP B/N'       => $i_bn,
        'COP COL'       => $c_col,
        'IMP COL'       => $i_col,
        'TOTAL B/N'     => $subtotal_bn,
        'TOTAL COL'     => $subtotal_color,
        'TOTAL GENERAL' => $total_general
    ]];
    
    // Ruta física en el servidor
    $rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/" . $nombreExcel;

    // Generar el archivo físico
    if (function_exists('generarExcel')) {
        generarExcel($datos, $rutaPublica);
    }

    // Redirección exitosa
    header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id&status=success");
    exit;

} else {
    echo "Error crítico en la base de datos: " . $conn->error;
}