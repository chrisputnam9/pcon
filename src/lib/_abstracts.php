<?php
/**
 * Command abstract
 *  - primary or subcommand structure
 *  - main "run" method accepts and parses arguments
 *  - default run method checks for available sub-methods
 */

class Command_Abstract
{

    /**
     * Callable Methods
     */
    protected static $METHODS = [
        'backup',
        'eval_file',
        'help',
        'install',
        'update',
        'version',
    ];

    /**
     * Utility Classes available
     */
    public static $util_classes=[];
    protected $util_instances=[];

    /**
     * Constructor
     */
    public function __construct()
    {
        foreach (self::$util_classes as $util_class)
        {
            $this->util_instances[]= new $util_class($this);
        }
    }

    /**
     * Run - parse args and run method specified
     */
    public static function run($argv)
    {
    }

    /**
     * Magic handling for utility classes
     */
    public function __call($method, $arguments)
    {
        foreach ($this->util_instances as $util)
        {
            $callable = [$util, $method];
            if (method_exists($util, $method) and is_callable($callable))
            {
                // Double-check, make sure we don't get stuck in a loop
                $r = new ReflectionObject($util);
                $rm = $r->getMethod($method);
                if ($rm->isPublic())
                {
                    return call_user_func_array ($callable, $arguments);
                }
                else
                {
                    throw new Exception("Method '$method' exists but is not public");
                }
            }
        }

        throw new Exception("Invalid method '$method'");
    }

}

class Util_Abstract
{
    protected $console_instance;

    /**
     * Constructor
     */
    public function __construct($console_instance)
    {
        $this->console_instance=$console_instance;
    }

    /**
     * Magic handling for utility classes, interacting
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array ([$this->console_instance, $method], $arguments);
    }

    /**
     * Magic get/set for properties
     */
    public function __get($property)
    {
        return $this->console_instance->$property;
    }
    public function __set($property, $value)
    {
        $this->console_instance->$property = $value;
    }
}
