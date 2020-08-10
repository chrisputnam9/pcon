<?php
/**
 * List Command
 */
class List_Command extends Command_Abstract
{
    public $list=[];
    public $filters = [];
    public $commands = [];
    public $multiselect;
    public $template;

    /**
     * Constructor
     */
    public function __construct($main_tool, $list, $options=[])
    {
        parent::__construct($main_tool);

        $options = array_merge([
            'commands' => [
                'Filter' =>[
                    'f',
                    [$this, 'filter'],
                ],
                'Quit' =>[
                    'q',
                    [$this, 'quit'],
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

        $this->list = $list;
        $this->filters = $options['filters'];
        $this->commands = $options['commands'];
        $this->multiselect = $options['multiselect'];
        $this->template = $options['template'];
    }

    /**
     * Run the listing subcommand
     */
    public function run()
    {
        if (empty($this->list))
        {
            $this->error($this->error_empty_list);
        }

        $count = count($this->list);

        $this->hr();
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
            $output = preg_replace_callback('/\{[^\}]+\}/', [$this, '_fill_item_to_template'], $output );
            $this->output($output, false);
            $this->br();
        }
        $this->hr();
        $this->output("$count total items");
        $this->hr();

        // TODO
        // Set up pagination based on terminal height
        // - paginate method on abstract - items, starting item, room for input (default to 1 for single input line)
        // Allow moving up and down
        // Allow selection
        // Allow filtering
        // Set up wrap/scrolling based on terminal width
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
}
