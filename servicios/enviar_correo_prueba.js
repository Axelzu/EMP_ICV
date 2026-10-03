// Simula el correo programado que enviaría una impresora al receptor SMTP.
// Uso: node enviar_correo_prueba.js KM-A1001 120500 31200 55
require('dotenv').config({ path: __dirname + '/.env' });
const nodemailer = require('nodemailer');

const [serie, bn, color, toner] = process.argv.slice(2);
if (!serie || !bn) { console.error('Uso: node enviar_correo_prueba.js <serie> <contador_bn> [contador_color] [toner%]'); process.exit(1); }

const transporte = nodemailer.createTransport({
  host: '127.0.0.1', port: parseInt(process.env.SMTP_PORT || '2525', 10), secure: false, ignoreTLS: true,
  auth: { user: process.env.SMTP_USUARIO || 'impresoras', pass: process.env.SMTP_CLAVE || 'icv-smtp-2026' }
});

transporte.sendMail({
  from: 'impresora@cliente.local', to: 'contadores@icv.com.ec',
  subject: `Reporte de contadores - ${serie}`,
  text: `Device Report\nSerial Number: ${serie}\nTotal Black: ${bn}\nTotal Color: ${color || 0}\n` + (toner ? `Toner Level: ${toner}%\n` : '')
}).then(i => console.log('Enviado:', i.response)).catch(e => { console.error('Error:', e.message); process.exit(1); });
