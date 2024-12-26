#!/bin/sh
# Linux / OpenBSD :: Smart.Framework Fix Privileges
# (c) 2009-2019 unix-world.org

# v.20190103
# conform the www/smart-framework as chmod/chown (must be run as root)
# info: run this script each time you add/modify manually files under www/smart-framework to fix privileges

clear

echo '##############################################################################'

echo '=============================='
echo "Conform www Recursive :$TARGET"
echo '==============================START'
echo ''

## CFG (modify this as needed on your system and take care of CHMODS to be set same as in etc/init.php of smart-framework ; owner must be the user/group under the www server is running as)
TARGET="/www/smart-framework"
DIR_CHMOD="0770"
FILE_CHMOD="0660"
OWNER="www"
GROUP="www"
##

#### chown
CMD_CHOWN="chown -R $OWNER:$GROUP $TARGET"
echo -n "$CMD_CHOWN"
$CMD_CHOWN
echo ' [done]'
echo ''
#### chmod
CMD_CHMOD_D="find $TARGET -type d -exec chmod $DIR_CHMOD {} ;"
echo -n "$CMD_CHMOD_D"
$CMD_CHMOD_D
echo ' [done]'
echo ''
##
CMD_CHMOD_F="find $TARGET -type f -exec chmod $FILE_CHMOD {} ;"
echo -n "$CMD_CHMOD_F"
$CMD_CHMOD_F
echo ' [done]'
echo ''
####

echo '==============================END'

echo '##############################################################################'

# END
