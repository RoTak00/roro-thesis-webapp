<?php

class Registry
{

    public $registry = [];

    public function __construct()
    {
    }

    public function __get($name)
    {
        // Fetch from the registry if it exists
        if (isset($this->registry[$name])) {
            return $this->registry[$name];
        }
        return null; // Or throw an error if you want strict behavior
    }

    public function add($reg_item, $reg_value)
    {
        $this->registry[$reg_item] = $reg_value;
    }
}