USE copper_bigru;

INSERT INTO users (name, email, password, created_at, updated_at)
VALUES ('Admin', 'admin@gmail.com', '$2y$10$gARa72wy5F4HgXCU/uUY5.tfYMtpM56zW5WO3zkp4/hLWADzn982K', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password = VALUES(password),
    updated_at = NOW();
