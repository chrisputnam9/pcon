#!/bin/bash

clear

cp -f pcon pcon-load.php

phpcs && rm pcon-load.php && exit 0

echo "==========================================="
echo "THERE HAS BEEN AN ISSUE - RESOLVE AND RERUN"
echo "==========================================="
exit 1
