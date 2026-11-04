// Buzón POP3 local de PRUEBA: sirve el pedido "requeststatus" a una impresora Ricoh que está en la misma red local.
//
// Para qué: la notificación de e-mail bajo demanda de Ricoh solo se entera del pedido leyendo un buzón por POP3. Si la red bloquea
// el POP3 hacia internet, este buzón vive en un computador de la propia red, así que el tráfico nunca sale por el firewall.
// La respuesta de la impresora sale por SMTP hacia contadores@icv.com.ec (puerto 465, que sí está permitido).
//
// Uso:   node pop3_local.js            (puerto 110; variable PORT para cambiarlo)
//        Pulse Enter en esta ventana para dejar un nuevo pedido en el buzón.
// En la impresora: Email > POP3/IMAP4 servidor = IP de este computador, puerto POP3 = 110, cifrado sin SSL;
// Cuenta de notificación de e-mail = usuario y contraseña de abajo, Recibir notificación = Activado.
const net = require('net');

const PORT = parseInt(process.env.PORT || '110', 10);
const USUARIO = process.env.POP_USER || 'impresora';
const CLAVE = process.env.POP_PASS || 'icv-local-1234';
const REMITENTE = process.env.POP_FROM || 'contadores@icv.com.ec';
const DESTINO = process.env.POP_TO || 'impresora@icv.com.ec';

let contador = 0;
const buzon = []; // { id, texto, borrado }

function nuevoPedido() {
  contador++;
  const texto = [
    `From: ${REMITENTE}`,
    `To: ${DESTINO}`,
    'Subject: requeststatus',
    `Date: ${new Date().toUTCString()}`,
    `Message-ID: <pedido-${Date.now()}-${contador}@icv.com.ec>`,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=us-ascii',
    '',
    'requeststatus',
    '',
  ].join('\r\n');
  // El identificador (UIDL) debe ser único aunque se reinicie el programa: si no, la impresora puede creer que ya lo leyó
  buzon.push({ id: contador, uid: `icv${Date.now()}n${contador}`, texto, borrado: false });
  console.log(`[POP3] Pedido #${contador} dejado en el buzón`);
}

const activos = () => buzon.filter((m) => !m.borrado);
const bytes = (m) => Buffer.byteLength(m.texto);

function atender(socket) {
  const peer = socket.remoteAddress;
  console.log(`[POP3] Conexión desde ${peer}`);
  let usuarioOk = false, autenticado = false, pendientes = [];
  const enviar = (s) => socket.write(s + '\r\n');
  enviar('+OK ICV POP3 local listo');

  let resto = '';
  socket.on('data', (d) => {
    resto += d.toString('latin1');
    let i;
    while ((i = resto.indexOf('\r\n')) >= 0) {
      const linea = resto.slice(0, i);
      resto = resto.slice(i + 2);
      comando(linea);
    }
  });
  socket.on('error', () => {});
  socket.on('close', () => console.log(`[POP3] Cierre de ${peer}`));

  function comando(linea) {
    const [cmd, ...args] = linea.trim().split(' ');
    const c = cmd.toUpperCase();
    if (c !== 'PASS') console.log(`[POP3] < ${linea.trim()}`);
    if (c === 'CAPA') { enviar('+OK'); ['USER', 'UIDL', 'TOP', 'RESP-CODES'].forEach(enviar); return enviar('.'); }
    if (c === 'USER') { usuarioOk = [USUARIO, DESTINO].includes(args[0]); return enviar(usuarioOk ? '+OK' : '-ERR usuario desconocido'); }
    if (c === 'PASS') {
      if (usuarioOk && args.join(' ') === CLAVE) {
        autenticado = true; pendientes = activos();
        console.log(`[POP3] Autenticación correcta; ${pendientes.length} mensaje(s) en el buzón`);
        return enviar(`+OK ${pendientes.length} mensajes`);
      }
      console.log(`[POP3] Autenticación RECHAZADA (${usuarioOk ? 'contraseña incorrecta' : 'usuario incorrecto'})`);
      return enviar('-ERR autenticación fallida');
    }
    if (c === 'QUIT') {
      if (autenticado) for (const m of pendientes) if (m.borrado === 'marca') m.borrado = true;
      enviar('+OK adiós');
      return socket.end();
    }
    if (!autenticado) return enviar('-ERR autentíquese primero');
    const lista = () => pendientes.filter((m) => m.borrado !== 'marca');
    if (c === 'STAT') return enviar(`+OK ${lista().length} ${lista().reduce((a, m) => a + bytes(m), 0)}`);
    if (c === 'LIST' || c === 'UIDL') {
      const f = (m, i) => c === 'LIST' ? `${i + 1} ${bytes(m)}` : `${i + 1} ${m.uid}`;
      if (args[0]) { const m = pendientes[+args[0] - 1]; return m && m.borrado !== 'marca' ? enviar(`+OK ${f(m, +args[0] - 1)}`) : enviar('-ERR sin mensaje'); }
      enviar('+OK');
      pendientes.forEach((m, i) => { if (m.borrado !== 'marca') enviar(f(m, i)); });
      return enviar('.');
    }
    if (c === 'RETR' || c === 'TOP') {
      const m = pendientes[+args[0] - 1];
      if (!m || m.borrado === 'marca') return enviar('-ERR sin mensaje');
      enviar(`+OK ${bytes(m)} octetos`);
      socket.write(m.texto.replace(/^\./gm, '..') + '\r\n.\r\n');
      return console.log(`[POP3] Pedido #${m.id} entregado a la impresora`);
    }
    if (c === 'DELE') {
      const m = pendientes[+args[0] - 1];
      if (!m || m.borrado === 'marca') return enviar('-ERR sin mensaje');
      m.borrado = 'marca';
      return enviar('+OK borrado');
    }
    if (c === 'NOOP') return enviar('+OK');
    if (c === 'RSET') { pendientes.forEach((m) => { if (m.borrado === 'marca') m.borrado = false; }); return enviar('+OK'); }
    enviar('-ERR comando no soportado');
  }
}

const servidor = net.createServer(atender);
servidor.listen(PORT, '0.0.0.0', () => {
  console.log(`[POP3] Escuchando en el puerto ${PORT} (usuario ${USUARIO}). Enter = dejar un nuevo pedido.`);
  nuevoPedido();
});
process.stdin.on('data', () => nuevoPedido());

// Pedido automático: con PEDIR_CADA_MIN=1440 deja un pedido nuevo cada 24 horas (0 = solo al iniciar y con Enter)
const cadaMin = parseInt(process.env.PEDIR_CADA_MIN || '0', 10);
if (cadaMin > 0) {
  setInterval(nuevoPedido, cadaMin * 60 * 1000);
  console.log(`[POP3] Pedido automático cada ${cadaMin} minuto(s)`);
}
