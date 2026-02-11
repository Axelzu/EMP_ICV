<?php
require "../config/db.php";
require "../config/excel.php"; // Función generarExcel

/* =========================
   1. DATOS DEL FORMULARIO
========================= */
$empresa_id = $_POST['empresa_id'] ?? null;
$marca      = $_POST['marca_impresora'] ?? null;
$serie      = $_POST['numero_serie'] ?? null;
$contador   = $_POST['contador_general'] ?? null;

if (!$empresa_id || !$marca || !$serie || !$contador) {
    die("Datos incompletos");
}

/* =========================
   2. GUARDAR EN BD
========================= */
$sql = "INSERT INTO impresoras_formulario 
        (empresa_id, marca_impresora, numero_serie, contador_general)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("issi", $empresa_id, $marca, $serie, $contador);
$stmt->execute();
$id_impresora = $conn->insert_id;

/* =========================
   3. CREAR EXCEL
========================= */
$datos = [[
    'ID Copiadora' => $id_impresora,
    'Empresa'      => $empresa_id,
    'Marca'        => $marca,
    'Serie'        => $serie,
    'Contador'     => $contador
]];

// Ruta pública en cPanel (carpeta exports dentro de public_html)
$rutaPublica = $_SERVER['DOCUMENT_ROOT'] . "/exports/empresa_{$empresa_id}_copiadora_{$id_impresora}.xlsx";

generarExcel($datos, $rutaPublica);

/* =========================
   4. REDIRECCIONAR
========================= */
header("Location: ../../frontend/pages/empresa.php?empresa_id=$empresa_id");
exit;
