// Interpreta el cuerpo de los correos de "reporte de contadores" que envía el firmware de las impresoras.
// Cada marca usa etiquetas distintas; se aceptan las variantes más comunes (ES/EN). Devuelve null si no hay serie.

const ETIQUETAS = {
  serie: /(?:serial(?:\s*(?:number|no\.?))?|n[uú]mero\s+de\s+serie|serie|s\/n)\s*[:=#]?\s*([A-Za-z0-9\-_.]{4,})/i,
  bn:    /(?:total\s*(?:b\s*\/?\s*n|bw|black(?:\s*(?:&|and)\s*white)?|mono(?:chrome)?)|black(?:\s*(?:&|and)\s*white)?(?:\s*(?:pages|count|total))?|b\/n|blanco\s*y\s*negro|mono(?:chrome)?(?:\s*count)?)\s*[:=]?\s*([\d.,]+)/i,
  color: /(?:total\s*colou?r|colou?r(?:\s*(?:pages|count|total))?)\s*[:=]?\s*([\d.,]+)/i,
  total: /(?:total\s*(?:pages|count|impresiones|copias)?|contador\s*total)\s*[:=]?\s*([\d.,]+)/i,
  toner: /(?:toner|t[oó]ner)(?:\s*(?:level|nivel|black|negro))?\s*[:=]?\s*(\d{1,3})\s*%/i,
  fecha: /(?:date|fecha)\s*[:=]?\s*(\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?)?)/i
};

function entero(txt) {
  if (txt == null) return null;
  const limpio = String(txt).replace(/[.,](?=\d{3}(\D|$))/g, '');
  const n = parseInt(limpio.replace(/[^\d]/g, ''), 10);
  return Number.isNaN(n) ? null : n;
}

function parsearReporte(texto) {
  if (!texto) return null;
  const t = String(texto);
  const m = (re) => { const r = t.match(re); return r ? r[1] : null; };

  const serie = m(ETIQUETAS.serie);
  if (!serie) return null;

  let bn = entero(m(ETIQUETAS.bn));
  let color = entero(m(ETIQUETAS.color));
  const total = entero(m(ETIQUETAS.total));
  if (bn == null && total != null) bn = total - (color || 0);
  if (bn == null) return null;

  const toner = m(ETIQUETAS.toner);
  return {
    serie: serie.trim(),
    contador_bn: bn,
    contador_color: color || 0,
    toner_pct: toner != null ? Math.min(100, parseInt(toner, 10)) : null,
    fecha: m(ETIQUETAS.fecha)
  };
}

module.exports = { parsearReporte };
