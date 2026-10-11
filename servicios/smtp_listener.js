// Receptor SMTP (RF-02 / CU-02 / HU-02): las impresoras envían su reporte programado por correo; este servicio
// lo recibe, identifica el equipo por su número de serie y guarda el contador en `lecturas` sin intervención humana.
const fs = require('fs');
const path = require('path');
const { SMTPServer } = require('smtp-server');
const { simpleParser } = require('mailparser');
const { pool } = require('./db');
const { parsearReporte } = require('./parser_reporte');

const DIR_CORREOS = path.join(__dirname, 'correos');

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

// Texto del correo + adjuntos de texto (muchas impresoras envían el reporte como .txt / .csv adjunto)
function textoCompleto(mail) {
  const partes = [mail.subject || '', mail.text || ''];
  for (const a of mail.attachments || []) {
    const tipo = (a.contentType || '').toLowerCase();
    const nombre = (a.filename || '').toLowerCase();
    if (tipo.startsWith('text/') || /\.(txt|csv|log|xml)$/.test(nombre)) partes.push(a.content.toString('utf8'));
  }
  return partes.join('\n');
}

function crearServidor() {
  const usuario = process.env.SMTP_USUARIO || 'impresoras';
  const clave = process.env.SMTP_CLAVE || 'icv-smtp-2026';
  // Algunas impresoras no soportan autenticación. Con SMTP_AUTH_REQUERIDA=false se aceptan sin usuario/clave
  // (usar solo en una red controlada o con el puerto restringido por firewall a la IP de la impresora).
  const authRequerida = (process.env.SMTP_AUTH_REQUERIDA || 'true').toLowerCase() !== 'false';
  const guardar = (process.env.GUARDAR_CORREOS || 'true').toLowerCase() !== 'false';
  if (guardar) fs.mkdirSync(DIR_CORREOS, { recursive: true });

  return new SMTPServer({
    name: 'icv-smtp',
    authOptional: !authRequerida,
    disabledCommands: ['STARTTLS'],   // en producción: poner certificado TLS y habilitarlo
    allowInsecureAuth: true,
    size: 10 * 1024 * 1024,
    onAuth(auth, session, cb) {
      if (auth.username === usuario && auth.password === clave) return cb(null, { user: usuario });
      return cb(new Error('Credenciales SMTP inválidas'));
    },
    onConnect(session, cb) {
      console.log(`[SMTP] Conexión desde ${session.remoteAddress}`);
      cb();
    },
    onData(stream, session, cb) {
      const trozos = [];
      stream.on('data', t => trozos.push(t));
      stream.on('end', async () => {
        const crudo = Buffer.concat(trozos);
        try {
          if (guardar) {
            const nombre = new Date().toISOString().replace(/[:.]/g, '-') + '_' + session.remoteAddress.replace(/[^\w.-]/g, '') + '.eml';
            fs.writeFileSync(path.join(DIR_CORREOS, nombre), crudo);
          }
          const mail = await simpleParser(crudo);
          const rep = parsearReporte(textoCompleto(mail));
          if (!rep) {
            console.warn(`[SMTP] Correo de ${mail.from?.text || session.remoteAddress} recibido pero sin serie/contador reconocibles` +
                         (guardar ? ' (copia guardada en servicios/correos/ para ajustar el parser)' : ''));
            return cb(); // se acepta para que el equipo no reintente
          }
          const r = await registrarLectura(rep);
          console.log(`[SMTP] ${rep.serie}: ${r.ok ? 'lectura guardada' : 'rechazada - ' + r.motivo}`);
          cb();
        } catch (err) {
          console.error('[SMTP] Error procesando correo:', err.message);
          cb(err);
        }
      });
    }
  });
}

if (require.main === module) {
  const puerto = parseInt(process.env.SMTP_PORT || '2525', 10);
  const servidor = crearServidor();
  servidor.on('error', e => console.error('[SMTP] Error del servidor:', e.message));
  servidor.listen(puerto, '0.0.0.0', () => console.log(`[SMTP] Escuchando en el puerto ${puerto} (todas las interfaces)`));
}

module.exports = { crearServidor, registrarLectura };
