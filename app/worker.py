# worker.py
import time
from db import get_connection, fetch_pending_task
from task_processor import process_task, fail_task
from config import POLL_SECONDS
from logger import log

log("worker started")

while True:
    conn = get_connection()

    try:
        task = fetch_pending_task(conn)

        if not task:
            time.sleep(POLL_SECONDS)
            continue

        try:
            log(f"running task {task['task_id']}")
            process_task(conn, task)
            log(f"done task {task['task_id']}")
        except Exception:
            fail_task(conn, task)
            log(f"failed task {task['task_id']}")

    finally:
        conn.close()