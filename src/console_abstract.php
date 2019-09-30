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
        'backup',
        'help',
        'install',
        'update',
        'version',
    ];

    /**
     * Methods that are OK to run as root without warning
     */
    protected static $ROOT_METHODS = [
        'help',
        'install',
        'update',
        'version',
    ];

	/**
	 * Config/option defaults
	 */
    protected $__allow_root = "OK to run as root";
    protected $allow_root = false;

    protected $__backup_age_limit = ["Age limit of backups to keep- number of days", "string"];
    public $backup_age_limit = '30';

    protected $__backup_dir = ["Default backup directory", "string"];
    public $backup_dir = null;

    protected $__install_path = ["Install path of this tool", "string"];
	public $install_path = "/usr/local/bin";

    protected $__stamp_lines = "Stamp output lines";
	public $stamp_lines = false;

    protected $__step = "Enable stepping points";
	public $step = false;

    protected $__timezone = ["Timezone - from http://php.net/manual/en/timezones.", "string"];
    public $timezone = "US/Eastern";

    /* Default: check every 24 hrs
        24 * 60 * 60 = 86400
    */
    protected $__update_auto = ["How often to automatically check for an update (seconds, 0 to disable)", "int"];
	public $update_auto = 86400;

    protected $__update_last_check = ["Formatted timestap of last update check (UTC)", "string"];
	public $update_last_check = "";

    // Note: this is configurable, and the child class can also set a default
    //  - empty string = not updatable
    //  - Tip: if using Github md file, use raw URL for simpler parsing
    protected $__update_version_url = ["URL to check for latest version number info", "string"];
	public $update_version_url = "";

    // Note: this is configurable, and the child class can also set a default
    protected $__update_check_hash = ["Whether to check hash of download when updating", "binary"];
	public $update_check_hash = true;

    protected $__verbose = "Enable verbose output";
	public $verbose = false;

    /**
     * Config paths
     */
    protected $config_dir = null;
    protected $config_file = null;

    /**
     * Stuff
     * Child class can override all as needed
     */
    protected $config_initialized = false;
    protected $config_to_save = null;
    protected $dt = null;
    protected $run_stamp = '';
    protected $method = '';

    protected $logged_in_user = '';
    protected $current_user = '';
    protected $logged_in_as_root = false;
    protected $running_as_root = false;

    // Update behavior
    // - DOWNLOAD - download and install update
    // - Other - show text as a custom message
    protected $update_behavior='DOWNLOAD';

    // Set this to false in child class to disable updates
    protected $update_pattern_standard = "~
        download\ latest\ version \s*
        \( \s*
            ( [\d.]+ )
        \s* \) \s* :
        \s* ( \S* ) \s*$
    ~ixm";

    // Set this to false in child class to disable hash check
    protected $hash_pattern_standard = "~
        latest\ version\ hash \s*
        \( \s*
            ( .+ )
        \s* \) \s* :
        \s* ([0-9a-f]+)
    ~ixm";

    // True to use the standard
    // - otherwise, specify pattern string as needed
    protected $update_version_pattern = [ true, 1 ];
    protected $update_download_pattern = [ true, 2 ];
    protected $update_hash_algorithm_pattern = [ true, 1 ];
    protected $update_hash_pattern = [ true, 2 ];

    protected $update_exists = null;
    protected $update_version = "0";
    protected $update_url = "";

    // This is used when packaging via pcon, for convenience,
    //  but will be read dynamically from the download page
    //  to check the downloaded file
    protected $update_hash_algorithm = "md5";
    protected $update_hash = "";
    
    /**
     * Constructor - set up basics
     */
    public function __construct()
    {
        date_default_timezone_set($this->timezone);
        $this->run_stamp = $this->stamp();

        exec('logname', $logged_in_user, $return);
        if ($return == 0 and !empty($logged_in_user))
        {
            $this->logged_in_user = trim(implode($logged_in_user));
        }
        $this->logged_in_as_root = ($this->logged_in_user == 'root');

        exec('whoami', $current_user, $return);
        if ($return == 0 and !empty($current_user))
        {
            $this->current_user = trim(implode($current_user));
        }
        $this->running_as_root = ($this->current_user == 'root');
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
        $instance->method = $method;

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

            // Check if running as root - if so, make sure that's OK
            if ($instance->running_as_root and !$instance->allow_root)
            {
                if (!in_array($method, self::$ROOT_METHODS))
                {
                    if (empty($class::$ROOT_METHODS) or !in_array($method, $class::$ROOT_METHODS))
                    {
                        $instance->error("Cowardly refusing to run as root. Use --allow_root to bypass this error.", 200);
                    }
                }
            }

            // Run an update check
            if ($instance->updateCheck(true, true)) // auto:true, output:true
            {
                if ($method != 'update')
                {
                    $instance->sleep(3);
                }
            }

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

    protected $___backup = [
        "Backup a file or files to the configured backup folder",
        ["Paths to back up", "string", "required"],
    ];
    public function backup($files, $output=true)
    {
        $success = true;

        $files = $this->prepArg($files, []);

        if (empty($this->backup_dir))
        {
            $this->warn('Backups are disabled - no backup_dir specified in config', true);
        }

        if (!is_dir($this->backup_dir))
            mkdir($this->backup_dir, 0755, true);

        foreach ($files as $file)
        {
            $this->log("Backing up $file...");
            if (!is_file($file))
            {
                $this->warn("$file does not exist - skipping", true);
                continue;
            }

            $backup_file = $this->backup_dir . DS . basename($file) . '-' . $this->stamp() . '.bak';
            $this->log(" - copying to $backup_file");

            // Back up target
            $success = ($success and copy($file, $backup_file));
        }
        
        if (!$success) $this->error('Unable to back up one or more files');

        // Clean up old backups - keep backup_age_limit days worth
        if ($success)
        {
            $this->exec("find \"{$this->backup_dir}\" -mtime +{$this->backup_age_limit} -type f -delete");

            if ($output or $verbose) $this->output('Backup successful');
        }
        
        return $success;
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

        $this->version();

        $this->output("\nUSAGE:\n");

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

            if (is_callable([$this, $specific]))
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

    protected $___install = [
        "Install a packaged PHP console tool",
        ["Install path", "string"],
    ];
    public function install($install_path=null)
    {
        if (!defined('PACKAGED') or !PACKAGED)
        {
            $this->error('Only packaged tools may be installed - package first using PCon (https://cmp.onl/tjNJ)');
        }

        $install_path = $this->prepArg($install_path, null);

        if (empty($install_path))
        {
            $install_path = $this->install_path;
        }

        if (!is_dir($install_path))
        {
            $this->warn("Install path ($install_path) does not exist and will be created", true);

            $success = mkdir($install_path, 0755);

            if (!$success) $this->error("Failed to create install path ($install_path) - may need higher privileges (eg. sudo)");
        }

        $tool_path = __FILE__;
        $filename = basename($tool_path);
        $install_tool_path = $install_path . DS . $filename;

        if (file_exists($install_tool_path))
        {
            $this->warn("This will overwrite the existing executable ($install_tool_path)", true);
        }

        $success = rename($tool_path, $install_tool_path);

        if (!$success) $this->error("Install failed - may need higher privileges (eg. sudo)");

        $this->configure('install_path', $install_path, true);
        $this->saveConfig();

        $this->log("Install completed to $install_tool_path with no errors");
    }

    protected $___update = [
        "Update an installed PHP console tool"
    ];
    public function update()
    {
        // Make sure update is available
        if (!$this->updateCheck(false, true)) // auto:false, output:true
        {
            return true;
        }

        // Check prescribed behavior
        if ($this->update_behavior != 'DOWNLOAD')
        {
            $this->output($this->update_behavior);
            return;
        }

        if (!defined('PACKAGED') or !PACKAGED)
        {
            $this->error('Only packaged tools may be updated - package first using PCon (https://cmp.onl/tjNJ), then install');
        }

        // Check install path valid
        $this_filename = basename(__FILE__);
        $config_install_tool_path = $this->install_path . DS . $this_filename;
        if ($config_install_tool_path != __FILE__)
        {
            $this->warn(
                "Install path mismatch.\n" . 
                " - Current tool path: " . __FILE__ . "\n" .
                " - Configured install path: " . $config_install_tool_path . "\n" .
                "Update will be installed to " . $config_install_tool_path,
                true
            );
        }

        // Create install path if needed
        if (!is_dir($this->install_path))
        {
            $this->warn("Install path ($this->install_path) does not exist and will be created", true);

            $success = mkdir($this->install_path, 0755);

            if (!$success) $this->error("Failed to create install path ($this->install_path) - may need higher privileges (eg. sudo)");
        }

        $this->log('Downloading update to temp file, from ' . $this->update_url);
        $temp_dir = sys_get_temp_dir();
        $temp_path = $temp_dir . DS . $this_filename . time();
        if (is_file($temp_path))
        {
            $success = unlink($temp_path);
            if (!$success) $this->error("Failed to delete existing temp file ($temp_path) - may need higher privileges (eg. sudo)");
        }

        $curl = $this->getCurl($this->update_url);
        $updated_contents = curl_exec($curl);
        if (empty($updated_contents)) $this->error("Download failed - no contents at " . $this->update_url);

        $success = file_put_contents($temp_path, $updated_contents);
        if (!$success) $this->error("Failed to write to temp file ($temp_path) - may need higher privileges (eg. sudo)");

        if ($this->update_check_hash)
        {
            $this->log('Checking hash of downloaded file ('.$this->update_hash_algorithm.')');
            $download_hash = hash_file($this->update_hash_algorithm, $temp_path);
            if ($download_hash != $this->update_hash)
            {
                $this->log('Download Hash: ' . $download_hash);
                $this->log('Update Hash: ' . $this->update_hash);
                unlink($temp_path);
                $this->error("Hash of downloaded file is incorrect; check download source");
            }
        }

        $this->log('Installing downloaded file');
        $success = rename($temp_path, $config_install_tool_path);
        $success = $success and chmod($config_install_tool_path, 0755);
        if (!$success) $this->error("Update failed - may need higher privileges (eg. sudo)");

        $this->output('Update complete');
    }

    protected $___version = [
        "Output version information"
    ];
    public function version()
    {
        $class = get_called_class();
        $this->output($class::SHORTNAME . ' version ' . $class::VERSION);
    }

    /**
     * Check for an update, and parse out all relevant information if one exists
     * @param $auto Whether this is an automatic check or triggered intentionally
     * @return Boolean True if newer version exists. False if:
     *  - no new version or
     *  - if auto, but auto check is disabled or
     *  - if auto, but not yet time to check or
     *  - if update is disabled
     */
    protected function updateCheck($auto=true, $output=false)
    {
        $this->log("Running update check");

        if (empty($this->update_version_url))
        {
            if (($output and !$auto) or $this->verbose) $this->output("Update is disabled - update_version_url is empty");
            return false; // update disabled
        }

        if (is_null($this->update_exists))
        {
            $now = time();

            // If this is an automatic check, make sure it's time to check again
            if ($auto)
            {
                $this->log("Designated as auto-update");

                // If disabled, return false
                if ($this->update_auto <= 0)
                {
                    $this->log("Auto-update is disabled - update_auto <= 0");
                    return false; // auto-update disabled
                }

                // If we haven't checked before, we'll check now
                // Otherwise...
                if (!empty($this->update_last_check))
                {
                    $last_check = strtotime($this->update_last_check);

                    // Make sure last check was a valid time
                    if (empty($last_check) or $last_check < 0)
                    {
                        $this->error('Issue with update_last_check value (' . $this->update_last_check . ')');
                    }

                    // Has it been long enough? If not, we'll return false
                    $seconds_since_last_check = $now - $last_check;
                    if ($seconds_since_last_check < $this->update_auto)
                    {
                        $this->log("Only $seconds_since_last_check seconds since last check.  Configured auto-update is " . $this->update_auto . " seconds");
                        return false; // not yet time to check
                    }
                }
            }

            // curl, get contents at config url
            $curl = $this->getCurl($this->update_version_url);
            $update_contents = curl_exec($curl);

            // look for version match
            if ($this->update_version_pattern[0] === true)
            {
                $this->update_version_pattern[0] = $this->update_pattern_standard;
            }
            if (!preg_match($this->update_version_pattern[0], $update_contents, $match))
            {
                $this->log($update_contents);
                $this->log($this->update_version_pattern[0]);
                $this->error('Issue with update version check - pattern not found at ' . $this->update_version_url);
            }
            $index = $this->update_version_pattern[1];
            $this->update_version = $match[$index];

            // check if remote version is newer than installed
            $class = get_called_class();
            $this->update_exists = version_compare($class::VERSION, $this->update_version, '<');

            if ($output or $this->verbose)
            {
                if ($this->update_exists)
                {
                    $this->hr('>');
                    $this->output("An update is available: version " . $this->update_version . " (currently installed version is " . $class::VERSION . ")");
                    if ($this->method != 'update')
                    {
                        $this->output(" - Run 'update' to install latest version.");
                        $this->output(" - See 'help update' for more information.");
                    }
                    $this->hr('>');
                }
                else
                {
                    $this->output("Already at latest version (" . $class::VERSION . ")");
                }
            }

            // look for download match
            if ($this->update_download_pattern[0] === true)
            {
                $this->update_download_pattern[0] = $this->update_pattern_standard;
            }
            if (!preg_match($this->update_download_pattern[0], $update_contents, $match))
            {
                $this->error('Issue with update download check - pattern not found at ' . $this->update_version_url);
            }
            $index = $this->update_download_pattern[1];
            $this->update_url = $match[$index];

            if ($this->update_check_hash)
            {
                // look for hash algorithm match
                if ($this->update_hash_algorithm_pattern[0] === true)
                {
                    $this->update_hash_algorithm_pattern[0] = $this->hash_pattern_standard;
                }
                if (!preg_match($this->update_hash_algorithm_pattern[0], $update_contents, $match))
                {
                    $this->error('Issue with update hash algorithm check - pattern not found at ' . $this->update_version_url);
                }
                $index = $this->update_hash_algorithm_pattern[1];
                $this->update_hash_algorithm = $match[$index];

                // look for hash match
                if ($this->update_hash_pattern[0] === true)
                {
                    $this->update_hash_pattern[0] = $this->hash_pattern_standard;
                }
                if (!preg_match($this->update_hash_pattern[0], $update_contents, $match))
                {
                    $this->error('Issue with update hash check - pattern not found at ' . $this->update_version_url);
                }
                $index = $this->update_hash_pattern[1];
                $this->update_hash = $match[$index];
            }

            $this->configure('update_last_check', date('Y-m-d H:i:s T', $now), true);
            $this->saveConfig();
        }

        $this->log(" -- update_exists: " . $this->update_exists);
        $this->log(" -- update_version: " . $this->update_version);
        $this->log(" -- update_url: " . $this->update_url);
        $this->log(" -- update_hash_algorithm: " . $this->update_hash_algorithm);
        $this->log(" -- update_hash: " . $this->update_hash);

        return $this->update_exists;
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
     * 
     * Code Guidelines:
     *  - 100 - expected error - eg. aborted due to user input
     *  - 200 - safety / caution error (eg. running as root)
     *  - 500 - misc. error
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
     * Sleep for set time, with countdown
     * @param $seconds - number of seconds to wait
     * @param $message - formatted string
     */
    public function sleep($seconds=3, $message="Continuing in %s...")
    {
        $seconds = (int) $seconds;
        $max_pad = 0;
        while ($seconds > 0)
        {
            $output = sprintf($message, $seconds);
            $pad = strlen($output);
            if ($pad < $max_pad)
            {
                $output = str_pad($output, $max_pad);
            }
            else
            {
                $max_pad = $pad;
            }

            echo $output;
            sleep(1);
            $seconds-=1;
            echo "\r";
        }
        echo str_pad("", $max_pad);
        echo "\n";
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
     * @param $required - wether input is required
     * @param $single - prompt for single character (vs waiting for enter key)
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
        $config_file = $this->getConfigFile();

        $this->backup_dir = $this->getConfigDir() . DS . 'backups';

        try
        {
            // Loading specific config values from file
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

            // Setting config to save, based on current values
            $this->config_to_save = [];
            foreach ($this->getPublicProperties() as $property)
            {
                $this->config_to_save[$property] = $this->$property;
            }
            ksort($this->config_to_save);

            $this->config_initialized = true;

            $this->saveConfig();
        }
        catch (Exception $e)
        {
            // Notify user
            $this->output('NOTICE: ' . $e->getMessage());
        }
    }

    /**
     * Save config values to file on demand
     */
    public function saveConfig()
    {
        if (!$this->config_initialized)
        {
            $this->warn('Config not initialized, refusing to save', true);
            return false;
        }

        $config_dir = $this->getConfigDir();
        $config_file = $this->getConfigFile();

        try
        {
            if (!is_dir($config_dir))
            {
                // $this->log("Creating directory - $config_dir");
                mkdir($config_dir, 0755);
            }

            // Rewrite config - pretty print
            $json = json_encode($this->config_to_save, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($config_file, $json);

            // Fix permissions if needed
            if ($this->running_as_root and !$this->logged_in_as_root)
            {
                $success = true;
                $success = ($success and chown($config_dir, $this->logged_in_user));
                $success = ($success and chown($config_file, $this->logged_in_user));
                $success = ($success and chgrp($config_dir, $this->logged_in_user));
                $success = ($success and chgrp($config_file, $this->logged_in_user));

                if (!$success)
                {
                    $this->warn("There may have been an issue setting correct permissions on the config directory ($config_dir) or file ($config_file).  Review these permissions manually.", true);
                }
            }
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
            $value = in_array($value, [true, 'true', 'yes', '1', 1]);
        }

        return $value;
    }

    /**
     * Configure property - if public
     */
    public function configure($key, $value, $save_value=false)
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

            if ($save_value)
            {
                $this->config_to_save[$key] = $value;
            }
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

// Note: leave this for packaging ?>
