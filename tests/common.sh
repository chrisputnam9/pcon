#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

function pause {
    echo "[Hit enter to continue]"
    read -r
}

function echodiv {
    echo "########################################################################################################################"
}

function pced {
    echo
    echodiv
    pause
    clear
    echo "$1"
    echodiv
    echo
}

PCON="$DIR/../pcon"
TMP_DIR="$DIR/tmp"
TEST_DIR="$TMP_DIR/_test"
TEST_SCRIPT="$TEST_DIR/test-thing-script"
