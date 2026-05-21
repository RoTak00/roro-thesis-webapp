<?php
class AjaxCreateEntryController extends BaseController
{
    public function index()
    {
        header('Content-Type: application/json');

        if (empty($_POST['text'])) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'error' => 'Missing text'
            ]);

            return;
        }

        $detail_level = $_POST['detail_level'] ?? 50;

        $task = $_POST['task'] ?? 'romd';
        $type = $_POST['type'] ?? 'cleaned';

        // Injection type
        $shap_injection = !empty($_POST['reinject_text']);
        $shap_injection_type = null;

        if ($shap_injection) {
            $shap_injection_type = str_contains($type, '-ph')
                ? 'placeholder'
                : 'spaces';
        }

        $allowedTasks = ['romd', 'regions'];
        $allowedTypes = ['cleaned', 'ner-ph', 'ner', 'stop-ph', 'stop-list', 'stop'];

        if (!in_array($task, $allowedTasks, true) || !in_array($type, $allowedTypes, true)) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'error' => 'Invalid task or type'
            ]);

            return;
        }

        $model_name = $_POST['model_name'] ?? '';

        if (empty($model_name)) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'error' => 'Missing model name'
            ]);

            return;
        }



        $modelName = '/app/app/models/logreg/' . $task . '/' . $type . '/' . $model_name . '.pkl';

        $shapParams = [
            'task' => $task,
            'type' => $type,
            'detail_level' => $detail_level,
            'shap_injection_type' => $shap_injection_type
        ];

        $tasksDir = getenv('TASKS_DIR') ?: __DIR__ . '/../../storage/tasks';

        if (!file_exists($tasksDir)) {
            mkdir($tasksDir, 0777, true);
        }

        $taskInputId = bin2hex(random_bytes(16));

        $inputFile = $tasksDir . '/' . $taskInputId . '.txt';

        $written = file_put_contents($inputFile, $_POST['text']);

        if ($written === false) {
            http_response_code(500);

            echo json_encode([
                'success' => false,
                'error' => 'Could not write input file',
                'input_file' => $inputFile,
                'tasks_dir' => $tasksDir,
                'is_dir' => is_dir($tasksDir),
                'is_writable' => is_writable($tasksDir),
            ]);

            return;
        }

        $this->db->query("
            INSERT INTO shap_tasks
            SET
                status = 'pending',
                input_file = '" . $this->db->escape($inputFile) . "',
                model_name = '" . $this->db->escape($modelName) . "',
                shap_params = '" . $this->db->escape(json_encode($shapParams)) . "',
                created_at = NOW()
        ");

        echo json_encode([
            'success' => true,
            'task_id' => $this->db->insert_id(),
            'input_file' => $inputFile
        ]);
    }

}