<?php
$directorio = $_SERVER['DOCUMENT_ROOT'] . '/exports/';
$archivos = scandir($directorio);
foreach ($archivos as $archivo) {
    if (pathinfo($archivo, PATHINFO_EXTENSION) === 'xlsx') {
        echo $archivo . "\n";
    }
}