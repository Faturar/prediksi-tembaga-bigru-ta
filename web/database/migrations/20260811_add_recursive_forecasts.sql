USE copper_bigru;

SET @has_horizon_steps := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'predictions'
      AND COLUMN_NAME = 'horizon_steps'
);
SET @sql := IF(
    @has_horizon_steps = 0,
    'ALTER TABLE predictions ADD COLUMN horizon_steps INT UNSIGNED NOT NULL DEFAULT 1 AFTER model_version',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_strategy := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'predictions'
      AND COLUMN_NAME = 'strategy'
);
SET @sql := IF(
    @has_strategy = 0,
    "ALTER TABLE predictions ADD COLUMN strategy VARCHAR(30) NOT NULL DEFAULT 'recursive' AFTER horizon_steps",
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS prediction_outputs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prediction_id BIGINT UNSIGNED NOT NULL,
    horizon_step INT UNSIGNED NOT NULL,
    predicted_close DECIMAL(18,6) NOT NULL,
    actual_close DECIMAL(18,6) NULL,
    created_at TIMESTAMP NULL,
    UNIQUE KEY uq_prediction_step (prediction_id, horizon_step),
    INDEX idx_prediction_output_prediction (prediction_id),
    CONSTRAINT fk_prediction_output_prediction
        FOREIGN KEY (prediction_id)
        REFERENCES predictions(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS prediction_inputs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prediction_id BIGINT UNSIGNED NOT NULL,
    sequence_order INT UNSIGNED NOT NULL,
    price_date DATE NOT NULL,
    close_price DECIMAL(18,6) NOT NULL,
    created_at TIMESTAMP NULL,
    CONSTRAINT fk_prediction_input
        FOREIGN KEY (prediction_id)
        REFERENCES predictions(id)
        ON DELETE CASCADE
);

INSERT INTO prediction_outputs (prediction_id, horizon_step, predicted_close, actual_close, created_at)
SELECT p.id, 1, p.predicted_close, p.actual_close, p.created_at
FROM predictions p
LEFT JOIN prediction_outputs po
    ON po.prediction_id = p.id
   AND po.horizon_step = 1
WHERE po.id IS NULL;
