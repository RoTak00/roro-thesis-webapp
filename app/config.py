from pathlib import Path

DB_CONFIG = {
    "host": "db",
    "port": 3306,
    "user": "app",
    "password": "app",
    "database": "app",
    "charset": "utf8mb4",
}

POLL_SECONDS = 2
OUTPUT_DIR = Path("storage/shap_outputs")