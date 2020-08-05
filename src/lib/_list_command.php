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
            $this->output($item, false);
            $this->br();
        }
        $this->hr();
        $this->output("$count total items");
        $this->hr();

        // TODO
        // Show the list using template
        // Allow moving up and down
        // Allow selection
        // Allow filtering
    }
}
