# ICV – Módulos agregados (correo automático, predicción, QR, semáforo, informes)

Todo corre en **PHP + MySQL**, listo para cPanel. La carpeta `servicios/` (Node.js + brain.js) queda como alternativa
para pruebas locales y no es necesaria en producción.

## Cómo llegan los contadores (cPanel)

```
Impresora ──SMTP──► Buzón del dominio ──"Canalizar a un programa"──► backend/cron/recibir_correo.php ──► MySQL ──► Web
```

1. **Crear el buzón** en cPanel > Correo > Cuentas de correo: p. ej. `contadores@sudominiipiipdsado.com`.
2. **Canalizar a un programa** en cPanel > Correo > Reenviadores > _Agregar reenviador_ > Opciones avanzadas >
   "Canalizar a un programa": `public_html/backend/cron/recibir_correo.php`
   (ruta relativa a la carpeta personal). El archivo debe tener permisos **755**.
3. **Configurar la impresora** (su página web interna, sección SMTP / Correo / Alertas):
   servidor `mail.sudominio.com`, puerto 587 (STARTTLS) o 465 (SSL), usuario y clave del buzón,
   destino del reporte: el mismo buzón, y programar el _reporte de contadores_ periódico.
4. Ver el resultado en `backend/storage/ingesta.log` (cada correo: lectura guardada / rechazada / no reconocida).
   Si un formato no se reconoce, la copia del correo queda en `backend/storage/correos/*.eml` para ajustar el lector.
5. Opcional: en `backend/config/ingesta.php` limitar `remitentes_permitidos` al correo/dominio de las impresoras.

> Si su hosting no permite "Canalizar a un programa", la alternativa es leer el buzón por IMAP con una tarea cron.

## Predicción y alertas

- Botón **Recalcular proyecciones** en el panel (`frontend/pages/dashboard.php`), o tarea diaria en cPanel > Tareas Cron:
  `/usr/local/bin/php /home/USUARIO_CPANEL/public_html/backend/cron/motor.php`
- Por equipo se comparan MLP (red neuronal), tendencia lineal y promedio móvil con un backtest de los últimos meses reales;
  se usa el de menor error. El MAE se mide sobre el **consumo mensual**.
- Con menos de 6 meses de historial el resultado se marca **preliminar**. Se requieren al menos 2 meses de lecturas.
- La cifra estimada es solo respaldo: nunca se mezcla con lecturas reales y el informe la marca en amarillo.

## Avisos automáticos de las impresoras (alertas por MAC)

Algunas impresoras (p. ej. Ricoh MP C2503, "Notificación automática de e-mail") envían avisos de eventos: tóner casi vacío,
falta de papel, tapa abierta. Esos correos **no traen número de serie ni contadores**; el equipo se identifica por su **MAC**
(o nombre de host), que viene en el correo.

- Registrar la MAC de cada impresora en **Agregar / Editar máquina** (campo "Dirección MAC", ej. `00:26:73:96:44:10`).
- `backend/lib/aviso_equipo.php` interpreta el aviso y lo guarda en `alertas_equipo`; un aviso de **tóner** crea además un
  ticket de mantenimiento si no hay uno pendiente. El mismo evento no se repite dentro de 6 horas.
- Se ven en la página del cliente (insignias por equipo + tabla "Alertas recientes") y en el panel (tarjeta de avisos sin leer).
- En `ingesta.log`: "Aviso de <MAC> guardado [TONER]: ..." o "rechazado - Equipo no registrado (MAC ...)".
- La impresora debe presentarse con un nombre de dominio completo para que el antispam del servidor acepte el correo:
  en la impresora, Red > IPv4 > Nombre del dominio = `icv.com.ec`.

## Base de datos

Ejecutar en phpMyAdmin, en este orden y una sola vez cada uno (MySQL 8 / MariaDB, aditivos e idempotentes). Respaldar antes:

1. `database/migracion_v2.sql`
2. `database/migracion_v3.sql` (MAC del equipo y tabla `alertas_equipo`)

## Desarrollo local

- `backend/config/db.local.php` (ignorado por git) apunta a la base local `icv_dev`.
- Servidor local: `php -S localhost:8080` en la raíz del proyecto.
- Probar la recepción de correo sin impresora: `php backend/cron/recibir_correo.php < ejemplo.eml`
- Pruebas: `php backend/tests/run.php`
- Datos de demostración (solo local): `cd servicios && npm install && npm run demo`

## Mapa documento → código

| Documento                    | Implementación                                                                                                      |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| RF-01 Inventario             | `equipos` (+estado, qr_token), `frontend/pages/nuevo_equipo.php`                                                    |
| RF-02 Lecturas / CU-02       | `backend/cron/recibir_correo.php`, `backend/lib/{mime,parser_reporte,ingesta}.php` (valida que el contador no baje) |
| RF-03 QR / CU-03             | `escanear.php`, `backend/mantenimiento/confirmar.php`, `qr_equipos.php`, `mantenimiento.php`                        |
| RF-04 Informe / CU-09        | `backend/reports/consolidado.php` (descarga directa desde `empresa.php` y `dashboard.php`)                          |
| RF-05 Semáforo / CU-07       | `frontend/pages/dashboard.php`, `motor_revisar_omisiones()`                                                         |
| RF-06 Predicción / CU-04..06 | `backend/lib/motor.php`, `backend/cron/motor.php`, `frontend/pages/tendencia.php`                                   |
| CU-08 Inyectar cifra         | `backend/admin/aplicar_proyeccion.php`                                                                              |
| HU-08 / HU-09 Usuarios       | `login_process.php`, `usuarios.php`, `crear_usuario.php`, `toggle_usuario.php`                                      |
| CI/CD                        | `.github/workflows/ci.yml`, `.cpanel.yml`                                                                           |
