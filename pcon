#!/usr/bin/env php
<?php
/**
 * Acts as the entry point; initializes the tool
 *
 * - Sets a few constants and loads in primary logic files
 * - **NOTE:** actual file is "pcon" with no extension - name temporarily modified for linting & doc generation
 *
 * @package pcon
 * @author  chrisputnam9
 */

// Miscellaneous configuration
if (! defined('ERRORS')) {
    define('ERRORS', true);
}
if (! defined('PACKAGED')) {
    define('PACKAGED', false);
}

// Path to pcon console_abstract.php
if (! defined('CONSOLE_ABSTRACT_PATH')) {
    define('CONSOLE_ABSTRACT_PATH', __DIR__ . '/src/console_abstract.php');
}

// Paths to other includes
$src_includes = array(
    __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'pcon.php',
);


if (empty($_PACKAGING)) {
    // Defined when loading file to do packaging
    require_once CONSOLE_ABSTRACT_PATH;
}

// vim: syntax=php
