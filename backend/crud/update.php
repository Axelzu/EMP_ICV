<?php
// ... (Toda tu cabecera de update.php igual hasta el UPDATE) ...

// 1. Buscamos el nombre de archivo que se creó en el 'store.php'
$sql_file = "SELECT nombre_archivo, (SELECT nombre FROM empresas WHERE id = impresoras_formulario.empresa_id) as nombre_empresa 
             FROM impresoras_formulario WHERE id = ?";
$stmt_file = $conn->prepare($sql_file);
$stmt_file->bind_param("i", $id);
$stmt_file->execute();
$data = $stmt_file->get_result()->fetch_assoc();

$nombreExcel = $data['nombre_archivo'];
$nombreEmpresaReal = $data['nombre_empresa'];

// 2. Si por alguna razón no tiene nombre, le creamos uno (seguridad)
if (!$nombreExcel) {
    $nombreEmpresaLimpio = str_replace([' ', '.', ','], '_', $nombreEmpresaReal);
    $nombreExcel = $nombreEmpresaLimpio . "_" . date('d_m_y_H_i') . ".xlsx";
}

// 3. Preparamos los nuevos datos
$datos = [['ID' => $id, 'Empresa' => $nombreEmpresaReal, 'Marca' => $marca, 'Serie' => $serie, 'Contador' => $contador]];
$rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/" . $nombreExcel;

// 4. GENERAR EXCEL (Como el nombre es el mismo, se SOBRESCRIBE)
generarExcel($datos, $rutaPublica);

header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id");
exit;