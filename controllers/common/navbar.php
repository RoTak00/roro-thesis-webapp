<?php

class CommonNavbarController extends BaseController
{
    public function index($setting = [])
    {
        $data = [];

        return $this->loadView('common/navbar.php', $data);
    }
}