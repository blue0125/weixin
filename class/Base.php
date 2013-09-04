<?php

//class Base
class Base
{
    public $config = array();

    public function __construct()
    {
        $this->configInit();
    }

    public function configInit() 
    {
        global $config;
        $this->config = $config;
    }
}

?>
