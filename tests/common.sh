#!/bin/bash
# shellcheck disable=SC2034

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

function pause {
    echo "[Hit enter to continue]"
    if $PAUSE; then
        read -r
    fi
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

# Variables used by script which includes this file
PCON="$DIR/../pcon"
TMP_DIR="$DIR/tmp"
TEST_DIR="$TMP_DIR/_test"

TEST_SCRIPT="$TEST_DIR/test-thing-script"
TEST_SCRIPT_PACKAGED="$TEST_DIR/dist/test-thing-script"
TEST_SCRIPT_INSTALLED="test-thing-script"

TEST_SCRIPT_CONFIG_DIR="$HOME/.test-thing-script"
