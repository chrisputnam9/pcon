#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
# shellcheck disable=SC1090
source "$DIR/common.sh"

PAUSE=true
if [ "$2" == "--no-pause" ]; then
    # shellcheck disable=SC2034
    PAUSE=false
fi

clear
echo "Usage - Update - Tests Starting"
echodiv

if [ ! -f "$TEST_SCRIPT_INSTALLED" ]; then
    echo "Test script 'usage_install.sh' must be run first - we will run that now.  Ready?"
    pced "Running usage_install.sh..."
    "$DIR/usage_install.sh" --use-test --pause

    pced "Ready to begin Update tests"
    echodiv
fi

echo "Running Update"
sudo "$TEST_SCRIPT_INSTALLED" update --verbose

if [ "$1" != "--no-use-test" ]; then
    pced "Testing version output:"
    "$TEST_SCRIPT_INSTALLED" version

    pced "Testing help output:"
    "$TEST_SCRIPT_INSTALLED" help

    pced "Testing help output for 'test' method:"
    "$TEST_SCRIPT_INSTALLED" help test

    pced "Testing 'test' method:"
    "$TEST_SCRIPT_INSTALLED" test

    pced "Testing 'test' method with custom message and verbose stamped output:"
    "$TEST_SCRIPT_INSTALLED" test "Custom Message" --verbose --stamp-lines

    pced "Testing 'backup' method by backing up config:"
    "$TEST_SCRIPT_INSTALLED" backup "$TEST_SCRIPT_CONFIG_DIR/config.json"

    pced "Usage - Update - Tests Complete"
fi
