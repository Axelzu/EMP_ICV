<?php
require "../config/db.php";
require "../config/excel.php"; // Función generarExcel

/* =========================
   1. DATOS DEL FORMULARIO
========================= */
$id         = $_POST['id'] ?? null;
$empresa_id = $_POST['empresa_id'] ?? null;
$marca      = $_POST['marca_impresora'] ?? null;
$serie      = $_POST['numero_serie'] ?? null;
$contador   = $_POST['contador_general'] ?? null;

if (!$id || !$empresa_id || !$marca || !$serie || !$contador) {
    die("Datos incompletos");
}

/* =========================
   2. ACTUALIZAR EN BD
========================= */
$sql = "UPDATE impresoras_formulario
        SET marca_impresora = ?, numero_serie = ?, contador_general = ?
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssii", $marca, $serie, $contador, $id);
$stmt->execute();

/* =========================
   3. CREAR/ACTUALIZAR EXCEL
========================= */
$datos = [[
    'ID Copiadora' => $id,
    'Empresa'      => $empresa_id,
    'Marca'        => $marca,
    'Serie'        => $serie,
    'Contador'     => $contador
]];

// Ruta pública en cPanel (carpeta exports dentro de public_html)
$rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/empresa_{$empresa_id}_copiadora_{$id}.xlsx";

generarExcel($datos, $rutaPublica);

/* =========================
   4. REDIRECCIONAR
========================= */
header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id");
exit;
