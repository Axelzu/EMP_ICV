<?php
/**
 * PROTECCIÓN DE CONEXIÓN TRUENAS - ICV
 */

// 1. Definir la Llave API (Cámbiala por una clave secreta que tú elijas)
$api_key_secreta = "ICV_2026_SECURE_ACCESS_KEY"; 

// 2. Verificar si la llave enviada por la URL es correcta
if (!isset($_GET['key']) || $_GET['key'] !== $api_key_secreta) {
    // Si la llave no coincide, enviamos un error 403 (Prohibido)
    header('HTTP/1.0 403 Forbidden');
    echo "Acceso denegado: Se requiere una llave válida.";
    exit;
}

// 3. Si la llave es correcta, procedemos a listar los archivos
$directorio = $_SERVER['DOCUMENT_ROOT'] . '/exports/';

// Verificar si el directorio existe para evitar errores
if (!is_dir($directorio)) {
    die("Error: El directorio de exportaciones no existe.");
}

$archivos = scandir($directorio);

foreach ($archivos as $archivo) {
    // Filtrar solo los archivos de Excel
    if (pathinfo($archivo, PATHINFO_EXTENSION) === 'xlsx') {
        echo $archivo . "\n";
    }
}