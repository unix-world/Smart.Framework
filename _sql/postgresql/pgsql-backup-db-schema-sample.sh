#!/bin/sh

### r.r.20210526
# INFO: this script will backup the selected DB: schema only from PostgreSQL
# To restore this dump a blank new PostgreSQL DB must be initialized and then: psql -U postgres -W -d postgres -f ${THE_FILE}
###

#### Settings

THE_SERVER=127.0.0.1
THE_PORT=5432
THE_USER=pgsql
THE_PASSWORD=pgsql
THE_DATABASE=smart_framework

THE_FILE=./pgsql-dump-db-${THE_DATABASE}-schema.`date +%u`.sql

### Runtime

export PGPASSWORD=${THE_PASSWORD}
pg_dump --encoding=UTF8 --create --column-inserts --blobs --schema-only --no-owner --no-privileges --host=${THE_SERVER} --port=${THE_PORT} --user=${THE_USER} --format=p ${THE_DATABASE} > ${THE_FILE}
sha512sum ${THE_FILE} > ${THE_FILE}.sha512

### END
