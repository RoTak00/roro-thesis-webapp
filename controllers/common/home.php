<?php

class CommonHomeController extends BaseController
{

    public function index()
    {
        $data = [];

        $this->response->addStyle("/resources/css/style.css");
        $this->response->addScript("/resources/scripts/home.js");

        $model_options = $this->loadModelOptions(__DIR__ . '/../../resources/model_config.csv');

        $this->response->localiseScripts([
            'MODEL_OPTIONS' => $model_options
        ]);

        $data['navbar'] = $this->loadController('common/navbar');
        $data['notification'] = $this->loadController('common/notification');
        $data['footer'] = $this->loadController('common/footer');
        $head_settings = ['page_title' => 'Home'];
        $data['head'] = $this->loadController('common/head', $head_settings);
        return $this->loadView('common/home.php', $data);
    }

    private function loadModelOptions($csvPath)
    {
        $options = [];

        if (!is_file($csvPath)) {
            return $options;
        }

        if (($handle = fopen($csvPath, 'r')) === false) {
            return $options;
        }

        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $item = array_combine($header, $row);

            $task = $item['task'];
            $type = $item['type'];

            $options[$task][$type][] = [
                'key' => $item['key'],
                'label' => $item['label'],
                'accuracy' => (float) $item['accuracy'],
            ];
        }

        fclose($handle);

        foreach ($options as &$taskTypes) {
            foreach ($taskTypes as &$models) {
                usort($models, function ($a, $b) {
                    return $b['accuracy'] <=> $a['accuracy'];
                });
            }
        }

        return $options;
    }
}