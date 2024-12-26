#!/bin/sh

#####
# check PHP line endings (files that contain new line after PHP ending tag
# version: 20190315
# (c) 2018-2019 unix-world.org
#####

find . -type f -name '*.php' | xargs pcregrep -rMl '\?>[\s\n]+\z'

#END

