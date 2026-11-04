<?php
/**
 * Copiar a backend/config/colector.local.php (ignorado por git) y definir un token largo y secreto (mínimo 16 caracteres).
 * Sin ese archivo, backend/api/lectura.php responde 503 y no acepta lecturas.
 */
return [
    'token' => 'CAMBIAR-por-un-texto-largo-y-secreto',
];
