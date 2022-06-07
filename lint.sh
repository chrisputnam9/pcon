#!/bin/bash

clear

cp -f pcon pcon.php

phpcs && rm pcon.php && exit 0

echo "==========================================="
echo "THERE HAS BEEN AN ISSUE - RESOLVE AND RERUN"
echo "==========================================="
exit 1
