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
    /*
     * @var string Directory separator for this OS
     * @global
     */
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
         * Default output height limit, if unable to determine dynamically
         *
         * @var integer
         */
        protected const DEFAULT_HEIGHT = 30;

        /**
         * Default output width limit, if unable to determine dynamically
         *
         * @var integer
         */
        protected const DEFAULT_WIDTH = 130;

        /**
         * Screen percentage for first column of table output
         *
         * @var integer
         */
        protected const COL1_WIDTH = 20;

        /**
         * Screen percentage for second column of table output
         *
         * @var integer
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
            'livefilter',
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
         *
         * @internal
         */
        protected $__allow_root = "OK to run as root without warning";

        /**
         * Whether or not to allow running the tool as root without any warning
         *
         * @var boolean
         * @api
         */
        public $allow_root = false;

        /**
         * Help info for $backup_age_limit
         *
         * @var mixed
         *
         * @internal
         */
        protected $__backup_age_limit = ["Age limit of backups to keep - number of days, 0 or greater", "string"];

        /**
         * How many days worth of backups to keep when cleaning up
         *
         * - Will be passed as X to: find -mtime +X
         * - Anything but a non-negative integer could cause errors or unexpected behavior
         *
         * @var string
         * @api
         */
        public $backup_age_limit = '30';

        /**
         * Help info for $backup_dir
         *
         * @var mixed
         *
         * @internal
         */
        protected $__backup_dir = ["Location to save backups", "string"];

        /**
         * Path in which to save backups
         *
         * - If null, backups are disabled
         *
         * @var string
         * @api
         */
        public $backup_dir = null;

        /**
         * Help info for $browser_exec
         *
         * @var mixed
         *
         * @internal
         */
        protected $__browser_exec = ["Command to open links in browser - %s for link placeholder via sprintf"];

        /**
         * Browser exec command to use when opening URLs
         *
         *  - %s placeholder is the URL to be opened
         *
         * @var string
         */
        protected $browser_exec = 'nohup google-chrome "%s" >/dev/null 2>&1 &';

        /**
         * Help info for $cache_lifetime
         *
         * @var mixed
         *
         * @internal
         */
        protected $__cache_lifetime = ["Default time to cache data in seconds"];

        /**
         * Default lifetime of cached data in seconds - when to expire
         *
         *  - Defaults to 86400 (24 hours)
         *
         * @var integer
         * @api
         */
        public $cache_lifetime = 86400;

        /**
         * Help info for $editor_exec
         *
         * @var mixed
         *
         * @internal
         */
        protected $__editor_exec = ["Command to open file in editor - %s for filepath placeholder via sprintf"];

        /**
         * Editor executable - command to be run when opening a new file
         *
         *  - %s is the filepath placeholder
         *  - Defaults to vim in *insert* mode
         *
         * @var string
         */
        protected $editor_exec = '/usr/bin/vim -c "startinsert" "%s" > `tty`';

        /**
         * Help info for $editor_modify_exec
         *
         * @var mixed
         *
         * @internal
         */
        protected $__editor_modify_exec = ["Command to open file in editor to review/modify existing text - %s for filepath placeholder via sprintf"];

        /**
         * Editor executable - command to be run when opening a file for *modification*
         *
         *  - Eg. existing file that is being modified
         *  - %s is the filepath placeholder
         *  - Defaults to vim in *normal* mode - preferred for modification
         *
         * @var string
         */
        protected $editor_modify_exec = '/usr/bin/vim "%s" > `tty`';

        /**
         * Help info for $install_path
         *
         * @var mixed
         *
         * @internal
         */
        protected $__install_path = ["Install path of this tool", "string"];

        /**
         * Install path for packaged tool executables
         *
         * @var string
         * @api
         */
        public $install_path = DS . "usr" . DS . "local" . DS . "bin";

        /**
         * Help info for $livefilter
         *
         * @var mixed
         *
         * @internal
         */
        protected $__livefilter = ["Status of livefilter for select interface - false/disabled, true/enabled, or autoenter", "string"];

        /**
         * Status of livefilter for select interface - false/disabled, true/enabled, or autoenter
         *
         * @var mixed
         */
        public $livefilter = 'disabled';

        /**
         * Help info for $ssl_check
         *
         * @var mixed
         *
         * @internal
         */
        protected $__ssl_check = "Whether to check SSL certificates with curl";

        /**
         * Whether to check SSL certificates on network connections
         *
         *  - Defaults to true
         *
         * @var boolean
         * @api
         */
        public $ssl_check = true;

        /**
         * Help info for $stamp_lines
         *
         * @var mixed
         *
         * @internal
         */
        protected $__stamp_lines = "Stamp / prefix output lines with the date and time";

        /**
         * Whether to prefix output lines with date and time
         *
         *  - Defaults to false
         *
         * @var boolean
         * @api
         */
        public $stamp_lines = false;

        /**
         * Help info for $step
         *
         * @var mixed
         *
         * @internal
         */
        protected $__step = "Enable stepping/pause points for debugging";

        /**
         * Whether to enable stepping / pause points for debugging
         *
         *  - Defaults to false
         *
         * @var boolean
         * @api
         */
        public $step = false;

        /**
         * Help info for $timezone
         *
         * @var mixed
         *
         * @internal
         */
        protected $__timezone = ["Timezone - from http://php.net/manual/en/timezones.", "string"];

        /**
         * Timezone - from http://php.net/manual/en/timezones.
         *
         *  - Defaults to "US/Eastern"
         *
         * @var string
         * @api
         */
        public $timezone = "US/Eastern";

        /**
         * Help info for $update_auto
         *
         * @var mixed
         *
         * @internal
         */
        protected $__update_auto = ["How often to automatically check for an update (seconds, 0 to disable)", "int"];

        /**
         * How often (in seconds) to automatically check for an update
         *
         *  - Defaults to 86400 (24 hours)
         *  - Set to 0 to disable updates
         *
         * @var integer
         * @api
         */
        public $update_auto = 86400;

        /**
         * Help info for $update_last_check
         *
         * @var mixed
         *
         * @internal
         */
        protected $__update_last_check = ["Formatted timestap of last update check", "string"];

        /**
         * Timestamp of last update check
         *
         *  - Not typically set manually
         *  - Stored in config for easy reference and simplicity
         *  - Defaults to "" - no update check completed yet
         *
         * @var string
         * @api
         */
        public $update_last_check = "";

        /**
         * Help info for $update_version_url
         *
         * @var mixed
         *
         * @internal
         */
        protected $__update_version_url = ["URL to check for latest version number info", "string"];

        /**
         * The URL to check for updates
         *
         *  - Empty string will disable checking for updates
         *  - The tool child class itself should set a default
         *  - Common choice would be to use raw URL of Github readme file
         *  - Set in config to set up a custom update methodology or disable updates
         *
         * @var string
         * @see PCon::update_version_url for an example setting
         * @api
         */
        public $update_version_url = "";

        /**
         * Help info for $update_check_hash
         *
         * @var mixed
         *
         * @internal
         */
        protected $__update_check_hash = ["Whether to check hash of download when updating", "binary"];

        /**
         * Whether to check the hash when downloading updates
         *
         *  - Defaults to true
         *
         * @var boolean
         * @api
         */
        public $update_check_hash = true;

        /**
         * Help info for $verbose
         *
         * @var mixed
         *
         * @internal
         */
        protected $__verbose = "Enable verbose output";

        /**
         * Whether to show log messages - verbose output
         *
         *  - Defaults to false
         *
         * @var boolean
         * @api
         */
        public $verbose = false;

        /**
         * Help info for $__WSC__
         *
         * @var mixed
         *
         * @internal
         */
        protected $____WSC__ = "HJSON Data for config file";

        /**
         * HJSON Data for the config file
         *
         * @var array
         * @api
         */
        public $__WSC__ = null;

        /**
         * Config directory
         *
         * @var string
         */
        protected $config_dir = null;

        /**
         * Config file
         *
         * @var string
         */
        protected $config_file = null;

        /**
         * Home directory
         *
         * @var string
         */
        protected $home_dir = null;

        /**
         * Config initialized flag
         *
         * @var boolean
         */
        protected $config_initialized = false;

        /**
         * Config data to be saved
         *
         * @var array
         */
        protected $config_to_save = null;

        /**
         * Timestamp when the tool was initilized - eg. when constructor ran
         *
         * @var string
         */
        protected $run_stamp = '';

        /**
         * The method being called
         *
         *  - set by Command::try_calling
         *
         * @var string
         */
        protected $method = '';

        /**
         * The user that initially logged in to the current session
         *
         *  - as reported by logname
         *
         * @var string
         */
        protected $logged_in_user = '';

        /**
         * The currently active user
         *
         *  - as reported by whoami
         *
         * @var string
         */
        protected $current_user = '';

        /**
         * Whether the user is logged in as root
         *
         *  - Ie. is logged_in_user === 'root'
         *
         * @var boolean
         */
        protected $logged_in_as_root = false;

        /**
         * Whether user is currently root
         *
         *  - Ie. current_user === 'root'
         *
         * @var boolean
         */
        protected $running_as_root = false;

        /**
         * Whether tool is running on a Windows operating system
         *
         * @var boolean
         */
        protected $is_windows = false;

        /**
         * The minimum PHP major version required for this tool
         *
         * @var integer
         */
        protected $minimum_php_major_version = 7;

        /**
         * Update behavior - either "DOWNLOAD" or custom text
         *
         *  - If set to "DOWNLOAD" then the update will download if available
         *  - Otherwise, whatever text is set here will show as a message - ie. instructions
         *    on how to update the tool manually.
         *  - Defaults to 'DOWNLOAD' as that is what most tools will use
         *  - However, as an example, PCon::update_behavior instructs users to pull the git repository to update it
         *
         * @var string
         */
        protected $update_behavior = 'DOWNLOAD';

        /**
         * The standard/default pattern to identify the latest version and URL
         * within the text found at $this->update_version_url.
         *
         *  - By default, group 1 is the version - see $this->update_version_pattern
         *  - By default, group 2 is the download URL - see $this->pdate_download_pattern
         *  - Defaults to look for a string like:
         *        Download Latest Version (1.1.1):
         *        https://example.com
         *
         * @var string
         */
        protected $update_pattern_standard = "~
            download\ latest\ version \s*
            \( \s*
                ( [\d.]+ )
            \s* \) \s* :
            \s* ( \S* ) \s*$
        ~ixm";

        /**
         * The standard/default pattern to identify the hash of the latest version download
         * within the text found at $this->update_version_url.
         *
         *  - By default, group 1 is the algorithm - see $this->update_hash_algorithm_pattern
         *  - By default, group 2 is the hash - see $this->update_hash_pattern
         *  - Defaults to look for a string like:
         *        Latest Version Hash (md5):
         *        hhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhh
         *
         * @var string
         */
        protected $hash_pattern_standard = "~
            latest\ version\ hash \s*
            \( \s*
                ( .+ )
            \s* \) \s* :
            \s* ([0-9a-f]+)
        ~ixm";

        /**
         * Instructions to find update version at $this->update_version_url.
         *
         *  - First element is pattern
         *      - Defaults to true to use $update_pattern_standard
         *  - Second element is match index
         *      - Defaults to 1 to use first match group as version
         *
         * @var array
         */
        protected $update_version_pattern = [ true, 1 ];

        /**
         * Instructions to find update download URL at $this->update_version_url.
         *
         *  - First element is pattern
         *      - Defaults to true to use $update_pattern_standard
         *  - Second element is match index
         *      - Defaults to 2 to use second match group as version
         *
         * @var array
         */
        protected $update_download_pattern = [ true, 2 ];

        /**
         * Instructions to find download hash algorithm at $this->update_version_url.
         *
         *  - First element is pattern
         *      - Defaults to true to use $hash_pattern_standard
         *  - Second element is match index
         *      - Defaults to 1 to use first match group as version
         *
         * @var array
         */
        protected $update_hash_algorithm_pattern = [ true, 1 ];

        /**
         * Instructions to find download hash at $this->update_version_url.
         *
         *  - First element is pattern
         *      - Defaults to true to use $hash_pattern_standard
         *  - Second element is match index
         *      - Defaults to 2 to use second match group as version
         *
         * @var array
         */
        protected $update_hash_pattern = [ true, 2 ];

        /**
         * Whether an update exists or not - to avoid multiple checks
         *
         * @var boolean
         *
         * @internal
         */
        protected $update_exists = null;

        /**
         * Latest version available for update
         *
         * @var string
         *
         * @internal
         */
        protected $update_version = "0";

        /**
         * URL of latest version available for update
         *
         * @var string
         *
         * @internal
         */
        protected $update_url = "";

        /**
         * Hash algorithm to use for packaging and update verification
         *
         *  - Defaults to md5
         *
         * @var string
         */
        protected $update_hash_algorithm = "md5";

        /**
         * Hash of latest version available for update
         *
         * @var string
         *
         * @internal
         */
        protected $update_hash = "";

        /**
         * Constructor
         *
         *  - Sets default timezone for date functions to use
         *  - Sets run_stamp
         *  - Determines runtime details - user, OS
         *  - Calls parent (Command) constructor
         */
        public function __construct()
        {
            date_default_timezone_set($this->timezone);
            $this->run_stamp = $this->stamp();

            exec('logname', $logged_in_user, $return);
            if ($return == 0 and ! empty($logged_in_user)) {
                $this->logged_in_user = trim(implode($logged_in_user));
            }
            $this->logged_in_as_root = ($this->logged_in_user === 'root');

            exec('whoami', $current_user, $return);
            if ($return == 0 and ! empty($current_user)) {
                $this->current_user = trim(implode($current_user));
            }
            $this->running_as_root = ($this->current_user === 'root');

            $this->is_windows = (strtolower(substr(PHP_OS, 0, 3)) === 'win');

            parent::__construct($this);
        }//end __construct()

        /**
         * Check requirements
         *
         *  - PHP Version & Modules
         *  - Extend in child if needed and pass problems to parent
         *
         * @param array $problems Existing problems passed by child class.
         *
         * @return void
         */
        protected function checkRequirements(array $problems = [])
        {
            $this->log("PHP Version: " . PHP_VERSION);
            $this->log("OS: " . PHP_OS);
            $this->log("Windows: " . ($this->is_windows ? "Yes" : "No"));

            $php_version = explode('.', PHP_VERSION);
            $major       = (int) $php_version[0];
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
         *
         *  - Entry point for the tool - child class will run this
         *  - Eg. see Pcon class
         *
         * @param array $arg_list Array of args passed via command line (Ie. built-in $argv).
         *
         * @return void
         */
        public static function run(array $arg_list = [])
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

        /**
         * Help info for backup method
         *
         * @var mixed
         *
         * @internal
         */
        protected $___backup = [
            "Backup a file or files to the configured backup folder",
            ["Paths to back up", "string", "required"],
            ["Whether to output when backup is complete"]
        ];

        /**
         * Method to backup files used by the tool
         *
         * @param mixed   $files  File(s) to back up.
         * @param boolean $output Whether to output information while running.
         *
         * @return boolean Whether backup was successful.
         * @api
         */
        public function backup(mixed $files, bool $output = true): bool
        {
            $success = true;

            $files = $this->prepArg($files, []);

            if (empty($this->backup_dir)) {
                $this->warn('Backups are disabled - no backup_dir specified in config', true);
                return false;
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


        /**
         * Help info for eval_file method
         *
         * @var mixed
         *
         * @internal
         */
        protected $___eval_file = [
            "Evaluate a php script file, which will have access to all internal methods via '\$this'",
            ["File to evaluate", "string", "required"]
        ];

        /**
         * Evaluate a script file in the tool environment.
         *
         *  - Use this to write scripts that can use the tool's methods
         *
         * @param string $file                    Path to the script file to run.
         * @param mixed  ...$evaluation_arguments Arguments to pass to the script file being run.
         *
         * @return void
         * @api
         */
        public function eval_file(string $file, mixed ...$evaluation_arguments)
        {
            if (! is_file($file)) {
                $this->error("File does not exist, check the path: $file");
            }

            if (! is_readable($file)) {
                $this->error("File is not readable, check permissions: $file");
            }

            require_once $file;
        }//end eval_file()


        /**
         * Help info for install method
         *
         * @var mixed
         *
         * @internal
         */
        protected $___install = [
            "Install a packaged PHP console tool",
            ["Install path", "string"],
        ];

        /**
         * Install the packaged tool.
         *
         * @param string $install_path Path to which to install the tool.  Defaults to configured install path.
         *
         * @return void
         * @api
         */
        public function install(string $install_path = null)
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


        /**
         * Help info for update method
         *
         * @var mixed
         *
         * @internal
         */
        protected $___update = [
            "Update an installed PHP console tool"
        ];

        /**
         * Update the tool - check for an update and install if available
         *
         * @return void
         * @api
         */
        public function update()
        {
            // Make sure update is available
            // - Not automatic, Show output
            if (! $this->updateCheck(false, true)) {
                return;
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


        /**
         * Help info for version method
         *
         * @var mixed
         *
         * @internal
         */
        protected $___version = [
            "Output version information"
        ];

        /**
         * Show the current version of the running/local tool.
         *
         * @param boolean $output Whether to output information while running.
         *
         * @return mixed The version string if output is false, otherwise false.
         * @api
         */
        public function version(bool $output = true): mixed
        {
            $class          = get_called_class();
            $version_string = $class::SHORTNAME . ' version ' . $class::VERSION;

            if ($output) {
                $this->output($version_string);
                return false;
            } else {
                return $version_string;
            }
        }//end version()

        /**
         * Check for an update, and parse out all relevant information if one exists
         *
         * @param boolean $auto   Whether this is an automatic check or triggered intentionally.
         * @param boolean $output Whether to output information while running..
         *
         * @return boolean True if newer version exists. False if:
         *  - no new version or
         *  - if auto, but auto check is disabled or
         *  - if auto, but not yet time to check or
         *  - if update is disabled
         */
        protected function updateCheck(bool $auto = true, bool $output = false): bool
        {
            $this->log("Running update check");

            if (empty($this->update_version_url)) {
                if (($output and ! $auto) or $this->verbose) {
                    $this->output("Update is disabled - update_version_url is empty");
                }
                // update disabled
                return false;
            }

            if (is_null($this->update_exists)) {
                $now = time();

                // If this is an automatic check, make sure it's time to check again
                if ($auto) {
                    $this->log("Designated as auto-update");

                    // If disabled, return false
                    if ($this->update_auto <= 0) {
                        $this->log("Auto-update is disabled - update_auto <= 0");
                        // auto-update disabled
                        return false;
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
                            // not yet time to check
                            return false;
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
         *
         *  - Provides the functionality for Command::clear()
         *
         * @return void
         * @api
         */
        public function clear()
        {
            system('clear');
        }//end clear()

        /**
         * Exec - run bash command
         *
         *  - run a command
         *  - return the output as a string
         *
         * @param string  $command The bash command to be run.
         * @param boolean $error   Whether to show an error if return code indicates error - otherwise, will show a warning.
         *
         * @return string Output resulting from the command run.
         */
        public function exec(string $command, bool $error = false): string
        {
            $this->log("exec: $command");
            exec($command, $output, $return);
            $output = empty($output) ? "" : "\n\t" . implode("\n\t", $output);
            if ($return) {
                $output = empty($output) ? "Return Code: " . $return : $output;
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
         * Error output.  Shows a message with ERROR prefix and either exits with the specified error code or prompts whether to continue.
         *
         *  - 100 - expected error - eg. aborted due to user input
         *  - 200 - safety / caution error (eg. running as root)
         *  - 500 - misc. error
         *
         * @param mixed   $data               Error message/data to output.
         * @param mixed   $code               Error code to exit with - false = no exit.
         * @param boolean $prompt_to_continue Whether to prompt/ask user whether to continue.
         *
         * @return void
         */
        public function error(mixed $data, mixed $code = 500, bool $prompt_to_continue = false)
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
         * Warn output.  Shows a message with WARNING prefix and optionally prompts whether to continue.
         *
         * @param mixed   $data               Error message/data to output.
         * @param boolean $prompt_to_continue Whether to prompt/ask user whether to continue.
         *
         * @return void
         */
        public function warn(mixed $data, bool $prompt_to_continue = false)
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
         * Log output - outputs data if $this->verbose is true - otherwise, does nothing.
         *
         * @param mixed $data Message/data to output.
         *
         * @return void
         */
        public function log(mixed $data)
        {
            if (! $this->verbose) {
                return;
            }

            $this->output($data);
        }//end log()

        /**
         * Output data to console.
         *
         * @param mixed   $data        Message/data to output.
         * @param boolean $line_ending Whether to output a line ending.
         * @param boolean $stamp_lines Whether to prefix each line with a timestamp.
         *
         * @return void
         */
        public function output(mixed $data, bool $line_ending = true, bool $stamp_lines = null)
        {
            $data = $this->stringify($data);

            $stamp_lines = is_null($stamp_lines) ? $this->stamp_lines : $stamp_lines;
            if ($stamp_lines) {
                echo $this->stamp() . ' ... ';
            }

            echo $data . ($line_ending ? "\n" : "");
        }//end output()

        /**
         * Output a progress bar to the console.
         *
         *  - If $this-verbose is set to true, then this shows text progress instead
         *  - $count/$total $description
         *
         * @param integer $count       The index of current progress - eg. current item index - start with 0.
         * @param integer $total       The total amount of progress to be worked through - eg. total number of items.
         * @param string  $description The description to be shown for verbose output.
         *
         * @return void
         */
        public function outputProgress(int $count, int $total, string $description = "remaining")
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
         * Stringify some data for output.  Processes differently depending on the type of data.
         *
         * @param mixed $data Message/data to output.
         *
         * @return string The stringified data - ready for output.
         */
        public function stringify(mixed $data): string
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
         * Colorize/decorate/format a string for output to console.
         *
         * @param string $string     The string to be colorized.
         * @param mixed  $foreground The foreground color(s)/decoration(s) to use.
         * @param mixed  $background The background color(s)/decoration(s) to use.
         * @param mixed  $other      Other color(s)/decoration(s) to use.
         *
         * @uses CONSOLE_COLORS::$foreground
         * @uses CONSOLE_COLORS::$background
         * @uses CONSOLE_COLORS::$other
         *
         * @return string The colorized / decorated string, ready for output to console.
         */
        public function colorize(string $string, mixed $foreground = null, mixed $background = null, mixed $other = []): string
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
         * Output up to 3 columns of text - ie. used by help output
         *
         * @param string $col1 Text to output in first column.
         * @param string $col2 Text to output in second column.
         * @param string $col3 Text to output in third column.
         *
         * @uses Console_Abstract::COL1_WIDTH
         * @uses Console_Abstract::COL2_WIDTH
         *
         * @return void
         */
        public function output3col(string $col1, string $col2 = null, string $col3 = null)
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
         * Output a line break
         *
         *  - basically output an empty line, which will automatically include a newline/break
         *
         * @return void
         */
        public function br()
        {
            $this->output('');
        }//end br()

        /**
         * Log a line break
         *
         *  - Same as br() - but only output if $this->verbose is true
         *
         * @return void
         */
        public function brl()
        {
            if (! $this->verbose) {
                return;
            }

            $this->br;
        }//end brl()

        /**
         * Output a horizonal rule / line - filling the width of the terminal
         *
         * @param string $c      The character to use to create the line.
         * @param string $prefix A prefix string to output before the line.
         *
         * @return void
         */
        public function hr(string $c = '=', string $prefix = "")
        {
            // Maybe adjust width - if stamping lines
            $adjust = 0;
            if ($this->stamp_lines) {
                $stamp = $this->stamp() . ' ... ';
                $adjust = strlen($stamp);
            }
            $string = str_pad($prefix, $this->getTerminalWidth() - $adjust, $c);
            $this->output($string);
        }//end hr()

        /**
         * Log a horizonal rule / line - filling the width of the terminal
         *
         *  - Same as hr() - but only output if $this->verbose is true
         *
         * @param string $c      The character to use to create the line.
         * @param string $prefix A prefix string to output before the line.
         *
         * @return void
         */
        public function hrl(string $c = '=', string $prefix = "")
        {
            if (! $this->verbose) {
                return;
            }

            $this->hr($c, $prefix);
        }//end hrl()

        /**
         * Pause during output for debugging/stepthrough
         *
         *  - Only pauses if $this->step is set to true
         *  - Will pause and wait for user to hit enter
         *  - If user enters 'finish' (case-insensitive) $this->step will be set to false
         *    and the program will finish execution normally
         *
         * @param string $message Message to show before pausing.
         *
         * @return void
         */
        public function pause(string $message = "[ ENTER TO STEP | 'FINISH' TO CONTINUE ]")
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
         * Sleep for the set time, with countdown
         *
         * @param integer $seconds Number of seconds to wait.
         * @param string  $message Formatted string to show with %d for the number of seconds.
         *
         * @return void
         */
        public function sleep(int $seconds = 3, string $message = "Continuing in %d...")
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
         * Get selection from list via CLI input
         *
         * @param array   $list       List of items to select from.
         * @param mixed   $message    Message to show, prompting input - defaults to false, no message.
         * @param integer $default    Default selection index if no input - defaults to 0 - first item.
         * @param boolean $q_to_quit  Add a 'q' option to the list to quite - defaults to true.
         * @param array   $preselects Pre-selected values - eg. could have been passed in as arguments to CLI.
         *                            Passed by reference so they can be passed through a chain of selections and/or narrowed-down lists.
         *                            Defaults to empty array - no preselections.
         * @param mixed   $livefilter Whether to filter the list while typing - falls back to configuration if not set.
         *
         * @return string The value of the item in the list that was selected.
         */
        public function select(array $list, mixed $message = false, int $default = 0, bool $q_to_quit = true, array &$preselects = [], mixed $livefilter = null): string
        {
            // Fall back to configuration if not specified
            if (is_null($livefilter)) {
                $livefilter = $this->livefilter;
            }
            if ($livefilter !== "disabled" && $livefilter !== false) {
                $entry = "";
                $error = "";

                if ($message) {
                    if ($message === true) {
                        $message = "";
                    }

                    if (! is_null($default)) {
                        $message .= " ($default)";
                    }
                    $message .= ": ";
                    $message = $this->colorize($message, null, null, 'bold');
                }

                $list_height = ($this->getTerminalHeight() / 2) - 10;
                $list_count = count($list);
                if ($list_count < $list_height) {
                    $list_height = $list_count;
                }

                while (true) {
                    $list = array_values($list);

                    $single_filtered_item = false;
                    $filtered_items = [];
                    foreach ($list as $i => $item) {
                        $item_simple = preg_replace('/[^a-z0-9]+/i', '', $item);
                        $entry_simple = preg_replace('/[^a-z0-9]+/i', '', $entry);
                        if (
                            $entry === ""
                            || stripos($item, $entry) !== false
                            || stripos($item_simple, $entry_simple) !== false
                            || is_numeric($entry) && stripos($i, $entry) !== false
                        ) {
                            $filtered_items[$i] = $item;
                        }
                    }

                    if (empty($filtered_items)) {
                        $error .= "[NO MATCHES - press X to clear/reset]";
                    } else if (count($filtered_items) === 1) {
                        $single_filtered_item = true;
                    }

                    // Auto-enter once filtered down to one option
                    if ($livefilter === 'autoenter' && $single_filtered_item) {
                        break;
                    }

                    // Display help info & prompt
                    $this->clear();
                    $this->output("Type to filter options");
                    if ($q_to_quit) {
                        $this->output(" - Q to quit");
                    }
                    $this->output(" - H to backspace");
                    $this->output(" - X to clear");
                    $this->output(" - G/E/M to Go/Enter - selecting top/bolded option");
                    $this->hr();
                    if ($message) {
                        $this->output($message);
                    }

                    // Display the list with indexes, with the top/default highlighted
                    $color = $single_filtered_item ? 'green' : null;
                    $bold = 'bold';
                    $output_lines = 0;
                    foreach ($filtered_items as $i => $item) {
                        // If there are too many items and we are at height limit, cut off
                        if (
                            $output_lines >= ($list_height - 1)
                            && count($filtered_items) > $list_height
                        ) {
                            $output_lines++;
                            $this->output('... [MORE BELOW IN LIST - TYPE TO FILTER] ...');
                            break;
                        }

                        $this->output($this->colorize("$i. $item", $color, null, $bold));
                        $output_lines++;
                        $color = null;
                        $bold = null;
                    }
                    for (; $output_lines < $list_height; $output_lines++) {
                        $this->br();
                    }
                    $this->hr();

                    // Clear the line for the prompt
                    echo str_pad(" ", $this->getTerminalWidth());
                    // Set cursor to first column
                    echo chr(27) . "[0G";
                    // Output the prompt & entry so far
                    $error = $this->colorize($error, 'red');
                    $ready_for_enter = "";
                    if ($single_filtered_item) {
                        $ready_for_enter = $this->colorize("Press [Enter] or [Space] to procced with highlighted item", "blue");
                    }
                    echo "$error $ready_for_enter\n";
                    echo "> $entry";
                    $error = "";

                    $char = $this->input(false, null, false, 'single', 'single_hide', false);

                    // For some reason, both space & enter come through as a new line
                    if ($char === "\n") {
                        // If there's only one item, treat this as Enter
                        if ($single_filtered_item) {
                            break;
                        }
                        // Otherwise treat it as space
                        $char = " ";
                    } else {
                        $char = trim($char);
                    }

                    if ($char === 'Q' && $q_to_quit) {
                        $this->warn('Selection Exited');
                        exit;
                    } elseif ($char === 'X') {
                        $entry = "";
                    } elseif (in_array($char, ['H', ""])) {
                        $entry = substr($entry, 0, -1);
                    } elseif (in_array($char, ['G', "E", "M", "", "\n"])) {
                        break;
                    } elseif (preg_match('/[A-Z]/', $char)) {
                        $error .= "[INVALID KEY - lowercase only]";
                    } else {
                        $entry = "$entry$char";
                    }
                }//end while

                $this->clear();
                foreach ($filtered_items as $s => $selected) {
                    // Return the top item in the filtered list
                    return $selected;
                }
            }//end if

            /*
             * Otherwise, fall back to normal select, ie. not livefilter
             */

            // Display the list with indexes
            $list = array_values($list);
            foreach ($list as $i => $item) {
                $this->output("$i. $item");
            }

            // Maybe show q - Quit option
            if ($q_to_quit) {
                $this->output("q. Quit and exit");
            }

            $max   = count($list) - 1;
            $index = -1;
            $entry = false;

            // Continually prompt for input until we get a valid entry
            while ($index < 0 or $index > $max) {
                // Warn if input was not in list
                if ($entry !== false) {
                    $this->warn("Invalid selection $entry");
                }

                if (empty($preselects)) {
                    // Prompt for human input entry
                    $this->output("Enter number or part of selection");
                    $entry = $this->input($message, $default);
                } else {
                    // If some pre-selection was passed in, shift it off as the entry
                    $entry = array_shift($preselects);
                }

                // Maybe process q - Quit option
                if ($q_to_quit and (strtolower(trim($entry)) == 'q')) {
                    $this->warn('Selection Exited');
                    exit;
                }

                // For non-numeric entries, find matching item(s)
                if (! is_numeric($entry)) {
                    $filtered_items = [];

                    // Look for list item containing the entry (case-insensitive)
                    foreach ($list as $item) {
                        if (stripos($item, $entry) !== false) {
                            $filtered_items[] = $item;
                        }
                    }

                    if (count($filtered_items) == 1) {
                        // Single match? Return it
                        return $filtered_items[0];
                    } elseif (! empty($filtered_items)) {
                        // Multiple matches? New select to narrow down further
                        return $this->select($filtered_items, $message, 0, $q_to_quit, $preselects);
                    }
                }

                // Make sure it's really a good entry
                // Eg. avoid 1.2 => 1 or j => 0
                // - which would result in unwanted behavior for bad entries
                $index = (int) $entry;
                if ((string) $entry !== (string) $index) {
                    $index = -1;
                }
            }//end while

            return $list[$index];
        }//end select()

        /**
         * Get a confirmation from the user (yes/no prompt)
         *
         *  - Gets input from user and returns true if it's 'y' or 'Y' - otherwise, false
         *
         * @param mixed   $message     Message to show before prompting user for input.
         * @param string  $default     Default value if nothing entered by user. Defaults to 'y'.
         * @param boolean $required    Whether input is required before continuing. Defaults to false.
         * @param boolean $single      Whether to prompt for a single character from the user - eg. they don't have to hit enter. Defaults to true.
         * @param boolean $single_hide Whether to hide the user's input when prompting for a single character. Defaults to false.
         *
         * @uses Console_Abstract::input()
         *
         * @return boolean Whether yes was entered by the user.
         */
        public function confirm(mixed $message, string $default = 'y', bool $required = false, bool $single = true, bool $single_hide = false): bool
        {
            $yn = $this->input($message, $default, $required, $single, $single_hide);
            $this->br();

            // True if first letter of response is y or Y
            return strtolower(substr($yn, 0, 1)) == 'y';
        }//end confirm()

        /**
         * Edit some text in external editor
         *
         * @param string  $text     The starting text to be edited. Defaults to "".
         * @param string  $filename The name of the temporary file to save when editing.  If null, filename will be generated with timestamp.
         * @param boolean $modify   Whether to use $this->editor_modify_exec vs. $this->editor_exec.  Defaults to false.
         *
         * @return string The edited contents of the file.
         */
        public function edit(string $text = "", string $filename = null, bool $modify = false): string
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
         * Get input from the user via CLI
         *
         * @param mixed  $message     Message to show before prompting user for input.
         * @param string $default     Default value if nothing entered by user.
         * @param mixed  $required    Whether input is required before continuing. Defaults to false.
         * @param mixed  $single      Whether to prompt for a single character from the user - eg. they don't have to hit enter. Defaults to false.
         * @param mixed  $single_hide Whether to hide the user's input when prompting for a single character. Defaults to false.
         * @param mixed  $trim        Whether to trim the user's input before returning. Defaults to true.
         *
         * @return string The text input from the user.
         */
        public function input(mixed $message = false, string $default = null, mixed $required = false, mixed $single = false, mixed $single_hide = false, mixed $trim = true): string
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

                if ($trim) {
                    $line = trim($line);
                }

                // Entered input - return
                if ($line !== "") {
                    return $line;
                }

                // Input not required? Return default
                if (! $required) {
                    return is_null($default) ? "" : $default;
                }

                // otherwise, warn, loop and try again
                $this->warn("Input required - please try again");
            }//end while
        }//end input()

        /**
         * Get a formatted timestamp - to use as logging prefix, for example.
         *
         * @return string Current timestamp
         */
        public function stamp(): string
        {
            return date('Y-m-d_H.i.s');
        }//end stamp()

        /**
         * Get the config directory
         *
         * - hidden folder (. prefix)
         * - named based on tool shortname
         * - in home folder
         *
         * @return string Full path to config directory.
         */
        public function getConfigDir(): string
        {
            if (is_null($this->config_dir)) {
                $this->config_dir = $this->getHomeDir() . DS . '.' . static::SHORTNAME;
            }

            return $this->config_dir;
        }//end getConfigDir()

        /**
         * Get the main/default config file
         *
         *  - config.hjson (HJSON - https://hjson.github.io/)
         *  - in config directory
         *
         * @uses Console_Abstract::getConfigDir()
         *
         * @return string Full path to config file.
         */
        public function getConfigFile(): string
        {
            if (is_null($this->config_file)) {
                $config_dir        = $this->getConfigDir();
                $this->config_file = $config_dir . DS . 'config.hjson';
            }

            return $this->config_file;
        }//end getConfigFile()

        /**
         * Get the user's home directory
         *
         * - Attempts to handle situation where user is running via sudo
         *   and still get the logged-in user's home directory instead of root
         *
         * @return string Full path to home directory.
         */
        public function getHomeDir(): string
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

                // Not running as root via sudo
                if (empty($sudo_user)) {
                    // Windows doesn't have 'HOME' set necessarily
                    if (empty($_SERVER['HOME'])) {
                        $this->home_dir = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEDRIVE'];

                    // Simplest and most typical - get home dir from env vars.
                    } else {
                        $this->home_dir = $_SERVER['HOME'];
                    }

                // Running as root via sudo - get home dir of sudo user (if not root)
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
         * Initialize config file
         *
         *  - Load config if file already exists
         *  - Create config file if it doesn't yet exist
         *
         * @uses Console_Abstract::configure()
         *
         * @return boolean Whether the config was successfully saved.
         */
        public function initConfig(): bool
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

                /*
                 * Setting config to save, based on current values
                 * - This adds any new config with default values to the config file
                 * - This also enables the initial config file creation with all default values
                 */
                $this->config_to_save = [];
                foreach ($this->getPublicProperties() as $property) {
                    $this->config_to_save[$property] = $this->$property;
                }
                ksort($this->config_to_save);

                $this->config_initialized = true;

                $this->saveConfig();
            } catch (Exception $e) {
                // Notify user
                $this->warn('ISSUE WITH CONFIG INIT: ' . $e->getMessage(), true);
                return false;
            }//end try

            return true;
        }//end initConfig()

        /**
         * Save config values to file on demand
         *
         * @return boolean Whether the config was successfully saved.
         */
        public function saveConfig(): bool
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
                        $value = '';
                        $help = $this->_help_var($key, 'option');
                        if (!empty($help)) {
                            $help = $this->_help_param($help);
                            $type = $help[1];
                            $info = $help[0];

                            $value = " // ($type) $info";
                        }
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
                return false;
            }//end try

            return true;
        }//end saveConfig()

        /**
         * Takes an argument which may have come from the shell and prepares it for use
         *
         *  - Trims strings
         *  - Optionally parses into a specified type, ie. array or boolean
         *
         * @param mixed   $value      The value to prepare.
         * @param mixed   $default    The default to return if $value is empty.
         *                            If this is an array, then force_type is auto-set to 'array'.
         * @param string  $force_type Optional type to parse from value.
         *                             - 'array': split on commas and/or wrap to force value to be an array.
         *                             - 'boolean': parse value as boolean (ie. 1/true/yes => true, otherwise false).
         *                             - Note: defaults to 'array' if $default is an array.
         * @param boolean $trim       Whether to trim whitespace from the value(s).  Defaults to true.
         *
         * @return mixed The prepared result.
         */
        public function prepArg(mixed $value, mixed $default, string $force_type = null, bool $trim = true): mixed
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
         * Open a URL in the browser
         *
         * @param string $url The URL to open.
         *
         * @uses Console_Abstract::browser_exec
         * @uses Console_Abstract::exec()
         *
         * @return void
         */
        public function openInBrowser(string $url)
        {
            $command = sprintf($this->browser_exec, $url);
            $this->exec($command, true);
        }//end openInBrowser()

        /**
         * Configure a property (if public and therefore configurable - otherwise gives a notice)
         *
         *  - First ensures the passed key is a public property
         *  - If public, sets the property to the passed value
         *    and adds the data to $this->config_to_save
         *  - If not public, shows a notice
         *
         * @param string  $key        The property to set.  Can be passed in either snake_case or kebab-case.
         * @param mixed   $value      The value to set the propery.
         * @param boolean $save_value Whether to save the value - ie. add it to config_to_save.
         *
         * @return void
         */
        public function configure(string $key, mixed $value, bool $save_value = false)
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

        /**
         * Get an initialied curl handle for a given URL, with our preferred defaults.
         *
         * @param string  $url            The URL to hit.
         * @param boolean $fresh_no_cache Whether to force this to be a fresh request / disable caching.
         *
         * @uses Console_Abstract::ssl_check to determine if curl should verify peer/host SSLs. Warns if not verifying.
         *
         * @return CurlHandle The initialized curl handle.
         */
        public function getCurl(string $url, bool $fresh_no_cache = false): CurlHandle
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

        /**
         * Execute a curl request, do some basic error processing, and return the response.
         *
         * @param CurlHandle $curl The curl handle to execute.
         *
         * @return string The response from the curl request.
         */
        public function execCurl(CurlHandle $curl): string
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

        /**
         * Update query arguments on a curl handle - soft merge (default) or overwrite existing query arguments.
         *
         * @param CurlHandle $curl      The curl handle to be updated.
         * @param array      $args      The new query arguments to set.
         * @param boolean    $overwrite Whether to overwrite existing query args.  Defaults to false.
         *
         * @return CurlHandle The upated curl handle.
         */
        public function updateCurlArgs(CurlHandle $curl, array $args, bool $overwrite = false): CurlHandle
        {
            // Get info from previous curl
            $curl_info = curl_getinfo($curl);

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
            curl_setopt($curl, CURLOPT_URL, $new_url);

            return $curl;
        }//end updateCurlArgs()

        /**
         * Get the contents of a specified cache file, if it has not expired.
         *
         * @param mixed   $subpath    The path to the cache file within the tool's cache folder.
         * @param integer $expiration The expiration lifetime of the cache file in seconds.
         *
         * @return mixed The contents of the cache file, or false if file expired, does not exist, or can't be read.
         */
        public function getCacheContents(mixed $subpath, int $expiration = null): mixed
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

        /**
         * Set the contents of a specified cache file.
         *
         * @param mixed  $subpath  The path to the cache file within the tool's cache folder.
         * @param string $contents The contents to write to the cache file.
         *
         * @return mixed The path to the new cache file, or false if failed to write.
         */
        public function setCacheContents(mixed $subpath, string $contents): mixed
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
         * Get the contents of a specified temp file
         *
         * @param mixed $subpath The path to the temp file within the tool's temp folder.
         *
         * @return mixed The contents of the temp file, or false if file does not exist or can't be read.
         */
        public function getTempContents(mixed $subpath): mixed
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

        /**
         * Set the contents of a specified temp file.
         *
         * @param mixed  $subpath  The path to the temp file within the tool's temp folder.
         * @param string $contents The contents to write to the temp file.
         *
         * @return mixed The path to the new temp file, or false if failed to write.
         */
        public function setTempContents(mixed $subpath, string $contents): mixed
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
         *
         * @param mixed $content Content to be displayed - string or array of lines.
         * @param array $options Options for pagination - array with kesy:
         *                        - 'starting_line'     Starting line number for pagination / vertical scrolling.
         *                        - 'starting_column'   Current starting column (for scrolling if wrap is off). NOT YET IMPLEMENTED.
         *                        - 'wrap'              Whether to wrap lines that are too long for screen width.
         *                        - 'line_buffer'       Number of lines to allow space for outside of output.
         *                        - 'output'            Whether to output directly to screen.
         *                        - 'include_page_info' Whether to include pagination info in output.
         *                        - 'fill_height'       Whether to fill the entire screen height - buffer with empty lines.
         *
         * @return array Details for pagination - array with keys:
         *                - 'output'        The output to display on the screen currently.
         *                - 'starting_line' The number of the current starting line.
         *                - 'page_length'   The length of the output being displayed.
         *                - 'ending_line'   THe number of the last line being displayed.
         */
        public function paginate(mixed $content, array $options = []): array
        {
            $options = array_merge([
                'starting_line' => 1,
                'starting_column' => 1,
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
            // for start/end line breaks
            $max_height = $max_height - 2;
            if ($options['include_page_info']) {
                // for page info and extra line break
                $max_height = $max_height - 2;
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
         * Get parameters for a given method - for help display
         *
         * @param string $method Method for which to get parameters.
         *
         * @return array The parameter names.
         */
        protected function _getMethodParams(string $method): array
        {
            $r      = new ReflectionObject($this);
            $rm     = $r->getMethod($method);
            $params = [];
            foreach ($rm->getParameters() as $param) {
                $params[] = $param->name;
            }
            return $params;
        }//end _getMethodParams()

        /**
         * Cached value for getPublicProperties()
         *
         * @var array
         */
        protected $_public_properties = null;
        /**
         * Get all public properties for the tool.  These are the properties that can
         * be set via flags and configuration file.
         *
         * @return array List of all public property names.
         */
        public function getPublicProperties(): array
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

        /**
         * Cached value for getCliInputHandle()
         *
         * @var resource
         */
        protected $_cli_input_handle = null;

        /**
         * Get a CLI Input handle resource - to read input from user
         *
         * @return resource CLI Input handle
         */
        protected function getCliInputHandle()
        {
            if (is_null($this->_cli_input_handle)) {
                $this->_cli_input_handle = fopen("php://stdin", "r");
            }

            return $this->_cli_input_handle;
        }//end getCliInputHandle()

        /**
         * Close the CLI Input handle if it has been opened
         *
         * @used-by Console_Abstract::_shutdown()
         *
         * @return void
         */
        protected function closeCliInputHandle()
        {
            if (! is_null($this->_cli_input_handle)) {
                fclose($this->_cli_input_handle);
            }
        }//end closeCliInputHandle()

        /**
         * Cached value for getTerminalHeight()
         *
         * @var integer
         */
        protected $_terminal_height = null;

        /**
         * Get the current height of the terminal screen for output
         *
         * @param mixed $fresh Whether to get height fresh (vs. reading cached value). Defaults to false.
         *
         * @return integer The height of the terminal output screen.
         */
        public function getTerminalHeight(mixed $fresh = false): int
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

        /**
         * Cached value for getTerminalWidth()
         *
         * @var integer
         */
        protected $_terminal_width = null;

        /**
         * Get the current width of the terminal screen for output
         *
         * @param mixed $fresh Whether to get width fresh (vs. reading cached value). Defaults to false.
         *
         * @return integer The width of the terminal output screen.
         */
        public function getTerminalWidth(mixed $fresh = false): int
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
         *
         *  Supports:
         *   - Bold
         *   - Italic (showing as dim)
         *   - Links (Underlined with link in parentheses)
         *   - Unordered Lists ( - )
         *   - Ordered Lists ( 1. )
         *   - Hierarchical lists (via indentation)
         *
         *  Not Yet Supported:
         *   - Text colors
         *   - Underline styles
         *   - Indentation styles
         *   - Less commonly supported terminal styles
         *
         *  Runs recursively to parse out all elements.
         *
         * @param mixed   $dom    HTML string or DOM object to be parsed.
         * @param integer $depth  The current depth of parsing - for hierarchical elements - eg. lists.
         * @param string  $prefix The current prefix to use - eg. for lists.
         *
         * @return string The processed output, ready for terminal.
         */
        public function parseHtmlForTerminal(mixed $dom, int $depth = 0, string $prefix = ""): string
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
                // output for this child
                $_output = "";
                // prefix for this child's children - start with current prefix, passed to function
                $_prefix = $prefix;
                // suffix for end of this child
                $_suffix = "";
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
         *
         * - uses Parsedown
         * - uses some defaults based on common use
         * - alternatively, can call Parsedown directly - ie. if other options are needed
         *
         * @param string $text The markdown to be parsed.
         *
         * @return string The resulting HTML.
         */
        public function parseMarkdownToHtml(string $text): string
        {
            $html = Parsedown::instance()
                ->setBreaksEnabled(true)
                ->setMarkupEscaped(true)
                ->setUrlsLinked(false)
                ->text($text);
            return $html;
        }//end parseMarkdownToHtml()

        /**
         * Decode JSON - supports HJSON as well
         *
         * @param string $json    The raw JSON/HJSON string to be interpreted.
         * @param mixed  $options Options to pass through to HJSON.  Also supports 'true' for associative array, to match json_decode builtin.
         *
         * @return mixed The decoded data - typically object or array.
         */
        public function json_decode(string $json, mixed $options = []): mixed
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

        /**
         * Encode data as HJSON
         *
         * @param mixed $data    The data to be encoded.
         * @param array $options Options to pass through to HJSON.
         *
         * @return string The encoded HJSON string.
         */
        public function json_encode(mixed $data, array $options = []): string
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

        /**
         * Clean up data after decoding from, or before encoding as HJSON
         *
         * @param mixed $data The data to clean up - passed by reference.
         *
         * @return void Updates $data directly by reference.
         */
        protected function _json_cleanup(mixed &$data)
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

        /**
         * Explicitly throws an error for methods called that don't exist.
         *
         * This is to prevent an infinite loop, since some classes have
         * a magic __call method that tries methods on Console_Abstract
         *
         * @param string $method    The method that is being called.
         * @param array  $arguments The arguments being passed to the method.
         *
         * @throws Exception Errors every time to prevent infinite loop.
         * @return mixed Doesn't really return anything - always throws error.
         */
        public function __call(string $method, array $arguments = []): mixed
        {
            throw new Exception("Invalid method '$method'");

            return false;
        }//end __call()

        /**
         * Startup logic - extend from child/tool if needed.
         *
         * @param array $arg_list The arguments passed to the tool.
         *
         * @return void
         */
        protected function _startup(array $arg_list)
        {
            // Nothing to do by default
        }//end _startup()

        /**
         * Shutdown logic - extend from child/tool if needed.
         *
         * @param array $arg_list The arguments passed to the tool.
         *
         * @return void
         */
        protected function _shutdown(array $arg_list)
        {
            $this->closeCliInputHandle();
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
