<?php

class Input extends Util_Abstract
{

    /**
     * Constructor
     */
    public function __construct($console_instance)
    {
        parent::__construct($console_instance);
    }

}
Command_Abstract::$util_classes[]="Input";
