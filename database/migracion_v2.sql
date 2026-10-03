-- ICV - Migración v2: lecturas automáticas (SMTP), mantenimiento por QR, proyecciones (ML), notificaciones.
-- Compatible con MySQL 8 y MariaDB. Es ADITIVA e IDEMPOTENTE: solo crea lo que falta y se puede ejecutar varias veces.
-- Uso: phpMyAdmin > base de datos > pestaña Importar (o SQL). HACER RESPALDO (Exportar) ANTES.

CREATE TABLE IF NOT EXISTS empresas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol VARCHAR(20) NOT NULL DEFAULT 'tecnico',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auditoria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL DEFAULT 0,
  accion VARCHAR(50) NOT NULL,
  detalle TEXT,
  ip VARCHAR(45),
  fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS equipos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  dependencia VARCHAR(150),
  marca_modelo VARCHAR(150),
  serie VARCHAR(100) NOT NULL UNIQUE,
  tipo_color VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS impresoras_formulario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  dependencia VARCHAR(150),
  marca_modelo VARCHAR(150),
  serie VARCHAR(100),
  copias_bn INT NOT NULL DEFAULT 0,
  copias_color INT NOT NULL DEFAULT 0,
  impresiones_bn INT NOT NULL DEFAULT 0,
  impresiones_color INT NOT NULL DEFAULT 0,
  contador_fecha_inicial DATETIME NULL,
  contador_fecha_final DATETIME NULL,
  nombre_archivo VARCHAR(255),
  fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Columnas nuevas en tablas existentes (cada una solo se agrega si falta) ----
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `rol` VARCHAR(20) NOT NULL DEFAULT ''tecnico''', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'rol');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `activo` TINYINT(1) NOT NULL DEFAULT 1', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'activo');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
-- RF-01: identificador unico del equipo (solo si la tabla no tenia columna id)
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `equipos` ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT UNIQUE FIRST', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipos' AND COLUMN_NAME = 'id');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `equipos` ADD COLUMN `estado` ENUM(''Operativo'',''En mantenimiento'',''Fuera de servicio'') NOT NULL DEFAULT ''Operativo''', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipos' AND COLUMN_NAME = 'estado');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `equipos` ADD COLUMN `qr_token` CHAR(32) NULL', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipos' AND COLUMN_NAME = 'qr_token');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `equipos` ADD COLUMN `umbral_mantenimiento` INT NOT NULL DEFAULT 100000', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipos' AND COLUMN_NAME = 'umbral_mantenimiento');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `equipos` ADD COLUMN `rendimiento_toner` INT NOT NULL DEFAULT 20000', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipos' AND COLUMN_NAME = 'rendimiento_toner');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

UPDATE equipos SET qr_token = MD5(CONCAT(serie, RAND(), NOW())) WHERE qr_token IS NULL;

-- ---- Tablas nuevas ----
-- Historial de lecturas de contadores (acumulados). origen: SMTP (automatica), MANUAL (formulario), ESTIMADO (reservado)
CREATE TABLE IF NOT EXISTS lecturas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  equipo_id INT NOT NULL,
  fecha DATETIME NOT NULL,
  contador_bn BIGINT NOT NULL DEFAULT 0,
  contador_color BIGINT NOT NULL DEFAULT 0,
  toner_pct TINYINT NULL,
  origen ENUM('SMTP','MANUAL','ESTIMADO') NOT NULL DEFAULT 'MANUAL',
  formulario_id INT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_lect_equipo_fecha (equipo_id, fecha),
  KEY idx_lect_formulario (formulario_id),
  CONSTRAINT fk_lect_equipo FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Proyeccion del motor predictivo (RF-06). 'aplicada' = el administrativo la autorizo para el informe (CU-08)
CREATE TABLE IF NOT EXISTS proyecciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  equipo_id INT NOT NULL,
  periodo CHAR(7) NOT NULL,
  contador_bn_estimado BIGINT NOT NULL,
  contador_color_estimado BIGINT NOT NULL DEFAULT 0,
  mae_pct DECIMAL(6,2) NULL,
  meses_historial INT NOT NULL DEFAULT 0,
  preliminar TINYINT(1) NOT NULL DEFAULT 0,
  toner_pct_estimado DECIMAL(5,2) NULL,
  fecha_umbral_mantenimiento DATE NULL,
  modelo VARCHAR(40) NOT NULL DEFAULT 'brain.js-LSTM',
  aplicada TINYINT(1) NOT NULL DEFAULT 0,
  aplicada_por INT NULL,
  aplicada_en DATETIME NULL,
  generada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_proy (equipo_id, periodo),
  CONSTRAINT fk_proy_equipo FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tickets de mantenimiento preventivo (RF-03). Los crea el motor predictivo; se cierran escaneando el QR.
CREATE TABLE IF NOT EXISTS tickets_mantenimiento (
  id INT AUTO_INCREMENT PRIMARY KEY,
  equipo_id INT NOT NULL,
  estado ENUM('Pendiente','Completado','Cancelado') NOT NULL DEFAULT 'Pendiente',
  motivo VARCHAR(255) NOT NULL,
  tecnico_id INT NULL,
  fecha_agendada DATE NOT NULL,
  fecha_completado DATETIME NULL,
  completado_por INT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_tk_estado (estado),
  CONSTRAINT fk_tk_equipo FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notificaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo VARCHAR(30) NOT NULL,
  mensaje VARCHAR(255) NOT NULL,
  equipo_id INT NULL,
  leida TINYINT(1) NOT NULL DEFAULT 0,
  creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Respaldo: copia al historial las lecturas manuales ya existentes (contador = copias + impresiones). Idempotente. ----
INSERT INTO lecturas (equipo_id, fecha, contador_bn, contador_color, origen, formulario_id)
SELECT e.id, f.fecha_registro, f.copias_bn + f.impresiones_bn, f.copias_color + f.impresiones_color, 'MANUAL', f.id
FROM impresoras_formulario f
JOIN equipos e ON CONVERT(e.serie USING utf8mb4) COLLATE utf8mb4_general_ci = CONVERT(f.serie USING utf8mb4) COLLATE utf8mb4_general_ci
WHERE NOT EXISTS (SELECT 1 FROM lecturas l WHERE l.formulario_id = f.id);
