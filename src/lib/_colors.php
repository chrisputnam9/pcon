<?php
/**
 * Defines the CONSOLE_COLORS class
 *
 * @package pcon
 * @author  chrisputnam9
 */

if (!class_exists("CONSOLE_COLORS")) {
    /**
     * Reference for console colors and other formatting used by the application
     *
     *  - Allows colors to be used by name
     *  - Allows 'bold', 'dim', etc. to be used by name
     *  - See https://en.wikipedia.org/wiki/ANSI_escape_code for code reference
     *  - See Console_Abstract::colorize for usage
     */
    class CONSOLE_COLORS
    {
        /**
         * Reference of foreground colors by name
         *
         * @var array
         */
        public static $foreground = [
            'black' => '0;30',
            'dark_gray' => '1;30',
            'blue' => '0;34',
            'light_blue' => '1;34',
            'green' => '0;32',
            'light_green' => '1;32',
            'cyan' => '0;36',
            'light_cyan' => '1;36',
            'red' => '0;31',
            'light_red' => '1;31',
            'purple' => '0;35',
            'light_purple' => '1;35',
            'brown' => '0;33',
            'yellow' => '1;33',
            'light_gray' => '0;37',
            'white' => '1;37',
        ];

        /**
         * Reference of background colors by name
         *
         * @var array
         */
        public static $background = [
            'black' => '40',
            'red' => '41',
            'green' => '42',
            'yellow' => '43',
            'blue' => '44',
            'magenta' => '45',
            'cyan' => '46',
            'light_gray' => '47',
        ];


        /**
         * Reference of other console formatting / decoration by name
         *
         * @var array
         */
        public static $other = [
            'bold' => '1',
            'dim' => '2',
            'underline' => '4',
            'blink' => '5',
            'inverse' => '7',

            // Don't seem to work for the author at least:
            // 'italic' => '3',
            // 'blink_fast' => '6',
            // 'concealed' => '8',
            // 'strike' => '9',
            // 'double_underline' => '21',
            // 'frame' => '51',
            // 'encircled' => '52',
            // 'overlined' => '53',
        ];
    }//end class

}//end if

// Note: leave the end tag for packaging
?>
