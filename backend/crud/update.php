<?php
require "../config/db.php";
require "../config/excel.php";
// --- NUEVO: CARGAR SEGURIDAD Y AUDITORÍA ---
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

// 2. Sobrescribir el Excel local
$datos = [['ID' => $id, 'Empresa' => $empresa_id, 'Marca' => $marca, 'Serie' => $serie, 'Contador' => $contador]];
$nombreExcel = "empresa_{$empresa_id}_copiadora_{$id}.xlsx";
$rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/" . $nombreExcel;

generarExcel($datos, $rutaPublica);

// 3. Redireccionar
header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id");
exit;