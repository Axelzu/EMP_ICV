// Motor Analítico Predictivo de Contingencia (RF-06, CU-04, CU-05, CU-06).
// Para cada equipo: (1) proyecta el contador de cierre del mes, (2) estima desgaste de tóner y fecha de mantenimiento,
// (3) agenda automáticamente un ticket de mantenimiento preventivo si se cruza el umbral crítico.
const { pool } = require('./db');
const { proyectarSerie } = require('./ml');

const HORIZONTE_TONER_DIAS = 60;
const TONER_CRITICO_PCT = 10;
const ANTICIPACION_VISITA_DIAS = 7;

const fmt = (d) => d.toISOString().slice(0, 10);
const sumarDias = (d, n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x; };

async function tecnicoMenosCargado() {
  const [r] = await pool.query(`
    SELECT u.id FROM users u
    LEFT JOIN tickets_mantenimiento t ON t.tecnico_id = u.id AND t.estado = 'Pendiente'
    WHERE u.rol = 'tecnico' AND u.activo = 1
    GROUP BY u.id ORDER BY COUNT(t.id) ASC, u.id ASC LIMIT 1`);
  return r.length ? r[0].id : null;
}

async function procesarEquipo(eq, hoy) {
  const periodo = fmt(hoy).slice(0, 7);
  const [lect] = await pool.query(
    "SELECT fecha, contador_bn, contador_color, toner_pct FROM lecturas WHERE equipo_id = ? AND origen <> 'ESTIMADO' ORDER BY fecha ASC, id ASC",
    [eq.id]
  );
  if (lect.length < 2) return { equipo: eq.serie, omitido: 'historial insuficiente (menos de 2 lecturas)' };

  const bn = proyectarSerie(lect.map(l => ({ fecha: l.fecha, valor: l.contador_bn })), periodo);
  if (!bn) return { equipo: eq.serie, omitido: 'historial mensual insuficiente' };
  const hayColor = lect.some(l => l.contador_color > 0);
  const col = hayColor ? proyectarSerie(lect.map(l => ({ fecha: l.fecha, valor: l.contador_color })), periodo) : null;

  const ultima = lect[lect.length - 1];
  const totalActual = Number(ultima.contador_bn) + Number(ultima.contador_color);
  const consumoMes = bn.consumoMensual + (col ? col.consumoMensual : 0);
  const velocidad = Math.max(consumoMes / 30, 0.0001); // páginas por día

  // ---- Tóner (derivado de la velocidad del contador) ----
  const rendimiento = eq.rendimiento_toner || 20000;
  let restantes;
  const conToner = [...lect].reverse().find(l => l.toner_pct != null);
  if (conToner) {
    const desde = totalActual - (Number(conToner.contador_bn) + Number(conToner.contador_color));
    restantes = Math.max(0, (conToner.toner_pct / 100) * rendimiento - desde);
  } else {
    restantes = rendimiento - (totalActual % rendimiento); // se asume cambio de tóner al completar cada rendimiento
  }
  const paginasHastaCritico = Math.max(0, restantes - (TONER_CRITICO_PCT / 100) * rendimiento);
  const diasToner = paginasHastaCritico / velocidad;
  const proyectadoCierre = bn.estimado + (col ? col.estimado : 0);
  const tonerCierre = Math.max(0, ((restantes - Math.max(0, proyectadoCierre - totalActual)) / rendimiento) * 100);

  // ---- Umbral de copias para mantenimiento preventivo ----
  const [[ultMant]] = await pool.query(
    "SELECT MAX(fecha_completado) f FROM tickets_mantenimiento WHERE equipo_id = ? AND estado = 'Completado'", [eq.id]);
  let base = lect[0];
  if (ultMant && ultMant.f) {
    const previas = lect.filter(l => l.fecha <= ultMant.f);
    if (previas.length) base = previas[previas.length - 1];
  }
  const desdeMant = totalActual - (Number(base.contador_bn) + Number(base.contador_color));
  const diasMant = Math.max(0, (eq.umbral_mantenimiento - desdeMant) / velocidad);

  const diasCritico = Math.min(diasToner, diasMant);
  const fechaUmbral = sumarDias(hoy, Math.floor(diasCritico));
  const motivoToner = diasToner <= diasMant;

  // ---- Guardar proyección (no se pisa una proyección ya aplicada al informe) ----
  const [[prev]] = await pool.query('SELECT aplicada FROM proyecciones WHERE equipo_id = ? AND periodo = ?', [eq.id, periodo]);
  const maeFinal = bn.mae == null ? null : Number(bn.mae.toFixed(2));
  if (!prev || !prev.aplicada) {
    await pool.query(`
      INSERT INTO proyecciones (equipo_id, periodo, contador_bn_estimado, contador_color_estimado, mae_pct, meses_historial,
                                preliminar, toner_pct_estimado, fecha_umbral_mantenimiento, modelo)
      VALUES (?,?,?,?,?,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE contador_bn_estimado=VALUES(contador_bn_estimado), contador_color_estimado=VALUES(contador_color_estimado),
        mae_pct=VALUES(mae_pct), meses_historial=VALUES(meses_historial), preliminar=VALUES(preliminar),
        toner_pct_estimado=VALUES(toner_pct_estimado), fecha_umbral_mantenimiento=VALUES(fecha_umbral_mantenimiento),
        modelo=VALUES(modelo), generada_en=CURRENT_TIMESTAMP`,
      [eq.id, periodo, bn.estimado, col ? col.estimado : 0, maeFinal, bn.meses, bn.preliminar ? 1 : 0,
       Number(tonerCierre.toFixed(2)), fmt(fechaUmbral), bn.modelo]);
  }

  // ---- Ticket de mantenimiento preventivo automático (RF-06 -> RF-03) ----
  let ticket = null;
  if (diasCritico <= HORIZONTE_TONER_DIAS) {
    const [[abierto]] = await pool.query(
      "SELECT COUNT(*) n FROM tickets_mantenimiento WHERE equipo_id = ? AND estado = 'Pendiente'", [eq.id]);
    if (!abierto.n) {
      let agenda = sumarDias(fechaUmbral, -ANTICIPACION_VISITA_DIAS);
      if (agenda < hoy) agenda = hoy;
      const motivo = motivoToner
        ? `Desgaste de tóner proyectado: nivel crítico (${TONER_CRITICO_PCT}%) hacia ${fmt(fechaUmbral)} (aprox. ${Math.round(velocidad * 30)} págs/mes).`
        : `Se alcanzará el umbral de ${eq.umbral_mantenimiento} copias para mantenimiento hacia ${fmt(fechaUmbral)}.`;
      const tecnico = await tecnicoMenosCargado();
      await pool.query(
        "INSERT INTO tickets_mantenimiento (equipo_id, motivo, tecnico_id, fecha_agendada) VALUES (?,?,?,?)",
        [eq.id, motivo, tecnico, fmt(agenda)]);
      await pool.query(
        "INSERT INTO notificaciones (tipo, mensaje, equipo_id) VALUES ('MANTENIMIENTO', ?, ?)",
        [`Ticket de mantenimiento preventivo agendado para ${eq.serie} (${fmt(agenda)}).`, eq.id]);
      ticket = fmt(agenda);
    }
  }

  return { equipo: eq.serie, periodo, estimado_bn: bn.estimado, estimado_color: col ? col.estimado : 0,
           mae_pct: maeFinal, modelo: bn.modelo, preliminar: bn.preliminar, toner_cierre_pct: Number(tonerCierre.toFixed(1)),
           fecha_umbral: fmt(fechaUmbral), ticket_agendado: ticket };
}

async function ejecutar(hoy = new Date()) {
  const [equipos] = await pool.query("SELECT * FROM equipos WHERE estado <> 'Fuera de servicio'");
  const resultados = [];
  for (const eq of equipos) {
    try { resultados.push(await procesarEquipo(eq, hoy)); }
    catch (e) { resultados.push({ equipo: eq.serie, error: e.message }); }
  }
  return resultados;
}

if (require.main === module) {
  const arg = process.argv.find(a => a.startsWith('--hoy='));
  const hoy = arg ? new Date(arg.split('=')[1] + 'T12:00:00') : new Date();
  ejecutar(hoy).then(r => { console.table(r); return pool.end(); }).catch(e => { console.error(e); process.exit(1); });
}

module.exports = { ejecutar, procesarEquipo };
