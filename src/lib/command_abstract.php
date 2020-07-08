<?php
/**
 * Command abstract
 *  - primary or subcommand structure
 *  - main "run" method accepts and parses arguments
 *  - default run method checks for available sub-methods
 */

class Command_Abstract
{

    /**
     * Callable Methods
     */
    protected static $METHODS = [
        'backup',
        'eval_file',
        'help',
        'install',
        'update',
        'version',
    ];

    /**
     * Run - parse args and run method specified
     */
    public static function run($argv)
    {
    }

    /**
     * Magic handling for utility classes
     */

}
