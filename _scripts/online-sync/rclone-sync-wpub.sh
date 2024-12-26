#!/bin/sh

#####
# rclone sync WPUB for remote WebDAV servers, as in rclone.conf (see the section webdavSample:)
# version: 20190103
# This script is used by AppCodePack
# (c) 2018-2019 unix-world.org
#####

### Configs
THE_CONFIG=webdavSample
THE_DIR_SRC=./wpub/
THE_SYNC_MODE=sync # sync | copy
THE_EXTRA_OPTS="-L -v"
THE_CONTROL_FILE="#wpub"
THE_WEBDAV_URL="https://your-webdav-server.ext:8443/webdav/wpub/"
###

##### Runtime: using the rClone sync # https://github.com/ncw/rclone
echo "=== Sync WPUB Local with Online (rclone) ... ==="
echo "WebDAV Sync: rClone"
echo "rClone Options: ${THE_SYNC_MODE} @ ${THE_EXTRA_OPTS}"
echo "rClone Config Remote: webdav # [${THE_CONFIG}]"
echo "Source Dir: ${THE_DIR_SRC}"
echo "Destination Remote (Dir on Server) URL: ${THE_WEBDAV_URL}"
if [ -d "${THE_DIR_SRC}" ]; then
	if [ -f "./${THE_DIR_SRC}/${THE_CONTROL_FILE}" ]; then
		if [ -f "./rclone.conf" ]; then
			echo "===== Checking for Control File: ${THE_DIR_SRC}${THE_CONTROL_FILE} ... OK, exists."
			rclone --config ./rclone.conf ${THE_EXTRA_OPTS} --log-file=./rclone.log --exclude ".ht*" --exclude "#wpub"  ${THE_SYNC_MODE} ${THE_DIR_SRC} ${THE_CONFIG}:
			THE_EXIT_CODE=$?
			cat ./rclone.log
			echo "" > ./rclone.log
			if [ ${THE_EXIT_CODE} != 0 ]; then
				echo "=== FAIL (${THE_EXIT_CODE}) ! ==="
			else
				echo "=== Done. (${THE_EXIT_CODE}) ==="
			fi
			exit ${THE_EXIT_CODE}
		else
			echo "=== FAIL: The config file rclone.conf is missing  ==="
			exit -103
		fi
	else
		echo "=== FAIL: The control file ${THE_DIR_SRC}${THE_CONTROL_FILE} is missing  ==="
		exit -102
	fi
else
	echo "=== FAIL: The source dir ${THE_DIR_SRC} is missing  ==="
	exit -101
fi
#####

### requires rclone.conf for webdav with section webdavSample : use command # rclone config
# see the includded rclone.conf to have a hint ;)
###

#END
