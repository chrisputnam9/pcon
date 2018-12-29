#!/bin/bash

function pause {
    echo "[Hit enter to continue]"
    read
}

function echodiv {
    echo "########################################################################################################################"
}

function pced {
    echo
    echodiv
    pause
    clear
    echo "$1"
    echodiv
    echo
}

clear
echo "Ready to begin? ../_test will be removed and recreated"

pced "Removing old test dir"
rm -rvf ../_test

pced "Creating new tool"
./pcon create "Test thing script" "_test" ../ true

pced "Testing help output:"
"../_test/test-thing-script" help

pced "Testing help output for 'test' method:"
"../_test/test-thing-script" help test

pced "Testing 'test' method:"
"../_test/test-thing-script" test

pced "Testing 'test' method with custom message and verbose stamped output:"
"../_test/test-thing-script" test "Custom Message" --verbose --stamp-lines

pced "All Tests Complete"
