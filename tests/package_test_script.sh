#!/bin/bash

set -e

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
# shellcheck disable=SC1091
source "$DIR/common.sh"

clear
./tests/cleanup.sh x --no-pause
./tests/usage_create.sh x --no-pause
clear
"$PCON" package "$TEST_SCRIPT"
cp "$TEST_SCRIPT_PACKAGED" "$TEST_SCRIPT_DIST"

echopackagenote
