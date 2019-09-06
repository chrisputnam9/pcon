#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
source "$DIR/common.sh"

clear
echo -n "Usage - Package - Tests Starting"
echo "$TEST_DIR"

if [ -d "$TEST_DIR" ]; then
    pced "Target directory already exists - cleanup required"
    "$DIR/cleanup.sh"
fi

pced "Creating new tool"
"$PCON" create "Test thing script" "_test" "$TMP_DIR" true

if [ "$1" != "--no-use-test" ]; then
    pced "Testing help output:"
    "$TEST_SCRIPT" help

    pced "Testing help output for 'test' method:"
    "$TEST_SCRIPT" help test

    pced "Testing 'test' method:"
    "$TEST_SCRIPT" test

    pced "Testing 'test' method with custom message and verbose stamped output:"
    "$TEST_SCRIPT" test "Custom Message" --verbose --stamp-lines

    pced "Usage - Create - Tests Complete"
fi
