// Receptor SMTP (RF-02 / CU-02 / HU-02): las impresoras envían su reporte programado por correo; este servicio
// lo recibe, identifica el equipo por su número de serie y guarda el contador en `lecturas` sin intervención humana.
const { SMTPServer } = require('smtp-server');
const { simpleParser } = require('mailparser');
const { pool } = require('./db');
const { parsearReporte } = require('./parser_reporte');

async function registrarLectura(rep) {
  const [eq] = await pool.query('SELECT id FROM equipos WHERE serie = ? LIMIT 1', [rep.serie]);
  if (!eq.length) return { ok: false, motivo: `Serie no registrada: ${rep.serie}` };
  const equipoId = eq[0].id;

  // Regla RF-02: el contador no puede ser menor al último real registrado
  const [ult] = await pool.query(
    "SELECT contador_bn, contador_color FROM lecturas WHERE equipo_id = ? AND origen <> 'ESTIMADO' ORDER BY fecha DESC, id DESC LIMIT 1",
    [equipoId]
  );
  if (ult.length && (rep.contador_bn < ult[0].contador_bn || rep.contador_color < ult[0].contador_color)) {
    await pool.query(
      "INSERT INTO notificaciones (tipo, mensaje, equipo_id) VALUES ('INCONSISTENCIA', ?, ?)",
      [`Lectura SMTP inconsistente en serie ${rep.serie}: ${rep.contador_bn} B/N es menor al último registrado (${ult[0].contador_bn}).`, equipoId]
    );
    return { ok: false, motivo: 'Contador menor al último registrado (inconsistencia)' };
  }

  const fecha = rep.fecha ? rep.fecha.replace('T', ' ') : new Date().toISOString().slice(0, 19).replace('T', ' ');
  await pool.query(
    "INSERT INTO lecturas (equipo_id, fecha, contador_bn, contador_color, toner_pct, origen) VALUES (?, ?, ?, ?, ?, 'SMTP')",
    [equipoId, fecha, rep.contador_bn, rep.contador_color, rep.toner_pct]
  );
  return { ok: true, equipoId };
}

function crearServidor() {
  const usuario = process.env.SMTP_USUARIO || 'impresoras';
  const clave = process.env.SMTP_CLAVE || 'icv-smtp-2026';

  return new SMTPServer({
    name: 'icv-smtp',
    authOptional: false,
    disabledCommands: ['STARTTLS'],   // en producción: poner certificado TLS y habilitarlo
    allowInsecureAuth: true,
    onAuth(auth, session, cb) {
      if (auth.username === usuario && auth.password === clave) return cb(null, { user: usuario });
      return cb(new Error('Credenciales SMTP inválidas'));
    },
    async onData(stream, session, cb) {
      try {
        const mail = await simpleParser(stream);
        const texto = `${mail.subject || ''}\n${mail.text || ''}`;
        const rep = parsearReporte(texto);
        if (!rep) {
          console.warn('[SMTP] Correo ignorado: no se encontró serie/contador en el cuerpo');
          return cb(); // se acepta el correo para que el equipo no reintente, pero no se registra
        }
        const r = await registrarLectura(rep);
        console.log(`[SMTP] ${rep.serie}: ${r.ok ? 'lectura guardada' : 'rechazada - ' + r.motivo}`);
        cb();
      } catch (err) {
        console.error('[SMTP] Error procesando correo:', err.message);
        cb(err);
      }
    }
  });
}

if (require.main === module) {
  const puerto = parseInt(process.env.SMTP_PORT || '2525', 10);
  crearServidor().listen(puerto, () => console.log(`[SMTP] Escuchando en el puerto ${puerto}`));
}

module.exports = { crearServidor, registrarLectura };
