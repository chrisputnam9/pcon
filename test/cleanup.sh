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

echo "$TEST_DIR will be removed and recreated"

pced "Removing old test dir - may need admin authentication"
sudo rm -rvf "$TEST_DIR" "$TEST_SCRIPT_INSTALLED" "$TEST_SCRIPT_CONFIG_DIR"

pced "Cleanup complete"
