// Tareas programadas (RF-05 / CU-07 / CU-08):
//  1) Ejecuta el motor predictivo (proyecciones + tickets de mantenimiento).
//  2) Alerta al área administrativa de los equipos que aún no reportan cuando faltan ALERTA_DIAS_ANTES días para el plazo.
//  3) Al llegar el día de plazo, si el equipo sigue sin reportar, aplica automáticamente la aproximación del motor (contingencia).
const { pool, cfg } = require('./db');
const { ejecutar } = require('./motor_predictivo');

const fmt = (d) => d.toISOString().slice(0, 10);

async function revisarOmisiones(hoy = new Date()) {
  const periodo = fmt(hoy).slice(0, 7);
  const dia = hoy.getDate();
  const diaAlerta = Math.max(1, cfg.plazoDia - cfg.alertaDiasAntes);
  const out = { alertas: 0, aplicadas: 0 };

  const [pend] = await pool.query(`
    SELECT e.id, e.serie FROM equipos e
    WHERE e.estado <> 'Fuera de servicio'
      AND NOT EXISTS (SELECT 1 FROM lecturas l WHERE l.equipo_id = e.id AND l.origen <> 'ESTIMADO' AND DATE_FORMAT(l.fecha,'%Y-%m') = ?)`,
    [periodo]);

  for (const eq of pend) {
    if (dia >= diaAlerta) {
      const msg = `Equipo ${eq.serie} sin reporte de ${periodo} (plazo: día ${cfg.plazoDia}). Se activó el motor de contingencia.`;
      const [[ya]] = await pool.query("SELECT COUNT(*) n FROM notificaciones WHERE equipo_id = ? AND tipo = 'OMISION' AND mensaje = ?", [eq.id, msg]);
      if (!ya.n) {
        await pool.query("INSERT INTO notificaciones (tipo, mensaje, equipo_id) VALUES ('OMISION', ?, ?)", [msg, eq.id]);
        out.alertas++;
      }
    }
    if (dia >= cfg.plazoDia) {
      const [r] = await pool.query(
        "UPDATE proyecciones SET aplicada = 1, aplicada_por = NULL, aplicada_en = NOW() WHERE equipo_id = ? AND periodo = ? AND aplicada = 0",
        [eq.id, periodo]);
      out.aplicadas += r.affectedRows;
    }
  }
  return out;
}

async function ciclo(hoy = new Date()) {
  const ml = await ejecutar(hoy);
  const om = await revisarOmisiones(hoy);
  console.log(`[${new Date().toISOString()}] Proyecciones: ${ml.length} equipos | alertas nuevas: ${om.alertas} | contingencias aplicadas: ${om.aplicadas}`);
  return { ml, om };
}

if (require.main === module) {
  const unaVez = process.argv.includes('--una-vez');
  const arg = process.argv.find(a => a.startsWith('--hoy='));
  const hoy = arg ? new Date(arg.split('=')[1] + 'T12:00:00') : new Date();
  ciclo(hoy).then(() => {
    if (unaVez) return pool.end();
    setInterval(() => ciclo().catch(e => console.error(e)), 24 * 60 * 60 * 1000); // diario
  }).catch(e => { console.error(e); process.exit(1); });
}

module.exports = { ciclo, revisarOmisiones };
