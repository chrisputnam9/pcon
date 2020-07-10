<?php

class Textual_User_Interface extends Util_Abstract
{

    /**
     * Constructor
     */
    public function __construct($console_instance)
    {
        parent::__construct($console_instance);
    }

}
Command_Abstract::$util_classes[]="Textual_User_Interface";
