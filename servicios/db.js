require('dotenv').config({ path: __dirname + '/.env' });
const mysql = require('mysql2/promise');

const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'icv_dev',
  waitForConnections: true,
  connectionLimit: 5,
  dateStrings: true,
  charset: 'utf8mb4'
});

const cfg = {
  plazoDia: parseInt(process.env.PLAZO_DIA || '7', 10),
  alertaDiasAntes: parseInt(process.env.ALERTA_DIAS_ANTES || '5', 10)
};

module.exports = { pool, cfg };
