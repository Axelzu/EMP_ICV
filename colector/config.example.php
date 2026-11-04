<?php
/**
 * Copiar a colector/config.php (ignorado por git) y completar.
 * El token debe ser igual al de backend/config/colector.local.php en el servidor.
 */
return [
    'url'   => 'https://icv.com.ec/backend/api/lectura.php',
    'token' => 'CAMBIAR-por-un-texto-largo-y-secreto',
    // IP de cada impresora Ricoh de la red del cliente
    'impresoras' => ['192.168.100.50'],
];
