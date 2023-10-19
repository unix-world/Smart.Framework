
# AuthAdmins: a general API for implement multi-account authentication in Smart.Framework admin areas (admin.php / task.php)

## Prior to following steps, be sure to edit etc/init.php, especially looking to change these values:
* SMART_FRAMEWORK_SECURITY_KEY
* SMART_SOFTWARE_NAMESPACE
* SMART_FRAMEWORK_ENV

# Implementing a secure multi-account Smart.Unicorn Admin Auth System (admin.php / task.php areas authentication) for Smart.Framework

## Do these after you setup a secret value to SMART_FRAMEWORK_SECURITY_KEY ; changing SMART_FRAMEWORK_SECURITY_KEY needs re-initialization ...

## Add the following line of code in modules/app/app-auth-admin.inc.php
```php
if((!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) OR (!class_exists('\\SmartModExtLib\\AuthAdmins\\SmartAuthAdminsHandler'))) {
	SmartFrameworkRuntime::Raise500Error('A required module is missing: `mod-auth-admins` # Smart.Unicorn Authentication ...');
	die('AppAuthAdmin:ModuleMissing:AuthAdmins');
} //end if
\SmartModExtLib\AuthAdmins\SmartAuthAdminsHandler::Authenticate(
	false, // enforce HTTPS: TRUE/FALSE
	false, // set this to TRUE to DISABLE Tokens
	false  // set this to TRUE to DISABLE 2FA
);
```
## Add the following setup lines in etc/config-admin.php
```php
define('APP_AUTH_ADMIN_INIT_IP_ADDRESS', '127.0.0.1'); // use your own IP address ; this is required for the 1st time initialization only
define('APP_AUTH_ADMIN_USERNAME', 'superadmin');
define('APP_AUTH_ADMIN_PASSWORD', 'encrypted password goes here ...'); // To generate an encrypted password for this config, use: \SmartAuth::encrypt_privkey($plainTextPassword, $userName)
define('APP_AUTH_PRIVILEGES', '<admin>,<custom-priv-a>,...,<custom-priv-n>');
$configs['app-auth']['adm-namespaces'] = [
	'Admins Manager' => 'admin.php?page=auth-admins.manager.stml',
	// ...
];
```
## If everything is correct, hit http(s)://your-url/admin.php and follow the steps. Finally remove from above config the values which will be asked to
### IMPORTANT: do not just comment them out ; remove them completely !! It is a security risk to keep them there after first time initialization


# Implementing single-account (Simple) Admin Auth System (admin.php / task.php areas authentication) for Smart.Framework


## Add the following line of code in modules/app/app-auth-admin.inc.php
```php
if((!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) OR (!class_exists('\\SmartModExtLib\\AuthAdmins\\SimpleAuthAdminsHandler'))) {
	SmartFrameworkRuntime::Raise500Error('A required module is missing: `mod-auth-admins` # SimpleAuth ...');
	die('AppAuthAdmin:ModuleMissing:AuthAdmins');
} //end if
\SmartModExtLib\AuthAdmins\SimpleAuthAdminsHandler::Authenticate(
	false, // enforce HTTPS: TRUE/FALSE
	false // set this to TRUE to DISABLE Tokens
	// does not support 2FA ...
);
```
## Add the following setup lines in etc/config-admin.php
```php
define('APP_AUTH_ADMIN_USERNAME', 'admin');
define('APP_AUTH_ADMIN_PASSWORD', 'encrypted password goes here ...'); // To generate an encrypted password for this config, use: \SmartAuth::encrypt_privkey($plainTextPassword, $userName)
//define('APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY', ''); // this is *optional* and used just by the Simple Admin Auth (hardcoded account) and may be required just for some extra modules
```

