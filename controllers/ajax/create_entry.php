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

        $task = $_POST['task'] ?? 'romd';
        $type = $_POST['type'] ?? 'cleaned';

        $allowedTasks = ['romd', 'regions'];
        $allowedTypes = ['cleaned', 'stop', 'ner', 'stop_ph', 'ner_ph'];

        if (!in_array($task, $allowedTasks, true) || !in_array($type, $allowedTypes, true)) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'error' => 'Invalid task or type'
            ]);

            return;
        }

        $modelName = '/app/app/models/logreg/' . $task . '/' . $type . '/ngrams_1_3.pkl';

        $shapParams = [
            'task' => $task,
            'type' => $type
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