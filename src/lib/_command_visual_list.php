<?php
/**
 * Defines Command_Visual_List class
 *
 * @package pcon
 * @author  chrisputnam9
 */

if (!class_exists("Command_Visual_List")) {

    /**
     * Command_Visual_List abstract class
     *
     *  - A class to present a List interface in response to some command
     *  - Shows a list of data with keys to scroll, filter, search
     */
    class Command_Visual_List extends Command_Visual
    {
        /**
         * The list of data currently being displayed
         *
         *  - Updated when filtering, etc.
         *
         * @var array
         */
        public $list = [];

        /**
         * The original list of data passed in
         *
         *  - Maintained to allow reverting from filters, etc.
         *
         * @var array
         */
        public $list_original = [];

        /**
         * The line index that currently has focus
         *
         * @var integer
         */
        public $focus = 0;

        /**
         * The line index that is the start of the current view
         *
         * @var integer
         */
        public $starting_line = 1;

        /**
         * Information regarding current pagination - ie. what lines are in view
         *
         * @var array
         */
        public $page_info = [];

        /**
         * The template to use to display each line
         *
         * @var string
         */
        public $template = "{_KEY}: {_VALUE}";

        /**
         * Constructor
         *
         * @param Console_Abstract $main_tool The instance of the main tool class
         *  - which should extend Console_Abstract.
         * @param array            $list      The list of data to be displayed.
         * @param array            $options   Array of options to initialize.
         */
        public function __construct(Console_Abstract $main_tool, array $list, array $options = [])
        {
            $this->setMainTool($main_tool);

            if (empty($list)) {
                $this->error("Empty list", false, true);
                return;
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
         * Run the listing subcommand - display the list
         *
         * @return void
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

                // Swap out placeholder areas for dynamic item data
                $content = preg_replace_callback('/\{[^\}]+\}/', function ($matches) use ($item) {
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
                    $target = $item;
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
                }, $output);

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


        /****************************************************
         * BUILT-IN COMMANDS
         ***************************************************/

        /**
         * Reload the list interface
         *
         *  - Calls parent reload method (see Command_Visual).
         *  - Resets the list to value returned by reeload method.
         *
         * @api
         * @return void
         */
        public function reload()
        {
            $list = parent::reload();
            $this->list_original = $list;
            $this->list = $list;
        }//end reload()

        /**
         * Remove filters and reset list to original state
         *
         * @api
         * @return void
         */
        public function filter_remove()
        {
            $this->list = $this->list_original;
            $this->focus_top();
        }//end filter_remove()


        // Filter - by text/regex (search)
        /**
         * Filter list by text or regex - eg. search
         *
         * @api
         * @return void
         */
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

        /**
         * Move line focus up - eg. scroll up
         *
         * @api
         * @return void
         */
        public function focus_up()
        {
            if ($this->focus > 0) {
                $this->focus--;
            }
            $this->page_to_focus();
        }//end focus_up()

        /**
         * Move line focus down - eg. scroll down
         *
         * @api
         * @return void
         */
        public function focus_down()
        {
            $max_focus = (count($this->list) - 1);
            if ($this->focus < $max_focus) {
                $this->focus++;
            }
            $this->page_to_focus();
        }//end focus_down()

        /**
         * Move line focus (scroll) to top of list
         *
         * @api
         * @return void
         */
        public function focus_top()
        {
            $this->focus = 0;
            $this->page_to_focus();
        }//end focus_top()

        /**
         * Move line focus (scroll) to bottom of list
         *
         * @api
         * @return void
         */
        public function focus_bottom()
        {
            $max_focus = (count($this->list) - 1);
            $this->focus = $max_focus;
            $this->page_to_focus();
        }//end focus_bottom()


        /****************************************************
         * HELPER FUNCTIONS
         ***************************************************/

        /**
         * Adjust starting_line based on set focus index
         *
         * @return void
         */
        private function page_to_focus()
        {
            $focus = $this->focus + 1;
            if ($focus < $this->starting_line) {
                $this->starting_line = $focus;
            }
            if ($focus > $this->page_info['ending_line']) {
                $this->starting_line = ($focus - $this->page_info['page_length']) + 1;
            }
        }//end page_to_focus()

        /**
         * Get the key of the line in the list that currently has focus
         *
         * @return The focused line's key.
         */
        public function getFocusedKey()
        {
            $list_keys = array_keys($this->list);
            return $list_keys[$this->focus];
        }//end getFocusedKey()

        /**
         * Get the value of the line in the list that currently has focus
         *
         * @return The focused line's value.
         */
        public function getFocusedValue()
        {
            $list_values = array_values($this->list);
            return $list_values[$this->focus];
        }//end getFocusedValue()
    }//end class

}//end if

// Note: leave this for packaging
?>
