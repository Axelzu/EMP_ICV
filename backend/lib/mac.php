<?php
/** Normaliza una dirección MAC a formato XX:XX:XX:XX:XX:XX (mayúsculas). Devuelve null si no es válida. */
function normalizarMac($texto) {
    $hex = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', (string)$texto));
    if (strlen($hex) !== 12) return null;
    return implode(':', str_split($hex, 2));
}
