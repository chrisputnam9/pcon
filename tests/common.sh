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

function echopackagenote {
    echo
    echodiv
    echo "ATTENTION: at this point, you should update tests/dist/test-readme.md with the hash output above"
    echodiv
    echo "Press any key to continue"
    read -r
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

TEST_SCRIPT_NAME="example-test-tool"
TEST_SCRIPT="$TEST_DIR/$TEST_SCRIPT_NAME"
TEST_SCRIPT_PACKAGED="$TEST_DIR/dist/$TEST_SCRIPT_NAME"
TEST_SCRIPT_DIST="$DIR/dist/$TEST_SCRIPT_NAME"
TEST_SCRIPT_INSTALLED="/usr/local/bin/$TEST_SCRIPT_NAME"

TEST_SCRIPT_CONFIG_DIR="$HOME/.$TEST_SCRIPT_NAME"
