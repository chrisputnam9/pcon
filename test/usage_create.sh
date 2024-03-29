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
echo -n "Usage - Create - Tests Starting"

if [ -d "$TEST_DIR" ]; then
    pced "Target directory already exists - cleanup required"
    "$DIR/cleanup.sh" x --no-pause
fi

pced "Creating new tool"
"$PCON" create \
    "Example Test Tool" \
    "chrisputnam9" \
    "A simple example tool, used to test PCon tooling." \
    "https://raw.githubusercontent.com/chrisputnam9/pcon/master/test/dist/test-readme.md" \
    "_test" \
    "$TMP_DIR" \
    "true"

if [ "$1" != "--no-use-test" ]; then
    pced "Testing version output:"
    "$TEST_SCRIPT" version --no-update-version-url

    pced "Testing help output:"
    "$TEST_SCRIPT" help --no-update-version-url

    pced "Testing help output for 'test' method:"
    "$TEST_SCRIPT" help test --no-update-version-url

    pced "Testing 'test' method:"
    "$TEST_SCRIPT" test --no-update-version-url

    pced "Testing 'test' method with custom message and verbose stamped output:"
    "$TEST_SCRIPT" test "Custom Message" --verbose --stamp-lines --no-update-version-url

    pced "Testing 'backup' method by backing up config:"
    "$TEST_SCRIPT" backup "$TEST_SCRIPT_CONFIG_DIR/config.hjson" --no-update-version-url

    pced "Usage - Create - Tests Complete"
fi
