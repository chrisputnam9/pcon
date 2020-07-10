<?php

class Utilities extends Util_Abstract
{

    /**
     * Constructor
     */
    public function __construct($console_instance)
    {
        parent::__construct($console_instance);
    }

    /**
     * Stringify some data for output
     */
    public function stringify($data)
    {
        if (is_object($data) or is_array($data))
        {
            $data = print_r($data, true);
        }
        else if (is_bool($data))
        {
            $data = $data ? "(Bool) True" : "(Bool) False";
        }
        elseif (is_null($data))
        {
            $data = "(NULL)";
        }
        elseif (is_int($data))
        {
            $data = "(int) $data";
        }
        else if (!is_string($data))
        {
            ob_start();
            var_dump($data);
            $data = ob_get_clean();
        }
        $data = trim($data, " \t\n\r\0\x0B/");
        return $data;
    }

}
Command_Abstract::$util_classes[]="Utilities";
