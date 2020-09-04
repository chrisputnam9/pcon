<?php
/**
 * PHP Console management tools
 * - helper to create and package your own PHP console tools
 */
Class PCon extends Console_Abstract
{

    const VERSION = "1.1.9";
    // Name of script and directory to store config
    const SHORTNAME = 'pcon';

    /**
     * Callable Methods
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
     */
    protected static $HIDDEN_CONFIG_OPTIONS = [
        'console_abstract_path',
    ];

    // Config Variables
    protected $__console_abstract_path = ["Path to console_abstract.php", "string"];
    public $console_abstract_path = CONSOLE_ABSTRACT_PATH;

    // Don't bother with hash, no download built in
	public $update_check_hash = false;

    // URL to check for updates
	public $update_version_url = "https://raw.githubusercontent.com/chrisputnam9/pcon/master/README.md";

    // When update exists, show this message
    protected $update_behavior='Pull git repository to update PHP Console tool (PCon) to latest version';

    protected $___create = [
        "Create a new PHP console tool - interactive, or specify options",
        ["Name of tool", "string"],
        ["Name of folder to create - defaults to name of tool if specified", "string"],
        ["Path in which to create folder - defaults to parent of pcon folder", "string"],
        ["Whether to create parent path", "binary"],
    ];
	public function create($tool_name=null, $tool_folder=null, $_parent_path=null, $create_parent=false)
    {
        $this->output('Creating New PHP Console Tool');

        $tool_name = $this->prepArg($tool_name, null);
        $tool_folder = $this->prepArg($tool_folder, null);
        $_parent_path = $this->prepArg($_parent_path, null);
        $create_parent = $this->prepArg($create_parent, false, 'boolean');

        if (is_null($tool_name))
        {
            $tool_name = $this->input("Enter name of tool to create", null, true);
        }

        $tool_name = trim($tool_name);

        $tool_shortname = strtolower($tool_name);
        $tool_shortname = preg_replace('/[^0-9a-z]+/i', '-', $tool_shortname);

        if (is_null($tool_folder))
        {
            $tool_folder = $this->input("Enter name of folder for tool", $tool_name);
        }

        // Validate argument and/or get input
        $parent_path = null;
        while (is_null($parent_path))
        {
            // Any argument passed/
            if (is_null($_parent_path))
            {
                $parent_path = $this->input("Enter path in which to set up the tool folder", realpath(__DIR__ . DS . '..' . DS . '..' . DS));

                $parent_path = $this->prepArg($parent_path, null);
            }
            else
            {
                $parent_path = $_parent_path;
                $_parent_path = null;
            }

            if (is_string($parent_path))
            {
                $parent_path = rtrim($parent_path, '/');
            }

            if (!is_dir($parent_path))
            {
                if ($create_parent)
                {
                    $this->log('Creating "' . $parent_path . '"');
                    $success = mkdir($parent_path, 0755, true);
                    if (!$success)
                    {
                        $this->warn("Unable to create folder ($parent_path) - please try again");
                        $parent_path = null; // invalidate to loop
                    }
                }
                else
                {
                    $this->warn("This folder doesn't exist ($parent_path) - please specify an existing location");
                    $parent_path = null; // invalidate to loop
                }
            }
        }

        $tool_path = $parent_path . DS . $tool_folder;

        if (is_dir($tool_path))
        {
            $this->error("The folder ($tool_path) already exists!");
        }

        $this->output('Details Summary:');
        $this->output('Name: ' . $tool_name);
        $this->output('Short Name: ' . $tool_shortname);
        $this->output('Full Path: ' . $tool_path);

        $this->hrl();
        $this->log('Creating tool path - ' . $tool_path);
        mkdir($tool_path, 0755, true);

        $this->log('Copying over template files');

        $package_dir = __DIR__ . DS . 'pkg' . DS;

        // Copy primary executable sample
        $tool_exec_path = $tool_path . DS . $tool_shortname;
        copy($package_dir . 'sample', $tool_exec_path);
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
            'class_name' => $class_name,
            'console_abstract_path' => $this->console_abstract_path,
            'tool_name' => $tool_name,
            'tool_shortname' => $tool_shortname,
        ];

        foreach ([$tool_exec_path, $tool_src_path] as $file)
        {
            $contents = file_get_contents($file);
            foreach ($template_vars as $search => $replace) {
                $contents = str_replace('{{'.$search.'}}', $replace, $contents);
            }
            file_put_contents($file, $contents);
        }

        $this->hr();
        $this->output('Finished:');
        $this->output($created);
    }

    protected $___package = [
        "Package a PHP console tool - interactive, or specify options",
        ["Path to tool folder", "string"],
    ];
    public function package($_tool_path=null)
    {
        $this->output('Packaging PHP Console Tool');

        $_tool_path = $this->prepArg($_tool_path, null);

        // Validate argument and/or get input
        $tool_path = null;
        while (is_null($tool_path))
        {
            // Any argument passed
            if (is_null($_tool_path))
            {
                $tool_path = $this->input("Enter path to primary file of console tool to package", null, true);
                $tool_path = $this->prepArg($tool_path, null);
            }
            else
            {
                $tool_path = $_tool_path;
                $_tool_path = null;
            }

            if (is_string($tool_path))
            {
                $tool_path = rtrim($tool_path, '/');
            }

            if (!is_file($tool_path))
            {
                $this->warn("This file doesn't exist ($tool_path) - please specify the executable script an existing tool");
                $tool_path = null;
            }
        }

        $tool_dir = dirname($tool_path);
        $tool_file = basename($tool_path);

        $this->output('Details Summary:');
        $this->output(' - Tool to Package: ' . $tool_path);
        $this->output(' - Tool Directory: ' . $tool_dir);
        $this->output(' - Tool Directory: ' . $tool_dir);

        $this->log('Creating dist folder');
        $dist_dir = $tool_dir . DS . 'dist';
        if (!is_dir($dist_dir))
        {
            mkdir($dist_dir, 0755);
        }
        $tool_exec_path = $dist_dir . DS . $tool_file;
        
        $this->log('Creating script file at: ' . $tool_exec_path);
        $tool_exec_handle = fopen($tool_exec_path, 'w');

        // Start with basic template, hashbang
        $package_dir = __DIR__ . DS . 'pkg' . DS;
        $package_template_src = file_get_contents($package_dir . 'package_template');
        fwrite($tool_exec_handle, $package_template_src);

        // Add Lib Files
        $lib_files = scandir(__DIR__ . DS . "lib");
        foreach ($lib_files as $lib_file)
        {
            $lib_file_path = __DIR__ . DS . "lib" . DS . $lib_file;
            if (is_file($lib_file_path))
            {
                $lib_file_src = file_get_contents($lib_file_path);
                fwrite($tool_exec_handle, $lib_file_src);
            }
        }

        // Add console abstract
        $console_abstract_src = file_get_contents(__DIR__ . DS . 'console_abstract.php');
        fwrite($tool_exec_handle, $console_abstract_src);

        $this->log('Loading requirements into script file');

        // Include script to get include paths, but don't output anything or run anything
        $_PACKAGING=true;
        ob_start();
        require_once($tool_path);
        ob_clean();

        foreach ($src_includes as $src_include)
        {
            $include_src = file_get_contents($src_include);
            fwrite($tool_exec_handle, $include_src);
        }
        $_PACKAGING=false;

        $this->log('Making script file executable');
        chmod($tool_exec_path, 0755);

        $package_hash = hash_file($this->update_hash_algorithm, $tool_exec_path);

        $this->output('Packaging complete to: ' . $tool_exec_path);
        $this->output('Package hash ('.$this->update_hash_algorithm.'): ' . $package_hash);

    }

    protected $___test_colors = [
        "Test available colors and text decoration - escape codes",
        ["Types to test - comma-separted - defaults to test all", "string"],
    ];
    public function test_colors($types=null)
    {
        $types = $this->prepArg($types, ["foreground","background","other"]);

        foreach ($types as $type)
        {
            if (empty(CONSOLE_COLORS::$$type))
            {
                $this->warn("Invalid type specified - $type");
            }

            $this->hr();
            $this->output($this->colorize(ucwords($type) . ":", null, null, "bold"));
            foreach (CONSOLE_COLORS::$$type as $text => $code)
            {
                $foreground = null;
                $background = null;
                $other = null;
                $$type = $text;

                if ($type == 'background')
                {
                    $foreground = ($background == 'light_gray') ? 'dark_gray' : 'white';
                    $text .= " ($foreground text)";
                }

                $this->output(str_pad(" - $text ", 35, ".") . " " . $this->colorize("In order to understand recursion, one must first understand recursion.", $foreground, $background, $other));
            }
        }

        $this->pause();
    }

}

// Kick it all off
PCon::run($argv);

// Note: leave this for packaging ?>
