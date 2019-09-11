<?php
/**
 * {{tool_name}}
 */
Class {{class_name}} extends Console_Abstract
{
    const VERSION = 1;

    // Name of script and directory to store config
    const SHORTNAME = '{{tool_shortname}}';

    /**
     * Callable Methods
     */
    protected static $METHODS = [
        'test',
    ];

    // Config Variables
    protected $__test_message = ["Test message - sample config to test new console tool", "string"];
    public $test_message = "Testing, testing, 0, 1, 10, 11";

	public $update_version_url = "";

    protected $___test = [
        "Sample method to test new console tool",
        ["Mesage - defaults to value of test_message (config)", "string"],
    ];
	public function test($message=null)
    {
        $this->prepArg($message, null);

        if (is_null($message))
        {
            $this->log('No message specified, using configured test message');
            $message = $this->test_message;
            if (empty($message))
            {
                $this->error('Failed to load a test message');
            }
        }

        $this->output($message);
    }
}

// Kick it all off
{{class_name}}::run($argv);

// Note: leave this for packaging ?>
