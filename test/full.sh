#!/bin/bash

set -e

php_version_to_test_with="$1"

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
# shellcheck disable=SC1091
source "$DIR/common.sh"

clear
echodiv
if [ -n "$php_version_to_test_with" ]; then
    echo "Running full test suite with PHP $php_version_to_test_with"
else
    echo "Running full test suite with all configured PHP versions"
fi

function full_test {
    _php_version="$1"
    pced "Running full test suite with PHP $_php_version"
    switch_php "$_php_version"
    "$DIR/cleanup.sh" x --no-pause && "$DIR/usage_update.sh" --no-use-test --no-pause
}

if [ -n "$php_version_to_test_with" ]; then
    full_test "$php_version_to_test_with"
else
    full_test "8.3"
    full_test "8.2"
    full_test "8.1"
    full_test "8.0"
fi

# Reset to latest PHP version
switch_php latest
