<?php
/**
 * PCon Class File
 *
 * @package pcon
 * @author  chrisputnam9
 */

/**
 * PHP Console management tool
 *
 * - Defines a CLI helper tool to create and package your own PHP Console tools
 * - NOTE: PCon *itself* will not function as a packaged tool, though it may be
 *    possible to package it. It relies on being run in unpackaged form.
 */
class PCon extends Console_Abstract
{
    /**
     * Current tool version
     *
     * @var string
     */
    public const VERSION = "1.7.1";

    /**
     * Tool shortname - used as name of configurationd directory.
     *
     * @var string
     */
    public const SHORTNAME = 'pcon';

    /**
     * Callable Methods / Sub-commands
     *  - Must be public methods defined on the class
     *
     * @var array
     */
    protected static $METHODS = [
        'create',
        'package',
        'test_colors',
    ];

    /**
     * Config options that are hidden from help output
     * - Add config values here that would not typically be overridden by a flag
     * - Cleans up help output and avoids confusion
     *
     * @var array
     */
    protected static $HIDDEN_CONFIG_OPTIONS = [
        'console_abstract_path',
        'default_author',
    ];

    /*********************************
     * Config Variables
     ********************************/

    /**
     * Help info for $console_abstract_path
     *
     * @var array
     *
     * @internal
     */
    protected $__console_abstract_path = ["Path to console_abstract.php", "string"];

    /**
     * Path to the console abstract class to be used
     *
     * @var array
     */
    public $console_abstract_path = CONSOLE_ABSTRACT_PATH;

    /**
     * Help info for $default_author
     *
     * @var array
     *
     * @internal
     */
    protected $__default_author = ["Default author handle for new tools.", "string"];

    /**
     * Default author handle for new tools.
     *
     * @var string
     */
    public $default_author = "";

    /**
     * Whether to check the hash when downloading updates
     *
     *  - Overriding to false for PCon - no downloading built in
     *
     * @var boolean
     * @api
     */
    public $update_check_hash = false;

    /**
     * The URL to check for updates
     *
     *  - PCon will check the README file - typical setup
     *
     * @var string
     * @see PCon::update_version_url
     * @api
     */
    public $update_version_url = "https://raw.githubusercontent.com/chrisputnam9/pcon/master/README.md";

    /**
     * Update behavior - either "DOWNLOAD" or custom text
     *
     *  - For PCon - standard updates don't make much sense, so instead we show a message
     *    instructing the user to update the git repository if updates are available
     *
     * @var string
     */
    protected $update_behavior = 'Pull git repository to update PHP Console tool (PCon) to latest version';

    /**
     * Help info for create method
     *
     * @var array
     *
     * @internal
     */
    protected $___create = [
        "Create a new PHP console tool - interactive, or specify options",
        ["Name of tool", "string"],
        ["Name of folder to create - defaults to name of tool if specified", "string"],
        ["Path in which to create folder - defaults to parent of pcon folder", "string"],
        ["Whether to create parent path", "binary"],
    ];

