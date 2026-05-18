CREATE TABLE IF NOT EXISTS shap_tasks (
    task_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    status ENUM('pending', 'running', 'done', 'failed') NOT NULL DEFAULT 'pending',

    input_file VARCHAR(255) NOT NULL,
    output_file VARCHAR(255) DEFAULT NULL,
    error_file VARCHAR(255) DEFAULT NULL,

    model_name VARCHAR(100) DEFAULT NULL,
    shap_params JSON DEFAULT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME DEFAULT NULL,
    finished_at DATETIME DEFAULT NULL,

    INDEX idx_status_created_at (status, created_at)
);