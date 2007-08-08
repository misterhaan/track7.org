#!/bin/sh
# disable filename globbing
set -f
echo Content-type: text/plain
echo
echo Updating live site
/usr/bin/svn update /home/misterhaan/track7.org
