<?php

class CommonHomeController extends BaseController
{

    public function index()
    {
        $data = [];

        $this->response->addStyle("/resources/css/style.css");
        $this->response->addScript("/resources/scripts/home.js");

        $data['navbar'] = $this->loadController('common/navbar');
        $data['notification'] = $this->loadController('common/notification');
        $data['footer'] = $this->loadController('common/footer');
        $head_settings = ['page_title' => 'Home'];
        $data['head'] = $this->loadController('common/head', $head_settings);
        return $this->loadView('common/home.php', $data);
    }
}