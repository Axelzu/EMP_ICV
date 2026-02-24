<?php
require "../config/db.php";
require "../config/excel.php";
// --- NUEVO: CARGAR SEGURIDAD Y AUDITORÍA ---
require "../security/functions.php"; 

// 🛡️ MEDIDA 1: Verificar el Token CSRF
if (!isset($_POST['csrf_token']) || !validarTokenCSRF($_POST['csrf_token'])) {
    die("Error de seguridad: Petición no autorizada o sesión expirada.");
}

// 🛡️ MEDIDA 2: Sanear y Validar Entradas
$empresa_id = filter_var($_POST['empresa_id'], FILTER_VALIDATE_INT);
$marca      = sanear($_POST['marca_impresora']);
$serie      = sanear($_POST['numero_serie']);
$contador   = filter_var($_POST['contador_general'], FILTER_VALIDATE_INT);

if (!$empresa_id || !$marca || !$serie || !$contador) {
    die("Datos incompletos o inválidos");
}

// 1. Guardar en Base de Datos
$sql = "INSERT INTO impresoras_formulario (empresa_id, marca_impresora, numero_serie, contador_general) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issi", $empresa_id, $marca, $serie, $contador);
$stmt->execute();
$id_impresora = $conn->insert_id;

// 🛡️ MEDIDA 3: Registrar en Auditoría (Logs)
registrarLog($conn, "REGISTRO", "El usuario creó una copiadora (ID: $id_impresora) para la empresa ID: $empresa_id");

// 2. Crear el Excel en la carpeta exports
$datos = [['ID' => $id_impresora, 'Empresa' => $empresa_id, 'Marca' => $marca, 'Serie' => $serie, 'Contador' => $contador]];
$nombreExcel = "empresa_{$empresa_id}_copiadora_{$id_impresora}.xlsx";
$rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/" . $nombreExcel;

generarExcel($datos, $rutaPublica);

// 3. Redireccionar
header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id");
exit;