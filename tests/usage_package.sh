#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
source "$DIR/common.sh"

clear
echo "Usage - Package - Tests Starting"
echodiv

if [ ! -f "$TEST_SCRIPT" ]; then
    echo "Test script 'usage_create.sh' must be run first - we will run that now.  Ready?"
    pced "Running usage_create.sh..."
    "$DIR/usage_create.sh"

    pced "Ready to begin Packaging tests"
    echodiv
fi

if [ -d "$TEST_DIR/dist" ]; then
    pced "Dist directory already exists - removing"
    rm -rf "$TEST_DIR/dist"
fi

echo "Packaging _test tool"
"$PCON" package "$TEST_SCRIPT"

pced "Testing help output:"
"$TEST_SCRIPT_PACKAGED" help

pced "Testing help output for 'test' method:"
"$TEST_SCRIPT_PACKAGED" help test

pced "Testing 'test' method:"
"$TEST_SCRIPT_PACKAGED" test

pced "Testing 'test' method with custom message and verbose stamped output:"
"$TEST_SCRIPT_PACKAGED" test "Custom Message" --verbose --stamp-lines

pced "Usage - Package - Tests Complete"
