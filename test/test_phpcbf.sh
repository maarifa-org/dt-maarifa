#!/bin/bash

set -e

cd "$(dirname "${BASH_SOURCE[0]}")/../"

if [ "$(php -r 'echo version_compare( phpversion(), "7.0", ">=" ) ? 1 : 0;')" != 1 ] ; then
    vendor/bin/phpcbf disciple-tools-maarifa.php
    exit
fi

eval vendor/bin/phpcbf
