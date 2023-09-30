#!/bin/bash
# shellcheck disable=SC2034

# ===============================================
# CONFIG
# ===============================================
LATEST_PHP="8.2"

# ===============================================

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
    echo "ATTENTION: at this point, you should update test/dist/test-readme.md with the hash output above"
    echodiv
    echo "Press any key to continue"
    read -r
}

function pced {
    echo
    echodiv
    pause
    clear
    echo "[Current PHP Version is: $(current_php_version)]"
    echo "$1"
    echodiv
    echo
}

# Switch PHP Version
function switch_php
{
	new_php_version="$1"
	if [ -z "$new_php_version" ]; then
		echo "Must specify version. Options: $(list_php_versions | tr '\n' ', ')latest"
		return 1
	fi
	if [ "$new_php_version" = "latest" ]; then
		new_php_version="$(list_php_versions | tail -n1)"
	fi
	if [ "$new_php_version" != "$(current_php_version)" ]; then
		echo "Switching to PHP $new_php_version"
		sudo update-alternatives --set php "/usr/bin/php$new_php_version"
		echo "Now running PHP $(current_php_version)"
	else
		echo "already running PHP $(current_php_version)"
	fi
}
function list_php_versions
{ 
	find "$(dirname "$(which php)")" -maxdepth 1 -type f -name "php[0-9.]*" | sed 's/.*php//g' | sort -n
}
function current_php_version {
    php -v | head -n1 | sed -E 's/PHP ([0-9]*\.[0-9]*).*/\1/g'
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
