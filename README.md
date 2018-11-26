# Overview
PCon - PHP Console - is an abstract that you can extend to create your own PHP console tools.

## Why Create Console Tools in PHP?
Great question.  Some people would say "Don't" - and depending on your situation, I might tell you
the same.  Hee are some of the reasons I reach for PHP:

 - It's the language I currently work with the most (and can therefore write quickly in it)
 - It's the language most of my teammates work with the most (and can therefore contribute easily)
 - It allows me to practice and build many re-usable techniques and methods in PHP
 - I and my team generally have it installed on our workstations already
 - Most systems I work with use the LAMP stack, and therefore most have PHP available in shell
 - It's good enough for most of my use-cases.  If I need a compilable, faster,
   multi-platform, multi-threading for a particular case, I'd probably use [Go](http://golang.org).

# Getting Started
OK, so your situation also seems like a good use-case for a PHP console tool?  Here we go then!

## Install PCon
(if you don't already have it installed)

 1. Clone this repository to the directory of your choice.  This directory can be kept for
    future use, and will have a special tool to help you create and managed console tools that you
    build.  We'll call it "dev/pcon"

## Create a New Console Tool
 1. CD to your local pcon folder (cd dev/pcon)
 2. Run `./pcon create` (optionally, run `./pcon help create` to see arguments)
 3. Follow prompts to specify deails

## Package a Console Tool
 1. CD to your local pcon folder (cd dev/pcon)
 2. Run `./pcon package` (optionally, run `./pcon help package` to see arguments)
 3. Follow prompts to specify deails

# Development Cookbook
Reference information below to help you as you build your console tools

## Documentation of Methods and Options
Define a protected property with same name as the option or method but with prefix of "\_\_" for
options, or "\_\_\_" for methods, and with an array as the defined value.

The first element should be the help text.  If this is the only element needed (eg. for methods with
no argument) this can be a string instead of a single element array. For example:

    protected $__sync = "Sync config files based on 'sync' config/option value";

For methods with arguments, after the first entry, there must be an entry for each argument, in the form of another array:

    ...
    ["Help text", "type: (boolean)|float|integer|string|...", "(optional)|required|..."]
    ...

These should be in the same order as the arguments in the method definition.

For example:

    protected $__help = [
        "Show help/usage information.",
        ["Name of method to provide specific in-depth help", "string", "optional"],
    ];

Treat an option just like the value array for the argument of a method.  For example:

    protected $__stamp_lines = [
        "Stamp output lines",
        "boolean",
        "optional",
    ];

Note that the default type is "boolean" and the default validation is "optional" - so those last two
examples may be defined as follows:

    protected $__help = [
        "Show help/usage information.",
        ["Name of method to provide specific in-depth help", "string"],
    ];

    // Note, as mentioned above, since there's just one element, we can also make this a string
    protected $__stamp_lines = "Stamp output lines";

# Future Plans
 - Install method
 - Auto-update workflow
