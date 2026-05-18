<?php
class Request
{

    public $get, $post;
    private $registry = [];
    public $ip;
    public $device;

    public $server;


    public function __construct($registry)
    {
        $this->registry = $registry;
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->ip = $this->server['REMOTE_ADDR'];
        $this->device = $this->server['HTTP_USER_AGENT'];
    }

    public function __get($name)
    {
        // Fetch from the registry if it exists
        if (isset($this->registry->registry[$name])) {
            return $this->registry->registry[$name];
        }
        return null; // Or throw an error if you want strict behavior
    }


}