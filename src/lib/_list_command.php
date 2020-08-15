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

    public $filters = [];
    public $commands = [];

    public $multiselect;
    public $template;

    public $continue_loop=true;

    /**
     * Constructor
     */
    public function __construct($main_tool, $list, $options=[])
    {
        parent::__construct($main_tool);

        if (empty($list))
        {
            $this->error("Empty list", false, true);
        }

        $options = array_merge([
            'commands' => [
                'Filter' => [
                    'f',
                    [$this, 'filter'],
                ],
                'Quit' => [
                    'q',
                    [$this, 'quit'],
                ],
                'Search' => [
                    '/',
                    [$this, 'filter_by_text'],
                ],
                'Focus Down' => [
                    ['j', 'OB'],
                    [$this, 'focus_down'],
                ],
                'Focus Up' => [
                    ['k', 'OA'],
                    [$this, 'focus_up'],
                ],
            ],
            'filters' => [
                'Text/Regex Search' => [
                    '/',
                    [$this, 'filter_by_text'],
                ],
            ],
            'multiselect' => false,
            'template' => "{_KEY}: {_VALUE}",
        ], $options);

        $this->list_original = $list;
        $this->list = $list;
        $this->filters = $options['filters'];
        $this->commands = $options['commands'];
        foreach ($this->commands as $command_name => $command_details)
        {
            if (is_string($command_details[0])) $command_details[0] = str_split($command_details[0]);
            if (!is_array($command_details[0])) $this->error("Invalid command keys for '$command_name'");
            $this->commands[$command_name][0] = $command_details[0];
        }

        $this->multiselect = $options['multiselect'];
        $this->template = $options['template'];
    }

    /**
     * Run the listing subcommand
     */
    public function run()
    {
        $count = count($this->list);

        $content_to_display=[];
        foreach ($this->list as $i => $item)
        {
            // Prep output using template
            $output = $this->template;

            $key_start = strpos($output, '{_KEY}');
            if ($key_start !== false)
            {
                $output = substr_replace($output, $i, $key_start, 6);
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
                $content = $this->colorize($content, 'blue', 'light_gray', ['bold']);
            }
            $content_to_display[]= $content;
        }

        $this->clear();
        $this->paginate($content_to_display, [
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
                    call_user_func($command_callable, $this);
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

    // Quit
    public function quit()
    {
        $this->continue_loop = false;
    }

    // Filter - present ways to filter
    public function filter()
    {
        die('filter');
    }

    // Filter - by text/regex (search)
    public function filter_by_text()
    {
        die('filter_by_text');
    }


    // Focus up/down
    public function focus_up()
    {
        if ($this->focus > 0)
        {
            $this->focus--;
        }
    }
    public function focus_down()
    {
        $max_focus = (count($this->list) - 1);
        if ($this->focus < $max_focus)
        {
            $this->focus++;
        }
    }
}
