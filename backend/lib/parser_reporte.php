<?php
/**
 * Interpreta el texto de los correos de "reporte de contadores" que envía el firmware de las impresoras.
 * Cada marca usa etiquetas distintas; se aceptan las variantes más comunes (ES/EN) y separadores ':' '=' ',' ';' o tabulador.
 * Devuelve null si no se encuentra número de serie o contador B/N.
 */

function parser_entero($txt) {
    if ($txt === null) return null;
    $limpio = preg_replace('/[.,](?=\d{3}(\D|$))/', '', (string)$txt);
    $limpio = preg_replace('/[^\d]/', '', $limpio);
    return $limpio === '' ? null : (int)$limpio;
}

function parsearReporte($texto) {
    if (!$texto) return null;

    $et = [
        'serie' => '/(?:serial(?:\s*(?:number|no\.?))?|n[uú]mero\s+de\s+serie|serie|s\/n)\s*[:=#,;\t]?\s*((?=[A-Za-z0-9\-_.]*\d)[A-Za-z0-9\-_.]{4,})/iu',
        'bn'    => '/(?:total\s*(?:b\s*\/?\s*n|bw|black(?:\s*(?:&|and)\s*white)?|mono(?:chrome)?)|black(?:\s*(?:&|and)\s*white)?(?:\s*(?:pages|count|total))?|b\/n|blanco\s*y\s*negro|mono(?:chrome)?(?:\s*count)?)\s*[:=,;\t]?\s*(\d[\d.,]*)/iu',
        'color' => '/(?:total\s*colou?r|colou?r(?:\s*(?:pages|count|total))?)\s*[:=,;\t]?\s*(\d[\d.,]*)/iu',
        'total' => '/(?:total\s*(?:pages|count|impresiones|copias)?|contador\s*total)\s*[:=,;\t]?\s*(\d[\d.,]*)/iu',
        'toner' => '/(?:toner|t[oó]ner)(?:\s*(?:level|nivel|black|negro))?\s*[:=,;\t]?\s*(\d{1,3})\s*%/iu',
        'fecha' => '/(?:date|fecha)\s*[:=]?\s*(\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?)?)/iu',
    ];
    $m = function ($clave) use ($et, $texto) {
        return preg_match($et[$clave], $texto, $r) ? $r[1] : null;
    };

    $serie = $m('serie');
    if ($serie === null) return null;

    $bn = parser_entero($m('bn'));
    $color = parser_entero($m('color'));
    $total = parser_entero($m('total'));
    if ($bn === null && $total !== null) $bn = $total - ($color ?? 0);
    if ($bn === null) return null;

    $toner = $m('toner');
    $fecha = $m('fecha');
    return [
        'serie'          => trim($serie),
        'contador_bn'    => $bn,
        'contador_color' => $color ?? 0,
        'toner_pct'      => $toner !== null ? min(100, (int)$toner) : null,
        'fecha'          => $fecha !== null ? str_replace('T', ' ', $fecha) : null,
    ];
}
