-- ICV - Migración v3: avisos automáticos de las impresoras (alertas de tóner, papel, tapa) identificadas por MAC.
-- Compatible con MySQL 8 y MariaDB. ADITIVA e IDEMPOTENTE. Ejecutar DESPUÉS de migracion_v2.sql.
-- Uso: phpMyAdmin > base de datos > pestaña Importar (o SQL). HACER RESPALDO (Exportar) ANTES.

-- Dirección MAC y nombre de host del equipo (los avisos de la Ricoh no traen número de serie)
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `equipos` ADD COLUMN `mac` VARCHAR(17) NULL', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipos' AND COLUMN_NAME = 'mac');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `equipos` ADD COLUMN `hostname` VARCHAR(100) NULL', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipos' AND COLUMN_NAME = 'hostname');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `equipos` ADD INDEX `idx_equipos_mac` (`mac`)', 'SELECT 1') FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipos' AND INDEX_NAME = 'idx_equipos_mac');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Alertas recibidas por correo desde los equipos
CREATE TABLE IF NOT EXISTS alertas_equipo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  equipo_id INT NOT NULL,
  categoria VARCHAR(20) NOT NULL,          -- TONER, PAPEL, TAPA, OTRO
  resumen VARCHAR(255) NOT NULL,           -- evento principal (asunto del aviso)
  detalle TEXT NULL,                       -- estado completo informado por la impresora
  leida TINYINT(1) NOT NULL DEFAULT 0,
  recibido_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_alerta_equipo (equipo_id, recibido_en),
  KEY idx_alerta_leida (leida),
  CONSTRAINT fk_alerta_equipo FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
