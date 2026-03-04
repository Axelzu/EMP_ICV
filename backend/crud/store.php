<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require "../config/db.php";
require "../config/excel.php";
require "../auth/guard.php"; 
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/security/functions.php'; 

$token = $_POST['csrf_token'] ?? '';
if (!validarTokenCSRF($token)) {
    die("Error de seguridad: Petición no autorizada.");
}

$empresa_id = filter_var($_POST['empresa_id'] ?? 0, FILTER_VALIDATE_INT);
$marca      = isset($_POST['marca_impresora']) ? sanear($_POST['marca_impresora']) : '';
$serie      = isset($_POST['numero_serie']) ? sanear($_POST['numero_serie']) : '';
$contador   = filter_var($_POST['contador_general'] ?? 0, FILTER_VALIDATE_INT);

if (!$empresa_id || empty($marca) || empty($serie)) {
    die("Datos incompletos.");
}

$sql = "INSERT INTO impresoras_formulario (empresa_id, marca_impresora, numero_serie, contador_general) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issi", $empresa_id, $marca, $serie, $contador);

if ($stmt->execute()) {
    $id_impresora = $conn->insert_id;

    $detalle_log = "El usuario creó la copiadora: $marca (Serie: $serie) para la empresa ID: $empresa_id";
    registrarLog($conn, "REGISTRO", $detalle_log);

    // --- NUEVO: OBTENER NOMBRE DE EMPRESA PARA EL EXCEL ---
    $sql_emp = "SELECT nombre FROM empresas WHERE id = ?";
    $stmt_emp = $conn->prepare($sql_emp);
    $stmt_emp->bind_param("i", $empresa_id);
    $stmt_emp->execute();
    $res_emp = $stmt_emp->get_result();
    $emp_data = $res_emp->fetch_assoc();
    $nombre_emp = str_replace(' ', '_', $emp_data['nombre'] ?? 'Empresa');
    
    $fechaHora = date('d_m_y_H_i');

    $datos = [[
        'ID' => $id_impresora, 
        'Empresa' => $emp_data['nombre'], 
        'Marca' => $marca, 
        'Serie' => $serie, 
        'Contador' => $contador
    ]];
    
    // Nombre solicitado: Clinica_Internacional_12_02_26_14_15
    $nombreExcel = $nombre_emp . "_" . $fechaHora . ".xlsx";
    $rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/" . $nombreExcel;

    if (function_exists('generarExcel')) {
        generarExcel($datos, $rutaPublica);
    }

    header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id&status=success");
    exit;
} else {
    die("Error: " . $conn->error);
}