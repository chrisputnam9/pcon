<?php
/**
 * PHP Console management tools
 * - helpers to create and package your own PHP console tools
 */
Class PCon extends Console_Abstract
{
    const VERSION = 1;

    // Name of script and directory to store config
    const SHORTNAME = 'pcon';

    /**
     * Callable Methods
     */
    protected static $METHODS = [
        'create',
        'package',
    ];

    // Config Variables
    protected $__console_abstract_path = ["Path to console_abstract.php", "string"];
    public $console_abstract_path = CONSOLE_ABSTRACT_PATH;

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

        // Copy primary executable sample
        $tool_exec_path = $tool_path . DS . $tool_shortname;
        copy(__DIR__ . DS . 'sample', $tool_exec_path);
        chmod($tool_exec_path, 0755);

        // Create src directory
        mkdir($tool_path . DS . 'src', 0755, true);

        // Copy Sample class
        $tool_src_path = $tool_path . DS . 'src' . DS . $tool_shortname . '.php';
        copy(__DIR__ . DS . 'sample.php', $tool_src_path);

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
                $tool_path = $this->input("Enter path to console tool to package", null, true);
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

            if (!is_dir($tool_path))
            {
                $this->warn("This folder doesn't exist ($tool_path) - please specify an existing tool directory");
            }
        }

        $this->output('Details Summary:');
        $this->output('Full Path: ' . $tool_path);

        $this->log('Creating dist folder');
        $this->log('Creating script file');
        $this->log('Loading requirements into script file');
        $this->log('Making script file executable');

    }
}

// Kick it all off
PCon::run($argv);

// Note: leave this for packaging ?>
