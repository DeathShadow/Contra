#!/bin/bash
if [ "`whoami`" = "root" ]; then
    echo "Don't run Contra on root user!";
    exit;
fi

if [ -n "$(readlink --version 2>/dev/null | grep "GNU")" ]; then cd "$(dirname "$(readlink -f "$0")")";
elif [ -e "$(which realpath)" ]; then cd "$(dirname "$(realpath "$0")")";
else cd "$(dirname "${BASH_SOURCE[0]}")"; fi
./RUN-Contra.command --config