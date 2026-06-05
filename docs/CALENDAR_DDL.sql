-- Calendarios dinámicos (años, festivos, rotaciones y patrón semanal)

CREATE TABLE IF NOT EXISTS uso_calendar_years (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year SMALLINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_calendar_year (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS uso_calendar_holidays (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    calendar_year_id BIGINT UNSIGNED NOT NULL,
    holiday_date DATE NOT NULL,
    holiday_type ENUM('nacional','local') NOT NULL DEFAULT 'nacional',
    holiday_label VARCHAR(160) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_calendar_holiday (calendar_year_id, holiday_date),
    KEY idx_calendar_holiday_year (calendar_year_id),
    CONSTRAINT fk_calendar_holiday_year FOREIGN KEY (calendar_year_id)
        REFERENCES uso_calendar_years(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS uso_calendar_rotations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    calendar_year_id BIGINT UNSIGNED NOT NULL,
    rotation_name VARCHAR(160) NOT NULL,
    weeks_cycle TINYINT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_rotation_year (calendar_year_id),
    CONSTRAINT fk_rotation_year FOREIGN KEY (calendar_year_id)
        REFERENCES uso_calendar_years(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS uso_calendar_rotation_pattern (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rotation_id BIGINT UNSIGNED NOT NULL,
    week_index TINYINT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,
    is_working TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rotation_day (rotation_id, week_index, day_of_week),
    KEY idx_pattern_rotation (rotation_id),
    CONSTRAINT fk_pattern_rotation FOREIGN KEY (rotation_id)
        REFERENCES uso_calendar_rotations(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
