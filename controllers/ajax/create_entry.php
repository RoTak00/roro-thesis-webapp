<?php
class AjaxCreateEntryController extends BaseController
{
    public function index()
    {
        $tasksDir = getenv('TASKS_DIR') ?: __DIR__ . '/storage/tasks';

        if (!file_exists($tasksDir)) {
            mkdir($tasksDir, 0777, true);
        }

        echo json_encode($_POST);

    }
}