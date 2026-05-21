<?php

class CommonHomeController extends BaseController
{

    public function index()
    {
        $data = [];

        $this->response->addStyle("/resources/css/style.css");
        $this->response->addScript("/resources/scripts/home.js");

        $model_options = [
            'romd' => [
                'cleaned' => [
                    'ngrams_1_3' => 'N-grams 1-3',
                    'ngrams_2_4' => 'N-grams 2-4',
                ],
                'ner-ph' => [
                    'ngrams_2_4' => 'N-grams 2-4',
                ],
            ],
            'regions' => [
                'cleaned' => [
                    'ngrams_1_4' => 'N-grams 1-4',
                ],
            ],
        ];

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
}