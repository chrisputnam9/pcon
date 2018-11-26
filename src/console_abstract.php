<?php
/**
 * Console Abstract to be extended by specific console tools
 */

// Global Constants
if (!defined('DS'))
{
    define('DS', DIRECTORY_SEPARATOR);
}

if (defined('ERRORS') and ERRORS)
{
    // Enable and show errors
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

/**
 * Console Abstract
 * Reusable abstract for creating PHP console utilities
 */
class Console_Abstract
{
    /**
     * Padding for output
     */
    const PAD_FULL = 100;
    const PAD_COL1 = 25;
    const PAD_COL2 = 40;

    /**
     * Callable Methods
     */
    protected static $METHODS = [
        'help',
    ];

	/**
	 * Config/option defaults
	 */
    protected $__stamp_lines = "Stamp output lines";
	public $stamp_lines = false;

    protected $__step = "Enable stepping points";
	public $step = false;

    protected $__verbose = "Enable verbose output";
	public $verbose = false;

    protected $__timezone = ["Timezone - from http://php.net/manual/en/timezones.", "string"];
    public $timezone = "US/Eastern";

    /**
     * Config paths
     */
    protected $config_dir = null;
    protected $config_file = null;

    /**
     * Stuff
     */
    protected $dt = null;
    protected $run_stamp = '';

    /**
     * Constructor - set up basics
     */
    public function __construct()
    {
        date_default_timezone_set($this->timezone);
        $this->run_stamp = $this->stamp();
    }

    /**
     * Run - parse args and run method specified
     */
    public static function run($argv)
    {
        $class = get_called_class();

        $script = array_shift($argv);
        $method = array_shift($argv);

        $instance = new $class();

        try
        {
            $instance->initConfig();

            $valid_methods = array_merge($class::$METHODS, self::$METHODS);
            if (!in_array($method, $valid_methods))
            {
                $instance->help();
                $instance->hr();
                $instance->error("Invalid method - $method");
            }

            $args = [];
            foreach ($argv as $_arg)
            {
                if (strpos($_arg, '--') === 0)
                {
                    $arg = substr($_arg,2);
                    $arg_split = explode("=",$arg,2);

                    if (!isset($arg_split[1]))
                    {
                        $arg_split[1] = true;
                    }

                    $instance->configure($arg_split[0], $arg_split[1]);
                }
                else
                {
                    $args[]= $_arg;
                }
            }

            date_default_timezone_set($instance->timezone);

            $call_info = "$class->$method(" . implode(",", $args) . ")";
            $instance->log("Calling $call_info");
            $instance->hrl();

            call_user_func_array([$instance, $method], $args);

            $instance->hrl();
            $instance->log("$call_info complete");

        } catch (Exception $e) {
            $instance->error($e->getMessage());
        }
    }

    protected $___help = [
        "Shows help/usage information.",
        ["Method/option for specific help", "string"],
    ];
    public function help($specific=false)
    {
        // Specific help?
        if ($specific) return $this->_help_specific($specific);

        $methods = array_merge(static::$METHODS, self::$METHODS);

        $this->output("USAGE:\n");
        $this->output(static::SHORTNAME." <method> (argument1) (argument2) ... [options]\n");

        $this->hr('-');
        $this->output3col("METHOD", "INFO");
        $this->hr('-');

        foreach($methods as $method)
        {
            $string = "";
            $help_text = "";
            $help = $this->_help_var($method, 'method');
            $help_text = empty($help) ? "" : array_shift($help);
            $this->output3col($method, $help_text);
        }

        $this->hr('-');
        $this->output("To get more help for a specific method:  ".static::SHORTNAME." help <method>");

        $this->output("");
        $this->hr('-');
        $this->output3col("OPTION", "TYPE", "INFO");
        $this->hr('-');
        foreach ($this->getPublicProperties() as $property)
        {
            $property = str_replace('_', '-', $property);
            $help = $this->_help_var($property, 'option');
            $type = "";
            $info = "";
            if ($help)
            {
                $help = $this->_help_param($help);
                $type = "($help[1])";
                $info = $help[0];
            }
            $this->output3col("--$property", $type, $info);
        }
        $this->hr('-');
        $this->output("Use no- to set boolean option to false - eg. --no-stamp-lines");
    }

        /**
        * Show help for a specific method or option
        */
        protected function _help_specific($specific)
        {
            $help = $this->_help_var($specific);
            if (empty($help))
            {
                $this->error("No help found for '$specific'");
            }

            $specific = str_replace('-', '_', $specific);

            if (is_callable(array($this, $specific)))
            {
                // Method Usage
                $help_text = array_shift($help);

                $usage = static::SHORTNAME." $specific";
                $params = $this->_getMethodParams($specific);
                foreach ($params as $p => $param)
                {
                    $help_param = $this->_help_param($help[$p]);

                    $param = $help_param['string']
                        ? "\"$param\""
                        : $param;

                    $param = $help_param['optional']
                        ? "($param)"
                        : $param;

                    $usage.= " $param";
                }

                $usage.= " [options]";

                $this->output("USAGE:\n");
                $this->output("$usage\n");

                $this->hr('-');
                $this->output3col("METHOD", "INFO");
                $this->hr('-');
                $this->output3col($specific, $help_text);
                $this->hr('-');
                $this->br();

                if (!empty($params))
                {
                    $this->hr('-');
                    $this->output3col("PARAMETER", "TYPE", "INFO");
                    $this->hr('-');
                    foreach ($params as $p => $param)
                    {
                        $help_param = $this->_help_param($help[$p]);
                        $output = $help_param['optional'] ? "" : "*";
                        $output.= $param;
                        $this->output3col($output, "($help_param[1])", $help_param[0]);
                    }
                    $this->hr('-');
                    $this->output("* Required parameter");
                }
            }
            else if (isset($this->$specific))
            {
                // Option info
                $help_param = $this->_help_param($help);
                $specific = str_replace('_', '-', $specific);

                $this->hr('-');
                $this->output3col("OPTION", "(TYPE)", "INFO");
                $this->hr('-');
                $this->output3col("--$specific", "($help_param[1])", $help_param[0]);
                $this->hr('-');
            }
        }

        /**
        * Get help var for specific method or option
        */
        protected function _help_var($specific, $type=false)
        {
            $help = false;
            $specific = str_replace('-', '_', $specific);

            if ($type == 'method' or empty($type))
            {
                $help_var = "___" . $specific;
            }

            if ($type == 'option' or (empty($type) and empty($this->$help_var)))
            {
                $help_var = "__" . $specific;
            }

            if (!empty($this->$help_var))
            {
                $help = $this->$help_var;
                if (!is_array($help))
                {
                    $help = [$help];
                }
            }
            return $help;
        }

        /**
         * Clean help param - fill in defaults
         */
        protected function _help_param ($param)
        {
            if (!is_array($param))
            {
                $param = [$param];
            }

            if (empty($param[1]))
            {
                $param[1] = "boolean";
            }

            if (empty($param[2]))
            {
                $param[2] = "optional";
            }

            $param['optional'] = ($param[2] == 'optional');
            $param['required'] = !$param['optional'];

            $param['string'] = ($param[1] == 'string');

            return $param;
        }


    /**
     * Exec - run bash command
     *  - run a command
     *  - return the output
     * @param $error - if true, throw error on bad return
     */
    public function exec($command, $error=false)
    {
        $this->log("exec: $command 2>&1");
        exec($command, $output, $return);
        $output = empty($output) ? "" : "\n\t" . implode("\n\t", $output);
        if ($return and $error)
        {
            $output = empty($output) ? $return : $output;
            $this->error($output);
        }
        $this->log($output);
        return $output;
    }

	/**
	 * Error output
	 */
	public function error($data, $code=500)
	{
        $this->hr('!');
		$this->output('ERROR: ', false);
		$this->output($data);
        $this->hr('!');
		if ($code)
		{
			exit($code);
		}
	}

	/**
	 * Warn output
	 */
	public function warn($data)
	{
        $this->hr('*');
		$this->output('WARNING: ', false);
		$this->output($data, true, false);
        $this->hr('*');
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
        if (is_object($data) or is_array($data))
        {
            $data = print_r($data, true);
        }
        else if (is_bool($data))
        {
            $data = $data ? "(Bool) True" : "(Bool) False";
        }
        else if (!is_string($data))
        {
            ob_start();
            var_dump($data);
            $data = ob_get_clean();
        }

        $stamp_lines = is_null($stamp_lines) ? $this->stamp_lines : $stamp_lines;
		if ($stamp_lines)
			echo $this->stamp() . ' ... ';

		echo $data . ($line_ending ? "\n" : "");
    }

    /**
     * Output 3 Columns - for help for example
     */
    public function output3col($col1, $col2=null, $col3=null, $line_ending=true, $stamp_lines=null)
    {
        $string = str_pad($col1, static::PAD_COL1, " ");
        if (!is_null($col2))
        {
            $string.= "| " . $col2;
        }
        if (!is_null($col3))
        {
            $string = str_pad($string, static::PAD_COL2, " ") . "| " . $col3;
        }
        $string = str_pad("| $string", static::PAD_FULL-1) . "|";
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
        $string = str_pad($prefix, static::PAD_FULL, $c);
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


    /**
     * Pause during output for debugging/stepthrough
     */
    public function pause($message="[ ENTER TO STEP | 'FINISH' TO CONTINUE ]")
    {
        if (!$this->step) return;

        $this->hr();
        $this->output($message);
        $this->hr();

        $line = $this->input();

        if (strtolower(trim($line)) == 'finish')
        {
            $this->step = false;
        }
    }

    /**
     * Get selection from list - from CLI
     * @param (array) $list of items to pick from
     * @param (any) $message (none) to show - prompt
     * @param (int) $default (0) index if no input
     */
    public function select($list, $message=false,$default=0)
    {
        $list = array_values($list);
        foreach ($list as $i => $item)
        {
            $this->output("$i. $item");
        }

        $max = count($list)-1;
        $s=-1;
        $first = true;
        while ($s < 0 or $s > $max)
        {
            if (!$first)
            {
                $this->warn("Invalid selection $s");
            }
            $s = (int) $this->input($message, $default);
            $first = false;
        }

        return $list[$s];
    }

    /**
     * Confirm yes/no
     * @param $message to show - yes/no question
     * @param $default (y) default if no input
     * @return (bool) true/false
     */
    public function confirm($message, $default='y')
    {
        $yn = $this->input($message, $default);

        // True if first letter of response is y or Y
        return strtolower(substr($yn,0,1)) == 'y';
    }

    /**
     * Get input from CLI
     * @param $message to show - prompt
     * @param $default if no input
     * @return input text or default
     */
    public function input($message=false, $default=null, $required=false, $single=false)
    {
        if ($message)
        {
            if (!is_null($default))
            {
                $message.= " ($default)";
            }
            $message.= ": ";
        }

        while (true)
        {
            $this->output($message, false);
            if ($single)
            {
                $line = strtolower( trim( `bash -c "read -n 1 -t 10 INPUT ; echo \\\$INPUT"` ) );
                $this->output('');
                // $line = fgetc($handle);
            }
            else
            {
                $handle = $this->getCliInputHandle();
                $line = fgets($handle);
            }
            $line = trim($line);

            // Entered input - return
            if (!empty($line)) return $line;

            // Input not required? Return default
            if (!$required) return $default;

            // otherwise, warn, loop and try again
            $this->warn("Input required - please try again");
        }


    }

    /**
     * Get timestamp
     */
    public function stamp()
    {
        return date('Y-m-d_H.i.s');
    }

    /**
     * Get Config Dir
     */
    public function getConfigDir()
    {
        if (is_null($this->config_dir))
        {
            $this->config_dir = $_SERVER['HOME'] . DS . '.' . static::SHORTNAME;
        }

        return $this->config_dir;
    }

    /**
     * Get Config File
     */
    public function getConfigFile()
    {
        if (is_null($this->config_file))
        {
            $config_dir = $this->getConfigDir();
            $this->config_file = $config_dir . DS . 'config.json';
        }

        return $this->config_file;
    }

    /**
     * Init/Load Config File
     */
    public function initConfig()
    {
        $config_dir = $this->getConfigDir();
        $config_file = $this->getConfigFile();

        try
        {
            if (!is_dir($config_dir))
            {
                // $this->log("Creating directory - $config_dir");
                mkdir($config_dir, 0755);
            }

            if (is_file($config_file))
            {
                // $this->log("Loading config file - $config_file");
                $json = file_get_contents($config_file);
                $config = json_decode($json, true);
                if (empty($config))
                {
                    $this->error("Likely Syntax Error: $config_file");
                }
                foreach ($config as $key => $value)
                {
                    $this->configure($key, $value);
                }
            }
            else
            {
                // $this->log("Creating default config file - $config_file");
                $config = [];
                foreach ($this->getPublicProperties() as $property)
                {
                    $config[$property] = $this->$property;
                }
            }

            // Rewrite config - pretty print
            ksort($config);
            $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($config_file, $json);
        }
        catch (Exception $e)
        {
            // Notify user
            $this->output('NOTICE: ' . $e->getMessage());
        }
    }

    /**
     * Prepare shell argument for use
     * @param $value to prep
     * @param $default to return if $value is empty
     * @param $force_type - whether to force a type for return value: 
     *  'array': split and/or wrap to force it to be an array
     *  'boolean': parse as boolean (1/true/yes).
     * Note: defaults to 'array' if $default is an array
     * @param $trim (true) - whether to trim whitespace from value(s)
     */
    public function prepArg($value, $default, $force_type=null, $trim=true)
    {
        $a = func_num_args();
        if ($a < 2) $this->error('prepArg requires value & default');

        if (is_null($force_type))
        {
            if (is_array($default))
            {
                $force_type = 'array';
            }
        }

        if ($force_type == 'bool')
        {
            $force_type = 'boolean';
        }

        // For backwards compatibility
        if ($force_type === true)
        {
            $force_type = 'array';
        }

        // Default?
        if (empty($value))
        {
            $value = $default;
        }

        // Change to array if needed
        if (is_string($value) and $force_type=='array')
        {
            $value = explode(",", $value);
        }

        // Trim
        if ($trim)
        {
            if (is_string($value))
            {
                $value = trim($value);
            }
            else if (is_array($value))
            {
                $value = array_map('trim', $value);
            }
        }

        if ($force_type == 'boolean')
        {
            $value = in_array($value, array(true, 'true', 'yes', '1', 1));
        }

        return $value;
    }

    /**
     * Configure property - if public
     */
    public function configure($key, $value)
    {
        $key = str_replace('-', '_', $key);

        if (substr($key, 0, 3) == 'no_' and $value === true)
        {
            $key = substr($key, 3);
            $value = false;
        }

        $public_properties = $this->getPublicProperties();
        if (in_array($key, $public_properties))
        {

            $value = preg_replace('/^\~/', $_SERVER['HOME'], $value);

            $this->{$key} = $value;
        }
        else
        {
            $this->output("NOTICE: invalid config key - $key");
        }
    }

    // Get basic curl
    public function getCurl($url)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        
        return $ch;
    }

    /**
     * Get parameters for a given method
     */
    protected function _getMethodParams($method)
    {
        $r = new ReflectionObject($this);
        $rm = $r->getMethod($method);
        $params = [];
        foreach ($rm->getParameters() as $param)
        {
            $params[]=$param->name;
        }
        return $params;
    }

    // Manage Properties
    protected $_public_properties = null;
    public function getPublicProperties()
    {
        if (is_null($this->_public_properties))
        {
            $this->_public_properties = [];
            $reflection = new ReflectionObject($this);
            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop)
            {
                $this->_public_properties[]= $prop->getName();
            }
        }

        return $this->_public_properties;

    }

    // Manage CLI Input Handle
    protected $_cli_input_handle = null;
    protected function getCliInputHandle()
    {
        if (is_null($this->_cli_input_handle))
        {
            $this->_cli_input_handle = fopen ("php://stdin","r");
        }

        return $this->_cli_input_handle;
    }
    protected function close_cli_input_handle()
    {
        if (!is_null($this->_cli_input_handle))
        {
            fclose($this->_cli_input_handle);
        }
    }

}

// For working locally
if (!empty($src_includes) and is_array($src_includes))
{
    foreach ($src_includes as $src_include)
    {
        require_once ($src_include);
    }
}

?>
