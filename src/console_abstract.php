<?php
/**
 * Primary logic entry point file
 *
 *  - Defines global configuration & constants
 *  - Defines the Console_Abstract class
 *
 * @package pcon
 * @author  chrisputnam9
 */

// Global Constants
if (! defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

// Either log or display all errors
error_reporting(E_ALL);

if (defined('ERRORS') and ERRORS) {
    // Enable and show errors
    echo "\n\n************************************\n";
    echo "* Displaying all errors & warnings *\n";
    echo "************************************\n\n";
    ini_set('display_errors', 1);
    ini_set('html_errors', 0);
} else {
    // Disable PHP Error display
    ini_set('display_errors', 0);
}

if (! defined('PACKAGED') or ! PACKAGED and is_dir(__DIR__ . DS . "lib")) {
    $lib_files = scandir(__DIR__ . DS . "lib");
    sort($lib_files);
    foreach ($lib_files as $file) {
        $path = __DIR__ . DS . "lib" . DS . $file;
        if (is_file($path) and preg_match('/\.php$/', $path)) {
            require $path ;
        }
    }
}

if (! class_exists("Console_Abstract")) {
    /**
     * The main console abstract which all console tools extend
     *
     *  - Is itself an extension of the "Command" class
     *  - Includes default commands that many tools might wish to use
     *  - Includes default internal supporting functionality likely to be used by tools,
     *     but not by subcommands
     *
     *  Sub-commands should be defined as public methods,
     *   and added to the static $METHODS property
     *
     *  All public non-static properties will be configurable
     *   - They will have default values set here
     *   - But, they will also be added to the tool's config file for modification
     *
     *  Dynamic help information should be added for each subcommand and configurable property
     *   using the same name, with __ prepended - see default / built-in subcommands
     *   and confiurable properties for syntax.
     */
    class Console_Abstract extends Command
    {
        /**
         * Default output height limit, if unable to determine
         *
         * @var int
         */
        protected const DEFAULT_HEIGHT = 30;

        /**
         * Default output width limit, if unable to determine
         *
         * @var int
         */
        protected const DEFAULT_WIDTH = 130;

        /**
         * Screen percentage for first column of table output
         *
         * @var int
         */
        protected const COL1_WIDTH = 20;

        /**
         * Screen percentage for second column of table output
         *
         * @var int
         */
        protected const COL2_WIDTH = 50;

        /**
         * Separator for data entry via text editor
         *
         * @var string
         */
        protected const EDIT_LINE_BREAK = "--------------------------------------------------";

        /**
         * Callable Methods
         *
         *  - Must be public methods defined on the class
         *
         * @var array
         */
        protected static $METHODS = [
            'backup',
            'eval_file',
            'install',
            'update',
            'version',
        ];

        /**
         * Methods that are OK to run as root without warning
         *
         *  - Must be values specified in static $METHODS
         *
         * @var array
         */
        protected static $ROOT_METHODS = [
            'help',
            'install',
            'update',
            'version',
        ];

        /**
         * Config options that are hidden from help output
         *
         * - Add config values here that would not typically be overridden by a flag
         * - Cleans up help output and avoids confusion about values that are more often
         *    used in configuration than in flags.
         *
         * @var array
         */
        protected static $HIDDEN_CONFIG_OPTIONS = [
            '__WSC__',
            'backup_age_limit',
            'backup_dir',
            'browser_exec',
            'cache_lifetime',
            'editor_exec',
            'editor_modify_exec',
            'install_path',
            'step',
            'timezone',
            'update_auto',
            'update_check_hash',
            'update_last_check',
            'update_version_url',
        ];

        /**
         * Help info for $allow_root
         *
         * @var mixed
         */
        protected $__allow_root = "OK to run as root without warning";

        /**
         * Whether or not to allow running the tool as root without any warning
         *
         * @var boolean
         */
        public $allow_root = false;

        /**
         * Help info for $backup_age_limit
         *
         * @var mixed
         */
        protected $__backup_age_limit = ["Age limit of backups to keep - number of days, 0 or greater", "string"];

        /**
         * How many days worth of backups to keep when cleaning up
         * - Will be passed as X to: find -mtime +X
         * - Anything but a non-negative integer could cause errors or unexpected behavior
         *
         * @var string
         */
        public $backup_age_limit = '30';

        /**
         * Help info for $backup_dir
         *
         * @var mixed
         */
        protected $__backup_dir = ["Location to save backups", "string"];

        /**
         * Path in which to save backups
         * - If null, backups are disabled
         *
         * @var string
         */
        public $backup_dir = null;

        /**
         * Help info for $browser_exec
         *
         * @var mixed
         */
        protected $__browser_exec = ["Command to open links in browser - %s for link placeholder via sprintf"];

        /**
         * Browser exec command to use when opening URLs
         *
         *  - %s placeholder is the URL to be opened
         *
         * @var string
         */
        protected $browser_exec   = 'nohup google-chrome "%s" >/dev/null 2>&1 &';

        /**
         * Help info for $cache_lifetime
         *
         * @var mixed
         */
        protected $__cache_lifetime = ["Default time to cache data in seconds"];
        public $cache_lifetime      = 86400;
// Default: 24 hours

        /**
         * Help info for $
         *
         * @var mixed
         */
        protected $__editor_exec = ["Command to open file in editor - %s for filepath placeholder via sprintf"];
        protected $editor_exec   = '/usr/bin/vim -c "startinsert" "%s" > `tty`';
// vim in insert mode

        /**
         * Help info for $
         *
         * @var mixed
         */
        protected $__editor_modify_exec = ["Command to open file in editor to review/modify existing text - %s for filepath placeholder via sprintf"];
        protected $editor_modify_exec   = '/usr/bin/vim "%s" > `tty`';
// vim in normal mode

        /**
         * Help info for $
         *
         * @var mixed
         */
        protected $__install_path = ["Install path of this tool", "string"];
        public $install_path      = DS . "usr" . DS . "local" . DS . "bin";

        /**
         * Help info for $
         *
         * @var mixed
         */
        protected $__ssl_check = "Whether to check SSL certificates with curl";
        public $ssl_check      = true;

        /**
         * Help info for $
         *
         * @var mixed
         */
        protected $__stamp_lines = "Stamp output lines";
        public $stamp_lines      = false;

        /**
         * Help info for $
         *
         * @var mixed
         */
        protected $__step = "Enable stepping points";
        public $step      = false;

        /**
         * Help info for $
         *
         * @var mixed
         */
        protected $__timezone = ["Timezone - from http://php.net/manual/en/timezones.", "string"];
        public $timezone      = "US/Eastern";

        /**
         * Help info for $
         *
         * @var mixed
         */
        /*
            Default: check every 24 hrs
            24 * 60 * 60 = 86400
        */
        protected $__update_auto = ["How often to automatically check for an update (seconds, 0 to disable)", "int"];
        public $update_auto      = 86400;

        /**
         * Help info for $
         *
         * @var mixed
         */
        protected $__update_last_check = ["Formatted timestap of last update check", "string"];
        public $update_last_check      = "";

        /**
         * Help info for $
         *
         * @var mixed
         */
        // Note: this is configurable, and the child class can also set a default
        // - empty string = not updatable
        // - Tip: if using Github md file, use raw URL for simpler parsing
        protected $__update_version_url = ["URL to check for latest version number info", "string"];
        public $update_version_url      = "";

        /**
         * Help info for $
         *
         * @var mixed
         */
        // Note: this is configurable, and the child class can also set a default
        protected $__update_check_hash = ["Whether to check hash of download when updating", "binary"];
        public $update_check_hash      = true;

        /**
         * Help info for $
         *
         * @var mixed
         */
        protected $__verbose = "Enable verbose output";
        public $verbose      = false;

        /**
         * Help info for $
         *
         * @var mixed
         */
        protected $____WSC__ = "HJSON Data for config file";
        public $__WSC__      = null;

        /**
         * Config paths
         */
        protected $config_dir  = null;
        protected $config_file = null;
        protected $home_dir    = null;

        /**
         * Stuff
         * Child class can override all as needed
         */
        protected $config_initialized = false;
        protected $config_to_save     = null;
        protected $dt                 = null;
        protected $run_stamp          = '';
        protected $method             = '';

        protected $logged_in_user    = '';
        protected $current_user      = '';
        protected $logged_in_as_root = false;
        protected $running_as_root   = false;
        protected $is_windows        = false;

        protected $minimum_php_major_version = '7';

        // Update behavior
        // - DOWNLOAD - download and install update
        // - Other - show text as a custom message
        protected $update_behavior = 'DOWNLOAD';

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
        protected $update_version_pattern        = [ true, 1 ];
        protected $update_download_pattern       = [ true, 2 ];
        protected $update_hash_algorithm_pattern = [ true, 1 ];
        protected $update_hash_pattern           = [ true, 2 ];

        protected $update_exists  = null;
        protected $update_version = "0";
        protected $update_url     = "";

        // This is used when packaging via pcon, for convenience,
        // but will be read dynamically from the download page
        // to check the downloaded file
        protected $update_hash_algorithm = "md5";
        protected $update_hash           = "";

        public function __construct()
        {
            date_default_timezone_set($this->timezone);
            $this->run_stamp = $this->stamp();

            exec('logname', $logged_in_user, $return);
            if ($return == 0 and ! empty($logged_in_user)) {
                $this->logged_in_user = trim(implode($logged_in_user));
            }
            $this->logged_in_as_root = ($this->logged_in_user == 'root');

            exec('whoami', $current_user, $return);
            if ($return == 0 and ! empty($current_user)) {
                $this->current_user = trim(implode($current_user));
            }
            $this->running_as_root = ($this->current_user == 'root');

            $this->is_windows = (strtolower(substr(PHP_OS, 0, 3)) === 'win');

            parent::__construct($this);
        }//end __construct()


        /**
         * Check requirements
         * - Extend in child if needed and pass problems to parent
         */
        protected function checkRequirements($problems = [])
        {
            $this->log("PHP Version: " . PHP_VERSION);
            $this->log("OS: " . PHP_OS);
            $this->log("Windows: " . ($this->is_windows ? "Yes" : "No"));

            $php_version = explode('.', PHP_VERSION);
            $major       = (int)$php_version[0];
            if ($major < $this->minimum_php_major_version) {
                $problems[] = "This tool is not well tested below PHP " . $this->minimum_php_major_version .
                    " - please consider upgrading to PHP " . $this->minimum_php_major_version . ".0 or higher";
            }

            if (! function_exists('curl_version')) {
                $problems[] = "This tool requires curl for many features such as update checks and installs - please install php-curl";
            }

            if (! empty($problems)) {
                $this->error("There are some problems with requirements: \n - " . implode("\n - ", $problems), false, true);
            }
        }//end checkRequirements()


        /**
         * Run - parse args and run method specified
         */
        public static function run($arg_list)
        {
            $class = get_called_class();

            $script = array_shift($arg_list);

            $instance = new $class();

            try {
                $instance->_startup($arg_list);

                $instance->initConfig();

                $instance->try_calling($arg_list, true);

                $instance->_shutdown($arg_list);
            } catch (Exception $e) {
                $instance->error($e->getMessage());
            }
        }//end run()


        protected $___backup = [
            "Backup a file or files to the configured backup folder",
            ["Paths to back up", "string", "required"],
            ["Whether to output when backup is complete"]
        ];
        public function backup($files, $output = true)
        {
            $success = true;

            $files = $this->prepArg($files, []);

            if (empty($this->backup_dir)) {
                $this->warn('Backups are disabled - no backup_dir specified in config', true);
                return;
            }

            if (! is_dir($this->backup_dir)) {
                mkdir($this->backup_dir, 0755, true);
            }

            foreach ($files as $file) {
                $this->output("Backing up $file...", false);
                if (! is_file($file)) {
                    $this->br();
                    $this->warn("$file does not exist - skipping", true);
                    continue;
                }

                $backup_file = $this->backup_dir . DS . basename($file) . '-' . $this->stamp() . '.bak';
                $this->log(" - copying to $backup_file");

                // Back up target
                $success = ($success and copy($file, $backup_file));
                if ($success) {
                    $this->output('successful');
                } else {
                    $this->br();
                    $this->warn("Failed to back up $file", true);
                    continue;
                }
            }//end foreach

            // Clean up old backups - keep backup_age_limit days worth
            $this->exec("find \"{$this->backup_dir}\" -mtime +{$this->backup_age_limit} -type f -delete");

            return $success;
        }//end backup()


        protected $___eval_file = [
            "Evaluate a php script file, which will have access to all internal methods via '\$this'",
            ["File to evaluate", "string", "required"]
        ];
        public function eval_file($file, ...$evaluation_arguments)
        {
            if (! is_file($file)) {
                $this->error("File does not exist, check the path: $file");
            }

            if (! is_readable($file)) {
                $this->error("File is not readable, check permissions: $file");
            }

            require_once $file;
        }//end eval_file()


        protected $___install = [
            "Install a packaged PHP console tool",
            ["Install path", "string"],
        ];
        public function install($install_path = null)
        {
            if (! defined('PACKAGED') or ! PACKAGED) {
                $this->error('Only packaged tools may be installed - package first using PCon (https://cmp.onl/tjNJ)');
            }

            $install_path = $this->prepArg($install_path, null);

            if (empty($install_path)) {
                $install_path = $this->install_path;
            }

            if ($this->is_windows) {
                $this->warn(
                    "Since you appear to be running on Windows, you will very likely need to modify your install path" .
                    "\n - The current setting is: " . $install_path .
                    "\n - The desired setting will vay based on your environment, but you'll probably want to use a directory that's in your PATH" .
                    "\n - For example, if you're using Git Bash, you may want to use: C:\Program Files\Git\usr\local\bin as your install path " .
                    "\n Enter 'y' if the install path is correct and you are ready to install" .
                    "\n Enter 'n' to halt now so you can edit 'install_path' in your config file (" . $this->getConfigFile() . ")",
                    true
                );
            }

            if (! is_dir($install_path)) {
                $this->warn("Install path ($install_path) does not exist and will be created", true);

                $success = mkdir($install_path, 0755, true);

                if (! $success) {
                    $this->error("Failed to create install path ($install_path) - may need higher privileges (eg. sudo or run as admin)");
                }
            }

            $tool_path         = __FILE__;
            $filename          = basename($tool_path);
            $install_tool_path = $install_path . DS . $filename;

            if (file_exists($install_tool_path)) {
                $this->warn("This will overwrite the existing executable ($install_tool_path)", true);
            }

            $success = rename($tool_path, $install_tool_path);

            if (! $success) {
                $this->error("Install failed - may need higher privileges (eg. sudo or run as admin)");
            }

            $this->configure('install_path', $install_path, true);
            $this->saveConfig();

            $this->log("Install completed to $install_tool_path with no errors");
        }//end install()


        protected $___update = [
            "Update an installed PHP console tool"
        ];
        public function update()
        {
            // Make sure update is available
            if (! $this->updateCheck(false, true)) {
// auto:false, output:true
                return true;
            }

            // Check prescribed behavior
            if ($this->update_behavior != 'DOWNLOAD') {
                $this->output($this->update_behavior);
                return;
            }

            if (! defined('PACKAGED') or ! PACKAGED) {
                $this->error('Only packaged tools may be updated - package first using PCon (https://cmp.onl/tjNJ), then install');
            }

            // Check install path valid
            $this_filename            = basename(__FILE__);
            $config_install_tool_path = $this->install_path . DS . $this_filename;
            if ($config_install_tool_path != __FILE__) {
                $this->warn(
                    "Install path mismatch.\n" .
                    " - Current tool path: " . __FILE__ . "\n" .
                    " - Configured install path: " . $config_install_tool_path . "\n" .
                    "Update will be installed to " . $config_install_tool_path,
                    true
                );
            }

            // Create install path if needed
            if (! is_dir($this->install_path)) {
                $this->warn("Install path ($this->install_path) does not exist and will be created", true);

                $success = mkdir($this->install_path, 0755, true);

                if (! $success) {
                    $this->error("Failed to create install path ($this->install_path) - may need higher privileges (eg. sudo or run as admin)");
                }
            }

            $this->log('Downloading update to temp file, from ' . $this->update_url);
            $temp_dir  = sys_get_temp_dir();
            $temp_path = $temp_dir . DS . $this_filename . time();
            if (is_file($temp_path)) {
                $success = unlink($temp_path);
                if (! $success) {
                    $this->error("Failed to delete existing temp file ($temp_path) - may need higher privileges (eg. sudo or run as admin)");
                }
            }

            $curl             = $this->getCurl($this->update_url, true);
            $updated_contents = $this->execCurl($curl);
            if (empty($updated_contents)) {
                $this->error("Download failed - no contents at " . $this->update_url);
            }

            $success = file_put_contents($temp_path, $updated_contents);
            if (! $success) {
                $this->error("Failed to write to temp file ($temp_path) - may need higher privileges (eg. sudo or run as admin)");
            }

            if ($this->update_check_hash) {
                $this->log('Checking hash of downloaded file (' . $this->update_hash_algorithm . ')');
                $download_hash = hash_file($this->update_hash_algorithm, $temp_path);
                if ($download_hash != $this->update_hash) {
                    $this->log('Download Hash: ' . $download_hash);
                    $this->log('Update Hash: ' . $this->update_hash);
                    unlink($temp_path);
                    $this->error("Hash of downloaded file is incorrect; check download source");
                }
            }

            $this->log('Installing downloaded file');
            $success = rename($temp_path, $config_install_tool_path);
            $success = $success and chmod($config_install_tool_path, 0755);
            if (! $success) {
                $this->error("Update failed - may need higher privileges (eg. sudo or run as admin)");
            }

            $this->output('Update complete');
        }//end update()


        protected $___version = [
            "Output version information"
        ];
        public function version($output = true)
        {
            $class          = get_called_class();
            $version_string = $class::SHORTNAME . ' version ' . $class::VERSION;

            if ($output) {
                $this->output($version_string);
            } else {
                return $version_string;
            }
        }//end version()


        /**
         * Check for an update, and parse out all relevant information if one exists
         *
         * @param  $auto Whether this is an automatic check or triggered intentionally
         * @return boolean True if newer version exists. False if:
         *  - no new version or
         *  - if auto, but auto check is disabled or
         *  - if auto, but not yet time to check or
         *  - if update is disabled
         */
        protected function updateCheck($auto = true, $output = false)
        {
            $this->log("Running update check");

            if (empty($this->update_version_url)) {
                if (($output and ! $auto) or $this->verbose) {
                    $this->output("Update is disabled - update_version_url is empty");
                }
                return false;
// update disabled
            }

            if (is_null($this->update_exists)) {
                $now = time();

                // If this is an automatic check, make sure it's time to check again
                if ($auto) {
                    $this->log("Designated as auto-update");

                    // If disabled, return false
                    if ($this->update_auto <= 0) {
                        $this->log("Auto-update is disabled - update_auto <= 0");
                        return false;
// auto-update disabled
                    }

                    // If we haven't checked before, we'll check now
                    // Otherwise...
                    if (! empty($this->update_last_check)) {
                        $last_check = strtotime($this->update_last_check);

                        // Make sure last check was a valid time
                        if (empty($last_check) or $last_check < 0) {
                            $this->error('Issue with update_last_check value (' . $this->update_last_check . ')');
                        }

                        // Has it been long enough? If not, we'll return false
                        $seconds_since_last_check = $now - $last_check;
                        if ($seconds_since_last_check < $this->update_auto) {
                            $this->log("Only $seconds_since_last_check seconds since last check.  Configured auto-update is " . $this->update_auto . " seconds");
                            return false;
// not yet time to check
                        }
                    }
                }//end if

                // curl, get contents at config url
                $curl            = $this->getCurl($this->update_version_url, true);
                $update_contents = $this->execCurl($curl);

                // look for version match
                if ($this->update_version_pattern[0] === true) {
                    $this->update_version_pattern[0] = $this->update_pattern_standard;
                }
                if (! preg_match($this->update_version_pattern[0], $update_contents, $match)) {
                    $this->log($update_contents);
                    $this->log($this->update_version_pattern[0]);
                    $this->error('Issue with update version check - pattern not found at ' . $this->update_version_url, null, true);
                    return false;
                }
                $index                = $this->update_version_pattern[1];
                $this->update_version = $match[$index];

                // check if remote version is newer than installed
                $class               = get_called_class();
                $this->update_exists = version_compare($class::VERSION, $this->update_version, '<');

                if ($output or $this->verbose) {
                    if ($this->update_exists) {
                        $this->hr('>');
                        $this->output("An update is available: version " . $this->update_version . " (currently installed version is " . $class::VERSION . ")");
                        if ($this->method != 'update') {
                            $this->output(" - Run 'update' to install latest version.");
                            $this->output(" - See 'help update' for more information.");
                        }
                        $this->hr('>');
                    } else {
                        $this->output("Already at latest version (" . $class::VERSION . ")");
                    }
                }

                // look for download match
                if ($this->update_download_pattern[0] === true) {
                    $this->update_download_pattern[0] = $this->update_pattern_standard;
                }
                if (! preg_match($this->update_download_pattern[0], $update_contents, $match)) {
                    $this->error('Issue with update download check - pattern not found at ' . $this->update_version_url, null, true);
                    return false;
                }
                $index            = $this->update_download_pattern[1];
                $this->update_url = $match[$index];

                if ($this->update_check_hash) {
                    // look for hash algorithm match
                    if ($this->update_hash_algorithm_pattern[0] === true) {
                        $this->update_hash_algorithm_pattern[0] = $this->hash_pattern_standard;
                    }
                    if (! preg_match($this->update_hash_algorithm_pattern[0], $update_contents, $match)) {
                        $this->error('Issue with update hash algorithm check - pattern not found at ' . $this->update_version_url);
                    }
                    $index                       = $this->update_hash_algorithm_pattern[1];
                    $this->update_hash_algorithm = $match[$index];

                    // look for hash match
                    if ($this->update_hash_pattern[0] === true) {
                        $this->update_hash_pattern[0] = $this->hash_pattern_standard;
                    }
                    if (! preg_match($this->update_hash_pattern[0], $update_contents, $match)) {
                        $this->error('Issue with update hash check - pattern not found at ' . $this->update_version_url);
                    }
                    $index             = $this->update_hash_pattern[1];
                    $this->update_hash = $match[$index];
                }//end if

                $this->configure('update_last_check', gmdate('Y-m-d H:i:s T', $now), true);
                $this->saveConfig();
            }//end if

            $this->log(" -- update_exists: " . $this->update_exists);
            $this->log(" -- update_version: " . $this->update_version);
            $this->log(" -- update_url: " . $this->update_url);
            $this->log(" -- update_hash_algorithm: " . $this->update_hash_algorithm);
            $this->log(" -- update_hash: " . $this->update_hash);

            return $this->update_exists;
        }//end updateCheck()


        /**
         * Clear - clear the CLI output
         */
        public function clear()
        {
            system('clear');
        }//end clear()


        /**
         * Exec - run bash command
         *  - run a command
         *  - return the output
         *
         * @param $error - if true, throw error on bad return
         */
        public function exec($command, $error = false)
        {
            $this->log("exec: $command");
            exec($command, $output, $return);
            $output = empty($output) ? "" : "\n\t" . implode("\n\t", $output);
            if ($return) {
                $output = empty($output) ? $return : $output;
                if ($error) {
                    $this->error($output);
                } else {
                    $this->warn($output);
                }
            }
            $this->log($output);
            return $output;
        }//end exec()


        /**
         * Error output
         *
         * Code Guidelines:
         *  - 100 - expected error - eg. aborted due to user input
         *  - 200 - safety / caution error (eg. running as root)
         *  - 500 - misc. error
         */
        public function error($data, $code = 500, $prompt_to_continue = false)
        {
            $this->br();
            $this->hr('!');
            $this->output('ERROR: ', false);
            $this->output($data);
            $this->hr('!');
            if ($code) {
                exit($code);
            }

            if ($prompt_to_continue) {
                $yn = $this->input("Continue? (y/n)", 'n', false, true);
                if (! in_array($yn, ['y', 'Y'])) {
                    $this->error('Aborted', 100);
                }
            }
        }//end error()


        /**
         * Warn output
         *
         * @param $data to output as warning
         * @param $prompt_to_continue - whether to prompt with Continue? y/n
         */
        public function warn($data, $prompt_to_continue = false)
        {
            $this->br();
            $this->hr('*');
            $this->output('WARNING: ', false);
            $this->output($data, true, false);
            $this->hr('*');

            if ($prompt_to_continue) {
                $this->log("Getting input to continue");
                $yn = $this->input("Continue? (y/n)", 'n', false, true);
                if (! in_array($yn, ['y', 'Y'])) {
                    $this->error('Aborted', 100);
                }
            }
        }//end warn()


        /**
         * Logging output - only when verbose=true
         */
        public function log($data)
        {
            if (! $this->verbose) {
                return;
            }

            $this->output($data);
        }//end log()


        /**
         * Output data
         */
        public function output($data, $line_ending = true, $stamp_lines = null)
        {
            $data = $this->stringify($data);

            $stamp_lines = is_null($stamp_lines) ? $this->stamp_lines : $stamp_lines;
            if ($stamp_lines) {
                echo $this->stamp() . ' ... ';
            }

            echo $data . ($line_ending ? "\n" : "");
        }//end output()


        /**
         * Progress Bar Output
         */
        public function outputProgress($count, $total, $description = "remaining")
        {
            if (! $this->verbose) {
                if ($count > 0) {
                    // Set cursor to first column
                    echo chr(27) . "[0G";
                    // Set cursor up 2 lines
                    echo chr(27) . "[2A";
                }

                $full_width = $this->getTerminalWidth();
                $pad        = $full_width - 1;
                $bar_count  = floor(($count * $pad) / $total);
                $output     = "[";
                $output     = str_pad($output, $bar_count, "|");
                $output     = str_pad($output, $pad, " ");
                $output    .= "]";
                $this->output($output);
                $this->output(str_pad("$count/$total", $full_width, " ", STR_PAD_LEFT));
            } else {
                $this->output("$count/$total $description");
            }
        }//end outputProgress()


        /**
         * Stringify some data for output
         */
        public function stringify($data)
        {
            if (is_object($data) or is_array($data)) {
                $data = print_r($data, true);
            } elseif (is_bool($data)) {
                $data = $data ? "(Bool) True" : "(Bool) False";
            } elseif (is_null($data)) {
                $data = "(NULL)";
            } elseif (is_int($data)) {
                $data = "(int) $data";
            } elseif (! is_string($data)) {
                ob_start();
                var_dump($data);
                $data = ob_get_clean();
            }
            // Trimming breaks areas where we *want* extra white space
            // - must be done explicitly instead, or modify to pass in as an option maybe...
            // $data = trim($data, " \t\n\r\0\x0B");
            return $data;
        }//end stringify()


        /**
         * Colorize a string for output to console
         *  - https://en.wikipedia.org/wiki/ANSI_escape_code
         */
        public function colorize($string, $foreground = null, $background = null, $other = [])
        {
            if (empty($foreground) and empty($background) and empty($other)) {
                return $string;
            }

            $colored_string = "";
            $colored        = false;

            foreach (['foreground', 'background', 'other'] as $type) {
                if (! is_null($$type)) {
                    if (! is_array($$type)) {
                        $$type = [$$type];
                    }

                    foreach ($$type as $value_name) {
                        if (isset(CONSOLE_COLORS::${$type}[$value_name])) {
                            $colored_string .= "\033[" . CONSOLE_COLORS::${$type}[$value_name] . "m";
                            $colored         = true;
                        } else {
                            $this->warn("Invalid '$type' color specification - " . $value_name);
                        }
                    }
                }
            }

            $colored_string .= $string;

            if ($colored) {
                $colored_string .= "\033[0m";
            }

            return $colored_string;
        }//end colorize()


        /**
         * Output 3 Columns - for help for example
         */
        public function output3col($col1, $col2 = null, $col3 = null)
        {
            $full_width = $this->getTerminalWidth();
            $col1_width = floor(($full_width * static::COL1_WIDTH) / 100);
            $col2_width = floor(($full_width * static::COL2_WIDTH) / 100);

            $string = str_pad($col1, $col1_width, " ");
            if (! is_null($col2)) {
                $string .= "| " . $col2;
            }
            if (! is_null($col3)) {
                $string = str_pad($string, $col2_width, " ") . "| " . $col3;
            }
            $string = str_pad("| $string", $full_width - 1) . "|";
            $this->output($string);
        }//end output3col()


        /**
         * Output break
         */
        public function br()
        {
            $this->output('');
        }//end br()


        /**
         * br, but only if logging is on
         */
        public function brl()
        {
            if (! $this->verbose) {
                return;
            }

            $this->br;
        }//end brl()

        /**
         * Output horizonal line - divider
         */
        public function hr($c = '=', $prefix = "")
        {
            $string = str_pad($prefix, $this->getTerminalWidth(), $c);
            $this->output($string);
        }//end hr()

        /**
         * hr, but only if logging is on
         */
        public function hrl($c = '=', $prefix = "")
        {
            if (! $this->verbose) {
                return;
            }

            $this->hr($c, $prefix);
        }//end hrl()



        /**
         * Pause during output for debugging/stepthrough
         */
        public function pause($message = "[ ENTER TO STEP | 'FINISH' TO CONTINUE ]")
        {
            if (! $this->step) {
                return;
            }

            $this->hr();
            $this->output($message);
            $this->hr();

            $line = $this->input();

            if (strtolower(trim($line)) == 'finish') {
                $this->step = false;
            }
        }//end pause()


        /**
         * Sleep for set time, with countdown
         *
         * @param $seconds - number of seconds to wait
         * @param $message - formatted string
         */
        public function sleep($seconds = 3, $message = "Continuing in %s...")
        {
            $seconds = (int)$seconds;
            $max_pad = 0;
            while ($seconds > 0) {
                $output = sprintf($message, $seconds);
                $pad    = strlen($output);
                if ($pad < $max_pad) {
                    $output = str_pad($output, $max_pad);
                } else {
                    $max_pad = $pad;
                }

                echo $output;
                sleep(1);
                $seconds -= 1;
                echo "\r";
            }
            echo str_pad("", $max_pad);
            echo "\n";
        }//end sleep()


        /**
         * Get selection from list - from CLI
         *
         * @param (array) $list        of items to pick from
         * @param (any)   $message     (none) to show - prompt
         * @param (int)   $default     (0) index if no input
         * @param (bool)  $q_to_quit   (true) enter q to quit select
         * @param (array) &$preselects ([]) selection entries - will be shifted off one at a time
         *  - passed by reference, so it can be used through a chain of selects
         */
        public function select($list, $message = false, $default = 0, $q_to_quit = true, &$preselects = [], $livefilter = true)
        {
            /*
                if ($livefilter)
                {
                $this->clear();

                // todo - figure out how to detect backspace & enter
                // - maybe focus on how to do it in bash
                while (true) {
                    $char = $this->input("Enter char", null, false, 'single', 'single_hide');
                    if ($char == 'q') die;
                    var_dump($char);
                    echo "\n";
                }

                }
            */
            $list = array_values($list);
            foreach ($list as $i => $item) {
                $this->output("$i. $item");
            }

            if ($q_to_quit) {
                $this->output("q. Quit and exit");
            }

            $max   = count($list) - 1;
            $index = -1;
            $entry = false;

            while ($index < 0 or $index > $max) {
                if ($entry !== false) {
                    $this->warn("Invalid selection $entry");
                }
                if (empty($preselects)) {
                    $this->output("Enter number or part of selection");
                    $entry = $this->input($message, $default);
                } else {
                    $entry = array_shift($preselects);
                }

                if ($q_to_quit and (strtolower(trim($entry)) == 'q')) {
                    $this->warn('Selection Canceled');
                    exit;
                }

                if (! is_numeric($entry)) {
                    $filtered_items = [];

                    foreach ($list as $item) {
                        if (stripos($item, $entry) !== false) {
                            $filtered_items[] = $item;
                        }
                    }

                    if (count($filtered_items) == 1) {
                        return $filtered_items[0];
                    } elseif (! empty($filtered_items)) {
                        return $this->select($filtered_items, $message, 0, $q_to_quit, $preselects);
                    }
                }

                // Make sure it's really a good entry
                // Eg. avoid 1.2 => 1 or j => 0
                // - which would result in unwanted behavior for bad entries
                $index = (int)$entry;
                if ((string)$entry !== (string)$index) {
                    $index = -1;
                }
            }//end while

            return $list[$index];
        }//end select()


        /**
         * Confirm yes/no
         *
         * @param  Same params as input - see descriptions there
         * @return (bool) true/false
         */
        public function confirm($message, $default = 'y', $required = false, $single = true, $single_hide = false)
        {
            $yn = $this->input($message, $default, $required, $single, $single_hide);
            $this->br();

            // True if first letter of response is y or Y
            return strtolower(substr($yn, 0, 1)) == 'y';
        }//end confirm()


        /**
         * Edit some text in external editor
         *
         * @param $text to edit
         */
        public function edit($text = "", $filename = null, $modify = false)
        {
            if (is_null($filename)) {
                $filename = "edit_" . date("YmdHis") . ".txt";
            }
            $filepath = $this->setTempContents($filename, $text);

            $command = sprintf(($modify ? $this->editor_modify_exec : $this->editor_exec), $filepath);
            $this->exec($command, true);

            return $this->getTempContents($filename);
        }//end edit()


        /**
         * Get input from CLI
         *
         * @param  $message to show - prompt
         * @param  $default if no input
         * @param  $required - wether input is required
         * @param  $single - prompt for single character (vs waiting for enter key)
         * @param  $single_hide - hide input for single character (is this working?)
         * @return input text or default
         */
        public function input($message = false, $default = null, $required = false, $single = false, $single_hide = false)
        {
            if ($message) {
                if ($message === true) {
                    $message = "";
                }

                if (! is_null($default)) {
                    $message .= " ($default)";
                }
                $message .= ": ";
            }

            while (true) {
                if ($message) {
                    $this->output($message, false);
                }
                if ($single) {
                    $single_hide = $single_hide ? ' -s' : '';
                    if ($this->is_windows) {
                        $line = `bash -c "read$single_hide -n1 CHAR && echo \$CHAR"`;
                    } else {
                        $line = `bash -c 'read$single_hide -n1 CHAR && echo \$CHAR'`;
                    }

                    // Single char entry doesn't result in a line break on its own
                    // - unless the character entered was 'enter'
                    if ("\n" !== $line) {
                        $this->br();
                    }
                } else {
                    $handle = $this->getCliInputHandle();
                    $line   = fgets($handle);
                }
                $line = trim($line);

                // Entered input - return
                if ($line !== "") {
                    return $line;
                }

                // Input not required? Return default
                if (! $required) {
                    return $default;
                }

                // otherwise, warn, loop and try again
                $this->warn("Input required - please try again");
            }//end while
        }//end input()


        /**
         * Get timestamp
         */
        public function stamp()
        {
            return date('Y-m-d_H.i.s');
        }//end stamp()


        /**
         * Get Config Dir
         */
        public function getConfigDir()
        {
            if (is_null($this->config_dir)) {
                $this->config_dir = $this->getHomeDir() . DS . '.' . static::SHORTNAME;
            }

            return $this->config_dir;
        }//end getConfigDir()


        /**
         * Get Config File
         */
        public function getConfigFile()
        {
            if (is_null($this->config_file)) {
                $config_dir        = $this->getConfigDir();
                $this->config_file = $config_dir . DS . 'config.hjson';
            }

            return $this->config_file;
        }//end getConfigFile()


        /**
         * Get Home Directory
         */
        public function getHomeDir()
        {
            if (is_null($this->home_dir)) {
                $return_error = false;

                $sudo_user = "";
                if ($this->running_as_root) {
                    // Check if run via sudo vs. natively running as root
                    exec('echo "$SUDO_USER"', $output, $return_error);
                    if (! $return_error and ! empty($output)) {
                        $sudo_user = trim(array_pop($output));
                    }
                }

                // Not Sudo User?
                if (empty($sudo_user)) {
                    // Windows doesn't have 'HOME' set necessarily
                    if (empty($_SERVER['HOME'])) {
                        $this->home_dir = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEDRIVE'];

                    // Simplest and most typical - get home dir from env vars.
                    } else {
                        $this->home_dir = $_SERVER['HOME'];
                    }
                // Running via sudo - get home dir of sudo user (if not root)
                } else {
                    exec('echo ~' . $sudo_user, $output, $return_error);

                    if (! $return_error and ! empty($output)) {
                        $this->home_dir = trim(array_pop($output));
                    }
                }

                if (empty($this->home_dir)) {
                    $this->error('Something odd about this environment... can\'t figure out your home directory; please submit an issue with details about your environment');
                } elseif (! is_dir($this->home_dir)) {
                    $this->error('Something odd about this environment... home directory looks like "' . $this->home_dir . '" but that is not a directory; please submit an issue with details about your environment');
                }
            }//end if

            return $this->home_dir;
        }//end getHomeDir()


        /**
         * Init/Load Config File
         */
        public function initConfig()
        {
            $config_file = $this->getConfigFile();

            $this->backup_dir = $this->getConfigDir() . DS . 'backups';

            try {
                // Move old json file to hjson if needed
                if (! is_file($config_file)) {
                    $old_json_config_file = str_ireplace('.hjson', '.json', $config_file);
                    if (is_file($old_json_config_file)) {
                        if (! rename($old_json_config_file, $config_file)) {
                            $this->warn("Old json config file found, but couldn't rename it.\nTo keep your config settings, move '$old_json_config_file' to '$config_file'.\nIf you continue now, a new config file will be created with default values at '$config_file'.", true);
                        }
                    }
                }

                // Loading specific config values from file
                if (is_file($config_file)) {
                    // $this->log("Loading config file - $config_file");
                    $json   = file_get_contents($config_file);
                    $config = $this->json_decode($json, true);
                    if (empty($config)) {
                        $this->error("Likely syntax error: $config_file");
                    }
                    foreach ($config as $key => $value) {
                        $this->configure($key, $value);
                    }
                }

                // Setting config to save, based on current values
                $this->config_to_save = [];
                foreach ($this->getPublicProperties() as $property) {
                    $this->config_to_save[$property] = $this->$property;
                }
                ksort($this->config_to_save);

                $this->config_initialized = true;

                $this->saveConfig();
            } catch (Exception $e) {
                // Notify user
                $this->output('NOTICE: ' . $e->getMessage());
            }//end try

        }


        /**
         * Save config values to file on demand
         */
        public function saveConfig()
        {
            if (! $this->config_initialized) {
                $this->warn('Config not initialized, refusing to save', true);
                return false;
            }

            $config_dir  = $this->getConfigDir();
            $config_file = $this->getConfigFile();

            try {
                if (! is_dir($config_dir)) {
                    // $this->log("Creating directory - $config_dir");
                    mkdir($config_dir, 0755);
                }

                // Update comments in config data
                $this->config_to_save['__WSC__']      = [];
                $this->config_to_save['__WSC__']['c'] = [];
                $this->config_to_save['__WSC__']['o'] = [];

                $this->config_to_save['__WSC__']['c'][" "] = "\n    /**\n     * " . $this->version(false) . " configuration\n     */\n";
                foreach ($this->config_to_save as $key => $value) {
                    if ($key != '__WSC__') {
                        $help = $this->_help_var($key, 'option');
                        if (empty($help)) {
                            continue;
                        }

                        $help = $this->_help_param($help);
                        $type = $help[1];
                        $info = $help[0];

                        $value = " // ($type) $info";
                    }

                    $this->config_to_save['__WSC__']['c'][$key] = $value;
                }

                // Rewrite config file
                $json = $this->json_encode($this->config_to_save);
                file_put_contents($config_file, $json);

                // Fix permissions if needed
                if ($this->running_as_root and ! $this->logged_in_as_root) {
                    $success = true;
                    $success = ($success and chown($config_dir, $this->logged_in_user));
                    $success = ($success and chown($config_file, $this->logged_in_user));

                    if (! $success) {
                        $this->warn("There may have been an issue setting correct permissions on the config directory ($config_dir) or file ($config_file).  Review these permissions manually.", true);
                    }
                }
            } catch (Exception $e) {
                // Notify user
                $this->output('NOTICE: ' . $e->getMessage());
            }//end try
        }//end saveConfig()


        /**
         * Prepare shell argument for use
         *
         * @param $value to prep
         * @param default to return if                                   $value is empty
         * @param $force_type - whether to force a type for return value:
         *  'array': split and/or wrap to force it to be an array
         *  'boolean': parse as boolean (1/true/yes).
         * Note: defaults to 'array' if $default is an array
         * @param $trim (true) - whether to trim whitespace from value(s)
         */
        public function prepArg($value, $default, $force_type = null, $trim = true)
        {
            $a = func_num_args();
            if ($a < 2) {
                $this->error('prepArg requires value & default');
            }

            if (is_null($force_type)) {
                if (is_array($default)) {
                    $force_type = 'array';
                }
            }

            if ($force_type == 'bool') {
                $force_type = 'boolean';
            }

            // For backwards compatibility
            if ($force_type === true) {
                $force_type = 'array';
            }

            // Default?
            if (empty($value)) {
                $value = $default;
            }

            // Change to array if needed
            if (is_string($value) and $force_type == 'array') {
                $value = explode(",", $value);
            }

            // Trim
            if ($trim) {
                if (is_string($value)) {
                    $value = trim($value);
                } elseif (is_array($value)) {
                    if (isset($value[0]) and is_string($value[0])) {
                        $value = array_map('trim', $value);
                    }
                }
            }

            if ($force_type == 'boolean') {
                $value = in_array($value, [true, 'true', 'yes', '1', 1]);
            }

            return $value;
        }//end prepArg()


        /**
         * Open link in browser
         */
        public function openInBrowser($url)
        {
            $command = sprintf($this->browser_exec, $url);
            $this->exec($command, true);
        }//end openInBrowser()


        /**
         * Configure property - if public
         */
        public function configure($key, $value, $save_value = false)
        {
            $key = str_replace('-', '_', $key);

            if (substr($key, 0, 3) == 'no_' and $value === true) {
                $key   = substr($key, 3);
                $value = false;
            }

            $public_properties = $this->getPublicProperties();
            if (in_array($key, $public_properties)) {
                if (is_string($value)) {
                    $value = preg_replace('/^\~/', $this->getHomeDir(), $value);
                }

                $this->{$key} = $value;

                if ($save_value) {
                    $this->config_to_save[$key] = $value;
                }
            } else {
                $this->output("NOTICE: invalid config key - $key");
            }
        }//end configure()


        // Get basic curl
        public function getCurl($url, $fresh_no_cache = false)
        {
            if (! $this->ssl_check) {
                $this->warn("Initializing unsafe connection to $url (no SSL check, as configured)", true);
            }

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 0,
                CURLOPT_TIMEOUT => 180,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYHOST => ($this->ssl_check ? 2 : 0),
                CURLOPT_SSL_VERIFYPEER => ($this->ssl_check ? 2 : 0),
            ]);

            if ($fresh_no_cache) {
                curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
            }

            return $curl;
        }//end getCurl()


        // Exec curl and handle errors, return response if good
        public function execCurl($curl)
        {
            $response = curl_exec($curl);

            if (empty($response)) {
                $number = curl_errno($curl);

                // Was there an error?
                if ($number) {
                    $message = curl_error($curl);

                    if (stripos($message, 'ssl') !== false) {
                        $message .= "\n\nFor some SSL issues, try downloading the latest CA bundle and pointing your PHP.ini to that (https://curl.haxx.se/docs/caextract.html)";
                        $message .= "\n\nAlthough risky and not recommended, you can also consider re-running your command with the --no-ssl-check flag";
                    }

                    $this->warn($message, true);
                }
            }

            return $response;
        }//end execCurl()


        // Update arguments for curl URL
        public function updateCurlArgs($ch, $args, $overwrite = false)
        {
            // Get info from previous curl
            $curl_info = curl_getinfo($ch);

            // Parse out URL and query params
            $url = $curl_info['url'];

            $url_parsed = parse_url($url);
            if (empty($url_parsed['query'])) {
                $query = [];
            } else {
                parse_str($url_parsed['query'], $query);
            }

            // Set new args
            foreach ($args as $key => $value) {
                if (! isset($query[$key]) or $overwrite) {
                    $query[$key] = $value;
                }
            }

            // Build new URL
            $new_url =
                $url_parsed['scheme'] .
                "://" .
                $url_parsed['host'] .
                $url_parsed['path'] .
                "?" .
                http_build_query($query);
            $this->log($new_url);
            curl_setopt($ch, CURLOPT_URL, $new_url);
        }//end updateCurlArgs()


        /**
         * Interact with cache files
         */
        public function getCacheContents($subpath, $expiration = null)
        {
            $expiration = $expiration ?? $this->cache_lifetime;

            $config_dir = $this->getConfigDir();
            $cache_dir  = $config_dir . DS . 'cache';
            $subpath    = is_array($subpath) ? implode(DS, $subpath) : $subpath;

            $cache_file = $cache_dir . DS . $subpath;
            $contents   = false;

            if (is_file($cache_file)) {
                $this->log("Cache file exists ($cache_file) - checking age");
                $cache_modified = filemtime($cache_file);
                $now            = time();
                $cache_age      = $now - $cache_modified;
                if ($cache_age < $expiration) {
                    $this->log("New enough - reading from cache file ($cache_file)");
                    $contents = file_get_contents($cache_file);
                    if ($contents === false) {
                        $this->warn("Failed to read cache file ($cache_file) - possible permissions issue", true);
                    }
                }
            }

            return $contents;
        }//end getCacheContents()

        public function setCacheContents($subpath, $contents)
        {
            $config_dir = $this->getConfigDir();
            $cache_dir  = $config_dir . DS . 'cache';
            $subpath    = is_array($subpath) ? implode(DS, $subpath) : $subpath;

            $cache_file = $cache_dir . DS . $subpath;
            $cache_dir  = dirname($cache_file);

            if (! is_dir($cache_dir)) {
                $success = mkdir($cache_dir, 0755, true);
                if (! $success) {
                    $this->error("Unable to create new directory - $cache_dir");
                }
            }

            $written = file_put_contents($cache_file, $contents);
            if ($written === false) {
                $this->warn("Failed to write to cache file ($cache_file) - possible permissions issue", true);
                return false;
            }

            return $cache_file;
        }//end setCacheContents()


        /**
         * Interact with temp files
         */
        public function getTempContents($subpath)
        {
            $config_dir = $this->getConfigDir();
            $temp_dir   = $config_dir . DS . 'temp';
            $subpath    = is_array($subpath) ? implode(DS, $subpath) : $subpath;

            $temp_file = $temp_dir . DS . $subpath;
            $contents  = false;

            if (is_file($temp_file)) {
                $this->log("Temp file exists ($temp_file) - reading from temp file");
                $contents = file_get_contents($temp_file);
                if ($contents === false) {
                    $this->warn("Failed to read temp file ($temp_file) - possible permissions issue", true);
                }
            }

            return $contents;
        }//end getTempContents()

        public function setTempContents($subpath, $contents)
        {
            $config_dir = $this->getConfigDir();
            $temp_dir   = $config_dir . DS . 'temp';
            $subpath    = is_array($subpath) ? implode(DS, $subpath) : $subpath;

            $temp_file = $temp_dir . DS . $subpath;
            $temp_dir  = dirname($temp_file);

            if (! is_dir($temp_dir)) {
                mkdir($temp_dir, 0755, true);
            }

            $written = file_put_contents($temp_file, $contents);
            if ($written === false) {
                $this->warn("Failed to write to temp file ($temp_file) - possible permissions issue", true);
                return false;
            }

            return $temp_file;
        }//end setTempContents()


        /**
         * Paginate some content for display on terminal
         */
        public function paginate($content, $options = [])
        {
            $options = array_merge([
                'starting_line' => 1,
                'starting_column' => 1,
// todo
                'wrap' => false,
                'line_buffer' => 1,
                'output' => true,
                'include_page_info' => true,
                'fill_height' => true,
            ], $options);

            // Split into lines if needed
            if (is_string($content)) {
                $content = preg_split("/\r\n|\n|\r/", $content);
            }

            $max_width = $this->getTerminalWidth();

            $max_height = $this->getTerminalHeight();
            $max_height = $max_height - $options['line_buffer'];
            $max_height = $max_height - 2;
// for start/end line breaks
            if ($options['include_page_info']) {
                $max_height = $max_height - 2;
// for page info and extra line break
            }

            if (! is_array($content)) {
                $content = explode("\n");
            }
            $content = array_values($content);

            // Pre-wrap lines if specified, to make sure pagination works based on real number of lines
            if ($options['wrap']) {
                $wrapped_content = [];
                foreach ($content as $line) {
                    $line_length = strlen($line);
                    while ($line_length > $max_width) {
                        $wrapped_content[] = substr($line, 0, $max_width);
                        $line              = substr($line, $max_width);
                        $line_length       = strlen($line);
                    }
                    $wrapped_content[] = $line;
                }
                $content = $wrapped_content;
            }

            $content_length = count($content);

            $are_prev = false;
            if ($options['starting_line'] > 1) {
                $are_prev = true;
            }

            $are_next = false;
            if (($options['starting_line'] + $max_height - 1) < $content_length) {
                $are_next = true;
            }

            $height = 0;
            $output = [];

            // Starting line break
            if ($are_prev) {
                $output[] = $this->colorize(str_pad("==[MORE ABOVE]", $max_width, "="), 'green', null, 'bold');
            } else {
                $output[] = str_pad("", $max_width, "=");
            }

            $l          = $options['starting_line'] - 1;
            $final_line = $options['starting_line'];
            while ($height < $max_height) {
                if ($l < ($content_length)) {
                    $final_line = $l + 1;
                    $line       = $content[$l];
                } else {
                    if ($options['fill_height']) {
                        $line = "";
                    } else {
                        break;
                    }
                }


                if (! is_string($line)) {
                    $this->error("Bad type for line $l of content - string expected");
                }
                if (strlen($line) > $max_width) {
                    if ($options['wrap']) {
                        $this->error("Something wrong with the following line - wrap was on, but it's still too long", false);
                        $this->output($line);
                        $this->error("Aborting");
                    }

                    $line = substr($line, 0, $max_width);
                }
                $output[] = $line;

                $l++;
                $height++;
            }//end while

            // Ending line break
            if ($are_next) {
                $output[] = $this->colorize(str_pad("==[MORE BELOW]", $max_width, "="), 'green', null, 'bold');
            } else {
                $output[] = str_pad("", $max_width, "=");
            }

            if ($options['include_page_info']) {
                $output[] = str_pad($options['starting_line'] . " - " . $final_line . " of " . $content_length . " items", $max_width, " ");
                $output[] = str_pad("", $max_width, "=");
            }

            $output = implode("\n", $output);

            if ($options['output']) {
                echo $output;
            }

            return [
                'output' => $output,
                'starting_line' => $options['starting_line'],
                'page_length' => $max_height,
                'ending_line' => min(($options['starting_line'] + $max_height) - 1, $content_length),
            ];
        }//end paginate()


        /**
         * Get parameters for a given method
         */
        protected function _getMethodParams($method)
        {
            $r      = new ReflectionObject($this);
            $rm     = $r->getMethod($method);
            $params = [];
            foreach ($rm->getParameters() as $param) {
                $params[] = $param->name;
            }
            return $params;
        }//end _getMethodParams()


        // Manage Properties
        protected $_public_properties = null;
        public function getPublicProperties()
        {
            if (is_null($this->_public_properties)) {
                $this->_public_properties = [];
                $reflection               = new ReflectionObject($this);
                foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
                    $this->_public_properties[] = $prop->getName();
                }
                sort($this->_public_properties);
            }

            return $this->_public_properties;
        }//end getPublicProperties()


        // Manage CLI Input Handle
        protected $_cli_input_handle = null;
        protected function getCliInputHandle()
        {
            if (is_null($this->_cli_input_handle)) {
                $this->_cli_input_handle = fopen("php://stdin", "r");
            }

            return $this->_cli_input_handle;
        }//end getCliInputHandle()

        protected function close_cli_input_handle()
        {
            if (! is_null($this->_cli_input_handle)) {
                fclose($this->_cli_input_handle);
            }
        }//end close_cli_input_handle()


        protected $_terminal_height = null;
        public function getTerminalHeight($fresh = false)
        {
            if ($fresh or empty($this->_terminal_height)) {
                exec("tput lines", $output, $return);

                if (
                    $return
                    or empty($output)
                    or empty($output[0])
                    or ! is_numeric($output[0])
                ) {
                    $this->_terminal_height = static::DEFAULT_HEIGHT;
                } else {
                    $this->_terminal_height = (int)$output[0];
                }
            }

            return $this->_terminal_height;
        }//end getTerminalHeight()


        protected $_terminal_width = null;
        public function getTerminalWidth($fresh = false)
        {
            if ($fresh or empty($this->_terminal_width)) {
                exec("tput cols", $output, $return);

                if (
                    $return
                    or empty($output)
                    or empty($output[0])
                    or ! is_numeric($output[0])
                ) {
                    $this->_terminal_width = static::DEFAULT_WIDTH;
                } else {
                    $this->_terminal_width = (int)$output[0];
                }
            }

            return $this->_terminal_width;
        }//end getTerminalWidth()


        /**
         * Parse HTML for output to terminal
         * Supporting:
         *  - Bold
         *  - Italic (showing as dim)
         *  - Links (Underlined with link in parentheses)
         *  - Unordered Lists ( - )
         *  - Ordered Lists ( 1. )
         *  - Hierarchical lists (via indentation)
         * Not Yet:
         *  - Text colors
         *  - Underline styles
         *  - Indentation styles
         *  - Less commonly supported terminal styles
         */
        public function parseHtmlForTerminal($dom, $depth = 0, $prefix = "")
        {
            $output = "";

            if (is_string($dom)) {
                $dom = trim($dom);

                if (empty($dom)) {
                    return $dom;
                }

                $tmp = new DOMDocument();
                if (! @$tmp->loadHTML(mb_convert_encoding($dom, 'HTML-ENTITIES', 'UTF-8'))) {
                    return $dom;
                }
                $dom = $tmp;
            }

            if (! is_object($dom) or ! in_array(get_class($dom), ["DOMDocumentType", "DOMDocument", "DOMElement"])) {
                $type = is_object($dom) ? get_class($dom) : gettype($dom);
                $this->error("Invalid type passed to parseHtmlForTerminal - $type");
            }

            $li_index = 0;
            foreach ($dom->childNodes as $child_index => $node) {
                $_output = "";
// output for this child
                $_prefix = $prefix;
// prefix for this child's children - start with current prefix, passed to function
                $_suffix = "";
// suffix for end of this child
                // Note coloring if needed
                $color_foreground = null;
                $color_background = null;
                $color_other      = null;

                switch ($node->nodeName) {
                    case 'a':
                        $color_other = 'underline';
                        $href        = trim($node->getAttribute('href'));
                        $content     = trim($node->nodeValue);
                        if (strtolower(trim($href, "/")) != strtolower(trim($content, "/"))) {
                            $_suffix = " [$href]";
                        }
                        break;

                    case 'br':
                    case 'p':
                        $_output .= "\n" . $_prefix;
                        break;

                    case 'b':
                    case 'strong':
                        $color_other = 'bold';
                        break;

                    case 'em':
                        $color_other = 'dim';
                        break;

                    case 'ol':
                    case 'ul':
                        $_output .= "\n";
                        break;

                    case 'li':
                        // Output number for ol child, otherwise, default to "-"
                        $list_char = " - ";
                        if ($dom->nodeName == "ol") {
                            $list_char = " " . ($li_index + 1) . ". ";
                        }

                        $_output .= $_prefix . $list_char;

                        // Update prefix for child elements
                        $_prefix = $_prefix . str_pad("", strlen($list_char));

                        $_suffix = "\n";

                        $li_index++;

                        break;

                    case '#text':
                        $_output .= $node->nodeValue;
                        break;

                    default:
                        break;
                }//end switch

                if ($node->hasChildNodes()) {
                    $_output .= $this->parseHtmlForTerminal($node, $depth + 1, $_prefix);
                }

                // Decorate the output as needed
                $_output = $this->colorize($_output, $color_foreground, $color_background, $color_other);

                $output .= $_output . $_suffix;
            }//end foreach

            // $output = str_replace("\u{00a0}", " ", $output);
            $output = str_replace("\r", "\n", $output);
            $output = preg_replace('/\n(\s*\n){2,}/', "\n\n", $output);

            return htmlspecialchars_decode($output);
        }//end parseHtmlForTerminal()


        /**
         * Parse Markdown to HTML
         * - uses Parsedown
         * - uses some defaults based on common use
         * - alternatively, can call Parsedown directly
         */
        public function parseMarkdownToHtml($text)
        {
            $html = Parsedown::instance()
                ->setBreaksEnabled(true)
                ->setMarkupEscaped(true)
                ->setUrlsLinked(false)
                ->text($text);
            return $html;
        }//end parseMarkdownToHtml()


        /**
         * JSON encode & decode, using HJSON
         */
        public function json_decode($json, $options = [])
        {
            $this->log("Running json_decode on console_abstract");

            // mimic json_decode behavior
            if ($options === true) {
                $options = ['assoc' => true];
            }

            // default to preserve comments and whitespace
            if (! isset($options['keepWsc'])) {
                $options['keepWsc'] = true;
            }

            $parser = new HJSONParser();
            $data   = $parser->parse($json, $options);
            $this->_json_cleanup($data);
            return $data;
        }//end json_decode()

        public function json_encode($data, $options = [])
        {
            $this->log("Running json_encode on console_abstract");

            $options = array_merge([
                'keepWsc' => true,
                'bracesSameLine' => true,
                'quotes' => 'always',
                'space' => 4,
                'eol' => PHP_EOL,
            ], $options);

            // default to preserve comments and whitespace
            if (! isset($options['keepWsc'])) {
                $options['keepWsc'] = true;
            }

            if (empty($options['keepWsc'])) {
                unset($data['__WSC__']);
            } else {
                if (! empty($data['__WSC__'])) {
                    $data['__WSC__'] = (object)$data['__WSC__'];
                    if (! empty($data['__WSC__']->c)) {
                        $data['__WSC__']->c = (object)$data['__WSC__']->c;
                    }
                }
            }

            $this->_json_cleanup($data);

            $stringifier = new HJSONStringifier();
            $json        = $stringifier->stringify($data, $options);
            return $json;
        }//end json_encode()

        protected function _json_cleanup(&$data)
        {
            if (is_iterable($data)) {
                foreach ($data as $key => &$value) {
                    if (is_object($value)) {
                        unset($value->__WSC__);
                    }
                    if (is_array($value)) {
                        unset($value['__WSC__']);
                    }

                    $this->_json_cleanup($value);
                }
            }
        }//end _json_cleanup()


        // Prevent infinite loop of magic method handling
        public function __call(string $method, array $arguments = []): mixed
        {
            throw new Exception("Invalid method '$method'");
        }//end __call()


        // Extendable method for startup logic
        protected function _startup($arglist)
        {
            // Nothing to do by default
        }//end _startup()


        // Extendable method for shutdown logic
        protected function _shutdown($arglist)
        {
            // Nothing to do by default
        }//end _shutdown()
    }//end class

}//end if

// For working unpackaged
if (! empty($src_includes) and is_array($src_includes)) {
    foreach ($src_includes as $src_include) {
        require $src_include;
    }
}

// Note: leave the end tag for packaging
?>
