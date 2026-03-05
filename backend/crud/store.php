<?php
// Reporte de errores para ver qué está pasando exactamente
ini_set('display_errors', 1);
error_reporting(E_ALL);

require "../config/db.php";
require "../config/excel.php";
require "../auth/guard.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/security/functions.php'; 

// 🛡️ Verificar Token CSRF
$token = $_POST['csrf_token'] ?? '';
if (!validarTokenCSRF($token)) {
    die("Error de seguridad: Sesión expirada.");
}

// --- CAPTURA DE LOS NUEVOS DATOS ---
$empresa_id   = filter_var($_POST['empresa_id'] ?? 0, FILTER_VALIDATE_INT);
$dependencia  = isset($_POST['dependencia']) ? sanear($_POST['dependencia']) : '';
$marca_modelo = isset($_POST['marca_modelo']) ? sanear($_POST['marca_modelo']) : '';
$serie        = isset($_POST['serie']) ? sanear($_POST['serie']) : '';
$c_bn         = filter_var($_POST['copias_bn'] ?? 0, FILTER_VALIDATE_INT);
$c_col        = filter_var($_POST['copias_color'] ?? 0, FILTER_VALIDATE_INT);
$i_bn         = filter_var($_POST['impresiones_bn'] ?? 0, FILTER_VALIDATE_INT);
$i_col        = filter_var($_POST['impresiones_color'] ?? 0, FILTER_VALIDATE_INT);
$f_ini        = $_POST['fecha_inicial'] ?? '';
$f_fin        = $_POST['fecha_final'] ?? '';

if (!$empresa_id || empty($marca_modelo) || empty($serie)) {
    die("Datos incompletos.");
}

// 1. Guardar en Base de Datos (Estructura Actualizada)
$sql = "INSERT INTO impresoras_formulario 
        (empresa_id, dependencia, marca_modelo, serie, copias_bn, copias_color, impresiones_bn, impresiones_color, contador_fecha_inicial, contador_fecha_final) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isssiiiiss", $empresa_id, $dependencia, $marca_modelo, $serie, $c_bn, $c_col, $i_bn, $i_col, $f_ini, $f_fin);

if ($stmt->execute()) {
    $id_impresora = $conn->insert_id;

    // 2. Obtener nombre de la empresa para el archivo
    $sql_emp = "SELECT nombre FROM empresas WHERE id = ?"; 
    $stmt_emp = $conn->prepare($sql_emp);
    $stmt_emp->bind_param("i", $empresa_id);
    $stmt_emp->execute();
    $res_emp = $stmt_emp->get_result();
    $emp_data = $res_emp->fetch_assoc();
    
    $nombreOriginal = $emp_data['nombre'] ?? 'Empresa';
    $nombreLimpio = str_replace([' ', '.', ','], '_', $nombreOriginal);
    $fechaHora = date('d_m_y_H_i');

    // Nombre final del archivo solicitado: Clinica_Internacional_12_02_26_14_15
    $nombreExcel = $nombreLimpio . "_" . $fechaHora . ".xlsx";

    // 3. ACTUALIZAR EL NOMBRE DEL ARCHIVO EN LA TABLA
    $upd = $conn->prepare("UPDATE impresoras_formulario SET nombre_archivo = ? WHERE id = ?");
    $upd->bind_param("si", $nombreExcel, $id_impresora);
    $upd->execute();

    // 4. Registrar Log
    registrarLog($conn, "REGISTRO", "Se creó copiadora $marca_modelo para empresa $empresa_id");

    // --- CÁLCULO DE TOTALES PARA EXCEL ---
    $subtotal_bn    = $c_bn + $i_bn;
    $subtotal_color = $c_col + $i_col;
    $total_general  = $subtotal_bn + $subtotal_color;

    // 5. Generar el Excel físico con los nuevos encabezados y totales
    $datos = [[
        'ID' => $id_impresora, 
        'DEPTO' => $dependencia,
        'MARCA/MODELO' => $marca_modelo, 
        'SERIE' => $serie, 
        'FECHA INI' => $f_ini,
        'FECHA FIN' => $f_fin,
        'COP B/N' => $c_bn,
        'IMP B/N' => $i_bn,
        'TOTAL B/N' => $subtotal_bn, // Nuevo Total BN
        'COP COL' => $c_col,
        'IMP COL' => $i_col,
        'TOTAL COL' => $subtotal_color, // Nuevo Total Color
        'TOTAL GENERAL' => $total_general
    ]];
    
    $rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/" . $nombreExcel;

    if (function_exists('generarExcel')) {
        generarExcel($datos, $rutaPublica);
    }

    header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id&status=success");
    exit;

} else {
    echo "Error en la base de datos: " . $conn->error;
}