    /**
     * Method to create a new tool.
     *
     *  - Arguments can be passed in - otherwise, will prompt interactively for values.
     *
     * @param string  $tool_name        Name of the tool to be created.
     *                                  Will prompt if not passed.
     * @param string  $author_handle    The author handle to use in Doc Block
     *                                  Will prompt if not passed.
     *                                  Defaults to configured $this->default_author.
     * @param string  $tool_description The description of the tool.
     *                                  Will prompt if not passed.
     *                                  Defaults to FILL_IN_LATER.
     * @param string  $update_url       The update URL for the tool.
     *                                  Will promopt if not passed.
     *                                  Defaults to FILL_IN_LATER.
     * @param string  $tool_folder      Folder name for the tool.
     *                                  Will prompt if not passed.
     *                                  Defaults to the slug version of $tool_name.
     * @param string  $_parent_path     Path in which to set up the tool folder.
     *                                  Will prompt if not passed.
     *                                  Defaults to the parent folder of the PCon tool.
     * @param boolean $create_parent    Whether to create the parent path if it doesn't exist.
     *                                  Defaults to false.
     *
     * @return void
     * @api
     */
    public function create(
        string $tool_name = null,
        string $author_handle = null,
        string $tool_description = null,
        string $update_url = null,
        string $tool_folder = null,
        string $_parent_path = null,
        bool $create_parent = false
    ) {
        $this->output('Creating New PHP Console Tool');

        $tool_name        = $this->prepArg($tool_name, null);
        $author_handle    = $this->prepArg($author_handle, null);
        $tool_description = $this->prepArg($tool_description, null);
        $update_url       = $this->prepArg($update_url, null);
        $tool_folder      = $this->prepArg($tool_folder, null);
        $_parent_path     = $this->prepArg($_parent_path, null);
        $create_parent    = $this->prepArg($create_parent, false, 'boolean');

        if (is_null($tool_name)) {
            $tool_name = $this->input("Enter name of tool to create", null, true);
        }
        $tool_name = trim($tool_name);

        $tool_shortname = strtolower($tool_name);
        $tool_shortname = preg_replace('/[^0-9a-z]+/i', '-', $tool_shortname);

        if (is_null($author_handle)) {
            $author_handle = $this->input("Enter the tool's Author's handle (eg. Github username)", $this->default_author);
        }

        if (is_null($update_url)) {
            $update_url = $this->input("Enter the tool's update URL", "FILL_IN_LATER");
        }

        if (is_null($tool_description)) {
            $tool_description = $this->input("Enter the tool description", "FILL_IN_LATER");
        }

        if (is_null($tool_folder)) {
            $tool_folder = $this->input("Enter name of folder for tool", $tool_shortname);
        }

        // Validate argument and/or get input
        $parent_path = null;
        while (is_null($parent_path)) {
            // Any argument passed/
            if (is_null($_parent_path)) {
                $parent_path = $this->input("Enter path in which to set up the tool folder", realpath(__DIR__ . DS . '..' . DS . '..' . DS));

                $parent_path = $this->prepArg($parent_path, null);
            } else {
                $parent_path  = $_parent_path;
                $_parent_path = null;
            }

            if (is_string($parent_path)) {
                $parent_path = rtrim($parent_path, '/');
            }

            if (! is_dir($parent_path)) {
                if ($create_parent) {
                    $this->log('Creating "' . $parent_path . '"');
                    $success = mkdir($parent_path, 0755, true);
                    if (! $success) {
                        $this->warn("Unable to create folder ($parent_path) - please try again");
                        // invalidate to loop
                        $parent_path = null;
                    }
                } else {
                    $this->warn("This folder doesn't exist ($parent_path) - please specify an existing location");
                    // invalidate to loop
                    $parent_path = null;
                }
            }
        }//end while

        $tool_path = $parent_path . DS . $tool_folder;

        if (is_dir($tool_path)) {
            $this->error("The folder ($tool_path) already exists!");
        }

        $this->output('Details Summary:');
        $this->output('Author: ' . $author_handle);
        $this->output('Name: ' . $tool_name);
        $this->output('Short Name: ' . $tool_shortname);
        $this->output('Update URL: ' . $update_url);
        $this->output('Full Path: ' . $tool_path);

        $this->hrl();
        $this->log('Creating tool path - ' . $tool_path);
        mkdir($tool_path, 0755, true);

        $this->log('Symlinking PCon folder from configured path');
        $pcon_dir        = realpath(__DIR__ . DS . "..");
        $symlink_command = 'ln -s "' . $pcon_dir . '" "' . $tool_path . DS . '"';
        $this->exec($symlink_command);

        $this->log('Copying over template files');

        $package_dir = __DIR__ . DS . 'pkg' . DS;

        // Copy gitignore
        $tool_gitignore = $tool_path . DS . '.gitignore';
        copy($package_dir . '.gitignore', $tool_gitignore);

        // Copy primary executable sample - pcon
        $tool_exec_path = $tool_path . DS . $tool_shortname;
        copy(__DIR__ . DS . '..' . DS . 'pcon', $tool_exec_path);
        chmod($tool_exec_path, 0755);

        // Create src directory
        mkdir($tool_path . DS . 'src', 0755, true);

        // Copy Sample class
        $tool_src_path = $tool_path . DS . 'src' . DS . $tool_shortname . '.php';
        copy($package_dir . 'sample.php', $tool_src_path);

        $created = $this->exec('ls -halR "' . $tool_path . '"');

        $this->log('Updating details in template files');

        $class_name = preg_replace('/[^0-9a-z]+/i', ' ', $tool_shortname);
        $class_name = ucwords($class_name);
        $class_name = str_replace(' ', '_', $class_name);

        $template_vars = [
            'pcon.php' => $tool_shortname . '.php',
            '___AUTHOR_HANDLE___' => $author_handle,
            '___CLASS_NAME___' => $class_name,
            '___TOOL_NAME___' => $tool_name,
            '___TOOL_DESCRIPTION___' => $tool_description,
            '___TOOL_SHORTNAME___' => $tool_shortname,
            '___UPDATE_URL___' => $update_url,
        ];

        foreach ([$tool_exec_path, $tool_src_path] as $file) {
            $contents = file_get_contents($file);
            foreach ($template_vars as $search => $replace) {
                $contents = str_replace($search, $replace, $contents);
            }
            file_put_contents($file, $contents);
        }

        $this->hr();
        $this->output('Finished:');
        $this->output($created);
    }//end create()

    /**
     * Help info for package method
     *
     * @var array
     *
     * @internal
     */
    protected $___package = [
        "Package a PHP console tool - interactive, or specify options",
        ["Path to tool folder", "string"],
    ];

