CREATE DATABASE IF NOT EXISTS copper_bigru CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE copper_bigru;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS copper_prices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    open DECIMAL(12,4) NULL,
    high DECIMAL(12,4) NULL,
    low DECIMAL(12,4) NULL,
    close DECIMAL(12,4) NOT NULL,
    volume BIGINT UNSIGNED NULL,
    change_percent DECIMAL(8,4) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_copper_close (close)
);

CREATE TABLE IF NOT EXISTS import_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    total_rows INT UNSIGNED NOT NULL DEFAULT 0,
    valid_rows INT UNSIGNED NOT NULL DEFAULT 0,
    imported_rows INT UNSIGNED NOT NULL DEFAULT 0,
    updated_rows INT UNSIGNED NOT NULL DEFAULT 0,
    skipped_rows INT UNSIGNED NOT NULL DEFAULT 0,
    duplicate_rows INT UNSIGNED NOT NULL DEFAULT 0,
    invalid_rows INT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    error_summary TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_import_status (status),
    CONSTRAINT fk_import_user FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS model_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(30) NOT NULL UNIQUE,
    model_name VARCHAR(100) NOT NULL DEFAULT 'BiGRU',
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    is_active BOOLEAN NOT NULL DEFAULT FALSE,
    dataset_hash VARCHAR(64) NULL,
    total_records INT UNSIGNED NOT NULL DEFAULT 0,
    dataset_start_date DATE NULL,
    dataset_end_date DATE NULL,
    train_start_date DATE NULL,
    train_end_date DATE NULL,
    test_start_date DATE NULL,
    test_end_date DATE NULL,
    window_size INT UNSIGNED NOT NULL DEFAULT 30,
    units INT UNSIGNED NOT NULL DEFAULT 64,
    dropout DECIMAL(4,3) NOT NULL DEFAULT 0.200,
    batch_size INT UNSIGNED NOT NULL DEFAULT 32,
    configured_epochs INT UNSIGNED NOT NULL DEFAULT 50,
    actual_epochs INT UNSIGNED NULL,
    best_epoch INT UNSIGNED NULL,
    learning_rate DECIMAL(10,8) NOT NULL DEFAULT 0.00100000,
    optimizer VARCHAR(50) NOT NULL DEFAULT 'Adam',
    loss VARCHAR(50) NOT NULL DEFAULT 'MSE',
    model_path VARCHAR(500) NULL,
    scaler_path VARCHAR(500) NULL,
    metadata_path VARCHAR(500) NULL,
    error_message TEXT NULL,
    requested_by BIGINT UNSIGNED NULL,
    trained_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_model_status (status),
    INDEX idx_model_active (is_active),
    INDEX idx_model_trained_at (trained_at),
    CONSTRAINT fk_model_user FOREIGN KEY (requested_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS model_metrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    model_run_id BIGINT UNSIGNED NOT NULL UNIQUE,
    train_samples INT UNSIGNED NOT NULL DEFAULT 0,
    test_samples INT UNSIGNED NOT NULL DEFAULT 0,
    final_training_loss DECIMAL(18,8) NULL,
    final_validation_loss DECIMAL(18,8) NULL,
    mae DECIMAL(18,6) NOT NULL,
    rmse DECIMAL(18,6) NOT NULL,
    mape DECIMAL(10,4) NOT NULL,
    training_duration_seconds DECIMAL(12,3) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_metric_mae (mae),
    INDEX idx_metric_rmse (rmse),
    INDEX idx_metric_mape (mape),
    CONSTRAINT fk_metric_model FOREIGN KEY (model_run_id) REFERENCES model_runs(id)
);

CREATE TABLE IF NOT EXISTS predictions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    model_run_id BIGINT UNSIGNED NOT NULL,
    prediction_date DATE NULL,
    input_start_date DATE NOT NULL,
    input_end_date DATE NOT NULL,
    predicted_close DECIMAL(18,6) NOT NULL,
    actual_close DECIMAL(18,6) NULL,
    model_version VARCHAR(30) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_prediction_model (model_run_id),
    INDEX idx_prediction_date (prediction_date),
    CONSTRAINT fk_prediction_model FOREIGN KEY (model_run_id) REFERENCES model_runs(id)
);

CREATE TABLE IF NOT EXISTS prediction_inputs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prediction_id BIGINT UNSIGNED NOT NULL,
    sequence_order INT UNSIGNED NOT NULL,
    price_date DATE NOT NULL,
    close_price DECIMAL(18,6) NOT NULL,
    created_at TIMESTAMP NULL,
    CONSTRAINT fk_prediction_input FOREIGN KEY (prediction_id) REFERENCES predictions(id)
);

INSERT INTO users (name, email, password, created_at, updated_at)
VALUES ('Admin', 'admin@example.com', '$2y$10$ZDKp3YlL7Gc6TeIZaCdtl.XD/18rRNqyY0CBOvgCFTzWHxRofhxw.', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password = VALUES(password),
    updated_at = NOW();
