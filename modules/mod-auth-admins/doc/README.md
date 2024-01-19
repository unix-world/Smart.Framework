
# AuthAdmins: a general API for implement multi-account authentication in Smart.Framework admin areas (admin.php / task.php)

## Prior to following steps, be sure to edit etc/init.php, especially looking to change these values:
* SMART_FRAMEWORK_SECURITY_KEY
* SMART_SOFTWARE_NAMESPACE
* SMART_FRAMEWORK_ENV
* SMART_SOFTWARE_AUTH_2FA
* SMART_SOFTWARE_AUTH_TOKENS

# Implementing a secure multi-account Smart.Unicorn Admins Auth System (admin.php / task.php areas authentication) for Smart.Framework

## Do these after you setup a secret value to SMART_FRAMEWORK_SECURITY_KEY ; changing SMART_FRAMEWORK_SECURITY_KEY needs re-initialization ...

## Add the following line of code in modules/app/app-auth-adm-tsk.inc.php
```php
if((!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) OR (!class_exists('\\SmartModExtLib\\AuthAdmins\\SmartAuthAdminsHandler'))) {
	SmartFrameworkRuntime::Raise500Error('A required module is missing: `mod-auth-admins` # Smart.Unicorn Authentication ...');
	die('AppAuthAdmin:ModuleMissing:AuthAdmins');
} //end if
final class SmartModelAuthAdmins    extends \SmartModDataModel\AuthAdmins\SqAuthAdmins{}
final class SmartModelAuthLogAdmins extends \SmartModDataModel\AuthAdmins\SqAuthLog{}
\SmartModExtLib\AuthAdmins\SmartAuthAdminsHandler::Authenticate(
	false, // enforce HTTPS: TRUE/FALSE
);
```
## Add the following setup lines in etc/config-admin.php ; just for the initialization step ; after initialization remove these from config !
```php
define('APP_AUTH_ADMIN_INIT_IP_ADDRESS', '127.0.0.1'); // use your own IP address ; this is required for the 1st time initialization only
define('APP_AUTH_ADMIN_USERNAME', 'superadmin');
define('APP_AUTH_ADMIN_PASSWORD', 'plain password goes here ...');
define('APP_AUTH_PRIVILEGES', '<admin>,<custom-priv-a>,...,<custom-priv-n>');
$configs['app-auth']['adm-namespaces'] = [
	'Admins Manager' => 'admin.php?page=auth-admins.manager.stml',
	// ...
];
```
## If everything is correct, hit http(s)://your-url/admin.php and follow the steps. Finally remove from above config the values which will be asked to
### IMPORTANT: do not just comment them out ; remove them completely !! It is a security risk to keep them there after first time initialization

#### If 2FA is Enabled for the backend (SMART_SOFTWARE_AUTH_2FA = 1 or 2), don't forget to save or scan the QR code that you get on the init page !
#### If Tokens are disabled for the backend (SMART_SOFTWARE_AUTH_TOKENS = 0 or 3) the cross-authentication between services will not work, it is based on on-the-fly SWT tokens + Auth Bearer