    /**
     * Method to package a tool for release.
     *
     * @param string $_tool_path Path to primary file of console tool to package.
     *
     * @return void
     * @api
     */
    public function package(string $_tool_path = null)
    {
        $this->output('Packaging PHP Console Tool');

        $_tool_path = $this->prepArg($_tool_path, null);

        // Validate argument and/or get input
        $tool_path = null;
        while (is_null($tool_path)) {
            // Any argument passed
            if (is_null($_tool_path)) {
                $tool_path = $this->input("Enter path to primary file of console tool to package", null, true);
                $tool_path = $this->prepArg($tool_path, null);
            } else {
                $tool_path  = $_tool_path;
                $_tool_path = null;
            }

            if (is_string($tool_path)) {
                $tool_path = rtrim($tool_path, '/');
            }

            if (! is_file($tool_path)) {
                $this->warn("This file doesn't exist ($tool_path) - please specify the executable script an existing tool");
                $tool_path = null;
            }
        }

        $tool_dir  = dirname($tool_path);
        $tool_file = basename($tool_path);

        $this->output('Details Summary:');
        $this->output(' - Tool to Package: ' . $tool_path);
        $this->output(' - Tool Directory: ' . $tool_dir);
        $this->output(' - Tool Directory: ' . $tool_dir);

        $this->log('Creating dist folder');
        $dist_dir = $tool_dir . DS . 'dist';
        if (! is_dir($dist_dir)) {
            mkdir($dist_dir, 0755);
        }
        $tool_exec_path = $dist_dir . DS . $tool_file;

        $this->log('Creating script file at: ' . $tool_exec_path);
        $tool_exec_handle = fopen($tool_exec_path, 'w');

        // Start with basic template, hashbang
        $package_dir          = __DIR__ . DS . 'pkg' . DS;
        $package_template_src = file_get_contents($package_dir . 'package_template');
        fwrite($tool_exec_handle, $package_template_src);

        // Add Lib Files
        $lib_files = scandir(__DIR__ . DS . "lib");
        foreach ($lib_files as $lib_file) {
            $lib_file_path = __DIR__ . DS . "lib" . DS . $lib_file;
            if (is_file($lib_file_path)) {
                $lib_file_src = file_get_contents($lib_file_path);
                fwrite($tool_exec_handle, $lib_file_src);
            }
        }

        // Add console abstract
        $console_abstract_src = file_get_contents(__DIR__ . DS . 'console_abstract.php');
        fwrite($tool_exec_handle, $console_abstract_src);

        $this->log('Loading requirements into script file');

        // Include script to get include paths, but don't output anything or run anything
        $_PACKAGING = true;
        ob_start();
        require_once($tool_path);
        ob_clean();

        foreach ($src_includes as $src_include) {
            $include_src = file_get_contents($src_include);
            fwrite($tool_exec_handle, $include_src);
        }
        $_PACKAGING = false;

        $this->log('Making script file executable');
        chmod($tool_exec_path, 0755);

        $package_hash = hash_file($this->update_hash_algorithm, $tool_exec_path);

        $this->output('Packaging complete to: ' . $tool_exec_path);
        $this->output('Package hash (' . $this->update_hash_algorithm . '): ' . $package_hash);
    }//end package()

    /**
     * Help info for test_colors method
     *
     * @var array
     *
     * @internal
     */
    protected $___test_colors = [
        "Test available colors and text decoration - escape codes",
        ["Types to test - comma-separted - defaults to test all", "string"],
    ];

    /**
     * Method to test console color / decoration output.
     *
     * @param mixed $types Types of color/decoration to be tested.
     *                     Defaults to all types - foreground, background, and other.
     *
     * @uses CONSOLE_COLORS - tests all colors / styles defined therein
     *
     * @return void
     * @api
     */
    public function test_colors($types = null)
    {
        $types = $this->prepArg($types, ["foreground","background","other"]);

        foreach ($types as $type) {
            if (empty(CONSOLE_COLORS::$$type)) {
                $this->warn("Invalid type specified - $type");
            }

            $this->hr();
            $this->output($this->colorize(ucwords($type) . ":", null, null, "bold"));
            foreach (CONSOLE_COLORS::$$type as $text => $code) {
                $foreground = null;
                $background = null;
                $other      = null;
                $$type      = $text;

                if ($type == 'background') {
                    $foreground = ($background == 'light_gray') ? 'dark_gray' : 'white';
                    $text      .= " ($foreground text)";
                }

                $this->output(str_pad(" - $text ", 35, ".") . " " . $this->colorize("In order to understand recursion, one must first understand recursion.", $foreground, $background, $other));
            }
        }//end foreach

        $this->pause();
    }//end test_colors()
}//end class

if (empty($__no_direct_run__)) {
    // Kick it all off
    PCon::run($argv);
}

// Note: leave the end tag for packaging
?>
