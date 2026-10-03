// DATOS DE DEMOSTRACIÓN (solo desarrollo / pruebas). Crea usuarios, clientes, equipos y 10 meses de lecturas sintéticas
// para poder mostrar el semáforo, las proyecciones del motor predictivo y los tickets de mantenimiento.
// Uso:  npm run demo        (borra y recrea los datos de demo en la base configurada en .env)
const { pool } = require('./db');

const HASH_ICV123 = '$2y$10$MMc2Rs8qznQBng3O/RQfreoC8LUjpdc76txDnL8CCRfgbLel2uR4m'; // contraseña: icv123
const MESES = 10;

// Generador pseudoaleatorio con semilla para que la demo sea reproducible
let semilla = 20260101;
const rnd = () => { semilla = (semilla * 1664525 + 1013904223) % 4294967296; return semilla / 4294967296; };

const EQUIPOS = [
  // empresa, dependencia, modelo, serie, color?, páginas/mes B/N base, color base, ¿reportó este mes?, tóner % último reporte
  [1, 'Administración', 'Konica Minolta bizhub C250i', 'KM-A1001', 1, 4200, 900, true, 62],
  [1, 'Contabilidad', 'Xerox VersaLink B405', 'XR-B2002', 0, 3100, 0, false, null],
  [1, 'Gerencia', 'Ricoh MP C3004', 'RC-C3003', 1, 2300, 700, false, 18],
  [2, 'Recepción', 'Canon imageRUNNER 2630i', 'CN-D4004', 0, 5200, 0, true, 35],
  [2, 'Laboratorio', 'HP LaserJet Enterprise M507', 'HP-E5005', 0, 2600, 0, false, null],
  [2, 'Farmacia', 'Kyocera ECOSYS M3655', 'KY-F6006', 0, 1800, 0, false, 12],
  [3, 'Sistemas', 'Konica Minolta bizhub 368e', 'KM-G7007', 0, 6100, 0, true, 80],
  [3, 'Talento Humano', 'Ricoh IM 4000', 'RC-H8008', 0, 3500, 0, false, null]
];

function fechaMes(hoy, mesesAtras, dia) {
  const d = new Date(hoy.getFullYear(), hoy.getMonth() - mesesAtras, dia, 8, 30, 0);
  return d.toISOString().slice(0, 19).replace('T', ' ');
}

(async () => {
  const hoy = new Date();
  const c = await pool.getConnection();
  try {
    await c.query('SET FOREIGN_KEY_CHECKS=0');
    for (const t of ['notificaciones', 'tickets_mantenimiento', 'proyecciones', 'lecturas', 'impresoras_formulario', 'equipos', 'empresas']) {
      await c.query(`TRUNCATE TABLE ${t}`);
    }
    await c.query('SET FOREIGN_KEY_CHECKS=1');

    await c.query("INSERT INTO empresas (id, nombre) VALUES (1,'Hospital Vozandes'),(2,'Clínica Internacional'),(3,'Fundación Demo')");

    const usuarios = [
      ['Administrador ICV', 'admin@icv.com.ec', 'admin'],
      ['Supervisor Demo', 'supervisor@icv.com.ec', 'supervisor'],
      ['Técnico Uno', 'tecnico1@icv.com.ec', 'tecnico'],
      ['Técnico Dos', 'tecnico2@icv.com.ec', 'tecnico']
    ];
    for (const [nombre, email, rol] of usuarios) {
      await c.query(
        `INSERT INTO users (nombre, email, password, rol, activo) VALUES (?,?,?,?,1)
         ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), password=VALUES(password), rol=VALUES(rol), activo=1`,
        [nombre, email, HASH_ICV123, rol]);
    }

    for (const [emp, dep, modelo, serie, color, baseBn, baseCol, reporto, toner] of EQUIPOS) {
      const [r] = await c.query(
        `INSERT INTO equipos (empresa_id, dependencia, marca_modelo, serie, tipo_color, qr_token, rendimiento_toner, umbral_mantenimiento)
         VALUES (?,?,?,?,?,MD5(CONCAT(?,RAND())),?,?)`,
        [emp, dep, modelo, serie, color ? 'Color' : 'B/N', serie, color ? 15000 : 20000, 100000]);
      const id = r.insertId;

      let acumBn = Math.round(40000 + rnd() * 60000);
      let acumCol = color ? Math.round(8000 + rnd() * 15000) : 0;
      const fase = rnd() * Math.PI * 2;
      const ultimoMesConLectura = reporto ? 0 : 1;
      for (let m = MESES; m >= ultimoMesConLectura; m--) {
        const estacion = 1 + 0.12 * Math.sin((2 * Math.PI * (hoy.getMonth() - m)) / 12 + fase);
        acumBn += Math.round(baseBn * estacion * (1 + (rnd() - 0.5) * 0.06));
        if (color) acumCol += Math.round(baseCol * estacion * (1 + (rnd() - 0.5) * 0.08));
        const esUltima = m === ultimoMesConLectura;
        await c.query(
          "INSERT INTO lecturas (equipo_id, fecha, contador_bn, contador_color, toner_pct, origen) VALUES (?,?,?,?,?,?)",
          [id, fechaMes(hoy, m, m === 0 ? Math.max(1, Math.min(hoy.getDate(), 28)) : 28),
           acumBn, acumCol, esUltima ? toner : null, 'SMTP']);
      }
    }
    await c.query("INSERT INTO auditoria (user_id, accion, detalle, ip) VALUES (0,'SISTEMA','Datos de demostración cargados','127.0.0.1')");
    console.log('Demo cargada: 4 usuarios (contraseña icv123), 3 clientes, %d equipos, %d meses de historial.', EQUIPOS.length, MESES);
  } finally {
    c.release();
    await pool.end();
  }
})().catch(e => { console.error(e); process.exit(1); });
