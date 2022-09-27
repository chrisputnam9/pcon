#!/bin/bash

clear

cp -f pcon pcon-load.php

rm -rf docs/* docs/.*

phpDocumentor -d . -t docs --setting=graphs.enabled=true

if [ "$1" == "-o" ]; then
    google-chrome "file:///home/chris/dev/personal/pcon/docs/index.html"
fi

rm pcon-load.php
