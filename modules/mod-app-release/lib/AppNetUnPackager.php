<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// App Net UnPackager
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT S EXECUTION [T]
if((!defined('SMART_FRAMEWORK_RUNTIME_MODE')) OR ((string)SMART_FRAMEWORK_RUNTIME_MODE != 'web.task')) { // this must be defined in the first line of the application :: {{{SYNC-RUNTIME-MODE-OVERRIDE-TASK}}}
	@http_response_code(500);
	die('Invalid Runtime Mode in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// PHP8

//--
// gzencode / gzdecode (rfc1952) is the gzip compatible algorithm which uses CRC32 minimal checksums (a bit safer and faster than ADLER32)
//--
if((!function_exists('gzencode')) OR (!function_exists('gzdecode'))) {
	@http_response_code(500);
	die('ERROR: The PHP ZLIB Extension (gzencode/gzdecode) is required for Smart.Framework / Lib Utils');
} //end if
//--

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * UnPackager for Software Releases
 *
 * DEPENDS:
 * Smart::
 * SmartUtils::
 * SmartFileSysUtils::
 * SmartFileSystem::
 * SmartHashCrypto::
 *
 * @depends: PHP ZLIB extension ; constants: APPCODEPACK_APP_ID
 *
 * @access 		private
 * @internal
 *
 */
final class AppNetUnPackager {

	// ::
	// v.20231106

	public const APP_NET_UNPACKAGER_VERSION = 'z.20231106';// {{{SYNC-SF-APPCODE-PACK-UNPACK-PACKAGE-VERSION}}}

	public const APP_NET_UNPACKAGER_MIN_PACK_SIZE = 777; // min 777 bytes by the headers

	public const APP_NET_UNPACKAGER_FOLDER 			= '#APPCODE-UNPACK#/'; // {{{SYNC-APPCODEUNPACK-FOLDER}}}
	public const APP_NET_UNPACKAGER_DEPLOYS_FOLDER 	= '#DEPLOY-VERSIONS/'; // {{{SYNC-APPCODEUNPACK-DEPLOYS-FOLDER}}}

	public const APP_NET_UNPACKAGER_HTACCESS_PROTECT = '
# Deny Access: Apache 2.2
<IfModule !mod_authz_core.c>
	Order allow,deny
	Deny from all
</IfModule>
# Deny Access: Apache 2.4
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>

# Disable Indexing
<IfModule mod_autoindex.c>
	IndexIgnore *
</IfModule>
Options -Indexes
'; // {{{SYNC-SMART-APP-INI-HTACCESS}}}

	private static $unpack_app_log_file = '';


	//================================================================
	public static function unpack_app_hash(?string $secret) : string {
		//--
		$hex = (string) SmartHashCrypto::hmac(
			'sha384',
			(string) $secret.'#'.(defined('SMART_FRAMEWORK_SECURITY_KEY') ? SMART_FRAMEWORK_SECURITY_KEY : ''),
			(string) '*AppCode(Un)Pack*'.self::unpack_get_app_id()
		);
		//--
		return (string) Smart::base_from_hex_convert((string)$hex, 62);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function unpack_valid_app_id(string $appid) : string {
		//--
		$appid = (string) $appid; // do not trim here !
		//--
		if(
			((string)trim((string)$appid) == '')
			OR
			((int)strlen((string)$appid) < 4)
			OR // {{{SYNC-APPCODEPACK-ID-SIZE}}}
			((int)strlen((string)$appid) > 63)
			OR
			(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$appid))
		) {
			return 'INVALID APP ID: '.$appid.' # must be between 4 and 63 characters';
		} //end if
		if(!preg_match('/^[_a-z0-9\-\.]+$/', (string)$appid)) { // regex namespace
			return 'INVALID APP ID: '.$appid.' # contains invalid characters';
		} //end if
		//--
		if((string)str_replace(['.', '-', '_'], '', (string)trim((string)$appid)) == '') {
			return 'INVALID APP ID: '.$appid.' # must contain letters or numbers as characters';
		} //end if
		//--
		switch((string)strtolower((string)trim((string)$appid))) {
			case '':
			case '0.0.0.0':
			case '127.0.0.1':
			case 'local':
			case 'localhost':
			case 'com':
			case 'net':
			case 'org':
		//	case '#appcode-unpack#':	// no testing, it cannot contain hash #
		//	case '#_optimized_#':		// no testing, it cannot contain hash #
		//	case '#app-release#':		// no testing, it cannot contain hash #
		//	case '#db':					// no testing, it cannot contain hash #
		//	case '_@releases':			// no testing, it cannot contain at @
			case '.ht-sf-singleuser-mode':
			case '.htaccess':
			case '.htpasswd':
			case 'sf-dev-only.nopack':
			case '_scripts':
			case '_sql':
			case 'etc':
			case 'lib':
			case 'modules':
			case 'tmp':
			case 'wpub':
				return 'INVALID APP ID: '.$appid.' # reserved name';
				break;
			default:
				// ok
		} //end switch
		if(self::unpack_test_dissalowed_ext((string)$appid) === true) {
			return 'INVALID APP ID: '.$appid.' # special name';
		} //end if
		//--
		$fext = (string) SmartFileSysUtils::extractPathFileExtension((string)$appid);
		if(self::unpack_test_dissalowed_ext((string)$fext) === true) {
			return 'INVALID APP ID: '.$appid.' # reserved name extension: '.$fext;
		} //end if
		//--
		return '';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function unpack_valid_file_name(string $file) : bool {
		//--
		$tfile = (string) trim((string)$file);
		//--
		if((string)$tfile != '') {
			//--
			if( // {{{SYNC-NETARCH-DENIED-PATHS}}}
				((string)trim((string)$tfile) != '') AND // check for empty file name
				((string)$tfile != '.') AND ((string)$tfile != '..') AND ((string)$tfile != '/') AND // check for reserved: . .. /
				((string)strtolower((string)$tfile) != '.svn') AND // ignore svn
				((string)strtolower((string)$tfile) != '.git') AND ((string)strtolower((string)$tfile) != '.gitignore') AND ((string)strtolower((string)$tfile) != '.gitattributes') AND // ignore git
				((string)strtoupper((string)substr((string)$tfile, 0, 4)) != '.DS_') AND // ignore macos special files
				((string)trim((string)str_replace([ '_', '-', '.', '@', '#' ], '', (string)$tfile)) != '') AND // can't be just a combination of: _ - . @ #
				((string)$tfile != 'tmp') AND // dissalow `tmp` folder, it should neved be replaced
				((string)strtolower((string)substr((string)$tfile, -4, 4)) != '.tmp') AND // dissalow temporary files
				((string)strtolower((string)substr((string)$tfile, -5, 5)) != '.lock') AND ((string)strtolower((string)substr((string)$tfile, -4, 4)) != '.lck') AND // dissalow lock files
				((string)strtolower((string)substr((string)$tfile, 0, 7)) != '.ht-sf-') // dissalow special files like single user lock, starting with: .ht-sf-
			) {
				return true; // valid
			} //end if
			//--
		} //end if
		//--
		return false; // invalid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function unpack_create_basefolder() : string {
		//--
		$unpack_basefolder = (string) self::APP_NET_UNPACKAGER_FOLDER;
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$unpack_basefolder);
		//--
		clearstatcache(true, (string)$unpack_basefolder);
		//--
		if(!SmartFileSystem::is_type_dir((string)$unpack_basefolder)) {
			SmartFileSystem::dir_create((string)$unpack_basefolder, false); // non-recursive !!
			if(!SmartFileSystem::is_type_dir((string)$unpack_basefolder)) {
				return 'Failed to create the NetArchive Unpack Base Folder';
			} //end if
		} //end if
		//--
		if(SmartFileSystem::write_if_not_exists((string)$unpack_basefolder.'.htaccess', (string)self::APP_NET_UNPACKAGER_HTACCESS_PROTECT, 'yes') != 1) { // write if not exists wit content compare
			return 'NetArchive Unpack Base Folder .htaccess failed to be (re)written !';
		} //end if
		//--
		if(!SmartFileSystem::is_type_file((string)$unpack_basefolder.'index.html')) {
			if(SmartFileSystem::write((string)$unpack_basefolder.'index.html', '') != 1) {
				return 'NetArchive Unpack Base Folder index.html failed to be (re)written !';
			} //end if
		} //end if
		//--
		return '';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function unpack_netarchive(string $y_content, bool $testonly) : string {
		//--
		if(!$testonly) {
			self::$unpack_app_log_file = ''; // clear, reset, but not in test only mode to avoid reset last real unpack log path
		} //end if
		//--
		if(!defined('APPCODEPACK_APP_ID')) {
			return 'A required constant has not been defined and is mandatory: APPCODEPACK_APP_ID';
		} //end if
		$test_err_appid = (string) self::unpack_valid_app_id((string)APPCODEPACK_APP_ID);
		if((string)$test_err_appid != '') {
			return 'APP ID ERROR: '.$test_err_appid;
		} //end if
		//--
		clearstatcache(true); // do a full clear stat cache at the begining
		//--
		if(!$testonly) { // IF NOT TEST: CREATE NEW @ TMP NETARCH FOLDER
			$test_create_basefolder = (string) self::unpack_create_basefolder();
			if((string)$test_create_basefolder != '') {
				return 'ERROR: '.$test_create_basefolder;
			} //end if
		} //end if
		//--
		$tmp_ppfx = (string) self::APP_NET_UNPACKAGER_FOLDER.'#TMP-UNPACK-@'.Smart::safe_filename((string)APPCODEPACK_APP_ID).'-'.Smart::uuid_35();
		//--
		$the_tmp_netarch_lock = (string) rtrim((string)$tmp_ppfx, '/').'.LOCK'; // the lock file ; {{{SYNC-NETARCH-DENIED-PATHS}}}
		if(SmartFileSysUtils::checkIfSafePath((string)$the_tmp_netarch_lock) != 1) {
			return 'ERROR: Invalid TMP Package Lock File Path: '.$the_tmp_netarch_lock;
		} //end if
		$the_tmp_netarch_folder = (string) SmartFileSysUtils::addPathTrailingSlash((string)$tmp_ppfx); // must end with trailing slash ; {{{SYNC-NETARCH-DENIED-PATHS}}}
		if(SmartFileSysUtils::checkIfSafePath((string)$the_tmp_netarch_folder) != 1) {
			return 'ERROR: Invalid TMP Package Unpack Folder Path: '.$the_tmp_netarch_folder;
		} //end if
		//--
		if(!$testonly) { // IF NOT TEST: CREATE NEW @ TMP NETARCH FOLDER
			//--
			if(SmartFileSystem::path_exists((string)$the_tmp_netarch_lock)) {
				return 'ERROR: A NetArchive Package Lock Exists. Perhaps another instance is running a deploy right now. If the problem persist it may be a dead lock and this Lock File must be manually cleared: `'.$the_tmp_netarch_lock.'` ...';
			} //end if
			//--
			$test_fxop = SmartFileSystem::write((string)$the_tmp_netarch_lock, 'NetArchive Unpack Lock File @ '.date('Y-m-d H:i:s O'));
			if(($test_fxop != 1) OR (!SmartFileSystem::is_type_file((string)$the_tmp_netarch_lock))) {
				return 'ERROR: TMP Package LockFile failed to be created !';
			} //end if
			//--
			if(SmartFileSystem::path_exists((string)$the_tmp_netarch_folder)) {
				SmartFileSystem::dir_delete((string)$the_tmp_netarch_folder);
				if(SmartFileSystem::path_exists((string)$the_tmp_netarch_folder)) {
					return 'ERROR: TMP Package Folder cannot be cleared !';
				} //end if
			} //end if
			//--
		} //end if
		//--
		$err = (string) self::unpack_operate_netarchive((string)$y_content, (bool)$testonly, (string)$the_tmp_netarch_folder);
		//--
		if(!$testonly) { // IF NOT TEST: CREATE NEW @ TMP NETARCH FOLDER
			//--
			if(SmartFileSystem::path_exists((string)$the_tmp_netarch_folder)) {
				SmartFileSystem::dir_delete((string)$the_tmp_netarch_folder);
				if(SmartFileSystem::path_exists((string)$the_tmp_netarch_folder)) {
					return 'ERROR: TMP Package Folder cannot be cleared !';
				} //end if
			} //end if
			//--
			$test_fxop = SmartFileSystem::delete((string)$the_tmp_netarch_lock);
			if(($test_fxop != 1) OR (SmartFileSystem::is_type_file((string)$the_tmp_netarch_lock))) {
				return 'ERROR: TMP Package LockFile failed to be removed !';
			} //end if
			//--
		} //end if
		//--
		return (string) $err;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function unpack_get_last_log_file() : string {
		//--
		return (string) self::$unpack_app_log_file; // this becomes available just after unpacking with unpack_netarchive() and is set by unpack_operate_netarchive()
		//--
	} //END FUNCTION
	//================================================================


	//======= [PRIVATES]


	//================================================================
	private static function unpack_test_dissalowed_ext(?string $fext) : bool {
		//--
		switch((string)strtolower((string)trim((string)$fext))) { // {{{SYNC-NETARCH-APPID-EXTS}}}
			case '.':
			case '-':
			case '_':
			case 'appcodepack':
			case 'appcodeunpack':
			case 'idx':
			case 'idx-sess':
			case 'index':
			case 'adm':
			case 'adm-sess':
			case 'admin':
			case 'tsk':
			case 'tsk-sess':
			case 'task':
			case 'runtime':
			case 'middleware':
			case 'controller':
			case 'model':
			case 'install':
			case 'bsd':
			case 'bsd.booted':
			case 'bsd.rd':
			case 'bsd.sp':
			case 'bsd.mp':
			case 'grub':
			case 'vmlinuz':
			case 'linux':
			case 'unix':
			case 'openbsd':
			case 'obsd':
			case 'subversion':
			case 'svn':
			case 'git':
			case 'boot':
			case 'root':
			case 'altroot':
			case 'dev':
			case 'mnt':
			case 'mnt2':
			case 'cfg':
			case 'etc':
			case 'bin':
			case 'sbin':
			case 'home':
			case 'opt':
			case 'usr':
			case 'var':
			case 'sys':
			case 'tmp':
			case 'www':
			case 'rm':
			case 'rmdir':
			case 'ls':
			case 'rsync':
			case 'rclone':
			case 'backup':
			case 'license':
			case 'license-bsd':
			case 'license-gplv3':
			case 'license-gpl':
			case 'license-mit':
			case 'nopack':
			case 'archive':
			case 'appid':
			case 'app-release':
			case 'dav':
			case 'webdav':
			case 'caldav':
			case 'carddav':
			case 'haproxy':
			case 'nginx':
			case 'apache':
			case 'ping':
			case 'tcp':
			case 'tcp6':
			case 'udp':
			case 'udp6':
			case 'smtp':
			case 'pop3':
			case 'imap4':
			case 'ssl':
			case 'tls':
			case 'http':
			case 'https':
			case 'htaccess':
			case 'htpasswd':
			case 'unsecure':
			case 'secure':
			case 'security':
			case 'pub':
			case 'public':
			case 'priv':
			case 'private':
			case 'sig':
			case 'cert':
			case 'cer':
			case 'crt':
			case 'key':
			case 'pem':
			case 'conf':
			case 'cache':
			case 'sessions':
			case 'dump':
			case 'sql':
			case 'sqlite':
			case 'sqlite3':
			case 'database':
			case 'mongo':
			case 'mongodb':
			case 'pgsql':
			case 'postgresql':
			case 'mysql':
			case 'mysqli':
			case 'mariadb':
			case 'redis':
			case 'memcache':
			case 'memcached':
			case 'sendmail':
			case 'opensmtpd':
			case 'dovecot':
			case 'bind':
			case 'bind9':
			case 'rspamd':
			case 'webmin':
			case 'db':
			case 'gdbm':
			case 'dbm':
			case 'dba':
			case 'lck':
			case 'md':
			case 'csv':
			case 'tab':
			case 'eml':
			case 'ics':
			case 'vcf':
			case 'txt':
			case 'log':
			case 'logs':
			case 'inc':
			case 'xml':
			case 'svg':
			case 'htm':
			case 'html':
			case 'phtml':
			case 'shtml':
			case 'tpl':
			case 'mtpl':
			case 'twist':
			case 'twig':
			case 't3fluid':
			case 'php':
			case 'js':
			case 'javascript':
			case 'json':
			case 'jquery':
			case 'qunit':
			case 'ajax':
			case 'css':
			case 'scss':
			case 'sass':
			case 'ini':
			case 'yaml':
			case 'go':
			case 'py':
			case 'pyc':
			case 'pl':
			case 'sh':
			case 'ksh':
			case 'ash':
			case 'dash':
			case 'bash':
			case 'tgz':
			case 'tbz':
			case 'lz':
			case 'xz':
			case 'gz':
			case 'bz2':
			case 'zip':
			case 'rar':
			case 'arj':
			case 'dmg':
			case '7z':
			case 'netarch':
			case 'z-netarch':
			case 'cgi':
			case 'fcgi':
			case 'fastcgi':
			case 'scgi':
			case 'wsgi':
			case 'so':
			case 'dylib':
			case 'dll':
			case 'msi':
			case 'exe':
			case 'cmd':
			case 'bat':
			case 'asp':
			case 'jsp':
			case 'rb':
			case 'png':
			case 'gif':
			case 'jpg':
			case 'jpeg':
			case 'webp':
			case 'webm':
			case 'mp4':
			case 'mov':
			case 'ogv':
			case 'ogg':
			case 'pdf':
			case 'odt':
			case 'ods':
			case 'odp':
				return true;
				break;
			default:
				// ok
		} //end switch
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function unpack_operate_netarchive(string $y_content, bool $testonly, string $the_tmp_netarch_folder) : string {
		//--
		if(!$testonly) {
			self::$unpack_app_log_file = ''; // clear, reset, but not in test only mode to avoid reset last real unpack log path
		} //end if
		//--
		$y_content = (string) trim((string)$y_content);
		$the_pack_size = (int) strlen((string)$y_content);
		if((int)$the_pack_size < (int)self::APP_NET_UNPACKAGER_MIN_PACK_SIZE) {
			return 'ERROR: The Package Size is invalid, must have at least '.(int)self::APP_NET_UNPACKAGER_MIN_PACK_SIZE.' bytes but have: '.(int)$the_pack_size;
		} //end if
		//-- CHECK RESTORE ROOT
		if(!defined('APPCODEPACK_APP_ID')) {
			return 'A required constant (APPCODEPACK_APP_ID) has not been defined and must be used as the restore root / validations';
		} //end if
		$restoreroot = (string) Smart::safe_filename((string)APPCODEPACK_APP_ID);
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$restoreroot);
		//-- DEFINE @ TMP NETARCH FOLDERS
		$unpack_versionsfolder = (string) self::APP_NET_UNPACKAGER_FOLDER.self::APP_NET_UNPACKAGER_DEPLOYS_FOLDER; // must have trailing slash
		$the_tmp_netarch_data_hash = (string) SmartHashCrypto::hmac('sha3-512', (string)APPCODEPACK_APP_ID, (string)$y_content, true);
		//-- CHECK SAFE NAME @ TMP NETARCH FOLDER
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$the_tmp_netarch_folder);
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$unpack_versionsfolder);
		//--
		if(!$testonly) { // IF NOT TEST: CREATE NEW @ TMP NETARCH FOLDER
			//--
			SmartFileSystem::dir_create((string)$the_tmp_netarch_folder, true); // recursive dir create
			if(SmartFileSystem::is_type_dir((string)$the_tmp_netarch_folder)) {
				if(!SmartFileSystem::have_access_write((string)$the_tmp_netarch_folder)) {
					return 'ERROR: TMP Package Folder is not writable !';
				} //end if
				if(SmartFileSystem::write((string)$the_tmp_netarch_folder.'.htaccess', (string)self::APP_NET_UNPACKAGER_HTACCESS_PROTECT) != 1) {
					return 'ERROR: TMP Package Folder .htaccess failed to be (re)written !';
				} //end if
				if(SmartFileSystem::write((string) $the_tmp_netarch_folder.'index.html', '') != 1) {
					return 'ERROR: TMP Package Folder index.html failed to be (re)written !';
				} //end if
			} else {
				return 'ERROR: TMP Package Folder cannot be created !';
			} //end if
			//--
			if(!SmartFileSystem::is_type_dir((string)$unpack_versionsfolder)) {
				SmartFileSystem::dir_create((string)$unpack_versionsfolder, false); // non-recursive !!
			} //end if
			if(!SmartFileSystem::is_type_dir((string)$unpack_versionsfolder)) {
				return 'ERROR: Failed to create the NetArchive Saved Versions TMP Base Folder: '.$unpack_versionsfolder;
			} //end if
			//--
			$tmp_chk_last_data_hash = '';
			$tmp_the_package_logfile_path = (string) $unpack_versionsfolder.'package-@'.Smart::safe_filename((string)APPCODEPACK_APP_ID).'.log'; // package-@APP_ID.log registers the checksum of last uploaded package to avoid re-upload many times the same package ; but will alow restore from older or newer non-identical packages
			SmartFileSysUtils::raiseErrorIfUnsafePath((string)$tmp_the_package_logfile_path);
			if(SmartFileSystem::is_type_file((string)$tmp_the_package_logfile_path)) {
				$tmp_chk_last_data_hash = (string) trim((string)SmartFileSystem::read((string)$tmp_the_package_logfile_path));
			} //end if
			if((string)$tmp_chk_last_data_hash == (string)$the_tmp_netarch_data_hash) {
				return 'WARNING: NetArchive Package already Deployed. The current package to be deployed is identical with the previous deployed package ...';
			} //end if
			if(SmartFileSystem::write((string)$tmp_the_package_logfile_path, (string)$the_tmp_netarch_data_hash) != 1) {
				return 'ERROR: NetArchive Saved Versions: EXTRACTION Folder '.$tmp_the_package_logfile_path.' failed to be (re)written !';
			} //end if
			//--
		} //end if
		//--
		if((string)$y_content == '') {
			return 'ERROR: Package is Empty !';
		} //end if
		//--
		if((string)substr((string)$y_content, 0, 23) != '#AppCodePack-NetArchive') {
			return 'ERROR: Invalid Package Type !';
		} //end if
		//--
		if(strpos((string)$y_content, '#END-NetArchive') === false) {
			return 'ERROR: Incomplete Package !';
		} //end if
		//--
		$y_content = (string)str_replace(["\r\n", "\r"], "\n", (string)$y_content);
		//--
		$the_pack_name = '';
		$the_pack_appid = '';
		$the_pack_dir = '';
		$the_pack_items = 0;
		$cksum_pak = '';
		$cksum_raw = '';
		$data = '';
		//--
		$arr = []; // init
		$arr = (array) explode("\n", (string)$y_content);
		//$y_content = ''; // free mem !!! DO NOT CLEAR, MUST BE LOGGED !!!
		//--
		for($i=0; $i<count($arr); $i++) {
			$arr[$i] = (string) trim((string)$arr[$i]);
			if(strlen($arr[$i]) > 0) {
				if((string)substr($arr[$i], 0, 1) == '#') {
					if((string)substr((string)strtolower($arr[$i]), 0, 6) == '#file:') {
						$the_pack_name = (string) trim((string)substr($arr[$i], 6));
					} elseif((string)substr((string)strtolower($arr[$i]), 0, 8) == '#app-id:') {
						$the_pack_appid = (string) trim((string)substr((string)$arr[$i], 8));
					} elseif((string)substr((string)strtolower($arr[$i]), 0, 21) == '#package-sources-dir:') {
						$the_pack_dir = (string) trim((string)substr((string)$arr[$i], 21));
					} elseif((string)substr((string)strtolower($arr[$i]), 0, 20) == '#package-info-items:') {
						$the_pack_items = (int) (string) trim((string)substr($arr[$i], 20));
					} elseif((string)substr((string)strtolower($arr[$i]), 0, 19) == '#package-signature:') {
						$cksum_pak = (string) trim((string)substr((string)$arr[$i], 19));
					} elseif((string)substr((string)strtolower($arr[$i]), 0, 20) == '#checksum-signature:') {
						$cksum_raw = (string) trim((string)substr((string)$arr[$i], 20));
					} //end if
				} else {
					$data = (string) trim((string)$arr[$i]);
				} //end if
			} //end if
		} //end for
		if(strpos($the_pack_dir, '/'.$the_pack_appid.'/') === false) {
			return 'ERROR: Invalid Package Sources Dir: '.$the_pack_dir;
		} //end if
		if((string)APPCODEPACK_APP_ID != (string)$the_pack_appid) {
			return 'ERROR: Invalid Package AppID: Expected='.APPCODEPACK_APP_ID.' / Got='.$the_pack_appid;
		} //end if
		$arr = null; // free mem
		if((string)$the_pack_name == '') {
			return 'ERROR: Empty Package File Name !';
		} //end if
		if(SmartFileSysUtils::checkIfSafeFileOrDirName((string)$the_pack_name) != 1) {
			return 'ERROR: Invalid Package File Name: '.$the_pack_name;
		} //end if
		if((string)$the_pack_appid == '') {
			return 'ERROR: Empty Package AppID !';
		} //end if
		if(SmartFileSysUtils::checkIfSafeFileOrDirName((string)$the_pack_appid) != 1) {
			return 'ERROR: Invalid Package AppID: '.$the_pack_appid;
		} //end if
		if((string)$the_pack_dir == '') {
			return 'ERROR: Empty Package Dir !';
		} //end if
		if(SmartFileSysUtils::checkIfSafePath((string)$the_pack_dir) != 1) {
			return 'ERROR: Invalid Package Path: '.$the_pack_dir;
		} //end if
		if((int)$the_pack_items <= 0) {
			return 'ERROR: Package Items Number appear to be Zero: '.$the_pack_items;
		} //end if
		if((string)$cksum_pak == '') {
			return 'ERROR: Empty Package Checksum !';
		} //end if
		if((string)$cksum_raw == '') {
			return 'ERROR: Empty Data Checksum !';
		} //end if
		if((string)$data == '') {
			return 'ERROR: Empty Data !';
		} //end if
		//--
		if((string)$cksum_pak != (string)SmartHashCrypto::sh3a384((string)self::unpack_get_app_id()."\v".$data, true)) { // {{{SYNC-APP-PAK-CKSUM}}}
			return 'ERROR: Package Checksum Failed !';
		} //end if else
		//--
		$data = base64_decode((string)$data, true); // STRICT ! don't make it string, may return false
		if(($data === false) OR ((string)trim((string)$data) == '')) {
			return 'ERROR: Package B64 Failed !';
		} //end if
		$data = @gzdecode((string)$data); // don't make it string, may return false
		if(($data === false) OR ((string)trim((string)$data) == '')) {
			return 'ERROR: Data inflate ERROR !';
		} //end if
		//--
		if((string)$cksum_raw != (string)SmartHashCrypto::sh3a512((string)$data, true)) { // {{{SYNC-APP-PAK-CONTENT-CKSUM}}}
			return 'ERROR: Data Checksum Failed !';
		} //end if else
		if(strpos((string)$data, '#[AppCodePack-Package//START]') === false) {
			return 'ERROR: Invalid Data Type !';
		} //end if
		if(strpos((string)$data, '#[AppCodePack-Package//END]') === false) {
			return 'ERROR: Incomplete Data !';
		} //end if
		//--
		$folders_pak = 0;
		$folders_num = 0;
		$files_pak = 0;
		$files_num = 0;
		//--
		$arr = []; // init
		$arr = (array) explode("\n", (string)$data);
		$data = ''; // free mem, we do not need it anymore
		$basefoldername = (string) $the_pack_appid;
		$the_pack_files_n_dirs = [];
		for($i=0; $i<count($arr); $i++) {
			//--
			$arr[$i] = (string) trim((string)$arr[$i]);
			//--
			if((string)$arr[$i] != '') {
				//--
				if((string)substr($arr[$i], 0, 1) == '#') {
					//--
					//echo $arr[$i]."\n";
					if((string)substr((string)strtolower($arr[$i]), 0, 9) == '#folders:') {
						$folders_pak = (int) trim(substr($arr[$i], 9));
					} elseif((string)substr((string)strtolower($arr[$i]), 0, 7) == '#files:') {
						$files_pak = (int) trim(substr($arr[$i], 7));
					} //end if
					//--
				} else {
					//--
					$cols = (array) explode("\t", (string)$arr[$i]);
					//--
					$tmp_fname 			= (string) trim((string)($cols[0] ?? null));
					$tmp_ftype 			= (string) trim((string)($cols[1] ?? null));
					$tmp_fsize 			= (int)    trim((string)($cols[2] ?? null));
					$tmp_cksum_name 	= (string) trim((string)($cols[3] ?? null));
					$tmp_cksum_cx_raw 	= (string) trim((string)($cols[4] ?? null));
					$tmp_cksum_cx_pak 	= (string) trim((string)($cols[5] ?? null));
					$tmp_fcontent 		= (string) trim((string)($cols[6] ?? null));
					//--
					$cols = null; // free mem
					//--
					if((string)$tmp_fname != '') {
						if(strpos((string)$tmp_fname, (string)trim((string)$the_pack_appid, '/').'/') !== 0) { // all archived paths must start with appid/ folder
							return 'ERROR: Invalid Archived Item Path vs. AppID Restore Root: '.$tmp_fname;
						} //end if
					} //end if
					if(((string)$tmp_ftype == 'DIR') AND ((string)$tmp_fname != '')) {
						//--
						// dirname[\t]DIR[\t]0[\t]sha224checksumName[\t][\t][\t][\n]
						//--
						if((string)$tmp_cksum_name != (string)SmartHashCrypto::sha224((string)$tmp_fname, true)) { // {{{SYNC-APP-PAK-DIR-CKSUM}}}
							return 'ERROR: DirName Checksum Failed on: '.$tmp_fname;
						} //end if
						//--
						if(!SmartFileSysUtils::checkIfSafePath((string)$tmp_fname)) {
							return 'ERROR: Invalid Folder Name in archive: '.$tmp_fname;
						} //end if
						$the_new_dir = (string) SmartFileSysUtils::addPathTrailingSlash((string)$the_tmp_netarch_folder).$tmp_fname;
						if(!SmartFileSysUtils::checkIfSafePath((string)$the_new_dir)) {
							return 'ERROR: Invalid Folder Path to unarchive: '.$the_new_dir;
						} //end if
						//--
						if(!$testonly) { // IF NOT TEST: CREATE NEW SUB-FOLDER AS IN ARCH @ TMP NETARCH FOLDER
							SmartFileSystem::dir_create((string)$the_new_dir, true); // recursive dir create
							if(SmartFileSystem::is_type_dir((string)$the_new_dir)) {
								if(!SmartFileSystem::have_access_write((string)$the_new_dir)) {
									return 'ERROR: TMP Package Sub-Folder is not writable: '.$the_new_dir;
								} //end if
							} else {
								return 'ERROR: TMP Package Sub-Folder cannot be created: '.$the_new_dir;
							} //end if
						} //end if
						//--
						$the_new_dir = ''; // free mem
						//--
						$folders_num += 1;
						$the_pack_files_n_dirs[] = (string) '(D): '.$tmp_fname;
						//--
					} elseif(((string)$tmp_ftype == 'FILE') AND ((string)$tmp_fname != '')) {
						//--
						// filename[\t]filetype[\t]filesize[\t]sh3a224b64checksumName[\t]sha256b64checksumFileContent[\t]sh3a256b64checksumArch[\t]filecontent_gzencode-FORCE_GZIP_bin2hex[\n]
						//--
						if((string)$tmp_cksum_name != (string)SmartHashCrypto::sh3a224((string)$tmp_fname, true)) { // {{{SYNC-APP-PAK-FILEPATH-CKSUM}}}
							return 'ERROR: FileName Checksum Failed on: '.$tmp_fname;
						} //end if
						//--
						if((string)$tmp_cksum_cx_pak != (string)SmartHashCrypto::sh3a256((string)$tmp_fcontent, true)) { // {{{SYNC-APP-PAK-FILEARCH-CKSUM}}}
							return 'ERROR: File Package Checksum Failed on: '.$tmp_fname;
						} //end if
						//--
						$tmp_fcontent = hex2bin((string)trim((string)$tmp_fcontent)); // don't make it string, may return false
						if($tmp_fcontent === false) {
							return 'ERROR: File Content Failed to be restored on: '.$tmp_fname;
						} //end if
						$tmp_fcontent = (string) $tmp_fcontent;
						if((string)$tmp_cksum_cx_raw != (string)SmartHashCrypto::sha256((string)$tmp_fcontent, true)) { // {{{SYNC-APP-PAK-FILECONTENT-CKSUM}}}
							return 'ERROR: File Content Checksum Failed on: '.$tmp_fname;
						} //end if
						//--
						$the_new_dir = (string) pathinfo((string)$tmp_fname, PATHINFO_DIRNAME);
						if((string)trim((string)$the_new_dir) == '') {
							return 'ERROR: Empty Folder Prefix for File Name to unarchive: '.$tmp_fname;
						} //end if
						$the_new_dir = (string) SmartFileSysUtils::addPathTrailingSlash((string)$the_tmp_netarch_folder).$the_new_dir;
						if(!SmartFileSysUtils::checkIfSafePath((string)$the_new_dir)) {
							return 'ERROR: Invalid Folder Path of File to unarchive: '.$the_new_dir.' @ '.$tmp_fname;
						} //end if
						$the_new_file = (string) SmartFileSysUtils::addPathTrailingSlash((string)$the_tmp_netarch_folder).$tmp_fname;
						if(!SmartFileSysUtils::checkIfSafePath((string)$the_new_file)) {
							return 'ERROR: Invalid File Path to unarchive: '.$the_new_file;
						} //end if
						//--
						if(!$testonly) { // IF NOT TEST: CREATE NEW FILES + RESTORE THEIR ORIGINAL CONTENT AS IN ARCH @ TMP NETARCH FOLDER
							SmartFileSystem::dir_create((string)$the_new_dir, true); // recursive dir create
							if(SmartFileSystem::is_type_dir((string)$the_new_dir)) {
								if(!SmartFileSystem::have_access_write((string)$the_new_dir)) {
									return 'ERROR: TMP Package Sub-Folder of File is not writable: '.$the_new_dir.' @ '.$tmp_fname;
								} //end if
								if(SmartFileSystem::write((string)$the_new_file, (string)$tmp_fcontent) != 1) { // returns 0/1
									return 'ERROR: Failed to restore a File from archive: '.$tmp_fname;
								} //end if
								if(!SmartFileSystem::is_type_file((string)$the_new_file)) {
									return 'ERROR: Failed to restore a File from archive (path check): '.$tmp_fname;
								} //end if
								if(!SmartFileSystem::have_access_read((string)$the_new_file)) {
									return 'ERROR: Failed to restore a File from archive (readable check): '.$tmp_fname;
								} //end if
								if(!SmartFileSystem::have_access_write((string)$the_new_file)) {
									return 'ERROR: Failed to restore a File from archive (writable check): '.$tmp_fname;
								} //end if
								$fop = (string) SmartFileSystem::read((string)$the_new_file);
								if((string)$fop !== (string)$tmp_fcontent) {
									return 'ERROR: Failed to restore a File from archive (content check): '.$tmp_fname;
								} //end if
								if((string)SmartHashCrypto::sha256((string)$fop, true) != (string)$tmp_cksum_cx_raw) { // {{{SYNC-APP-PAK-FILECONTENT-CKSUM}}}
									return 'ERROR: Failed to restore a File from archive (content checksum): '.$tmp_fname;
								} //end if
								$fop = ''; // free mem
								$tmp_fcontent = ''; // free mem
							} else {
								return 'ERROR: TMP Package Sub-Folder of File cannot be created: '.$the_new_dir.' @ '.$tmp_fname;
							} //end if
						} //end if
						//--
						$the_new_dir = ''; // free mem
						$the_new_file = ''; // free mem
						//--
						$files_num += 1;
						$the_pack_files_n_dirs[] = (string) '(F): '.$tmp_fname;
						//--
					} else {
						//--
						return 'ERROR: Invalid or Empty Item Type in NetArchive: ['.$tmp_ftype.'] @ '.$tmp_fname;
						//--
					} //end if else
					//--
				} //end if
				//--
			} //end if
			//--
		} //end for
		//--
		$arr = null; // free mem
		//--
		if(($folders_pak <= 0) OR ($folders_pak != $folders_num)) {
			return 'ERROR: Invalid Folders Number: '.SmartFileSysUtils::addPathTrailingSlash((string)$folders_pak).$folders_num;
		} //end if else
		if(($files_pak <= 0) OR ($files_pak != $files_num)) {
			return 'ERROR: Invalid Files Number: '.SmartFileSysUtils::addPathTrailingSlash((string)$files_pak).$files_num;
		} //end if else
		if((int)Smart::array_size($the_pack_files_n_dirs) !== (int)$the_pack_items) {
			return 'ERROR: Invalid Archive Total Items: [Registered='.(int)$the_pack_items.';Detected='.(int)Smart::array_size($the_pack_files_n_dirs).']';
		} //end if
		//--
		if((string)$basefoldername == '') {
			return 'ERROR: Failed to detect the Base Folder of Archive';
		} //end if
		if(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$basefoldername)) {
			return 'ERROR: Invalid Base Folder Name of Archive (check): '.$basefoldername;
		} //end if
		if((string)$the_pack_appid !== (string)$basefoldername) {
			return 'ERROR: The detected Base Folder of Archive does not match registered one: [Registered='.$the_pack_appid.';Detected='.$basefoldername.']';
		} //end if
		//--
		if(!$testonly) {
			//--
			$basefolderpath = (string) SmartFileSysUtils::addPathTrailingSlash((string)$the_tmp_netarch_folder.$basefoldername);
			//--
			if(!SmartFileSysUtils::checkIfSafePath((string)$basefolderpath)) {
				return 'ERROR: Invalid Base Folder Path of Archive (Invalid Path): '.$basefolderpath;
			} //end if
			if(!SmartFileSystem::is_type_dir((string)$basefolderpath)) {
				SmartFileSystem::dir_create((string)$basefolderpath);
			} //end if
			if(!SmartFileSystem::is_type_dir((string)$basefolderpath)) {
				return 'ERROR: Invalid Base Folder Path of Archive (Not Directory): '.$basefolderpath;
			} //end if
			if(!SmartFileSystem::have_access_read((string)$basefolderpath)) {
				return 'ERROR: Invalid Base Folder Path of Archive (Not Readable): '.$basefolderpath;
			} //end if
			//--
			$the_tmp_netarch_versions_hash = (string) Smart::safe_filename((string)APPCODEPACK_APP_ID.'@'.date('YmdHis').'#'.Smart::format_number_dec(microtime(true), 4, '.', '')); // use AppID and microtime
			$the_tmp_netarch_versions_folder = (string) $unpack_versionsfolder.$the_tmp_netarch_versions_hash.'/'; // must end with trailing slash ; {{{SYNC-NETARCH-DENIED-PATHS}}}
			$the_tmp_netarch_versions_logfile = (string) $unpack_versionsfolder.$the_tmp_netarch_versions_hash.'.log'; // must be file
			self::$unpack_app_log_file = (string) $the_tmp_netarch_versions_logfile;
			//--
			SmartFileSystem::dir_create((string)$the_tmp_netarch_versions_folder, false);
			if(!SmartFileSystem::is_type_dir((string)$the_tmp_netarch_versions_folder)) {
				return 'ERROR: Failed to create the NetArchive Saved Versions EXTRACTION Folder: '.$the_tmp_netarch_versions_folder;
			} //end if
			//--
			if((string)$restoreroot == '') { // restore to script path
				//--
				return 'ERROR: The NetArchive CURRENT Restore (Root) Folder Name is Empty !';
				//--
			} else { // have a restore root sub-folder
				//--
				if(strpos((string)$restoreroot, '/') !== false) { // must not have slashes
					return 'ERROR: Invalid NetArchive Restore (Root) Folder (must not contain slashes): '.$restoreroot;
				} //end if
				//--
				if((string)$the_pack_appid !== (string)$restoreroot) {
					return 'ERROR: The NetArchive Restore (Root) Folder does not match the AppID: [Registered='.$the_pack_appid.';Detected='.$restoreroot.']';
				} //end if
				//--
				$restoreroot = SmartFileSysUtils::addPathTrailingSlash((string)$restoreroot); // add the trailing slash
				//--
				if(SmartFileSysUtils::checkIfSafePath((string)$restoreroot)) {
					SmartFileSystem::dir_create((string)$restoreroot, false); // not recursive
					if(!SmartFileSystem::is_type_dir((string)$restoreroot)) {
						return 'ERROR: Failed to create the NetArchive Restore (Root) Folder: '.$restoreroot;
					} //end if
					if(!SmartFileSystem::have_access_read((string)$restoreroot)) {
						return 'ERROR: The NetArchive Restore (Root) Folder is not readable: '.$restoreroot;
					} //end if
					if(!SmartFileSystem::have_access_write((string)$restoreroot)) {
						return 'ERROR: The NetArchive Restore (Root) Folder is not writable: '.$restoreroot;
					} //end if
				} else {
					return 'ERROR: Invalid NetArchive Restore (Root) Folder: '.$restoreroot;
				} //end if
				//--
				SmartFileSystem::write((string)$restoreroot.'.sf-unpack', (string)APPCODEPACK_APP_ID."\n".date('Y-m-d H:i:s O'));
				//--
			} //end if
			//--
			$found_files_restored = [];
			//--
			clearstatcache(true, (string)$basefolderpath);
			$arr_dir_files = scandir((string)$basefolderpath); // don't make it array, can be false
			if(($arr_dir_files !== false) AND (Smart::array_size($arr_dir_files) > 0)) {
				$arr_dir_sorted_files = []; // init
				for($i=0; $i<Smart::array_size($arr_dir_files); $i++) {
					if((string)$arr_dir_files[$i] == 'maintenance.html') { // maintenance.html must be first !
						$arr_dir_sorted_files[] = (string) $arr_dir_files[$i];
					} //end if
				} //end for
				for($i=0; $i<Smart::array_size($arr_dir_files); $i++) {
					if(((string)$arr_dir_files[$i] != 'maintenance.html') AND ((string)trim((string)$arr_dir_files[$i]) != '') AND ((string)$arr_dir_files[$i] != '.') AND ((string)$arr_dir_files[$i] != '..')) { // fix ok
						$arr_dir_sorted_files[] = (string) $arr_dir_files[$i]; // add the rest of files except . and ..
					} //end if
				} //end for
				$arr_dir_files = (array) $arr_dir_sorted_files;
				$arr_dir_sorted_files = []; // free mem
			} else {
				$arr_dir_files = [];
			} //end if else
			if(Smart::array_size($arr_dir_files) > 0) {
				$found_files_total = 0;
				$found_files_ok = 0;
				$found_files_notok = [];
				for($i=0; $i<Smart::array_size($arr_dir_files); $i++) {
					$file = (string) $arr_dir_files[$i];
					if( // {{{SYNC-NETARCH-DENIED-PATHS}}}
						((string)trim((string)$file) != '') AND // check for empty file name
						((string)$file != '.') AND ((string)$file != '..') AND ((string)$file != '/') AND // check for reserved: . .. /
						(self::unpack_valid_file_name((string)$file) === true)
					) {
						$found_files_total++;
						if(SmartFileSysUtils::checkIfSafeFileOrDirName((string)$file)) {
							$fpath = (string) $basefolderpath.$file;
							if(SmartFileSysUtils::checkIfSafePath((string)$fpath)) {
								if((SmartFileSystem::is_type_dir((string)$fpath)) OR (SmartFileSystem::is_type_file((string)$fpath))) { // dir or file
									if(!SmartFileSysUtils::checkIfSafePath((string)$restoreroot.$file)) {
										return 'ERROR: Invalid NetArchive Restore Path: '.$restoreroot.$file;
									} //end if
									if(SmartFileSystem::path_exists((string)$restoreroot.$file)) {
										$move_xop = (int) self::unpack_move_file_or_dir_netarchive((string)$restoreroot.$file, (string)$the_tmp_netarch_versions_folder.$file);
										if($move_xop != 1) {
											return 'ERROR: Failed to move a File or Dir to the NetArchive Saved Versions EXTRACTION Folder ['.($move_xop === -7 ? '@link:-7' : $move_xop).']: '.$file; // $the_tmp_netarch_versions_folder.$file
										} //end if
									} //end if
									$move_xop = (int) self::unpack_move_file_or_dir_netarchive((string)$fpath, (string)$restoreroot.$file);
									if($move_xop != 1) {
										return 'ERROR: Failed to restore a File or Dir from the NetArchive EXTRACTION Folder ['.$move_xop.']: '.$fpath;
									} //end if
									$found_files_ok++;
									$found_files_restored[] = (string) $file;
								} else {
									$found_files_notok[] = (string) $file;
								} //end if
							} else {
								$found_files_notok[] = (string) $file;
							} //end if
						} else {
							$found_files_notok[] = (string) $file;
						} //end if
					} elseif((string)trim((string)str_replace([ '_', '-', '.', '@', '#' ], '', (string)$file)) == '') {
						return 'ERROR: NetArchive unsupported File or Folder name `'.$file.'`';
					} elseif((string)trim((string)$file) == 'tmp') {
						return 'ERROR: NetArchive dissalow Files or Folders named `tmp`';
					} //end if
				} //end for
				if(((int)$found_files_total !== (int)$found_files_ok) OR (Smart::array_size($found_files_notok) > 0)) {
					return 'ERROR: Invalid Files found in the Folder Path of Archive ('.((int)$found_files_total-(int)$found_files_ok).'): ['."\n".implode("\n", (array)$found_files_notok)."\n".']';
				} //end if
			} else {
			//	return 'ERROR: Invalid Base Folder Path of Archive (Is Empty, Have No Contents): '.$basefolderpath;
			} //end if else
			//--
			clearstatcache(true, (string)$basefolderpath);
			$arr_dir_files = scandir((string)$basefolderpath, SCANDIR_SORT_ASCENDING); // mixed
			if(!is_array($arr_dir_files)) {
				return 'ERROR: NetArchive failed to scan the directory `'.$basefolderpath.'`';
			} //end if
			$not_restored_files = [];
			if(Smart::array_size($arr_dir_files) > 0) {
				for($i=0; $i<Smart::array_size($arr_dir_files); $i++) {
					$file = (string) $arr_dir_files[$i];
					if(((string)trim((string)$file) != '') AND ((string)$file != '.') AND ((string)$file != '..')) { // fix ok
						$not_restored_files[] = (string) $file;
					} //end if
				} //end for
			} //end if
			//--
			if(Smart::array_size($not_restored_files) > 0) {
				return 'ERROR: The Base Folder Path of Archive (IS NOT EMPTY, There are some not-restored files or dirs ('.Smart::array_size($not_restored_files).'): ['."\n".implode("\n", (array)$not_restored_files)."\n".']';
			} //end if
			//--
			$the_log_txt = [];
			$the_log_txt[] = '##### AppCodePack/Unpack ('.self::APP_NET_UNPACKAGER_VERSION.') - Log (for AppID: '.(string)APPCODEPACK_APP_ID.') @ '.$the_tmp_netarch_versions_hash;
			$the_log_txt[] = '##### IP: '.trim((string) SmartUtils::get_ip_client().' ; '.SmartUtils::get_ip_proxyclient(), '; ').' @ Client-Signature: '.SmartUtils::get_visitor_useragent();
			$the_log_txt[] = '### NetArchive Package: '.$the_pack_name;
			if(Smart::array_size($not_restored_files) > 0) {
				$the_log_txt[] = '### NOT OK: There are some Not Restored Files / Dirs ('.Smart::array_size($not_restored_files).'): '.'['."\n".implode("\n", (array)$not_restored_files)."\n".']';
			} else {
				$the_log_txt[] = '### *** OK: ALL FILES AND DIRS RESTORED ***';
			} //end if else
			$the_log_txt[] = '### OK: The list with Restored Base Files / Dirs: ('.Smart::array_size($found_files_restored).') ['."\n".implode("\n", (array)$found_files_restored)."\n".']';
			$the_log_txt[] = '## INFO: The complete list with archive Files and Dirs ('.Smart::array_size($the_pack_files_n_dirs).'): '.'['."\n".implode("\n", (array)$the_pack_files_n_dirs)."\n".']';
			$the_log_txt[] = "\n".'### PACKAGE:'."\n\n".$y_content."\n\n";
			$the_log_txt[] = '##### END';
			//--
			SmartFileSystem::write((string)$the_tmp_netarch_versions_logfile, (string)implode("\n\n", (array) $the_log_txt));
			//--
			SmartFileSystem::delete((string)$restoreroot.'.sf-unpack');
			//--
		} //end if
		//--
		return ''; // OK
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function unpack_move_file_or_dir_netarchive(string $path, string $newpath) : int {
		//--
		if((string)$path == '') {
			return -1;
		} //end if
		if((string)$newpath == '') {
			return -2;
		} //end if
		//--
		if(!SmartFileSysUtils::checkIfSafePath((string)$path)) {
			return -3;
		} //end if
		if(!SmartFileSysUtils::checkIfSafePath((string)$newpath)) {
			return -4;
		} //end if
		//--
		if(!SmartFileSystem::path_exists((string)$path)) {
			return -5;
		} //end if
		if(SmartFileSystem::path_exists((string)$newpath)) {
			return -6;
		} //end if
		//--
		if(SmartFileSystem::is_type_link((string)$path)) { // link
			return -7; // important: don't operate on symlinks (they must not be moved or replaced) !!
		} elseif(SmartFileSystem::is_type_dir((string)$path)) { // dir
			return (int) SmartFileSystem::dir_rename((string)$path, (string)$newpath);
		} elseif(SmartFileSystem::is_type_file((string)$path)) { // file
			return (int) SmartFileSystem::rename((string)$path, (string)$newpath);
		} //end if else
		//--
		return -8;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function unpack_get_app_id() : string {
		//--
		return (string) (defined('APPCODEPACK_APP_ID') ? APPCODEPACK_APP_ID : '!');
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
