#!/bin/sh

#####
# PostgreSQL Restore for Online Sync
# version: 20190103
# This script is used by AppCodeUnPack
# (c) 2018-2019 unix-world.org
#####

### Configs
THE_SERVER=169.254.99.77
THE_PORT=5432
THE_USER=pgsql
THE_PASSWORD=pgsql
THE_DATABASE=my_online_db
THE_FILE=./_sql/db-upgrade.sql.tar.gz
###

##### Runtime: using (PostgreSQL) pg_restore utility + gunzip
export PGPASSWORD=${THE_PASSWORD}
gunzip -c ${THE_FILE} | pg_restore --no-privileges --disable-triggers --clean --if-exists --host=${THE_SERVER} --port=${THE_PORT} --user=${THE_USER} --dbname=${THE_DATABASE}
#####

# END
