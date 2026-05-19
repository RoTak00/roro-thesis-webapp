<?php
class AjaxTaskStatusController extends BaseController
{
    public function index()
    {
        header('Content-Type: application/json');

        $taskId = (int) ($_GET['task_id'] ?? 0);

        if ($taskId <= 0) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'error' => 'Missing task_id'
            ]);

            return;
        }

        $query = $this->db->query("
            SELECT *
            FROM shap_tasks
            WHERE task_id = " . (int) $taskId . "
            LIMIT 1
        ");

        if (empty($query->row)) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'error' => 'Task not found'
            ]);

            return;
        }

        $task = $query->row;

        $response = [
            'success' => true,
            'task_id' => (int) $task['task_id'],
            'status' => $task['status'],
            'created_at' => $task['created_at'],
            'started_at' => $task['started_at'],
            'finished_at' => $task['finished_at'],
        ];

        if ($task['status'] === 'done' && !empty($task['output_file'])) {
            $response['html'] = is_file($task['output_file'])
                ? file_get_contents($task['output_file'])
                : '<pre>Output file missing</pre>';
        }

        if ($task['status'] === 'failed') {
            $response['error'] = 'Task failed';

            if (!empty($task['error_file']) && is_file($task['error_file'])) {
                $response['error_details'] = file_get_contents($task['error_file']);
            }
        }

        echo json_encode($response);
    }
}