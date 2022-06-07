<?php

/**
 * Visual Command type
 *  - Think visual mode in vim
 *  - single letter commands, or sequences thereof
 */

if (!class_exists("Command_Visual")) {
    class Command_Visual extends Command
    {
        public $commands = [];

        public $reload_function;
        public $reload_data;

        /**
        * Constructor
        */
        public function __construct($main_tool, $options = [])
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
                    'description' => 'Reload - refresh list',
                    'keys' => 'r',
                    'callback' => [$this, 'reload'],
                ],
                'quit' => [
                    'description' => 'Quit - exit the list',
                    'keys' => 'q',
                    'callback' => [$this, 'quit'],
                ],
            ];
            if (isset($options['commands'])) {
                $this->commands = $this->mergeArraysRecursively($this->commands, $options['commands']);
            }
            $this->cleanCommandArray($this->commands);
        }

        /**
        * Clean an array of commands
        *  - Make sure keys are set properly as array of single keys
        */
        protected function cleanCommandArray(&$commands)
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
        }

        /**
        * Prompt for input and run the associated command if valid
        * @param $commmands - the commands to select from
        * @return boolean whether command was valid or not
        */
        protected function promptAndRunCommand($commands, $show_options = false)
        {
            if (!is_array($commands) or empty($commands)) {
                $this->error("Invalid commands passed - expecting array of command definitions");
            }

            if ($show_options) {
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
                                        return;
                                    }
                                }
                            }
                        }

                        $this->error("Uncallable method for $input", false, true);
                        return true;
                    }

                    $continue_loop = call_user_func($command_callable, $this);

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
                }
            }

            if (!$matched) {
                $this->log("Invalid input $input");
            }
        }

        /**
        * Built-in commands
        */

        // Help
        public function help($specific = false)
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
        }

        // Quit
        public function quit()
        {
            return false; // Back to previous area, basically
        }

        // Reload
        public function reload()
        {
            return call_user_func($this->reload_function, $this->reload_data, $this);
        }
    }
}

// Note: leave this for packaging ?>
