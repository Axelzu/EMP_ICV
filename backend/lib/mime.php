<?php
/**
 * Lector mínimo de correos (RFC 5322 / MIME) sin dependencias externas.
 * Extrae asunto, remitente y el texto del cuerpo, incluidos los adjuntos de texto (.txt/.csv/.log/.xml),
 * porque muchas impresoras envían el reporte de contadores como adjunto.
 */

function mime_decodificar_cabecera($valor) {
    $valor = trim(preg_replace('/\r?\n[ \t]+/', ' ', (string)$valor));
    if (function_exists('mb_decode_mimeheader')) return mb_decode_mimeheader($valor);
    return $valor;
}

function mime_separar($crudo) {
    $crudo = str_replace("\r\n", "\n", $crudo);
    $pos = strpos($crudo, "\n\n");
    if ($pos === false) return [$crudo, ''];
    return [substr($crudo, 0, $pos), substr($crudo, $pos + 2)];
}

function mime_cabeceras($bloque) {
    $bloque = preg_replace('/\n[ \t]+/', ' ', $bloque);
    $h = [];
    foreach (explode("\n", $bloque) as $linea) {
        $p = strpos($linea, ':');
        if ($p === false) continue;
        $h[strtolower(trim(substr($linea, 0, $p)))] = trim(substr($linea, $p + 1));
    }
    return $h;
}

function mime_parametro($cabecera, $nombre) {
    if (preg_match('/' . preg_quote($nombre, '/') . '\s*=\s*"([^"]*)"/i', $cabecera, $m)) return $m[1];
    if (preg_match('/' . preg_quote($nombre, '/') . '\s*=\s*([^;\s]+)/i', $cabecera, $m)) return $m[1];
    return null;
}

function mime_decodificar_cuerpo($cuerpo, $cabeceras) {
    $enc = strtolower($cabeceras['content-transfer-encoding'] ?? '7bit');
    if ($enc === 'base64') $cuerpo = base64_decode($cuerpo);
    elseif ($enc === 'quoted-printable') $cuerpo = quoted_printable_decode($cuerpo);

    $charset = strtolower(mime_parametro($cabeceras['content-type'] ?? '', 'charset') ?? 'utf-8');
    if ($charset !== 'utf-8' && $charset !== 'us-ascii' && function_exists('mb_convert_encoding')) {
        $conv = @mb_convert_encoding($cuerpo, 'UTF-8', $charset);
        if ($conv !== false) $cuerpo = $conv;
    }
    return $cuerpo;
}

/** Recorre las partes del mensaje y acumula el texto útil en $salida. */
function mime_recolectar($crudo, &$salida, $profundidad = 0) {
    if ($profundidad > 8) return;
    list($bloque, $cuerpo) = mime_separar($crudo);
    $h = mime_cabeceras($bloque);
    $tipo = strtolower($h['content-type'] ?? 'text/plain');

    if (strpos($tipo, 'multipart/') === 0) {
        $borde = mime_parametro($h['content-type'], 'boundary');
        if (!$borde) return;
        foreach (explode('--' . $borde, $cuerpo) as $parte) {
            $parte = ltrim($parte, "\n");
            if ($parte === '' || strpos($parte, '--') === 0) continue;
            mime_recolectar($parte, $salida, $profundidad + 1);
        }
        return;
    }

    $nombre = strtolower(mime_parametro($h['content-disposition'] ?? '', 'filename')
                         ?? mime_parametro($h['content-type'] ?? '', 'name') ?? '');
    $esTexto = strpos($tipo, 'text/plain') === 0
            || (strpos($tipo, 'text/') === 0 && strpos($tipo, 'text/html') !== 0)
            || preg_match('/\.(txt|csv|log|xml)$/', $nombre);
    if ($esTexto) $salida[] = mime_decodificar_cuerpo($cuerpo, $h);
}

/** @return array{asunto:string, remitente:string, texto:string} */
function mime_parsear($crudo) {
    list($bloque) = mime_separar($crudo);
    $h = mime_cabeceras($bloque);
    $partes = [];
    mime_recolectar($crudo, $partes);
    return [
        'asunto'    => mime_decodificar_cabecera($h['subject'] ?? ''),
        'remitente' => mime_decodificar_cabecera($h['from'] ?? ''),
        'texto'     => implode("\n", $partes),
    ];
}
