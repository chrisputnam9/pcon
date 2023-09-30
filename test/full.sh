#!/bin/bash

set -e

version="$1"

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
# shellcheck disable=SC1091
source "$DIR/common.sh"

clear
echodiv
if [ -n "$version" ]; then
    echo "Running full test suite with PHP $version"
else
    echo "Running full test suite with all configured PHP versions"
fi

function full_test {
    php_version="$1"
    pced "Running full test suite with PHP $php_version"
    switch_php "$php_version"
    "$DIR/cleanup.sh" x --no-pause && "$DIR/usage_update.sh" --no-use-test --no-pause
}

if [ -n "$version" ]; then
    full_test "$version"
else
    full_test "8.2"
    full_test "8.1"
    full_test "8.0"
    full_test "7.4"
fi

# Reset to latest PHP version
switch_php latest
