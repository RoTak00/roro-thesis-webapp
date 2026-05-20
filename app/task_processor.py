import json
import traceback
from pathlib import Path

from config import OUTPUT_DIR
from db import mark_running, mark_done, mark_failed
from shap_exporter import RoRoShapWorkerExporter
from logger import log

def process_task(conn, task):
    task_id = int(task["task_id"])

    mark_running(conn, task_id)
    
    input_file = Path(task["input_file"])
    log(f"loading input file {input_file}")
    text = input_file.read_text(encoding="utf-8")

    shap_params = json.loads(task["shap_params"] or "{}")

    log(f"shap params: {shap_params}")
    
    log(f"loading model {task['model_name']}")
    exporter = RoRoShapWorkerExporter(
        pickle_path=task["model_name"],
        detail_level=shap_params.get("detail_level", 50),
        text_variant = shap_params.get("type", "cleaned"),
    )

    html = exporter.export_one(text)

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

    output_file = OUTPUT_DIR / f"task_{task_id}.html"
    log(f"writing output {output_file}")
    output_file.write_text(html, encoding="utf-8")

    mark_done(conn, task_id, output_file)


def fail_task(conn, task):
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

    error_file = OUTPUT_DIR / f"task_{task['task_id']}.error.txt"
    error_file.write_text(traceback.format_exc(), encoding="utf-8")

    mark_failed(conn, task["task_id"], error_file)