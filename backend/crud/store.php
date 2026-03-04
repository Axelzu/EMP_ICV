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

// Capturar datos
$empresa_id = filter_var($_POST['empresa_id'] ?? 0, FILTER_VALIDATE_INT);
$marca      = isset($_POST['marca_impresora']) ? sanear($_POST['marca_impresora']) : '';
$serie      = isset($_POST['numero_serie']) ? sanear($_POST['numero_serie']) : '';
$contador   = filter_var($_POST['contador_general'] ?? 0, FILTER_VALIDATE_INT);

if (!$empresa_id || empty($marca) || empty($serie)) {
    die("Datos incompletos.");
}

// 1. Guardar en Base de Datos
$sql = "INSERT INTO impresoras_formulario (empresa_id, marca_impresora, numero_serie, contador_general) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issi", $empresa_id, $marca, $serie, $contador);

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

    // Nombre final del archivo
    $nombreExcel = $nombreLimpio . "_" . $fechaHora . ".xlsx";

    // 3. ACTUALIZAR EL NOMBRE EN LA TABLA (Aquí fallará si no creaste la columna)
    $upd = $conn->prepare("UPDATE impresoras_formulario SET nombre_archivo = ? WHERE id = ?");
    $upd->bind_param("si", $nombreExcel, $id_impresora);
    $upd->execute();

    // 4. Registrar Log
    registrarLog($conn, "REGISTRO", "Se creó copiadora $marca para empresa $empresa_id");

    // 5. Generar el Excel físico
    $datos = [['ID' => $id_impresora, 'Empresa' => $nombreOriginal, 'Marca' => $marca, 'Serie' => $serie, 'Contador' => $contador]];
    $rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/" . $nombreExcel;

    if (function_exists('generarExcel')) {
        generarExcel($datos, $rutaPublica);
    }

    header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id&status=success");
    exit;

} else {
    echo "Error en la base de datos: " . $conn->error;
}