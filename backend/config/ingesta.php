<?php
/**
 * Configuración de la recepción de reportes por correo y del cierre mensual.
 */
return [
    // Direcciones o dominios desde los que se aceptan reportes (coincidencia parcial, sin distinguir mayúsculas).
    // Vacío = se acepta cualquier remitente. Recomendado: poner el correo/dominio con que envían las impresoras.
    'remitentes_permitidos' => [],

    // Guardar copia de cada correo recibido en backend/storage/correos (útil para ajustar el lector a cada marca).
    'guardar_correos' => true,

    // Día del mes en que vence la entrega del informe y días de anticipación para la alerta (RF-05).
    'plazo_dia' => 7,
    'alerta_dias_antes' => 5,
];
