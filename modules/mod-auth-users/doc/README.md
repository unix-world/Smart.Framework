
# Using the Smart AuthUsers Module for Smart.Framework, rev.20250203

## required settings in etc/config.php
```php
define('SMART_AUTHUSERS_DB_TYPE', 'sqlite'); // to use AuthUsers with SQLite DB
//define('SMART_AUTHUSERS_DB_TYPE', 'pgsql'); // or comment the above and uncomment this to use AuthUsers with PostgreSQL DB
```
### for PostgreSQL only, must edit and activate the $configs['pgsql'] from etc/config.php

## optional settings in etc/config-index.php
```php
define('SMART_FRAMEWORK_CUSTOM_ERR_PAGE_401', 'modules/mod-auth-users/error-pages/'); // optional, register a custom 401 handler by mod auth users
define('SMART_AUTHUSERS_FAIL_EXTLOG', true); // optional, if set will log to 'tmp/logs/idx/' all the ExtAuth Fails
```

##### END
