<?php
class AjaxCreateEntryController extends BaseController
{
    public function index()
    {
        header('Content-Type: application/json');

        $inputText = $_POST['text'] ?? null;
        $randomSource = null;

        if (empty($inputText)) {
            $task = $_POST['task'] ?? 'romd';

            if (!in_array($task, ['romd', 'regions'], true)) {
                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid task'
                ]);

                return;
            }

            $datasetRoot = '/app/data/compress_full_dataset/' . $task;

            if (!is_dir($datasetRoot)) {
                http_response_code(500);

                echo json_encode([
                    'success' => false,
                    'error' => 'Dataset folder not found',
                    'dataset_root' => $datasetRoot
                ]);

                return;
            }

            $pattern = $task === 'regions'
                ? $datasetRoot . '/*/*/*/*.json'
                : $datasetRoot . '/*/*/*.json';

            $files = glob($pattern);

            if (empty($files)) {
                http_response_code(500);

                echo json_encode([
                    'success' => false,
                    'error' => 'No dataset JSON files found',
                    'dataset_root' => $datasetRoot
                ]);

                return;
            }

            $randomFile = $files[array_rand($files)];
            $json = json_decode(file_get_contents($randomFile), true);

            if (!is_array($json) || empty($json['text'])) {
                http_response_code(500);

                echo json_encode([
                    'success' => false,
                    'error' => 'Random dataset file has no text field',
                    'file' => $randomFile
                ]);

                return;
            }

            $inputText = $json['text'];

            $relativePath = str_replace($datasetRoot . '/', '', $randomFile);
            $parts = explode('/', $relativePath);

            $randomSource = [
                'file' => $randomFile,
                'relative_path' => $relativePath,
                'class' => $parts[0] ?? null,
                'journal' => $parts[count($parts) - 2] ?? null,
            ];
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

        $model_name = $_POST['model'] ?? '';

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
            'shap_injection_type' => $shap_injection_type,
            'random_source' => $randomSource
        ];

        $tasksDir = getenv('TASKS_DIR') ?: __DIR__ . '/../../storage/tasks';

        if (!file_exists($tasksDir)) {
            mkdir($tasksDir, 0777, true);
        }

        $taskInputId = bin2hex(random_bytes(16));

        $inputFile = $tasksDir . '/' . $taskInputId . '.txt';

        $written = file_put_contents($inputFile, $inputText);

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
            'input_file' => $inputFile,
            'random_source' => $randomSource
        ]);
    }

}