<?php
/**
 * ___CLASS_NAME___ Class File
 *
 * @package ___TOOL_SHORTNAME___
 * @author  ___AUTHOR_HANDLE___
 */

/**
 * ___TOOL_NAME___
 *
 * ___TOOL_DESCRIPTION___
 */
class ___CLASS_NAME___ extends Console_Abstract
{
    /**
     * Current tool version
     *
     * @var string
     */
    public const VERSION = "0.0.1";

    /**
     * Tool shortname - used as name of configurationd directory.
     *
     * @var string
     */
    public const SHORTNAME = '___TOOL_SHORTNAME___';

    /**
     * Callable Methods / Sub-commands
     *  - Must be public methods defined on the class
     *
     * @var array
     */
    protected static $METHODS = [
        'test',
    ];

    /**
     * Help info for $test_message
     *
     * @var array
     *
     * @internal
     */
    protected $__test_message = ["Default test message - sample config to test new console tool", "string"];

    /**
     * Default test message - sample config to test new console tool
     *
     * @var string
     * @api
     */
    public $test_message = "Testing, testing, 0, 1, 10, 11";

    /**
     * The URL to check for updates
     *
     *  - PCon will check the README file - typical setup
     *
     * @var string
     * @see PCon::update_version_url
     * @api
     */
    public $update_version_url = "___UPDATE_URL___";

    /**
     * Help info for test method
     *
     * @var array
     *
     * @internal
     */
    protected $___test = [
        "Sample method to test new console tool",
        ["Mesage - defaults to value of test_message (config)", "string"],
    ];

    /**
     * Method to show a test method - sample method to test new console tool
     *
     * @param string $message Message to output. Defaults to configured $this->test_message.
     *
     * @return void;
     */
    public function test(string $message = null)
    {
        $this->prepArg($message, null);

        if (is_null($message)) {
            $this->log('No message specified, using configured test message');
            $message = $this->test_message;
            if (empty($message)) {
                $this->error('Failed to load a test message');
            }
        }

        $this->output($message);
    }//end test()
}//end class


if (empty($__no_direct_run__)) {
    // Kick it all off
    ___CLASS_NAME___::run($argv);
}

// Note: leave the end tag for packaging
?>
