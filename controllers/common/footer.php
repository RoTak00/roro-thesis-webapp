<?php

class CommonFooterController extends BaseController
{

    public function index($setting = [])
    {
        $data = [];



        return $this->loadView('common/footer.php', $data);
    }
}