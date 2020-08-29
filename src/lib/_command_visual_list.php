<?php
/**
 * Visual command that shows a list of items
 */
class Command_Visual_List extends Command_Visual
{
    public $list=[];
    public $list_original=[];
    public $list_selection=[];

    public $focus=0;
    public $starting_line=1;
    public $page_info=[];

    public $commands = [];

    public $reload_function;
    public $reload_data;

    public $multiselect = false;
    public $template = "{_KEY}: {_VALUE}";

    /**
     * Constructor
     */
    public function __construct($main_tool, $list, $reload_function, $reload_data=[], $options=[])
    {
        parent::__construct($main_tool);

        if (empty($list))
        {
            $this->error("Empty list", false, true);
            return false;
        }

        if (empty($reload_function) or !is_callable($reload_function))
        {
            $this->error("Argument reload_function is required");
        }

        $this->list_original = $list;
        $this->list = $list;

        $this->reload_function = $reload_function;
        $this->reload_data = $reload_data;

        $this->commands = [
            'help' => [
                'description' => 'Help - list available commands',
                'keys' => '?',
                'callback' => [$this, 'help'],
            ],
            'filter' => [
                'description' => 'Filter the list',
                'keys' => 'f',
                'callback' => [
                    'subcommands' => [
                        'filter_by_text' => [
                            'description' => 'Text/Regex Search',
                            'keys' => '/',
                            'callback' => [$this, 'filter_by_text'],
                        ],
                        'filter_remove' => [
                            'description' => 'Remove filters - go back to full list',
                            'keys' => 'r',
                            'callback' => [$this, 'filter_remove'],
                        ],
                    ],
                ],
            ],
            'filter_by_text' => [
                'description' => 'Search list (filter by text entry)',
                'keys' => '/',
                'callback' => [$this, 'filter_by_text'],
            ],
            'focus_up' => [
                'description' => 'Up - move focus up in the list',
                'keys' => 'k',
                'callback' => [$this, 'focus_up'],
            ],
            'focus_down' => [
                'description' => 'Down - move focus down in the list',
                'keys' => 'j',
                'callback' => [$this, 'focus_down'],
            ],
            'focus_top' => [
                'description' => 'Top - move focus to top of list',
                'keys' => 'g',
                'callback' => [$this, 'focus_top'],
            ],
            'focus_bottom' => [
                'description' => 'Bottom - move focus to bottom of list',
                'keys' => 'G',
                'callback' => [$this, 'focus_bottom'],
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
        if (isset($options['commands']))
        {
            $this->commands = $this->mergeArraysRecursively($this->commands, $options['commands']);
        }
        $this->cleanCommandArray($this->commands);

        if (isset($options['multiselect']))
        {
            $this->multiselect = $options['multiselect'];
        }

        if (isset($options['template']))
        {
            $this->template = $options['template'];
        }
    }

    /**
     * Run the listing subcommand
     */
    public function run()
    {
        $count = count($this->list);

        $content_to_display=[];
        $i=0;
        foreach ($this->list as $key => $item)
        {
            // Prep output using template
            $output = $this->template;

            $key_start = strpos($output, '{_KEY}');
            if ($key_start !== false)
            {
                $output = substr_replace($output, $key, $key_start, 6);
            }

            $value_start = strpos($output, '{_VALUE}');
            if ($key_start !== false)
            {
                $output = substr_replace($output, $this->stringify($item), $value_start, 8);
            }

            $this->_fill_item = $item;
            $content = preg_replace_callback('/\{[^\}]+\}/', [$this, '_fill_item_to_template'], $output );
            if ($this->focus == $i)
            {
                $content = "[*] " . $content;
                $content = $this->colorize($content , 'blue', 'light_gray', ['bold']);
            }
            else
            {
                $content = "[ ] " . $content;
            }
            $content_to_display[]= $content;
            $i++;
        }

        $this->clear();
        $this->page_info = $this->paginate($content_to_display, [
            'starting_line' => $this->starting_line,
        ]);

        $continue_loop = $this->promptAndRunCommand($this->commands);

        if ($continue_loop !== false)
        {
            $this->log("Looping!");
            $this->pause();
            $this->run();
        }
    }

        /**
         * Used to fill item data into template string
         *  by preg_replace_callback
         */
        protected $_fill_item;
        protected function _fill_item_to_template ($matches)
        {
            $value = "";
            $format = false;

            $match = $matches[0];
            $match = substr($match, 1, -1);

            $match_exploded = explode("|", $match);
            if (count($match_exploded) > 1)
            {
                $format = array_pop($match_exploded);
            }
            $key_string = array_shift($match_exploded);

            $keys = explode(":", $key_string);
            $target = $this->_fill_item;
            while (!empty($keys))
            {
                $key = array_shift($keys);
                if (isset($target[$key]))
                {
                    $target = $target[$key];
                }
                else
                {
                    $keys = [];
                }
            }
            if (is_string($target))
            {
                $value = $target;
            }

            //var_dump($value);
            if (!empty($format) and !empty($value))
            {
                $value = sprintf($format, $value);
            }
            //var_dump($value);

            return $value;
        }

    /**
     * Built-in commands
     */

    // Help
    public function help($specific=false)
    {
        $this->clear();
        $this->hr();
        $this->output("Available Commands:");
        $this->hr();
        foreach ($this->commands as $command_slug => $command_details)
        {
            $command_name = $command_details['description'];
            $command_keys = $command_details['keys'];
            $this->output( str_pad( implode( ",", $command_keys) . " ", 15, ".") . " " . $command_name );
        }
        $this->hr();
        $this->input("Hit any key to exit help", null, false, true);
    }

    // Reload
    public function reload()
    {
        $list = call_user_func($this->reload_function, $this->reload_data, $this);
        $this->list_original = $list;
        $this->list = $list;
    }

    // Quit
    public function quit()
    {
        return false; // Back to previous area, basically
    }

    // Filter - remove filters
    public function filter_remove()
    {
        $this->list = $this->list_original;
        $this->focus_top();
    }

    // Filter - by text/regex (search)
    public function filter_by_text()
    {
        while (true)
        {
            $this->clear();
            $this->hr();
            $this->output("Filter by Text:");
            $this->output(" - Case insensive if search string is all lowercase");
            $this->output(" - Start with / to use RegEx");
            $this->hr();
            $search_pattern = $this->input("Enter text", null, true);

            $current_list = $this->list;
            $filtered_list = [];

            $search_pattern = trim($search_pattern);
            $is_regex = (substr($search_pattern, 0, 1) == '/');
            $case_insensitive = (!$is_regex and (strtolower($search_pattern) == $search_pattern));

            foreach ($current_list as $item)
            {
                $json = json_encode($item);
                $match = false;
                if ($is_regex)
                {
                    $match = preg_match($search_pattern, $json);
                }
                elseif ($case_insensitive)
                {
                    $match = ( stripos($json, $search_pattern) !== false );
                }
                else
                {
                    $match = ( strpos($json, $search_pattern) !== false );
                }

                if ($match)
                {
                    $filtered_list[]= $item;
                }
            }

            if (!empty($filtered_list))
            {
                // Results found - display as the new current list
                $this->list = $filtered_list;
                $this->focus_top();
                return;
            }
            else
            {
                // No results - offer to try a new search
                $this->output("No Results found");
                $new_search = $this->confirm("Try a new search?", "y", false, true);
                if ( ! $new_search)
                {
                    return;
                }
                // Otherwise, will continue the loop
            }
        }
    }


    // Focus up/down
    public function focus_up()
    {
        if ($this->focus > 0)
        {
            $this->focus--;
        }
        $this->page_to_focus();
    }
    public function focus_down()
    {
        $max_focus = (count($this->list) - 1);
        if ($this->focus < $max_focus)
        {
            $this->focus++;
        }
        $this->page_to_focus();
    }
    public function focus_top()
    {
        $this->focus = 0;
        $this->page_to_focus();
    }
    public function focus_bottom()
    {
        $max_focus = (count($this->list) - 1);
        $this->focus = $max_focus;
        $this->page_to_focus();
    }

    /**
     * Helper functions
     */

    // Adjust page view to focus
    public function page_to_focus()
    {
        $focus = $this->focus+1;
        if ($focus < $this->starting_line)
        {
            $this->starting_line = $focus;
        }
        if ($focus > $this->page_info['ending_line'])
        {
            $this->starting_line = ($focus - $this->page_info['page_length']) + 1;
        }
    }

    // Get Focused Data
    public function getFocusedKey()
    {
        $list_keys = array_keys($this->list);
        return $list_keys[$this->focus];
    }
    public function getFocusedValue()
    {
        $list_values = array_values($this->list);
        return $list_values[$this->focus];
    }
}
