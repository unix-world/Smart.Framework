#!/bin/sh

### r.20210526
# INFO: this script will backup the selected DB: schema and data from PostgreSQL ; will compress to gzip
# To restore this dump a blank new PostgreSQL DB must be initialized and then: gunzip -c ${THE_FILE} | psql -U postgres -W -d postgres
###

#### Settings

THE_SERVER=127.0.0.1
THE_PORT=5432
THE_USER=pgsql
THE_PASSWORD=pgsql
THE_DATABASE=smart_framework

THE_FILE=./pgsql-dump-db-${THE_DATABASE}.`date +%u`.sql.gz

### Runtime

export PGPASSWORD=${THE_PASSWORD}
pg_dump --encoding=UTF8 --create --column-inserts --blobs --no-owner --no-privileges --host=${THE_SERVER} --port=${THE_PORT} --user=${THE_USER} --format=p ${THE_DATABASE} | gzip -9 > ${THE_FILE}
sha512sum ${THE_FILE} > ${THE_FILE}.sha512

### END
