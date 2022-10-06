<?php
/**
 * Defines Command_Visual class
 *
 * @package pcon
 * @author  chrisputnam9
 */

if (!class_exists("Command_Visual")) {

    /**
     * Command_Visual abstract class
     *
     *  - A class to present a Visual interface in response to some command
     *  - Provides a structure for commands that show data / lists / etc. visually
     *  - Provides subcommand structure for interacting with the data via keystrokes
     */
    class Command_Visual extends Command
    {
        /**
         * Available subcommands / keystrokes
         *
         * @var array
         */
        public $commands = [];

        /**
         * Reload function to be called when needed
         *
         *  - Passed to constructor via options
         *
         * @var callable
         */
        public $reload_function;

        /**
         * Optional data to be passed to the reload function
         *
         *  - Passed to constructor via options
         *
         * @var callable
         */
        public $reload_data;

        /**
         * Constructor
         *
         * @param Console_Abstract $main_tool The instance of the main tool class
         *  - which should extend Console_Abstract.
         * @param array            $options   Array of options to initialize.
         *
         * @return void
         */
        public function __construct(Console_Abstract $main_tool, array $options = [])
        {
            parent::__construct($main_tool);

            if (empty($options['reload_function']) or !is_callable($options['reload_function'])) {
                $this->error("Option 'reload_function' is required");
            }

            $this->reload_function = $options['reload_function'];
            if (isset($options['reload_data'])) {
                $this->reload_data = $options['reload_data'];
            }

            $this->commands = [
                'help' => [
                    'description' => 'Help - list available commands',
                    'keys' => '?',
                    'callback' => [$this, 'help'],
                ],
                'reload' => [
                    'description' => 'Reload - refresh this view',
                    'keys' => 'r',
                    'callback' => [$this, 'reload'],
                ],
                'quit' => [
                    'description' => 'Quit - exit this view',
                    'keys' => 'q',
                    'callback' => [$this, 'quit'],
                ],
            ];
            if (isset($options['commands'])) {
                $this->commands = $this->mergeArraysRecursively($this->commands, $options['commands']);
            }
            $this->cleanCommandArray($this->commands);
        }//end __construct()


        /**
         * Clean an array of commands
         *
         *  - Make sure keys are set properly as array of single keys
         *
         * @param array $commands Array of commands to be cleaned. Passed by reference.
         *
         * @return void
         */
        protected function cleanCommandArray(array &$commands)
        {
            foreach ($commands as $command_slug => $command_details) {
                if (is_string($command_details['keys'])) {
                    $command_details['keys'] = str_split($command_details['keys']);
                }
                if (!is_array($command_details['keys'])) {
                    $this->error("Invalid command keys for '$command_slug'");
                }

                if (
                    isset($command_details['callback'])
                    and is_array($command_details['callback'])
                    and isset($command_details['callback']['subcommands'])
                ) {
                    $this->cleanCommandArray($command_details['callback']['subcommands']);
                }

                $commands[$command_slug] = $command_details;
            }
        }//end cleanCommandArray()


        /**
         * Prompt for input and run the requested command if valid
         *
         *  - Expected to be called by a child class - eg. Command_Visual_List
         *
         * @param array $commands      The commands to select from.
         * @param mixed $show_commands Whether to show the available commands.
         *
         * @return boolean Whether or not to continue the command prompt loop
         */
        protected function promptAndRunCommand(array $commands, mixed $show_commands = false): bool
        {
            if (!is_array($commands) or empty($commands)) {
                $this->error("Invalid commands passed - expecting array of command definitions");
            }

            if ($show_commands) {
                foreach ($commands as $key => $details) {
                    $name = $details['description'];
                    $keys = $details['keys'];
                    $this->output(str_pad(implode(",", $keys) . " ", 15, ".") . " " . $name);
                }
            }

            $input = $this->input(true, null, false, 'single', 'hide_input');
            $matched = false;

            foreach ($commands as $command_slug => $command_details) {
                $command_name = $command_details['description'];
                $command_keys = $command_details['keys'];
                $command_callable = $command_details['callback'];

                if (in_array($input, $command_keys)) {
                    $matched = true;

                    if (! is_callable($command_callable)) {
                        if (is_array($command_callable)) {
                            if (isset($command_callable['subcommands'])) {
                                while (true) {
                                    $this->clear();
                                    $this->hr();
                                    $this->output("$command_name:");
                                    $this->hr();
                                    $continue_loop = $this->promptAndRunCommand($command_callable['subcommands'], true);

                                    if (
                                        $continue_loop === false
                                        or (isset($command_details['continue']) and $command_details['continue'] === false)
                                    ) {
                                        // Finish - but not a failure
                                        return true;
                                    }
                                }
                            }
                        }

                        $this->error("Uncallable method for $input", false, true);
                        return true;
                    }//end if

                    $continue_loop = call_user_func($command_callable);

                    // Reload if set
                    if (!empty($command_details['reload'])) {
                        $this->reload();
                    }

                    if (
                        $continue_loop === false
                        or (isset($command_details['continue']) and $command_details['continue'] === false)
                    ) {
                        return false;
                    }

                    return true;
                }//end if
            }//end foreach

            if (!$matched) {
                $this->log("Invalid input $input");
            }

            return true;
        }//end promptAndRunCommand()


        /****************************************************
         * BUILT-IN COMMANDS
         ***************************************************/

        /**
         * Help for the visual interface
         *
         *  - Lists all available commands to run in this area
         *
         * @param string $specific A specific method or option to show detailed help for. NOT YET IMPLEMENTED - specified mainly to line up with parent method.
         *
         * @api
         * @return void
         */
        public function help(string $specific = "")
        {
            $this->clear();
            $this->hr();
            $this->output("Available Commands:");
            $this->hr();
            foreach ($this->commands as $command_slug => $command_details) {
                $command_name = $command_details['description'];
                $command_keys = $command_details['keys'];
                $this->output(str_pad(implode(",", $command_keys) . " ", 15, ".") . " " . $command_name);
            }
            $this->hr();
            $this->input("Hit any key to exit help", null, false, true);
        }//end help()

        /**
         * Exit the visual interface
         *
         *  - Will return to previous area (eg. main prompt or exit tool perhaps)
         *  - Returns false statically to let the prompt loop know not to continue
         *
         * @api
         * @return false
         */
        public function quit()
        {
            return false;
        }//end quit()

        /**
         * Reload the visual interface
         *
         *  - Calls the configured reload_function with optional reload_data if any
         *
         * @api
         * @return mixed Result of reload function call - can vary based on context.
         */
        public function reload()
        {
            return call_user_func($this->reload_function, $this->reload_data, $this);
        }//end reload()
    }//end class

}//end if

// Note: leave this for packaging
?>
