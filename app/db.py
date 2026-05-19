import pymysql
from pymysql.cursors import DictCursor

from config import DB_CONFIG


def get_connection():
    return pymysql.connect(
        host=DB_CONFIG["host"],
        port=DB_CONFIG["port"],
        user=DB_CONFIG["user"],
        password=DB_CONFIG["password"],
        database=DB_CONFIG["database"],
        charset=DB_CONFIG.get("charset", "utf8mb4"),
        cursorclass=DictCursor,
        autocommit=False,
    )


def fetch_pending_task(conn):
    with conn.cursor() as cur:
        cur.execute("""
            SELECT *
            FROM shap_tasks
            WHERE status = 'pending'
            ORDER BY created_at ASC
            LIMIT 1
        """)

        return cur.fetchone()


def mark_running(conn, task_id):
    with conn.cursor() as cur:
        cur.execute("""
            UPDATE shap_tasks
            SET
                status = 'running',
                started_at = NOW()
            WHERE task_id = %s
              AND status = 'pending'
        """, (task_id,))

    conn.commit()


def mark_done(conn, task_id, output_file):
    with conn.cursor() as cur:
        cur.execute("""
            UPDATE shap_tasks
            SET
                status = 'done',
                output_file = %s,
                finished_at = NOW()
            WHERE task_id = %s
        """, (
            str(output_file),
            task_id,
        ))

    conn.commit()


def mark_failed(conn, task_id, error_file):
    with conn.cursor() as cur:
        cur.execute("""
            UPDATE shap_tasks
            SET
                status = 'failed',
                error_file = %s,
                finished_at = NOW()
            WHERE task_id = %s
        """, (
            str(error_file),
            task_id,
        ))

    conn.commit()
