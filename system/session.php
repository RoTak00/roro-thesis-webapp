<?php

class Session
{

    private $registry = [];

    public $data, $cookie;
    public function __construct($registry)
    {
        session_start();

        $this->registry = $registry;

        $this->data = &$_SESSION;
        $this->cookie = &$_COOKIE;
    }

    public function setCookie($name, $value, $time = null)
    {
        if (is_null($time)) {
            $time = time() + 60 * 60 * 24 * 30;
        } else {
            $time = time() + $time;
        }
        setcookie($name, $value, $time, "/");
    }

    public function removeCookie($name)
    {
        setcookie($name, "", time() - 3600, "/");
    }

    public function __get($name)
    {
        // Fetch from the registry if it exists
        if (isset($this->registry->registry[$name])) {
            return $this->registry->registry[$name];
        }
        return null; // Or throw an error if you want strict behavior
    }

    public function __destruct()
    {
        //session_destroy();
    }
}