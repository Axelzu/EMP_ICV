<?php
/**
 * Lector de las páginas de estado de Ricoh Web Image Monitor (sin login, desde la red local).
 *
 *   /web/guest/en/websys/status/getUnificationCounter.cgi   -> contadores de la máquina
 *   /web/guest/en/websys/status/configuration.cgi           -> "Machine ID" (el número de serie)
 *
 * Los contadores B/N y Color se obtienen sumando copiadora e impresora; así la suma coincide con el «Total» de la máquina.
 * Se usa la versión en inglés de la página porque sus etiquetas no dependen del idioma configurado en el equipo.
 */

/** Convierte el HTML de una página en una lista de líneas de texto (sin estilos ni scripts). */
function ricoh_lineas($html) {
    $html = preg_replace('#<(style|script)\b.*?</\1>#is', '', (string)$html);
    $texto = html_entity_decode(preg_replace('/<[^>]+>/', "\n", $html), ENT_QUOTES, 'UTF-8');
    $lineas = [];
    foreach (preg_split('/\R/u', $texto) as $l) {
        $l = trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $l)));
        if ($l !== '') $lineas[] = $l;
    }
    return $lineas;
}

/**
 * @return array{contador_bn:int, contador_color:int, total:int, consistente:bool}|null  null si la página no es la de contadores.
 */
function ricoh_parsear_contadores($html) {
    $L = ricoh_lineas($html);
    $seccion = null;
    $total = null;
    $v = ['Copier' => [], 'Printer' => []];
    $n = count($L);
    for ($i = 0; $i < $n; $i++) {
        $l = $L[$i];
        if ($l === 'Coverage') break; // desde aquí son porcentajes de cobertura, no contadores
        if (in_array($l, ['Copier', 'Printer', 'Fax', 'Send/TX Total', 'Fax Transmission', 'Scanner Send'], true)) { $seccion = $l; continue; }
        if ($i + 2 < $n && $L[$i + 1] === ':' && ctype_digit($L[$i + 2])) {
            $valor = (int)$L[$i + 2];
            if ($l === 'Total' && $seccion === null) $total = $valor;
            elseif (isset($v[$seccion])) $v[$seccion][$l] = $valor;
        }
    }
    if ($total === null || !$v['Copier'] || !$v['Printer']) return null;

    $bn = $color = 0;
    foreach ($v as $etiquetas) {
        foreach ($etiquetas as $etq => $valor) {
            if ($etq === 'Black & White') $bn += $valor;
            else $color += $valor; // Full Color, Single Color, Two-color
        }
    }
    return ['contador_bn' => $bn, 'contador_color' => $color, 'total' => $total, 'consistente' => ($bn + $color === $total)];
}

/** @return array{serie:string, modelo:?string}|null */
function ricoh_parsear_equipo($html) {
    $L = ricoh_lineas($html);
    $id = $modelo = null;
    for ($i = 0, $n = count($L); $i + 2 < $n; $i++) {
        if ($L[$i + 1] !== ':') continue;
        if ($L[$i] === 'Machine ID') $id = $L[$i + 2];
        elseif ($L[$i] === 'Model Name') $modelo = $L[$i + 2];
    }
    return $id ? ['serie' => $id, 'modelo' => $modelo] : null;
}
