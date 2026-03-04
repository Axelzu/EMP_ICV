<?php
// ... (Toda tu cabecera de store.php igual hasta el INSERT) ...

if ($stmt->execute()) {
    $id_impresora = $conn->insert_id;

    // 1. Obtener nombre de empresa y crear nombre con FECHA Y HORA
    $sql_emp = "SELECT nombre FROM empresas WHERE id = ?";
    $stmt_emp = $conn->prepare($sql_emp);
    $stmt_emp->bind_param("i", $empresa_id);
    $stmt_emp->execute();
    $emp_data = $res_emp = $stmt_emp->get_result()->fetch_assoc();
    $nombre_emp = str_replace([' ', '.', ','], '_', $emp_data['nombre'] ?? 'Empresa');
    
    $fechaHora = date('d_m_y_H_i');
    $nombreExcel = $nombre_emp . "_" . $fechaHora . ".xlsx"; // <--- Nombre con hora

    // 2. GUARDAR EL NOMBRE EN LA BASE DE DATOS
    $sql_update_name = "UPDATE impresoras_formulario SET nombre_archivo = ? WHERE id = ?";
    $upd_stmt = $conn->prepare($sql_update_name);
    $upd_stmt->bind_param("si", $nombreExcel, $id_impresora);
    $upd_stmt->execute();

    // 3. Generar el Excel
    $datos = [['ID' => $id_impresora, 'Empresa' => $emp_data['nombre'], 'Marca' => $marca, 'Serie' => $serie, 'Contador' => $contador]];
    $rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/" . $nombreExcel;
    generarExcel($datos, $rutaPublica);

    header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id&status=success");
    exit;
}