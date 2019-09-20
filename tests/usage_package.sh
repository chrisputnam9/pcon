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
echo "Usage - Package - Tests Starting"
echodiv

if [ ! -f "$TEST_SCRIPT" ]; then
    echo "Test script 'usage_create.sh' must be run first - we will run that now.  Ready?"
    pced "Running usage_create.sh..."
    "$DIR/usage_create.sh" --no-use-test --no-pause

    pced "Ready to begin Packaging tests"
    echodiv
fi

if [ -d "$TEST_DIR/dist" ]; then
    pced "Dist directory already exists - removing"
    rm -rf "$TEST_DIR/dist"
fi

echo "Packaging test tool"
"$PCON" package "$TEST_SCRIPT"

cp "$TEST_SCRIPT_PACKAGED" "$TEST_SCRIPT_DIST"

if [ "$1" != "--no-use-test" ]; then
    pced "Testing help output:"
    "$TEST_SCRIPT_PACKAGED" help

    pced "Testing help output for 'test' method:"
    "$TEST_SCRIPT_PACKAGED" help test

    pced "Testing 'test' method:"
    "$TEST_SCRIPT_PACKAGED" test

    pced "Testing 'test' method with custom message and verbose stamped output:"
    "$TEST_SCRIPT_PACKAGED" test "Custom Message" --verbose --stamp-lines

    pced "Usage - Package - Tests Complete"
fi
