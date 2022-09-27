<?php
/**
 * Defines Command class
 *
 * @package pcon
 * @author  chrisputnam9
 */

if (!class_exists("Command")) {

    /**
     * Command abstract class
     *
     *  - General command structure - both for primary and sub-commands
     *  - Contains default sub-commands that many commands might wish to use
     *  - Contains default internal supporting functionality many commands might wish to use
     */
    class Command
    {
        /**
         * Callable Methods / Sub-commands
         *
         *  - Must be public methods defined on the class
         *
         * @var array
         */
        protected static $METHODS = [
            'clear',
            'exit',
            'help',
            'prompt',
        ];

        /**
         * Alternate commands for callable methods
         *
         *  - Keys are aliases and values are the original method to run
         *
         * @var array
         */
        protected static $METHOD_ALIASES = [
            'h' => 'help',
            '?' => 'help',
            'p' => 'prompt',
            'x' => 'exit',
            'q' => 'exit',
            'quit' => 'exit',
        ];

        /**
         * Default method to run on command launch if none specified
         *
         *  - Must be one of the values specified in static $METHODS
         *
         * @var string
         */
        protected static $DEFAULT_METHOD = "prompt";

        /**
         * Methods that are OK to run as root without warning
         *
         *  - Must be values specified in static $METHODS
         *
         * @var array
         */
        protected static $ROOT_METHODS = [];

        /**
         * Config options that are hidden from help output
         *
         *  - Add config values here that would not typically be overridden by a flag
         *  - Cleans up help output and avoids confusion
         *  - Must be values specified in static $METHODS
         *
         * @var array
         */
        protected static $HIDDEN_CONFIG_OPTIONS = [];

        /**
         * Main Tool instance
         *
         *  - Expected to be an instance of a class extending Console_Abstract
         *
         * @var Console_Abstract
         */
        protected $main_tool;

        /**
         * Constructor
         *
         * @param Console_Abstract $main_tool The instance of the main tool class
         *  - which should extend Console_Abstract.
         */
        public function __construct(Console_Abstract $main_tool)
        {
            $this->setMainTool($main_tool);
        }//end __construct()


        /**
         * Set Main Tool - needed backreference for some functionality
         *
         * @param Console_Abstract $main_tool The instance of the main tool class - which should extend Console_Abstract.
         *
         * @return void
         */
        public function setMainTool(Console_Abstract $main_tool)
        {
            $this->main_tool = $main_tool;
        }//end setMainTool()


        /**
         * Given input from user, try running the requested command / method
         *
         *  - Parses out input (arg_list) into method and arguments
         *  - Uses ancestor-merged $METHOD_ALIASES to allow shorter commands
         *  - Restricts method to ancestor-merged $METHODS anything else gives an error
         *  - Warns about running as root except for methods in ancestor-merged $ROOT_METHODS
         *  - Runs initialization if $initial is true
         *
         * @param array $arg_list         List of arguments from the user's input.
         * @param mixed $initial          Whether this is the initial command run by the tool.
         * @param mixed $prompt_when_done Whether to show command prompt when done.
         *
         * @return void
         */
        public function try_calling(array $arg_list, mixed $initial = false, mixed $prompt_when_done = false)
        {
            $this->log($arg_list);

            $method = array_shift($arg_list);

            $class = get_class($this);

            if (empty($method)) {
                $method = static::$DEFAULT_METHOD;
            }

            $aliases = static::getMergedProperty('METHOD_ALIASES');

            if (isset($aliases[$method])) {
                $method = $aliases[$method];
            }

            $this->method = $method;

            try {
                $valid_methods = static::getMergedProperty('METHODS');

                if (!in_array($method, $valid_methods)) {
                    if ($prompt_when_done) {
                        $this->warn("Invalid method - $method");
                        $this->prompt(false, true);
                    } else {
                        $this->help();
                        $this->hr();
                        $this->error("Invalid method - $method");
                    }
                }

                $args = [];
                foreach ($arg_list as $_arg) {
                    if (strpos($_arg, '--') === 0) {
                        $arg = substr($_arg, 2);
                        $arg_split = explode("=", $arg, 2);

                        if (!isset($arg_split[1])) {
                            $arg_split[1] = true;
                        }

                        $this->main_tool->configure($arg_split[0], $arg_split[1]);
                    } else {
                        $args[] = $_arg;
                    }
                }

                // Check if running as root - if so, make sure that's OK
                if ($this->main_tool->running_as_root and !$this->main_tool->allow_root) {
                    $root_methods = static::getMergedProperty('ROOT_METHODS');
                    if (!in_array($method, $root_methods)) {
                        $this->error("Cowardly refusing to run as root. Use --allow-root to bypass this error.", 200);
                    }
                }

                if ($initial) {
                    date_default_timezone_set($this->main_tool->timezone);

                    $this->checkRequirements();

                    $this->log('Determined home directory to be ' . $this->main_tool->home_dir);


                    // Run an update check
                    if ($this->updateCheck(true, true)) {
// auto:true, output:true
                        if ($method != 'update') {
                            $this->sleep(3);
                        }
                    }
                }

                $call_info = "$class->$method(" . implode(",", $args) . ")";
                $this->log("Calling $call_info");
                $this->hrl();

                try {
                    call_user_func_array([$this, $method], $args);
                } catch (ArgumentCountError $e) {
                    $this->_run_error($e, $method);
                } catch (InvalidArgumentException $e) {
                    $this->_run_error($e, $method);
                } catch (Exception $e) {
                    $this->_run_error($e, $method);
                }

                $this->hrl();
                $this->log("$call_info complete");
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }//end try

            if ($prompt_when_done) {
                $this->prompt(false, false);
            }
        }//end try_calling()

        /**
         * Show an error message and help output
         *
         *  - Used for errors while trying to run a method, so incorrect usage is suspected
         *  - Exits with a 500 error code
         *  - Can be confusing during development - turn on verbose mode to throw the original
         *     Exception as well for easier debugging.
         *
         * @param Exception $e      The exception object.
         * @param string    $method The method being called.
         *
         * @return void
         *
         * @throws Exception Throws Exception $e that was passed if running in verbose mode.
         */
        protected function _run_error(Exception $e, string $method)
        {
            $class = get_class($e);
            $error = in_array($class, ['Exception', 'HJSONException'])
                ? $e->getMessage()
                : "Incorrect usage - see method help below:";
            $this->error($error, false);
            $this->help($method);
            if ($this->verbose) {
                throw $e;
            }
            exit(500);
        }//end _run_error()

        /**
         * Help info for clear command
         *
         * @var mixed
         */
        protected $___clear = [
            "Clear the screen",
        ];

        /**
         * Method to clear the console screen
         *
         *  - Most useful in built-in CLI / prompt interface
         *
         * @api
         * @return void
         */
        public function clear()
        {
            $this->main_tool->clear();
        }//end clear()

        /**
         * Help info for exit command
         *
         * @var mixed
         */
        protected $___exit = [
            "Exit the command prompt",
        ];

        /**
         * Method to exit the tool / script runtime
         *
         *  - Most useful to exit built-in CLI / prompt interface
         *
         * @api
         * @return void
         */
        public function exit()
        {
            exit();
        }//end exit()

        /**
         * Help info for the help command itself
         *
         * @var mixed
         */
        protected $___help = [
            "Shows help/usage information.",
            ["Method/option for specific help", "string"],
        ];

        /**
         * Method to show help information
         *
         *  - Shows list of available commands and descriptions
         *  - Pass specific command to show more details for that command
         *
         *  - Shows list of commonly used configuration options
         *     (those not listed in ancestor-merged $HIDDEN_CONFIG_OPTIONS)
         *  - If verbose mode is on, will list ALL configuration options
         *
         * @param string $specific A specific method or option to show detailed help for.
         *
         * @api
         * @return void
         */
        public function help(string $specific = "")
        {
            // Specific help?
            $specific = trim($specific);
            if (!empty($specific)) {
                $this->_help_specific($specific);
                return;
            }

            $methods = static::getMergedProperty('METHODS');
            sort($methods);

            $this->version();

            $this->output("\nUSAGE:\n");

            $this->output(static::SHORTNAME . " <method> (argument1) (argument2) ... [options]\n");

            $this->hr('-');
            $this->output3col("METHOD", "INFO");
            $this->hr('-');

            foreach ($methods as $method) {
                $string = "";
                $help_text = "";
                $help = $this->_help_var($method, 'method');
                $help_text = empty($help) ? "" : array_shift($help);
                $this->output3col($method, $help_text);
            }

            $this->hr('-');
            $this->output("To get more help for a specific method:  " . static::SHORTNAME . " help <method>");

            $this->output("");
            $this->hr('-');
            $this->output3col("OPTION", "TYPE", "INFO");
            $this->hr('-');

            $hidden_options = static::getMergedProperty('HIDDEN_CONFIG_OPTIONS');

            foreach ($this->getPublicProperties() as $property) {
                if (!$this->verbose and in_array($property, $hidden_options)) {
                    continue;
                }
                $property = str_replace('_', '-', $property);
                $help = $this->_help_var($property, 'option');
                $type = "";
                $info = "";
                if ($help) {
                    $help = $this->_help_param($help);
                    $type = "($help[1])";
                    $info = $help[0];
                }
                $this->output3col("--$property", $type, $info);
            }
            $this->hr('-');
            $this->output("Use no- to set boolean option to false - eg. --no-stamp-lines");
            if (!$this->verbose) {
                $this->output($this->colorize("Less common options are hidden.  Use --verbose to show ALL options.", "yellow"));
            }
        }//end help()

        /**
         * Help info for prompt command
         *
         * @var mixed
         */
        protected $___prompt = [
            "Show interactive prompt"
        ];

        /**
         * Method to show interactive CLI prompt
         *
         *  - Typically used as default command to run, so that
         *     if no command passed, prompt is shown
         *
         * @param mixed $clear Pass truthy value to clear screen before showing prompt.
         * @param mixed $help  Pass trhthy value to show help at start of prompt.
         *
         * @return void
         */
        public function prompt(mixed $clear = false, mixed $help = true)
        {
            if ($clear) {
                $this->clear();
            }

            if ($help) {
                $this->hr();
                $this->output("Enter 'help' to list valid commands");
            }

            $this->hr();
            $command_string = $this->input("cmd");
            $arg_list = explode(" ", $command_string);

            $this->try_calling($arg_list, false, true);
        }//end prompt()

        /**
         * Helper method  for 'help' command - shows help details for a specific subcommand
         *
         * @param string $specific A specific method or option to show detailed help for.
         *
         * @return void
         */
        protected function _help_specific(string $specific)
        {
            $help = $this->_help_var($specific);
            if (empty($help)) {
                $this->error("No help found for '$specific'");
            }

            $specific = str_replace('-', '_', $specific);

            if (isset($this->$specific)) {
                // Option info
                $help_param = $this->_help_param($help);
                $specific = str_replace('_', '-', $specific);

                $this->hr('-');
                $this->output3col("OPTION", "(TYPE)", "INFO");
                $this->hr('-');
                $this->output3col("--$specific", "($help_param[1])", $help_param[0]);
                $this->hr('-');
            } elseif (is_callable([$this, $specific])) {
                // Method Usage
                $help_text = array_shift($help);

                $usage = static::SHORTNAME . " $specific";
                $params = $this->_getMethodParams($specific);
                foreach ($params as $p => $param) {
                    $help_param = $this->_help_param($help[$p]);

                    $param = $help_param['string']
                        ? "\"$param\""
                        : $param;

                    $param = $help_param['optional']
                        ? "($param)"
                        : $param;

                    $usage .= " $param";
                }

                $usage .= " [options]";

                $this->output("USAGE:\n");
                $this->output("$usage\n");

                $this->hr('-');
                $this->output3col("METHOD", "INFO");
                $this->hr('-');
                $this->output3col($specific, $help_text);
                $this->hr('-');
                $this->br();

                if (!empty($params)) {
                    $this->hr('-');
                    $this->output3col("PARAMETER", "TYPE", "INFO");
                    $this->hr('-');
                    foreach ($params as $p => $param) {
                        $help_param = $this->_help_param($help[$p]);
                        $output = $help_param['optional'] ? "" : "*";
                        $output .= $param;
                        $this->output3col($output, "($help_param[1])", $help_param[0]);
                    }
                    $this->hr('-');
                    $this->output("* Required parameter");
                }
            }//end if
        }//end _help_specific()


        /**
         * Helper method for _help_specific - get the help var (parameter) for specific method or option
         *
         * @param string $specific A specific method or option to show detailed help for.
         * @param string $type     Type to look for - 'method' or 'option' (will check both by default).
         *
         * @return mixed help information, or empty string if none found
         */
        protected function _help_var(string $specific, string $type = ""): mixed
        {
            $help = "";
            $specific = str_replace('-', '_', $specific);

            if ($type == 'method' or empty($type)) {
                $help_var = "___" . $specific;
            }

            if ($type == 'option' or (empty($type) and empty($this->$help_var))) {
                $help_var = "__" . $specific;
            }

            if (!empty($this->$help_var)) {
                $help = $this->$help_var;
                if (!is_array($help)) {
                    $help = [$help];
                }
            }

            return $help;
        }//end _help_var()

        /**
         * Clean up / standardize a help parameter - fill in defaults from defaults
         *
         * @param mixed $param The original parameter value to clean up.
         *
         * @return string The cleaned paramater value.
         */
        protected function _help_param(mixed $param): array
        {
            if (!is_array($param)) {
                $param = [$param];
            }

            if (empty($param[1])) {
                $param[1] = "boolean";
            }

            if (empty($param[2])) {
                $param[2] = "optional";
            }

            $param['optional'] = ($param[2] == 'optional');
            $param['required'] = !$param['optional'];

            $param['string'] = ($param[1] == 'string');

            return $param;
        }//end _help_param()

        /**
         * Get static property by merging up with ancestor values
         *
         *  - Elsewhere referred to as 'ancestor-merge'
         *  - The value of the specified property (on the class and each ancestor) is expected to be an array.
         *
         * @param string $property The name of the property to merge.
         *
         * @return array The resulting merged value.
         */
        protected static function getMergedProperty(string $property): array
        {
            $merged_array = [];
            $class = get_called_class();
            while ($class and class_exists($class)) {
                if (isset($class::$$property)) {
                    $merged_array = array_merge($merged_array, $class::$$property);
                    $class = get_parent_class($class);
                }
            }

            // If integer keys, then make sure array values are uniuqe
            if (is_int(array_key_first($merged_array))) {
                $merged_array = array_unique($merged_array);
            }

            return $merged_array;
        }//end getMergedProperty()

        /**
         * Merge arrays recursively, in a special way
         *
         * Primarily, we are expecting meaningful keys - eg. option arrays, commands/subcommands, etc. So, we:
         *  - Start with array1
         *  - Check each key - if that key exists in array2, overwrite with array2's value, UNLESS:
         *     - If both values are an array, merge the values instead - recursively
         *  - Last, add keys that are in array2 only
         *
         * @param array $array1 Original array to merge values into - values may be overwritten by array2.
         * @param array $array2 Array to merge into original - values may overwrite array1.
         *
         * @return array The resulting merged array.
         */
        protected function mergeArraysRecursively(array $array1, array $array2): array
        {
            $merged_array = [];
            foreach ($array1 as $key => $value1) {
                if (isset($array2[$key])) {
                    if (is_array($array1[$key]) and is_array($array2[$key])) {
                        $merged_array[$key] = $this->mergeArraysRecursively($array1[$key], $array2[$key]);
                    } else {
                        $merged_array[$key] = $array2[$key];
                        unset($array2[$key]);
                    }
                } else {
                    $merged_array[$key] = $value1;
                }
            }
            foreach ($array2 as $key => $value2) {
                $merged_array[$key] = $value2;
            }

            return $merged_array;
        }//end mergeArraysRecursively()

        /**
         * Magic handling for subcommands to call main command methods
         *
         *  - Primarly used as an organization tool
         *  - Allows us to keep some methods in console_abstract and still have them available in other places
         *  - FWIW, not super happy with this approach, but it works for now
         *
         * @param string $method    The method that is being called.
         * @param array  $arguments The arguments being passed to the method.
         *
         * @throws Exception If the method can't be found on the "main_tool" instance.
         *
         * @return mixed If able to call the method on the "main_tool" (instance of Console_Abstract) then, return the value from calling that method.
         */
        public function __call(string $method, array $arguments = []): mixed
        {
            $callable = [$this->main_tool, $method];
            if (is_callable($callable)) {
                return call_user_func_array($callable, $arguments);
            }

            throw new Exception("Invalid class method '$method'");
        }//end __call()
    }//end class

}//end if

// Note: leave this for packaging
?>
