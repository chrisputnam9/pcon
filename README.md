# Overview
PCon - PHP Console - is a console tool and abstract that you can use to help build your own PHP
console tools.

Download Latest Version (1.5.3):
https://github.com/chrisputnam9/pcon

## Why Create Console Tools in PHP?
Great question.  Some people would say "Don't" - and depending on your situation, I might tell you
the same.  Here are some of the reasons I do reach for PHP:

 - It's the language I currently work with the most (so I can write quickly in it)
 - It's the language most of my teammates work with the most (so they can contribute easily)
 - It allows me to practice and build many re-usable techniques and methods in PHP for re-use in
   other projects
 - I and my team generally have it installed on our workstations already
 - Most systems I work with use the LAMP stack, so they typically have PHP available in the shell by
   default
 - It's good enough for most of my use-cases.  If I need a compiling, fast,
   multi-platform, multi-threading language to build for wider distribution, I'll probably use
   [Go](http://golang.org).

# Development Cookbook
[See Wiki](https://github.com/chrisputnam9/pcon/wiki) for information on building custom PCon tools.

# Tools Built With PCon
 - [PACLI - Asana CLI](https://github.com/chrisputnam9/pacli)
 - [PBCC - Basecamp Classic CLI](https://github.com/chrisputnam9/pbcc)
 - [PGH - Github CLI](https://github.com/chrisputnam9/pgh)
 - [PSSH - Shared SSH Config](https://github.com/chrisputnam9/pssh)
 - [PTFX - Internal TFX CLI Utility](https://www.webfx.com/)
 - [PXBRO - XML Browser Tool](https://github.com/chrisputnam9/pxbro) - using a very early version of PCon, hasn't been updated
 - [Quicknote - Personal note/todo helper tool](https://github.com/chrisputnam9/quicknote)

# Troubleshooting & Common Issues

## Permission Error - Failed to write to config file
Review the permissions on the config file (path should be included in error message)

In certain cases, the config file is created and owned by the root user during installation.

The file must be owned, readable and writable by your user.

Example, in Unix-type environment:

    sudo chown myuser:myuser ~/.config_folder/config.hjson
    sudo chmod 644 ~/.config_folder/config.hjson

## Windows - SSL Errors
Try downloading the latest CA bundle and pointing your PHP.ini to that (https://curl.haxx.se/docs/caextract.html)

Read more here: https://www.php.net/manual/en/curl.configuration.php

## WSL - Logname: No Login Name
In WSL, you may be warned:

    logname: no login name

As a workaround (via [therealkenc](https://github.com/microsoft/WSL/issues/888#issuecomment-393846024)), run:

    sudo touch /var/run/utmp
    sudo login -f yourusername

After this, you may need to remove config files and/or re-install to be sure files are owned by the right user(s), and that correct paths are used.
