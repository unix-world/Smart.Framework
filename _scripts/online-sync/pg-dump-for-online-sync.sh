#!/bin/sh

#####
# PostgreSQL Dump for Online Sync
# version: 20190103
# This script is used by AppCodePack
# (c) 2018-2019 unix-world.org
#####

### Configs
THE_SERVER=127.0.0.1
THE_PORT=5432
THE_USER=pgsql
THE_PASSWORD=pgsql
THE_DATABASE=my_db
THE_FILE=_sql/db-upgrade.sql.tar.gz
# use empty value for THE_EXCLUDES="" if nothing to exclude ...
THE_EXCLUDES="--exclude-schema=smart_runtime --exclude-schema=online"
###

##### Runtime: using (PostgreSQL) pg_dump utility + gzip
echo "=== PgDump/Gzip Local ... ==="
echo "Dump File: ${THE_FILE}"
echo "Dump Options.Excludes: ${THE_EXCLUDES}"
echo "PostgreSQL Server: ${THE_SERVER}"
echo "Database: ${THE_DATABASE}"
export PGPASSWORD=${THE_PASSWORD}
if [ -f "./${THE_FILE}" ]; then
	rm "./${THE_FILE}"
fi
if [ -f "./${THE_FILE}" ]; then
	echo "=== FAIL: could not remove the old SQL Dump ${THE_FILE} ==="
	exit -101
fi
pg_dump --encoding=UTF8 --clean --column-inserts --blobs --no-owner --no-privileges ${THE_EXCLUDES} --host=${THE_SERVER} --port=${THE_PORT} --user=${THE_USER} --format=t ${THE_DATABASE} | gzip -9 > "./${THE_FILE}"
THE_EXIT_CODE=$?
if [ ${THE_EXIT_CODE} != 0 ]; then
	echo "=== FAIL (${THE_EXIT_CODE}): could not create the SQL Dump ${THE_FILE} on running export command ==="
else
	if [ ! -f "./${THE_FILE}" ]; then
		echo "=== FAIL (${THE_EXIT_CODE}): could not find the SQL Dump ${THE_FILE} after export ==="
		exit -102
	else
		echo "=== Done. (${THE_EXIT_CODE}) ==="
	fi
fi
exit ${THE_EXIT_CODE}
#####

# END
