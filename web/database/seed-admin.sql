USE copper_bigru;

INSERT INTO users (name, email, password, created_at, updated_at)
VALUES ('Admin', 'admin@example.com', '$2y$10$ZDKp3YlL7Gc6TeIZaCdtl.XD/18rRNqyY0CBOvgCFTzWHxRofhxw.', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password = VALUES(password),
    updated_at = NOW();
