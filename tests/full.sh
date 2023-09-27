#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

clear && "$DIR/cleanup.sh" x --no-pause && "$DIR/usage_update.sh" --no-use-test --no-pause
