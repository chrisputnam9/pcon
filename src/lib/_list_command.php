<?php
/**
 * List Command
 */
class List_Command extends Command_Abstract
{
    public $list=[];
    public $list_original=[];
    public $list_selection=[];

    public $focus=0;
    public $starting_line=1;
    public $page_info=[];

    public $filters = [];

    public $commands = [];

    public $reload_function;
    public $reload_data;

    public $multiselect = false;
    public $template = "{_KEY}: {_VALUE}";

    public $continue_loop=true;

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

        $this->filters = [
            'Text/Regex Search' => [
                '/',
                [$this, 'filter_by_text'],
            ],
            'Remove filters - go back to full list' => [
                'r',
                [$this, 'filter_remove'],
            ],
        ];
        if (isset($options['filters']))
        {
            $this->filters = array_merge($this->filters, $options['filters']);
        }
        foreach ($this->filters as $filter_name => $filter_details)
        {
            if (is_string($filter_details[0])) $filter_details[0] = str_split($filter_details[0]);
            if (!is_array($filter_details[0])) $this->error("Invalid filter keys for '$filter_name'");
            $this->filters[$filter_name][0] = $filter_details[0];
        }



        $this->commands = [
            'Help - list available commands' => [
                '?',
                [$this, 'help'],
            ],
            'Filter list' => [
                'f',
                [$this, 'filter'],
            ],
            'Search list (filter by text entry)' => [
                '/',
                [$this, 'filter_by_text'],
            ],
            'Up - move focus up in the list' => [
                ['k'],
                [$this, 'focus_up'],
            ],
            'Down - move focus down in the list' => [
                ['j'],
                [$this, 'focus_down'],
            ],
            'Top - move focus to top of list' => [
                'g',
                [$this, 'focus_top'],
            ],
            'Bottom - move focus to bottom of list' => [
                'G',
                [$this, 'focus_bottom'],
            ],
            'Reload - refresh list' => [
                'r',
                [$this, 'reload'],
            ],
            'Quit - exit the list' => [
                'q',
                [$this, 'quit'],
            ],
        ];
        if (isset($options['commands']))
        {
            $this->commands = array_merge($this->commands, $options['commands']);
        }
        foreach ($this->commands as $command_name => $command_details)
        {
            if (is_string($command_details[0])) $command_details[0] = str_split($command_details[0]);
            if (!is_array($command_details[0])) $this->error("Invalid command keys for '$command_name'");
            $this->commands[$command_name][0] = $command_details[0];
        }

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
        $input = $this->input(true, null, false, 'single', 'hide_input');
        $matched = false;

        foreach ($this->commands as $command_name => $command_details)
        {
            $command_keys = $command_details[0];
            $command_callable = $command_details[1];

            if (in_array($input, $command_keys))
            {
                $matched = true;
                if (is_callable($command_callable))
                {
                    $list_values = array_values($this->list);
                    $focused_value = $list_values[$this->focus];

                    $list_keys = array_keys($this->list);
                    $focused_key = $list_keys[$this->focus];

                    call_user_func($command_callable, $this, $focused_key, $focused_value);

                    // May have modified data, so we call reload, unless reload was what we just called
                    if ($input != 'r')
                    {
                        $this->reload();
                    }
                }
                else $this->error("Uncallable method for $input", false, true);
            }
        }

        if (!$matched)
        {
            $this->log("Invalid input $input");
        }

        if ($this->continue_loop)
        {
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
        foreach ($this->commands as $command_name => $command_details)
        {
            $command_keys = $command_details[0];
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
        $this->continue_loop = false;
    }

    // Filter - present ways to filter
    public function filter()
    {
        while (true)
        {
            $this->clear();
            $this->hr();
            $this->output("Available Filters:");
            $this->hr();
            foreach ($this->filters as $filter_name => $filter_details)
            {
                $filter_keys = $filter_details[0];
                $this->output( str_pad( implode( ",", $filter_keys) . " ", 15, ".") . " " . $filter_name );
            }
            $this->output( str_pad( "q ", 15, ".") . " Quit - cancel filtering and go back to list" );
            $this->hr();
            $input = $this->input(true, null, false, 'single', 'hide_input');

            $matched = false;

            if ($input == 'q') return;

            foreach ($this->filters as $filter_name => $filter_details)
            {
                $filter_keys = $filter_details[0];
                $filter_callable = $filter_details[1];

                if (in_array($input, $filter_keys))
                {
                    $matched = true;
                    if (is_callable($filter_callable))
                    {
                        call_user_func($filter_callable, $this);
                        // Not expected to modify the data itself, so we do not call reload
                        return;
                    }
                    else $this->error("Uncallable method for $input", false, true);
                }
            }

            if (!$matched)
            {
                $this->log("Invalid input $input");
            }
        }
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
}
