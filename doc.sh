#!/bin/bash

clear

cp -f pcon pcon-load.php

rm -rf docs

phpDocumentor -d . -t docs --setting=graphs.enabled=true

if [ "$1" == "-srv" ]; then
    cd docs || exit 1
    php -S localhost:8000
elif [ "$1" == "-o" ]; then
    google-chrome "http://localhost:8000"
fi

rm pcon-load.php
