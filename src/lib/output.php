<?php

class Output extends Util_Abstract
{

    /**
     * Constructor
     */
    public function __construct($console_instance)
    {
        parent::__construct($console_instance);
    }

	/**
	 * Error output
     * 
     * Code Guidelines:
     *  - 100 - expected error - eg. aborted due to user input
     *  - 200 - safety / caution error (eg. running as root)
     *  - 500 - misc. error
	 */
	public function error($data, $code=500, $prompt_to_continue=false)
	{
        $this->hr('!');
		$this->output('ERROR: ', false);
		$this->output($data);
        $this->hr('!');
		if ($code)
		{
			exit($code);
		}

        if ($prompt_to_continue)
        {
            $yn = $this->input("Continue? (y/n)", 'n', false, true);
            if (!in_array($yn, ['y', 'Y']))
            {
                $this->error('Aborted', 100);
            }
        }
	}

	/**
	 * Warn output
     * @param $data to output as warning
     * @param $prompt_to_continue - whether to prompt with Continue? y/n
	 */
	public function warn($data, $prompt_to_continue=false)
	{
        $this->hr('*');
		$this->output('WARNING: ', false);
		$this->output($data, true, false);
        $this->hr('*');

        if ($prompt_to_continue)
        {
            $yn = $this->input("Continue? (y/n)", 'n', false, true);
            if (!in_array($yn, ['y', 'Y']))
            {
                $this->error('Aborted', 100);
            }
        }

	}

    /**
     * Logging output - only when verbose=true
     */
    public function log($data)
    {
        if (!$this->verbose) return;
        
        $this->output($data);
    }

    /**
     * Output data
     */
    public function output($data, $line_ending=true, $stamp_lines=null)
    {
        $data = $this->stringify($data);

        $stamp_lines = is_null($stamp_lines) ? $this->stamp_lines : $stamp_lines;
		if ($stamp_lines)
			echo $this->stamp() . ' ... ';

		echo $data . ($line_ending ? "\n" : "");
    }

    /**
     * Progress Bar Output
     */
    public function outputProgress($count, $total, $description = "remaining")
    {
        if (!$this->verbose)
        {
            if ($count > 0)
            {
                // Set cursor to first column
                echo chr(27) . "[0G";
                // Set cursor up 2 lines
                echo chr(27) . "[2A";
            }

            $full_width = $this->getTerminalWidth();
            $pad = $full_width - 1;
            $bar_count = floor(($count * $pad) / $total);
            $output = "[";
            $output = str_pad($output, $bar_count, "|");
            $output = str_pad($output, $pad, " ");
            $output.= "]";
            $this->output($output);
            $this->output(str_pad("$count/$total", $full_width, " ", STR_PAD_LEFT));
        }
        else
        {
            $this->output("$count/$total $description");
        }
    }

    /**
     * Output 3 Columns - for help for example
     */
    public function output3col($col1, $col2=null, $col3=null)
    {
        $full_width = $this->getTerminalWidth();
        $col1_width = floor(($full_width * static::COL1_WIDTH) / 100);
        $col2_width = floor(($full_width * static::COL2_WIDTH) / 100);

        $string = str_pad($col1, $col1_width, " ");
        if (!is_null($col2))
        {
            $string.= "| " . $col2;
        }
        if (!is_null($col3))
        {
            $string = str_pad($string, $col2_width, " ") . "| " . $col3;
        }
        $string = str_pad("| $string", $full_width-1) . "|";
        $this->output($string);
    }

    /**
     * Output break
     */
    public function br()
    {
        $this->output('');
    }

    /**
     * br, but only if logging is on
     */
    public function brl()
    {
        if (!$this->verbose) return;

        $this->br;
    }
    /**
     * Output horizonal line - divider
     */
    public function hr($c='=', $prefix="")
    {
        $string = str_pad($prefix, $this->getTerminalWidth(), $c);
        $this->output($string);
    }
    /**
     * hr, but only if logging is on
     */
    public function hrl($c='=', $prefix="")
    {
        if (!$this->verbose) return;

        $this->hr($c, $prefix);
    }

}
Command_Abstract::$util_classes[]="Output";
