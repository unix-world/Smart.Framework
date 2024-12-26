#!/bin/sh

#####
# lftp sync WPUB for remote FTP/SFTP servers
# version: 20190103
# This script is used by AppCodePack
# (c) 2018-2019 unix-world.org
#####

### Configs
THE_HOST=some-ftp-or-sftp.host
THE_PORT=21
THE_USER=adminuser
THE_PASS="YWRtaW51c2VyCg==" #echo "your password" | openssl enc -base64
THE_DIR_SRC=./wpub/downloads/
THE_DIR_DEST=/home/adminuser/test-wpub-downloads/
THE_EXTRA_OPTS=-vvv
THE_CONTROL_FILE="#downloads"
THE_PROTOCOL="ftp" # can be: ftp | sftp
###

##### Runtime: using the LFTP Mirror # https://lftp.yar.ru/
echo "=== Sync WPUB Local with Online (lftp) ... ==="
echo "Host: ${THE_PROTOCOL}://${THE_HOST}:${THE_PORT}"
echo "Username: ${THE_USER}"
echo "Source Dir: ${THE_DIR_SRC}"
echo "Destination Dir: ${THE_DIR_DEST}"
if [ -d "${THE_DIR_SRC}" ]; then
	cd "${THE_DIR_SRC}"
	if [ -f "./${THE_CONTROL_FILE}" ]; then
		echo "===== Checking for Control File: ${THE_DIR_SRC}${THE_CONTROL_FILE} ... OK, exists."
		THE_DEC_PASS=$(echo ${THE_PASS} | openssl enc -base64 -d)
		lftp -c "set net:timeout 10; set net:max-retries 1; open -p ${THE_PORT} -u ${THE_USER},${THE_DEC_PASS} ${THE_PROTOCOL}://${THE_HOST}; mirror ${THE_EXTRA_OPTS} -c -e -R -L ./ ${THE_DIR_DEST}"
		THE_EXIT_CODE=$?
		if [ ${THE_EXIT_CODE} != 0 ]; then
			echo "=== FAIL (${THE_EXIT_CODE}) ! ==="
		else
			echo "=== Done. (${THE_EXIT_CODE}) ==="
		fi
		exit ${THE_EXIT_CODE}
	else
		echo "=== FAIL: The control file ${THE_DIR_SRC}${THE_CONTROL_FILE} is missing  ==="
		exit -102
	fi
else
	echo "=== FAIL: The source dir ${THE_DIR_SRC} is missing  ==="
	exit -101
fi
#####

# END
