<?php


class Url
{
    private $registry = [];
    public function __construct($registry)
    {

        $this->registry = $registry;
    }


    public function __get($name)
    {
        if (isset($this->registry->registry[$name])) {
            return $this->registry->registry[$name];
        }
        return null; // Or throw an error if you want strict behavior
    }

    public function link($controller, $add_host = false)
    {
        global $HOST, $PORT;
        return ($add_host ? ($HOST . ':' . $PORT) : '') . BASE_LOCATION . $controller;
    }

}