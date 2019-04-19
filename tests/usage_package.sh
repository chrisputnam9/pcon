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

echo "Packaging _test tool"
"$PCON" package "$TEST_SCRIPT"

pced "Usage - Package - Tests Complete"
