#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
source "$DIR/common.sh"

echo "$TEST_DIR will be removed and recreated"

pced "Removing old test dir"
rm -rvf "$TEST_DIR"
