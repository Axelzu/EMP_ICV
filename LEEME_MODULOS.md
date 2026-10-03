# ICV – Módulos agregados (SMTP, Machine Learning, QR, semáforo, informes)

## Arquitectura
- **PHP + MySQL** (existente): interfaz web, roles, semáforo, QR, tickets, informes Excel.
- **Servicio Node.js** (`servicios/`): receptor SMTP de contadores y motor predictivo con **brain.js**. Escribe en la misma base MySQL.

## Puesta en marcha local (XAMPP)
1. Iniciar MySQL en XAMPP y crear la base: `CREATE DATABASE icv_dev CHARACTER SET utf8mb4;`
2. Cargar el esquema: `mysql -uroot icv_dev < database/migracion_v2.sql`
3. Copiar `backend/config/db.local.php` (ya apunta a `icv_dev`; no se sube a git).
4. Servicio Node:
   ```
   cd servicios
   npm install
   cp .env.example .env      # ajustar credenciales de la base
   npm run demo              # (opcional) datos de demostración: usuarios, equipos y 10 meses de lecturas
   npm run ml                # calcula proyecciones y agenda tickets de mantenimiento
   npm run smtp              # receptor SMTP (puerto 2525)
   npm run scheduler         # tareas diarias: alertas de omisión y contingencia automática
   npm test                  # pruebas unitarias
   ```
5. Con la demo cargada: usuarios `admin@icv.com.ec`, `supervisor@icv.com.ec`, `tecnico1@icv.com.ec`, `tecnico2@icv.com.ec`, contraseña `icv123`.

Probar el receptor SMTP: `node enviar_correo_prueba.js KM-A1001 120500 31200 55`

## Producción (cPanel)
- Ejecutar `database/migracion_v2.sql` una sola vez (es aditiva e idempotente; respalda antes).
- El servicio Node requiere un host que permita procesos Node de larga duración y un puerto SMTP entrante. En cPanel compartido suele
  hacerse con "Setup Node.js App"; el puerto 25 normalmente no está disponible, por lo que las impresoras deberán apuntar al puerto
  que se habilite. Alternativa: leer un buzón por IMAP y reutilizar `parser_reporte.js`.
- Las cámaras de los celulares exigen **HTTPS** para escanear QR.
- Cambiar `SMTP_CLAVE` y las credenciales en `servicios/.env`.

## Mapa documento → código
| Documento | Implementación |
|---|---|
| RF-01 Inventario | `equipos` (+estado, qr_token), `frontend/pages/nuevo_equipo.php` |
| RF-02 Lecturas / CU-02 | `servicios/smtp_listener.js`, `parser_reporte.js`, validación en `backend/crud/store.php` y `backend/lib/lecturas.php` |
| RF-03 QR / CU-03 | `frontend/pages/escanear.php`, `backend/mantenimiento/confirmar.php`, `qr_equipos.php`, `mantenimiento.php` |
| RF-04 Informe / CU-09 | `backend/reports/consolidado.php` |
| RF-05 Semáforo / CU-07 | `frontend/pages/dashboard.php`, `servicios/scheduler.js` |
| RF-06 Predicción / CU-04..06 | `servicios/ml.js`, `servicios/motor_predictivo.js`, `frontend/pages/tendencia.php` |
| CU-08 Inyectar cifra | `backend/admin/aplicar_proyeccion.php` |
| HU-08 / HU-09 Usuarios | `login_process.php`, `usuarios.php`, `crear_usuario.php`, `toggle_usuario.php` |
| CI/CD | `.github/workflows/ci.yml`, `.cpanel.yml` |

## Notas sobre el motor predictivo
- Por equipo se comparan LSTM, MLP y promedio móvil con un backtest de los últimos meses reales y se usa el de menor error.
- El MAE se calcula sobre el **consumo mensual** (criterio más exigente que medirlo sobre el contador acumulado).
- Con menos de 6 meses de historial el resultado se marca **preliminar**.
- La cifra estimada es solo respaldo: nunca se mezcla con lecturas reales y el informe la marca en amarillo.
