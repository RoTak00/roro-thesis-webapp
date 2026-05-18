<?php

class CommonNotificationController extends BaseController
{

    public function index()
    {
        $data = [];
        $notifications = $this->notification->get();
        $this->notification->clear();

        $data['notifications'] = $notifications;

        return $this->loadView('common/notification.php', $data);
    }

    public function set()
    {
        if (isset($this->request->post['type']) && isset($this->request->post['message'])) {

            $this->notification->set($this->request->post['type'], $this->request->post['message'], $this->request->post['key'] ?? null);
        }
    }
}