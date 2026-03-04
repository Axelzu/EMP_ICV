<?php
require "../config/db.php";
require "../config/excel.php";
// --- CARGAR SEGURIDAD Y AUDITORÍA ---
require "../security/functions.php"; 

// 🛡️ MEDIDA 1: Verificar el Token CSRF
if (!isset($_POST['csrf_token']) || !validarTokenCSRF($_POST['csrf_token'])) {
    die("Error de seguridad: Petición no autorizada.");
}

// 🛡️ MEDIDA 2: Sanear y Validar Entradas
$id         = filter_var($_POST['id'], FILTER_VALIDATE_INT);
$empresa_id = filter_var($_POST['empresa_id'], FILTER_VALIDATE_INT);
$marca      = sanear($_POST['marca_impresora']);
$serie      = sanear($_POST['numero_serie']);
$contador   = filter_var($_POST['contador_general'], FILTER_VALIDATE_INT);

if (!$id || !$empresa_id || !$marca || !$serie || !$contador) {
    die("Datos incompletos o inválidos");
}

// 1. Actualizar Base de Datos
$sql = "UPDATE impresoras_formulario SET marca_impresora = ?, numero_serie = ?, contador_general = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssii", $marca, $serie, $contador, $id);
$stmt->execute();

// 🛡️ MEDIDA 3: Registrar en Auditoría (Logs)
registrarLog($conn, "ACTUALIZACIÓN", "El usuario editó la copiadora ID: $id (Empresa: $empresa_id)");

// --- NUEVO: OBTENER NOMBRE DE LA EMPRESA PARA EL NOMBRE DEL EXCEL ---
$sql_emp = "SELECT nombre FROM empresas WHERE id = ?";
$stmt_emp = $conn->prepare($sql_emp);
$stmt_emp->bind_param("i", $empresa_id);
$stmt_emp->execute();
$res_emp = $stmt_emp->get_result();
$data_emp = $res_emp->fetch_assoc();

// Limpiamos el nombre (quitar espacios por guiones bajos)
$nombreEmpresaOriginal = $data_emp['nombre'] ?? 'Empresa';
$nombreEmpresaLimpio = str_replace([' ', '.', ','], '_', $nombreEmpresaOriginal);

// Generamos la fecha y hora actual (Formato: d_m_y_H_i)
$fechaHora = date('d_m_y_H_i');

// 2. Definir datos y Nombre del Excel
// Incluimos el ID de la impresora al inicio para que el sistema sepa qué archivo sobrescribir si editas de nuevo
$datos = [[
    'ID' => $id, 
    'Empresa' => $nombreEmpresaOriginal, 
    'Marca' => $marca, 
    'Serie' => $serie, 
    'Contador' => $contador
]];

// El nombre quedará: ID_Clinica_Internacional_12_02_26_14_15.xlsx
$nombreExcel = $id . "_" . $nombreEmpresaLimpio . "_" . $fechaHora . ".xlsx";
$rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/" . $nombreExcel;

// --- LÓGICA DE SOBRESCRITURA ---
// Buscamos si ya existe un archivo previo de este ID de registro para borrarlo antes de crear el nuevo
$patron = $_SERVER['DOCUMENT_ROOT'] . "/exports/" . $id . "_*.xlsx";
$archivosViejos = glob($patron);
foreach($archivosViejos as $archivoViejo){
    if(is_file($archivoViejo)) unlink($archivoViejo);
}

// Generamos el nuevo Excel actualizado
generarExcel($datos, $rutaPublica);

// 3. Redireccionar
header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id");
exit;