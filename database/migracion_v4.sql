-- ICV - Migración v4: lecturas enviadas por el colector de contadores (impresoras que no reportan contadores por correo).
-- Compatible con MySQL 8 y MariaDB. IDEMPOTENTE (puede ejecutarse varias veces). Ejecutar DESPUÉS de migracion_v3.sql.
-- Uso: phpMyAdmin > base de datos > pestaña SQL o Importar. HACER RESPALDO (Exportar) ANTES.

ALTER TABLE lecturas MODIFY origen ENUM('SMTP','MANUAL','ESTIMADO','COLECTOR') NOT NULL DEFAULT 'MANUAL';
