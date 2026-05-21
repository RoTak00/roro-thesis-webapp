<?php

class CommonHeadController extends BaseController
{

    public function index($setting = [])
    {
        global $WEBSITE_NAME;
        $this->response->addStyle("/resources/inc/bootstrap.min.css", [], 1);
        $this->response->addScript("/resources/inc/bootstrap.bundle.min.js", [], 1);
        $this->response->addScript("/resources/inc/jquery.js", [], 1);

        $data = [];

        $data['styles'] = $this->response->getStyles();
        $data['scripts'] = $this->response->getScripts();
        $data['javascript_variable_names'] = $this->response->getJavascriptVariableNames();

        $data['title'] = $setting['page_title'] ?? $WEBSITE_NAME ?? '';

        if ($data['title'] != $WEBSITE_NAME) {
            $data['title'] = $data['title'] . ' | ' . $WEBSITE_NAME;
        }


        return $this->loadView('common/head.php', $data);
    }
}