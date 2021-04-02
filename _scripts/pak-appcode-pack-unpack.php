#!/bin/sh

#####
# build the appcode:pack/unpack
# version: 20210331
# (c) 2021 unix-world.org
#####

cat ../tmp/test.php | sed -E 's/^(<\?php)$/\/\/ \# php code start/' | sed -E 's/^(\/\/( |    )+\[@\[#\[!NO-STRIP!\]#\]@\])$/\/\/ nostrip tag/'

#END

