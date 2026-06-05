-- Tabla para almacenar tokens FCM de dispositivos Android
-- Ejecutar en la base de datos: dbir06ahsyrzxp

CREATE TABLE IF NOT EXISTS app_push_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario VARCHAR(100) NOT NULL,
    token_fcm VARCHAR(255) NOT NULL,
    plataforma VARCHAR(20) DEFAULT 'android',
    activo TINYINT(1) DEFAULT 1,
    user_agent TEXT NULL,
    fecha_alta DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_token (token_fcm),
    KEY idx_usuario (id_usuario),
    KEY idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
