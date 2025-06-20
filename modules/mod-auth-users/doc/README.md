
# Using the Smart AuthUsers Module for Smart.Framework, rev.20250620

## required settings in etc/config.php
```php
//define('SMART_AUTHUSERS_DB_TYPE', 'sqlite'); // to use AuthUsers with SQLite DB
define('SMART_AUTHUSERS_DB_TYPE', 'pgsql'); // or comment the above and uncomment this to use AuthUsers with PostgreSQL DB
```
### for PostgreSQL only, must edit and activate the $configs['pgsql'] from etc/config.php

## optional settings in etc/config-index.php
```php
define('SMART_FRAMEWORK_CUSTOM_ERR_PAGE_401', 'modules/mod-auth-users/error-pages/'); // optional, register a custom 401 handler by mod auth users
define('SMART_AUTHUSERS_FAIL_EXTLOG', true); // optional, if set will log to 'tmp/logs/idx/' all the ExtAuth Fails
define('SMART_AUTHUSERS_EMAIL_TPL_PATH', 'etc/templates/email/auth-users/'); // it must exist ; make a copy of modules/mod-auth-users/templates/email and customize it ...
```


# Privileges vs Restrictions

## Privileges are assigned by admin
Default privileges: <oauth2>

## Restrictions can be set by users for virtual login accounts based on their account
Default available restrictions for JWT: <readonly>,<virtual>

## Important settings
set in config-index.php
## Enable mod auth users (required), set in config-index.php ; for slaves must be set also in config-admin.php
```php
const SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS = true;
```
## Restrict registration by IP (optional), set in config-index.php
```php
const SMART_FRAMEWORK_MOD_AUTH_USERS_REGISTRATION_ALLOW_IPLIST = '<127.0.0.1>';
```

# Auth Cluster Management
Auth can operate in Auth Standard Mode or Auth Cluster Mode
To use an authentication cluster must setup one master and 1..n slaves.
The master must have access to the database, create it using: mod-auth-users/models/sql for pgsql or sqlite.
Only master can access the authentication DB and all authorizations are given just by the master.
This architecture is using a distributed authn model than needs no DB access on distributed nodes because the auth data is pushed on the coresponding node on each successful authentication on the master.
An account that is assigned to srv1 by example will login on master, can operate on master only changing the account settings and all the rest of actions will be redirected to the srv1 node
where that account have his storage workspace as: `#db/idx/s8/s82vf212p0.0892641437`. The remote account data is pushed into `#db/idx/s8/s82vf212p0.0892641437/account.json`.
All user's data (by custom apps) must be stored under the above folder only.
Note that `#db/` is secured with an `.htaccess` file that works under apache that will deny the access to that folder in a web environment.
To setup apache to take in consideration .htaccess security files you must change the `AllowOverride None` with `AllowOverride All` in the Directory section of the apache configuration.
Under Nginx or other servers the procedure is different, consult the web server documentation how to restrict the access to certain folders under the web root.
Before going live test the access to it, you must test the security access on these folders, all must return `403 Forbidden`.
* `#db/` as https://my-server/%23db/
* `tmp/` as https://my-server/tmp/
If you see a blank page instead of `403 Forbidden` it means the access is not restricted.
DO NOT GO LIVE WITHOUT RESTRICTING THE ACCESS TO the above two folders, otherwise they are exposed to the world access and is a huge security risk !
```
<Directory /www>
#	AllowOverride None
	AllowOverride All
</Directory>
```
Therefore on successful authentication, the JWT token is issued and the auth data is pushed to the coresponding slave in the cluster where the account belongs to (have it's own workspace).
Some accounts may be keep on master server or not, depending on how the rules are made.

## On Master
set in `init.php`
```php
const SMART_FRAMEWORK_AUTH_CLUSTER_ID = ''; // master, empty
```
set in `config-index.php`
```php
define('SMART_AUTH_USERS_CLUSTER_NODES', [
	'srv1' => [
		'insecure' 	=> true, // optional: allows http:// or insecure https://
		'url' 		=> 'https://127.0.0.1:8443/server101/', // must end with a slash
		'user' 		=> ':SWT', // this is for Auth Bearer (SWT) authentication type ; this username is mandatory to select Auth Type Bearer SWT
		'pass' 		=> 'admin#base64ofpasshash...', // format: 'username#base64ofpasshash...' ; can be any admin user than must have the <auth-users:cluster> as privilege for the given pass hash, base64 encoded: 'base64ofpasshash...'
	],
	'srv2' => [
		'insecure' 	=> true, // optional: allows http:// or insecure https://
		'url' 		=> 'https://127.0.0.1:8443/server102/', // must end with a slash
		'user' 		=> ':TOKEN', // this is for Auth Token authentication type ; this username is mandatory to select Auth Type Token
		'pass' 		=> 'admin#abcdef...', // format: 'username#abcdef...' ; can be any admin user than must have the <auth-users:cluster> as privilege for the given token: 'abcdef...'
	],
	'srv3' => [
		'insecure' 	=> true, // optional: allows http:// or insecure https://
		'url' 		=> 'http://127.0.0.1:8088/server103/', // must end with a slash
		'user' 		=> 'admin#token', // format: 'username#token' ; this is for Auth Basic authentication type ; can be any admin user than must have the <auth-users:cluster> as privilege for the given token
		'pass' 		=> 'abcdef...', // the given token: 'abcdef...'
	],
]); // master
```

## On Slaves, set in `init.php`
for 1st slave:
```php
const SMART_FRAMEWORK_AUTH_CLUSTER_ID = 'srv1'; // slave ID: 'srv1' or can use any other valid sub-domain name
```
for 2nd slave:
```php
const SMART_FRAMEWORK_AUTH_CLUSTER_ID = 'srv2'; // slave ID: 'srv2' or can use any other valid sub-domain name
```
for all slaves:
set in `config-index.php`
```php
define('SMART_AUTH_USERS_CLUSTER_MASTER', 'https://127.0.0.1:8443/server000/');
```

## Hints for Master/Slaves Auth
It will not work if the user path is detected in #db/ ; user path must be moved on slave ...


##### END
