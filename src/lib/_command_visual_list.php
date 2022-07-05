<?php

/**
 * Visual command that shows a list of items
 */

if (!class_exists("Command_Visual_List")) {
    class Command_Visual_List extends Command_Visual
    {
        public $list = [];
        public $list_original = [];
        public $list_selection = [];

        public $focus = 0;
        public $starting_line = 1;
        public $page_info = [];

        public $multiselect = false;
        public $template = "{_KEY}: {_VALUE}";

        /**
         * Constructor
         */
        public function __construct($main_tool, $list, $options = [])
        {
            $this->setMainTool($main_tool);

            if (empty($list)) {
                $this->error("Empty list", false, true);
                return false;
            }

            $this->list_original = $list;
            $this->list = $list;

            if (isset($options['multiselect'])) {
                $this->multiselect = $options['multiselect'];
            }

            if (isset($options['template'])) {
                $this->template = $options['template'];
            }

            $commands = [
                'filter' => [
                    'description' => 'Filter the list',
                    'keys' => 'f',
                    'callback' => [
                        'subcommands' => [
                            'filter_by_text' => [
                                'description' => 'Text/Regex Search',
                                'keys' => '/',
                                'callback' => [$this, 'filter_by_text'],
                                'continue' => false,
                            ],
                            'filter_remove' => [
                                'description' => 'Remove filters - go back to full list',
                                'keys' => 'r',
                                'callback' => [$this, 'filter_remove'],
                                'continue' => false,
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
            ];

            if (isset($options['commands'])) {
                $commands = $this->mergeArraysRecursively($commands, $options['commands']);
            }
            $options['commands'] = $commands;

            parent::__construct($main_tool, $options);
        }//end __construct()


        /**
         * Run the listing subcommand
         */
        public function run()
        {
            $count = count($this->list);

            $content_to_display = [];
            $i = 0;
            foreach ($this->list as $key => $item) {
                // Prep output using template
                $output = $this->template;

                $key_start = strpos($output, '{_KEY}');
                if ($key_start !== false) {
                    $output = substr_replace($output, $key, $key_start, 6);
                }

                $value_start = strpos($output, '{_VALUE}');
                if ($key_start !== false) {
                    $output = substr_replace($output, $this->stringify($item), $value_start, 8);
                }

                $this->_fill_item = $item;
                $content = preg_replace_callback('/\{[^\}]+\}/', [$this, '_fill_item_to_template'], $output);
                if ($this->focus == $i) {
                    $content = "[*] " . $content;
                    $content = $this->colorize($content, 'blue', 'light_gray', ['bold']);
                } else {
                    $content = "[ ] " . $content;
                }
                $content_to_display[] = $content;
                $i++;
            }//end foreach

            $this->clear();
            $this->page_info = $this->paginate($content_to_display, [
                'starting_line' => $this->starting_line,
            ]);

            $continue_loop = $this->promptAndRunCommand($this->commands);

            if ($continue_loop !== false) {
                $this->log("Looping!");
                $this->pause();
                $this->run();
            }
        }//end run()


        /**
         * Used to fill item data into template string
         *  by preg_replace_callback
         */
        protected $_fill_item;
        protected function _fill_item_to_template($matches)
        {
            $value = "";
            $format = false;

            $match = $matches[0];
            $match = substr($match, 1, -1);

            $match_exploded = explode("|", $match);
            if (count($match_exploded) > 1) {
                $format = array_pop($match_exploded);
            }
            $key_string = array_shift($match_exploded);

            $keys = explode(":", $key_string);
            $target = $this->_fill_item;
            while (!empty($keys)) {
                $key = array_shift($keys);
                if (isset($target[$key])) {
                    $target = $target[$key];
                } else {
                    $keys = [];
                }
            }
            if (is_string($target)) {
                $value = $target;
            }

            // var_dump($value);
            if (!empty($format) and !empty($value)) {
                $value = sprintf($format, $value);
            }
            // var_dump($value);
            return $value;
        }//end _fill_item_to_template()


        /**
         * Built-in commands
         */

        // Reload
        public function reload()
        {
            $list = parent::reload();
            $this->list_original = $list;
            $this->list = $list;
        }//end reload()


        // Filter - remove filters
        public function filter_remove()
        {
            $this->list = $this->list_original;
            $this->focus_top();
        }//end filter_remove()


        // Filter - by text/regex (search)
        public function filter_by_text()
        {
            while (true) {
                $this->clear();
                $this->hr();
                $this->output("Filter by Text:");
                $this->output(" - Case insensive if search string is all lowercase");
                $this->output(" - Start with / to use RegEx");
                $this->hr();
                $search_pattern = $this->input("Enter text", null, false);

                $current_list = $this->list;
                $filtered_list = [];

                $search_pattern = trim($search_pattern);
                if (empty($search_pattern)) {
                    return;
                }

                $is_regex = (substr($search_pattern, 0, 1) == '/');
                $case_insensitive = (!$is_regex and (strtolower($search_pattern) == $search_pattern));

                foreach ($current_list as $item) {
                    $json = json_encode($item);
                    $match = false;
                    if ($is_regex) {
                        $match = preg_match($search_pattern, $json);
                    } elseif ($case_insensitive) {
                        $match = ( stripos($json, $search_pattern) !== false );
                    } else {
                        $match = ( strpos($json, $search_pattern) !== false );
                    }

                    if ($match) {
                        $filtered_list[] = $item;
                    }
                }

                if (!empty($filtered_list)) {
                    // Results found - display as the new current list
                    $this->list = $filtered_list;
                    $this->focus_top();
                    return;
                } else {
                    // No results - offer to try a new search
                    $this->output("No Results found");
                    $new_search = $this->confirm("Try a new search?", "y", false, true);
                    if (! $new_search) {
                        return;
                    }
                    // Otherwise, will continue the loop
                }
            }//end while
        }//end filter_by_text()



        // Focus up/down/top/bottom
        public function focus_up()
        {
            if ($this->focus > 0) {
                $this->focus--;
            }
            $this->page_to_focus();
        }//end focus_up()

        public function focus_down()
        {
            $max_focus = (count($this->list) - 1);
            if ($this->focus < $max_focus) {
                $this->focus++;
            }
            $this->page_to_focus();
        }//end focus_down()

        public function focus_top()
        {
            $this->focus = 0;
            $this->page_to_focus();
        }//end focus_top()

        public function focus_bottom()
        {
            $max_focus = (count($this->list) - 1);
            $this->focus = $max_focus;
            $this->page_to_focus();
        }//end focus_bottom()


        /**
         * Helper functions
         */

        // Adjust page view to focus
        public function page_to_focus()
        {
            $focus = $this->focus + 1;
            if ($focus < $this->starting_line) {
                $this->starting_line = $focus;
            }
            if ($focus > $this->page_info['ending_line']) {
                $this->starting_line = ($focus - $this->page_info['page_length']) + 1;
            }
        }//end page_to_focus()


        // Get Focused Data
        public function getFocusedKey()
        {
            $list_keys = array_keys($this->list);
            return $list_keys[$this->focus];
        }//end getFocusedKey()

        public function getFocusedValue()
        {
            $list_values = array_values($this->list);
            return $list_values[$this->focus];
        }//end getFocusedValue()
    }//end class

}//end if

// Note: leave this for packaging ?>
