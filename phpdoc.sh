#!/bin/bash

clear

# cp -f pcon pcon.php

rm -rf docs/*

phpDocumentor -d . -t docs

if [ "$1" == "-o" ]; then
    google-chrome "file:///home/chris/dev/personal/pcon/docs/index.html"
fi

# && rm pcon.php && exit 0

# echo "==========================================="
# echo "THERE HAS BEEN AN ISSUE - RESOLVE AND RERUN"
# echo "==========================================="
# exit 1
