#!/bin/bash

set -e

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
# shellcheck disable=SC1091
source "$DIR/common.sh"

PAUSE=true
if [ "$2" == "--no-pause" ]; then
    # shellcheck disable=SC2034
    PAUSE=false
fi

clear
echo "Usage - Install - Tests Starting"
echodiv

if [ ! -f "$TEST_SCRIPT_PACKAGED" ]; then
    echo "Test script 'usage_package.sh' must be run first - we will run that now.  Ready?"
    pced "Running usage_package.sh..."
    "$DIR/usage_package.sh" --use-test --pause

    pced "Ready to begin Install tests"
    echodiv
fi

echo "Installing test tool"
sudo "$TEST_SCRIPT_PACKAGED" install --verbose --no-update-version-url

if [ "$1" != "--no-use-test" ]; then
    pced "Testing version output:"
    "$TEST_SCRIPT_INSTALLED" version --no-update-version-url

    pced "Testing help output:"
    "$TEST_SCRIPT_INSTALLED" help --no-update-version-url

    pced "Testing help output for 'test' method:"
    "$TEST_SCRIPT_INSTALLED" help test --no-update-version-url

    pced "Testing 'test' method:"
    "$TEST_SCRIPT_INSTALLED" test --no-update-version-url

    pced "Testing 'test' method with custom message and verbose stamped output:"
    "$TEST_SCRIPT_INSTALLED" test "Custom Message" --verbose --stamp-lines --no-update-version-url

    pced "Testing 'backup' method by backing up config:"
    "$TEST_SCRIPT_INSTALLED" backup "$TEST_SCRIPT_CONFIG_DIR/config.hjson" --no-update-version-url

    pced "Usage - Install - Tests Complete"
fi
