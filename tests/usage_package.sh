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
echo "Usage - Package - Tests Starting"
echodiv

if [ ! -f "$TEST_SCRIPT" ]; then
    echo "Test script 'usage_create.sh' must be run first - we will run that now.  Ready?"
    pced "Running usage_create.sh..."
    "$DIR/usage_create.sh" --use-test --pause

    pced "Ready to begin Packaging tests"
    echodiv
fi

if [ -d "$TEST_DIR/dist" ]; then
    pced "Dist directory already exists - removing"
    rm -rf "$TEST_DIR/dist"
fi

echo "Packaging test tool"
"$PCON" package "$TEST_SCRIPT" --verbose

echopackagenote

cp "$TEST_SCRIPT_PACKAGED" "$TEST_SCRIPT_DIST"

if [ "$1" != "--no-use-test" ]; then
    pced "Testing version output:"
    "$TEST_SCRIPT_PACKAGED" version --no-update-version-url

    pced "Testing help output:"
    "$TEST_SCRIPT_PACKAGED" help --no-update-version-url

    pced "Testing help output for 'test' method:"
    "$TEST_SCRIPT_PACKAGED" help test --no-update-version-url

    pced "Testing 'test' method:"
    "$TEST_SCRIPT_PACKAGED" test --no-update-version-url

    pced "Testing 'test' method with custom message and verbose stamped output:"
    "$TEST_SCRIPT_PACKAGED" test "Custom Message" --verbose --stamp-lines --no-update-version-url

    pced "Testing 'backup' method by backing up config:"
    "$TEST_SCRIPT_PACKAGED" backup "$TEST_SCRIPT_CONFIG_DIR/config.hjson" --no-update-version-url

    pced "Usage - Package - Tests Complete"
fi
