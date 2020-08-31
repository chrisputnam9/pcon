<?php
/**
 * Visual Command type
 *  - Think visual mode in vim
 *  - single letter commands, or sequences thereof
 */
class Command_Visual extends Command
{
    public $commands = [];

    public $reload_function;
    public $reload_data;

    /**
     * Constructor
     */
    public function __construct($main_tool, $reload_function, $reload_data=[], $options=[])
    {
        parent::__construct($main_tool);

        if (empty($reload_function) or !is_callable($reload_function))
        {
            $this->error("Argument reload_function is required");
        }

        $this->reload_function = $reload_function;
        $this->reload_data = $reload_data;

    }

    /**
     * Clean an array of commands
     *  - Make sure keys are set properly as array of single keys
     */
    protected function cleanCommandArray(&$commands)
    {
        foreach ($commands as $command_slug => $command_details)
        {
            if (is_string($command_details['keys'])) $command_details['keys'] = str_split($command_details['keys']);
            if (!is_array($command_details['keys'])) $this->error("Invalid command keys for '$command_slug'");

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
    protected function promptAndRunCommand($commands, $show_options=false)
    {
        if (!is_array($commands) or empty($commands))
        {
            $this->error("Invalid commands passed - expecting array of command definitions");
        }

        if ($show_options)
        {
            foreach ($commands as $key => $details)
            {
                $name = $details['description'];
                $keys = $details['keys'];
                $this->output( str_pad( implode( ",", $keys) . " ", 15, ".") . " " . $name );
            }
        }

        $input = $this->input(true, null, false, 'single', 'hide_input');
        $matched = false;

        foreach ($commands as $command_slug => $command_details)
        {
            $command_name = $command_details['description'];
            $command_keys = $command_details['keys'];
            $command_callable = $command_details['callback'];

            if (in_array($input, $command_keys))
            {
                $matched = true;

                if ( ! is_callable($command_callable))
                {
                    if (is_array($command_callable))
                    {
                        if (isset($command_callable['subcommands']))
                        {
                            while (true)
                            {
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
                if (!empty($command_details['reload']))
                {
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

        if (!$matched)
        {
            $this->log("Invalid input $input");
        }
    }

    // Reload
    public function reload()
    {
        return call_user_func($this->reload_function, $this->reload_data, $this);
    }
}
