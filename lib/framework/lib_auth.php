<?php
// [LIB - Smart.Framework / Authentication Support]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Authentication Support
//======================================================

// [PHP8]


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: Smart Authentication - provides a safe, in-memory object, to store the authentication data and to provide a standard way to work with authentication inside the Smart.Framework / modules.
 *
 * It must be re-populated on each execution. This ensure using a good practice for Auth mechanisms.
 * It provides the core and only STATIC methods to integrate authentication with Smart.Framework
 *
 * The very important security concerns regarding the authentication protection against forgery,
 * the mechanism implemented in this class will offer a very good protection by using CONSTANTS,
 * so after a successful or failed authentication, the page needs to refresh or load another page
 * in order to change authentication data.
 * This comes as a result of more than 15 years research over web and client/server technologies ...
 *
 * The best practices are to never store Authentication objects in session because session can be forged.
 * To avoid such bad practices this object provide just STATIC methods !!
 * The best way is to store just the login ID and a safe password HASH (irreversible) in session
 * and re-check authentication each time when the page is loaded or to use the HTTP AUTH mechanism
 * to avoid store in session the username / password hash. Or this can be combined with cookies, but requires
 * much more atention to pay by avoiding session forgery or cookie leakage.
 * Session must be protected against forgery by implementing advanced detection mechanisms based on
 * IP address and the browser signature of the client. The Smart Session provides a good layer for this purpose.
 *
 * <code>
 * // Usage example:
 * SmartAuth::some_method_of_this_class(...);
 * </code>
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		PUBLIC
 * @depends 	Smart, SmartEnvironment, SmartCipherCrypto
 * @version 	v.20250307
 * @package 	@Core:Authentication
 *
 */
final class SmartAuth {

	// ::

	public const ALGO_PASS_NONE 					=   0; // for tokens ...
	public const ALGO_PASS_PLAIN 					=   1;
	public const ALGO_PASS_SMART_SAFE_SF_PASS 		=  77;
	public const ALGO_PASS_SMART_SAFE_ARGON_PASS 	=  78; // currently unsupported in PHP, supported just in Go lang
	public const ALGO_PASS_SMART_SAFE_BCRYPT 		= 123;
	public const ALGO_PASS_SMART_SAFE_OPQ_TOKEN 	= 204; // Opaque Token
	public const ALGO_PASS_SMART_SAFE_WEB_TOKEN 	= 216; // Web (Signed) Token / JWT
	public const ALGO_PASS_SMART_SAFE_SWT_TOKEN 	= 228; // SWT Tokens
	public const ALGO_PASS_CUSTOM_TOKEN 			= 244; // other, custom implementations of token logic
	public const ALGO_PASS_CUSTOM_HASH_PASS 		= 255; // other, custom implementations of pass hashing ; needs custom implementation

	public const DEFAULT_PRIVILEGES 				= '<super-admin>,<admin>'; 							// {{{SYNC-AUTH-DEFAULT-ADM-SUPER-PRIVS}}}
	public const DEFAULT_RESTRICTIONS 				= ''; 												// {{{SYNC-AUTH-DEFAULT-ADM-SUPER-RESTR}}}
	public const REGEX_VALID_PRIV_KEY 				= '/^([a-z]{1}[a-z0-9\-\:]{0,20}[a-z0-9]{1})$/'; 	// valid name for one privilege key from list of privileges ; a valid privilege key can have 2..22 characters and can contain only: `a-z`, `0-9`, `:` and `-` ; must start with `a-z` only ; must not end with `:` or `-`
	public const REGEX_SAFE_AUTH_EMAIL_ADDRESS 		= '/^[_a-z0-9\-\.]{1,41}@[a-z0-9\-\.]{3,30}$/'; 	// Safe Auth Email regex ; internet email@(subdomain.)domain.name ; max 72 ; practical
	public const REGEX_SAFE_AUTH_USER_NAME 			= '/^[_a-z0-9\-\.@]{5,72}$/';  						// Safe Auth Username Regex ; cover boths above
	public const REGEX_SAFE_CLUSTER_ID 				= '/^[_a-z0-9\-]{1,63}$/';
	public const REGEX_VALID_JWT_SERIAL 			= '/^[A-Z0-9]{10}\-[A-Z0-9]{10}$/';

	public const PASSWORD_BHASH_LENGTH 				= 60; 			// the length of PASSWORD_BCRYPT / cost=8 ; {{{SYNC-PASS-HASH-AUTH-LEN}}}
	public const PASSWORD_BHASH_PREFIX 				= '$2y$08$'; 	// the prefix of PASSWORD_BCRYPT / cost=8 ; {{{SYNC-PASS-HASH-AUTH-PFX}}} ; PHP 5.3.7 and above uses $2y$

	public const SWT_VERSION_PREFIX 				= 'SWT'; 		// {{{SYNC-AUTH-TOKEN-SWT}}}
	public const SWT_VERSION_SUFFIX 				= 'v1.3';
	public const SWT_VERSION_SIGNATURE 				= 'swt:1.3';
	public const SWT_MAX_LIFETIME 					= 3600 * 24; 	// max 24 hours


	private static bool    $AuthCompleted 	= false;	// prevent re-authentication, ... the results may be unpredictable !!
	private static array   $AuthData 		= []; 		// register Auth Data
	private static ?string $AuthCluster 	= null; 	// the Auth Cluster


	//================================================================
	/**
	 * Validate an Auth Cluster ID, as a sub-domain name for Auth
	 *
	 * @param 	STRING 	$cluster  			:: The Auth Cluster ID ; empty or max length is 63, can contain just: _ a-z 0-9 -
	 *
	 * @return 	BOOLEAN						:: TRUE if the cluster ID is valid or FALSE if not
	 */
	public static function validate_cluster_id(?string $cluster) : bool { // {{{SYNC-VALIDATE-AUTH-CLUSTER-ID}}}
		//--
		$cluster = (string) trim((string)$cluster);
		if((string)$cluster == '') {
			return true; // is OK, default cluster ID is Empty
		} //end if
		//--
		if(((int)strlen((string)$cluster) >= 1) AND ((int)strlen((string)$cluster) <= 63) AND (!!preg_match((string)self::REGEX_SAFE_CLUSTER_ID, (string)$cluster))) {
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the current (Server) Cluster ID, as a sub-domain name for Auth, from what is set into: SMART_FRAMEWORK_AUTH_CLUSTER_ID
	 *
	 * @return 	STRING						:: `` empty string if Master, or sub-domain if Auth Cluster Slave, ex: `srv1`
	 */
	public static function get_cluster_id() : string {
		//--
		if(self::$AuthCluster !== null) {
			return (string) self::$AuthCluster;
		} //end if
		//--
		$cluster = '';
		//--
		if(defined('SMART_FRAMEWORK_AUTH_CLUSTER_ID')) { // this is mandatory just for slaves, but for security master can define it with an empty value
			$cluster = (string) trim((string)SMART_FRAMEWORK_AUTH_CLUSTER_ID);
			if((string)$cluster != '') {
				if(self::validate_cluster_id((string)$cluster) !== true) { // {{{SYNC-AUTH-USERS-SAFE-VALIDATE-CLUSTER}}}
					Smart::log_warning(__METHOD__.' # Invalid Cluster ID definition for SMART_FRAMEWORK_AUTH_CLUSTER_ID: `'.$cluster.'`');
					$cluster = '__--invalid--__';
				} //end if
			} //end if
		} //end if
		//--
		self::$AuthCluster = (string) $cluster;
		//--
		return (string) self::$AuthCluster;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Tests if current Auth Server is the Master Auth, used for clustered Authentication only
	 *
	 * @return 	BOOLEAN						:: TRUE if the cluster ID is Empty (is Master) or FALSE if the cluster ID is non-empty, a slave node
	 */
	public static function is_cluster_master_auth() : bool {
		//--
		if((string)self::get_cluster_id() == '') {
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Tests if current Auth Server is the Master Auth, used for clustered Authentication only
	 *
	 * @return 	BOOLEAN						:: TRUE if the cluster ID is Empty (is Master) or FALSE if the cluster ID is non-empty, a slave node
	 */
	public static function is_cluster_current_workspace() : bool {
		//--
		if(self::is_authenticated() !== true) { // this check is mandatory because if not authenticated the get_auth_cluster_id() will return an empty value, the same as get_cluster_id(), but actually have no workspace set !
			return false;
		} //end if
		//--
		if((string)self::get_cluster_id() !== (string)self::get_auth_cluster_id()) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Validate an extended Auth User Name, ex: using email as username
	 *
	 * @param 	STRING 	$auth_user_name  	:: The Auth User Name to be validated ; max length is 25, can contain just: _ a-z 0-9 - . @
	 *
	 * @return 	BOOLEAN						:: TRUE if the username is valid or FALSE if not
	 */
	public static function validate_auth_ext_username(?string $auth_user_name) : bool { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
		//--
		$auth_user_name = (string) $auth_user_name;
		//--
		if(
			((string)trim((string)$auth_user_name) == '') OR // must not be empty
			((int)strlen((string)$auth_user_name) < 5) OR // min length is 5 characters
			((int)strlen((string)$auth_user_name) > 72) OR // max length is 72 characters
			(!preg_match((string)self::REGEX_SAFE_AUTH_USER_NAME, (string)$auth_user_name)) OR // may contain only _ a-z 0-9 - . @
			(!preg_match((string)self::REGEX_SAFE_AUTH_EMAIL_ADDRESS, (string)$auth_user_name)) OR // must be in the format of email address
			(strpos((string)$auth_user_name, '@@') !== false) OR // cannot contain 2 or more successive @
			((string)substr((string)$auth_user_name, 0, 1) == '.') OR // cannot start with a . (dot)
			((string)substr((string)$auth_user_name, -1, 1) == '.') // cannot end with a . (dot)
		) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Validate a standard Auth User Name
	 *
	 * @param 	STRING 	$auth_user_name  	:: The Auth User Name to be validated ; max length is 25, can contain just: a-z 0-9 .
	 * @param 	BOOL 	$check_reasonable 	:: Check for reasonable length ; if FALSE, min length is 3 ; if TRUE, min length is 5
	 *
	 * @return 	BOOLEAN						:: TRUE if the username is valid or FALSE if not
	 */
	public static function validate_auth_username(?string $auth_user_name, bool $check_reasonable=false) : bool { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
		//--
		$auth_user_name = (string) $auth_user_name;
		//--
		if(
			((string)trim((string)$auth_user_name) == '') OR // must not be empty
			((int)strlen((string)$auth_user_name) < 3) OR // min length is 3 characters
			((int)strlen((string)$auth_user_name) > 25) OR // max length is 25 characters
			(!preg_match((string)Smart::REGEX_SAFE_USERNAME, (string)$auth_user_name)) OR // may contain only a-z 0-9 .
			((string)trim((string)$auth_user_name, '.') == '') OR // cannot contain only dots
			(strpos((string)$auth_user_name, '..') !== false) OR // cannot contain 2 or more successive dots
			((string)substr((string)$auth_user_name, 0, 1) == '.') OR // cannot start with a . (dot)
			((string)substr((string)$auth_user_name, 1, 1) == '.') OR // {{{SYNC-AUTH-ACCOUNT-ID-PATH-2-CHARS-PREFIX}}} ; cannot have a . (dot) as 2nd character (req. by prefixed dirs) which must have 2 characters prefix
			((string)substr((string)$auth_user_name, -1, 1) == '.') OR // cannot end with a . (dot)
			((int)substr_count((string)$auth_user_name, '.') > (int)(floor((int)strlen((string)$auth_user_name) / 3))) // cannot contain more dots (.) than 33% from all characters
		) {
			return false;
		} //end if
		//--
		if($check_reasonable !== false) {
			if((int)strlen((string)$auth_user_name) < 5) { // check for a reasonable length
				return false;
			} //end if
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Validate an Auth Password
	 *
	 * @param 	STRING 	$auth_user_pass  	:: The Auth Password (plain text) to be validated ; min length is 7 (8 if complexity is enabled) ; max length is 55
	 * @param 	BOOL 	$check_complexity 	:: Check for password complexity ; if set to TRUE will allow just complex passwords
	 *
	 * @return 	BOOLEAN						:: TRUE if the password is valid or FALSE if not
	 */
	public static function validate_auth_password(?string $auth_user_pass, bool $check_complexity=false) : bool { // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
		//--
		// TO KEEP SAFE HASHING OF PASSWORDS WITH SHA512, max length is 55 ; {{{SYNC-PASS-HASH-SHA512-PLUS-SALT-SAFE}}}
		//--
		if( // {{{SYNC-AUTH-VALIDATE-PASSWORD-LEN}}}
			((string)trim((string)$auth_user_pass) == '') // cannot be empty
			OR
			((string)$auth_user_pass != (string)strtr((string)$auth_user_pass, [ (string)chr(0) => '' ])) // DISSALOW NULL CHARACTER
			OR
			((int)SmartUnicode::str_len((string)$auth_user_pass) < (int)SmartHashCrypto::PASSWORD_PLAIN_MIN_LENGTH)
			OR // min length is 7, check also against trimmed password
			((int)SmartUnicode::str_len((string)trim((string)$auth_user_pass)) < (int)SmartHashCrypto::PASSWORD_PLAIN_MIN_LENGTH)
			OR
			((int)SmartUnicode::str_len((string)$auth_user_pass) > (int)SmartHashCrypto::PASSWORD_PLAIN_MAX_LENGTH)
			OR
			((string)substr((string)$auth_user_pass, 0, 1) == ' ') // cannot start with a space (after space normalizations)
			OR
			((string)substr((string)$auth_user_pass, -1, 1) == ' ') // cannot end with a space (after space normalizations)
			OR
			((int)substr_count((string)$auth_user_pass, ' ') > (int)((int)floor((int)SmartUnicode::str_len((string)$auth_user_pass) / 3))) // cannot contain more dots than 33% from all characters
		) {
			return false;
		} //end if
		//--
		if($check_complexity !== false) {
			if(
				((int)SmartUnicode::str_len((string)$auth_user_pass) < 8)
				OR // min length is 8 for complex passwords, check also against trimmed password
				((int)SmartUnicode::str_len((string)trim((string)$auth_user_pass)) < 8)
				OR
				(!preg_match('/[A-Z]/', (string)$auth_user_pass)) OR // must have at least one caps letter
				(!preg_match('/[a-z]/', (string)$auth_user_pass)) OR // must have at least one small letter
				(!preg_match('/[0-9]/', (string)$auth_user_pass)) OR // must have at least one digit
				(!preg_match('/[^A-Za-z0-9]/', (string)$auth_user_pass)) // must have at least one special character
			) {
				return false;
			} //end if
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Creates a SAFE Auth Password Hash, using algo=PASSWORD_BCRYPT (strong one-way hashing algorithm) ; cost=8 (fast, considered an acceptable baseline cost, with low hardware usage)
	 * Password min length is 7 (8 if complexity is enabled) ; max length is 55 (as supported by PASSWORD_BCRYPT algo)
	 *
	 * This will produce a standard crypt() compatible hash using the `$2y$` identifier
	 * The salt is RANDOM and will be returned as standard being part of the hashed password
	 *
	 * A random salt will always be used here, because:
	 * 	- the salt option is deprecated since PHP 7.4 and removed since PHP 8.0
	 * 	- it is now preferred to simply use a random salt that is generated by default
	 *
	 * It will return a 60 character string if success or empty string in case of failure
	 *
	 * @param 	STRING 	$plainpass  		:: The plain password ; min length is 7 ; max length is 55
	 *
	 * @return 	STRING						:: will return an EMPTY string if the password is not valid or in case of failure ; if all OK will always return a 60 character string
	 */
	public static function password_hash_create(string $plainpass) : string { // {{{SYNC-PASS-HASH-AUTH-BCRYPT}}}
		//--
		if((string)trim((string)$plainpass) == '') {
			Smart::log_notice(__METHOD__.' # Failed to create a password hash, Empty Password Length !');
			return '';
		} //end if
		//-- {{{SYNC-PASS-HASH-AUTH-CHECKS}}}
		if(!self::validate_auth_password((string)$plainpass, false)) { // don't check here for complexity
			Smart::log_notice(__METHOD__.' # Failed to create a password hash, Invalid Password or Invalid Length: '.(int)SmartUnicode::str_len((string)$plainpass));
			return '';
		} //end if
		//--
		$hash = (string) trim((string)password_hash((string)$plainpass, PASSWORD_BCRYPT, [ 'cost' => 8 ])); // default cost is 8 (fast)
		//--
		if((int)strlen((string)$hash) != (int)self::PASSWORD_BHASH_LENGTH) { // {{{SYNC-PASS-HASH-AUTH-LEN}}}
			Smart::log_warning(__METHOD__.' # Failed to create a password hash, Invalid Length !');
			return '';
		} //end if
		//--
		if(strpos((string)$hash, (string)self::PASSWORD_BHASH_PREFIX) !== 0) { // {{{SYNC-PASS-HASH-AUTH-PFX}}}
			Smart::log_warning(__METHOD__.' # Failed to create a password hash, Invalid Prefix !');
			return '';
		} //end if
		//--
		if(self::password_hash_validate_format((string)$hash) !== true) { // {{{SYNC-PASS-HASH-AUTH-FORMAT}}}
			Smart::log_warning(__METHOD__.' # Failed to create a password hash, Invalid Format !');
			return '';
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//================================================================


	//==============================================================
	/**
	 * Check (verify) a password hash provided by SmartAuth::password_hash_create() have a valid format
	 * IMPORTANT: this method just validate the password hash format and DOES NOT CHECK if the Pass Hash is Valid against the provided plain password !
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING $passhash 			The password hash of which format needs to be validated by certain criteria
	 * @return BOOL 					Will return TRUE if password format is valid or FALSE if not
	 */
	public static function password_hash_validate_format(string $passhash) : bool { // {{{SYNC-PASS-HASH-AUTH-FORMAT}}} ; {{{SYNC-PASS-HASH-AUTH-BCRYPT}}}
		//--
		if((string)trim((string)$passhash) == '') {
			return false;
		} //end if
		if((string)trim((string)$passhash) != (string)$passhash) {
			return false;
		} //end if
		//--
		if((int)strlen((string)$passhash) !== (int)self::PASSWORD_BHASH_LENGTH) { // {{{SYNC-PASS-HASH-AUTH-LEN}}}
			return false;
		} //end if
		//--
		if(
			(strpos((string)$passhash, (string)self::PASSWORD_BHASH_PREFIX) !== 0)
			AND
			(strpos((string)$passhash, (string)strtr((string)self::PASSWORD_BHASH_PREFIX, ['$2y$' => '$2a$'])) !== 0)
		) { // {{{SYNC-PASS-HASH-AUTH-PFX}}}
			return false;
		} //end if
		//-- see: https://www.php.net/manual/en/function.crypt.php # CRYPT_BLOWFISH
		if(!preg_match('/^[a-zA-Z0-9\.\/]+$/', (string)substr((string)$passhash, (int)strlen((string)self::PASSWORD_BHASH_PREFIX)))) { // PHP/BCRYPT Hash will use characters: $ (prefix only) ; for the rest: . / 0-9 A-Z a-z
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//==============================================================


	//================================================================
	/**
	 * Check (verify) a password hash provided by SmartAuth::password_hash_create()
	 * It must use the same complexity check as it was used when password was hashed ; if not may return wrong results
	 *
	 * @param 	STRING 	$plainpass  			:: The plain password ; min length is 7 ; max length is 55
	 * @param 	STRING 	$passhash 				:: The password hash to be checked
	 * @return 	BOOL 							:: Will return TRUE if password match or FALSE if not
	 */
	public static function password_hash_check(string $plainpass, string $passhash) : bool { // {{{SYNC-PASS-HASH-AUTH-BCRYPT}}}
		//--
		if((string)trim((string)$plainpass) == '') {
			return false;
		} //end if
		//--
		$passhash = (string) trim((string)$passhash); // for check, this is OK, to trim !
		if((string)$passhash == '') {
			return false;
		} //end if
		//--
		if(self::password_hash_validate_format((string)$passhash) !== true) { // {{{SYNC-PASS-HASH-AUTH-FORMAT}}}
			return false;
		} //end if
		//--
		if(!self::validate_auth_password((string)$plainpass, false)) { // don't check here for complexity ; {{{SYNC-PASS-HASH-AUTH-CHECKS}}}
			return false;
		} //end if
		//--
		if(strpos((string)$passhash, '$2a$') === 0) { // fix for the passwords hashed in golang
			$passhash = (string) '$2y$'.trim((string)substr((string)$passhash, 4));
		} //end if
		//--
		return (bool) password_verify((string)$plainpass, (string)$passhash);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Lock the (in-memory) Auth Login Data
	 * This should prevent running Set Auth Data after certain waypoint in code
	 */
	public static function lock_auth_data() : void {
		//--
		self::$AuthCompleted = true;
		//--
		return;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Set the (in-memory) Auth Login Data
	 * It can be used just once per execution (session) as it stores the data using constants,
	 * and the data cannot be changed after a successful or failed authentication has set.
	 *
	 * @param 	STRING 			$y_realm 					:: *OPTIONAL* The user Authentication Realm(s)
	 * @param 	ENUM 			$y_method 					:: *OPTIONAL* The authentication method used, as description only: HTTP-BASIC / OTHER / ...
	 * @param   STRING 			$y_cluster 					:: Cluster ID or ''
	 * @param 	INTEGER 		$y_algo_pass 				:: The Pass (Hash) Algo
	 * @param 	STRING 			$y_hashpass					:: The user login password hash or plain password ; for admin/task area it only supports the SmartHashCrypto::password() type pass hash (fixed, 128 characters long) and NOT the plain password ; for index area (and other areas) it may store either: the plain password (7..55 characters) or the pass hash (60..128 characters) ; is not recommended to store here the plain password ; however, it will be stored in memory as encrypted to avoid exposure
	 * @param 	STRING 			$y_user_name				:: The user username ; Mandatory ; must be valid safe username
	 * @param 	STRING 			$y_user_id 					:: The user (login) ID ; can be the Username or Email (on backend this should be always set with the same value as Username)
	 * @param 	ARRAY 			$y_keys 					:: *OPTIONAL* The user Private Key (will be stored in memory as encrypted to avoid exposure)
	 * @param 	STRING 			$y_user_email 				:: *OPTIONAL* The user Email ; if email is used as login ID this may be redundant !
	 * @param 	STRING 			$y_user_fullname 			:: *OPTIONAL* The user Full Name (First Name + Last Name)
	 * @param 	ARRAY/STRING 	$y_user_privileges_list 	:: *OPTIONAL* The user Privileges List as string '<priv-a>,<priv-b>,...' or array ['priv-a','priv-b'] that list all the current user privileges ; a privilege key must have 3..28 characters and can contain only: a-z -
	 * @param   ARRAY/STRING 	$y_user_restrictions_list 	:: *OPTIONAL* The user Restrictions List as string '<restr-a>,<restr-b>,...' or array ['restr-a','restr-b'] that list all the current user restrictions ; a restriction key must have 3..28 characters and can contain only: a-z -
	 * @param 	STRING 			$y_user_quota 				:: *OPTIONAL* The user (storage) Quota
	 * @param 	ARRAY 			$y_user_metadata 			:: *OPTIONAL* The user metainfo, associative array with max 7 levels (sub-arrays must have no more than 6 sub-levels, can be either associative or not) ; Ex: [ 'some-key' => 101, 'another-key' => 'abc', '3rd-key' => true, '4th-key' => [0, 1, 2, 'a', 'b', 'c'], '5th-key' => [ 'x' => 'X', 'y' => 'y', 'z' => 'Z' ] ]
	 * @param 	ARRAY 			$y_workspaces 				:: *OPTIONAL* The user workspaces defs with max 1 level
	 *
	 * @return 	BOOLEAN										:: TRUE if all data is OK, FALSE if not or try to reauthenticate under the same execution (which is not allowed ; must be just once per execution)
	 */
	public static function set_auth_data(?string $y_realm, ?string $y_method, ?string $y_cluster, int $y_algo_pass, ?string $y_hashpass, ?string $y_user_name, ?string $y_user_id, ?string $y_user_email='', ?string $y_user_fullname='', $y_user_privileges_list=['none','no-privilege'], $y_user_restrictions_list=['none','no-restriction'], array $y_keys=[], int $y_user_quota=-1, array $y_user_metadata=[], array $y_workspaces=[]) : bool {
		//--
		// IMPORTANT: $y_user_privileges_list and $y_user_restrictions_list can be STRING or ARRAY, do not cast !
		// v.20250218
		//--
		if(self::$AuthCompleted !== false) { // avoid to re-auth
			Smart::log_warning(__METHOD__.' # Auth Data is Locked. You either called Auth Set Data method twice or called before the Auth Lock method ...');
			return false;
		} //end if
		self::$AuthCompleted = true;
		//--
		self::$AuthData = []; // reset the auth data
		//--
		$y_realm = (string) strtoupper((string)trim((string)$y_realm));
		if((string)$y_realm == '') {
			$y_realm = 'DEFAULT';
		} //end if
		//--
		$y_method = (string) trim((string)$y_method);
		//--
		$y_cluster = (string) trim((string)$y_cluster);
		if(self::validate_cluster_id((string)$y_cluster) !== true) {
			Smart::log_warning(__METHOD__.' # Invalid ClusterID ...');
			return false;
		} //end if
		//--
		$y_user_id = (string) trim((string)$y_user_id); // validate the same way as username, except it can have also uppercase letters: ex: UUID from DB
		if(((string)$y_user_id == '') OR (self::validate_auth_username((string)$y_user_id, false) !== true)) {
			Smart::log_warning(__METHOD__.' # Invalid UserID ...');
			return false;
		} //end if
		//--
		$y_user_name = (string) trim((string)$y_user_name);
		if(SmartEnvironment::isAdminArea() === false) { // index area
			if(((string)$y_user_name == '') OR (self::validate_auth_ext_username((string)$y_user_name, false) !== true)) {
				Smart::log_warning(__METHOD__.' # Invalid [I] UserName ...');
				return false;
			} //end if
		} else { // admin / task area
			if(((string)$y_user_name == '') OR (self::validate_auth_username((string)$y_user_name, false) !== true)) {
				Smart::log_warning(__METHOD__.' # Invalid [A] UserName ...');
				return false;
			} //end if
		} //end if
		//--
		if(SmartEnvironment::isAdminArea()) {
			if((string)$y_user_id !== (string)$y_user_name) {
				Smart::log_warning(__METHOD__.' # on Admin or Task area, the UserID must be the same as UserName ...');
				return false;
			} //end if
		} //end if
		//--
		$y_hashpass = (string) trim((string)$y_hashpass);
		if(SmartEnvironment::isAdminArea() === false) { // index area
			//--
			switch((int)$y_algo_pass) { // {{{SYNC-AUTH-USERS-ALLOWED-ALGOS}}}
				case self::ALGO_PASS_NONE:
					return false; // cannot be authenticated
					break;
				case self::ALGO_PASS_PLAIN:
					if(self::validate_auth_password((string)$plainPassword) !== true) {
						Smart::log_warning(__METHOD__.' # Invalid [I] Area Password ('.(int)$y_algo_pass.') ...');
						return false; // unsecure or empty
					} //end if
					break;
				case self::ALGO_PASS_SMART_SAFE_SF_PASS:
				case self::ALGO_PASS_SMART_SAFE_SWT_TOKEN: // the SWT works only with SF Pass, must have this kind of checks
					if(
						((int)strlen((string)$y_hashpass) != (int)SmartHashCrypto::PASSWORD_HASH_LENGTH) // {{{SYNC-AUTHADM-PASS-LENGTH}}}
						OR
						(SmartHashCrypto::validatepasshashformat((string)$y_hashpass) !== true) // {{{SYNC-AUTH-HASHPASS-FORMAT}}}
					) {
						Smart::log_warning(__METHOD__.' # Invalid [I] Area Pass Hash ('.(int)$y_algo_pass.') ...');
						return false; // the length or pass hash format is invalid
					} //end if
					break;
				case self::ALGO_PASS_SMART_SAFE_ARGON_PASS:
					return false; // currently unsupported in PHP
					break;
				case self::ALGO_PASS_SMART_SAFE_BCRYPT:
					if(
						((int)strlen((string)$y_hashpass) != (int)self::PASSWORD_BHASH_LENGTH) // {{{SYNC-AUTHADM-PASS-LENGTH}}}
						OR
						(self::password_hash_validate_format((string)$y_hashpass) !== true) // {{{SYNC-AUTH-HASHPASS-FORMAT}}}
					) {
						Smart::log_warning(__METHOD__.' # Invalid [I] Area Pass Hash ('.(int)$y_algo_pass.') ...');
						return false; // the length or pass hash format is invalid
					} //end if
					break;
				case self::ALGO_PASS_SMART_SAFE_OPQ_TOKEN:
				case self::ALGO_PASS_SMART_SAFE_WEB_TOKEN:
				case self::ALGO_PASS_CUSTOM_TOKEN:
					if( // just a general check for tokens
						((int)strlen((string)$y_hashpass) < 42) // at least SF Opaque Tokens B58 ; {{{SYNC-MAX-AUTH-TOKEN-LENGTH}}} ;
						OR
						((int)strlen((string)$y_hashpass) > 3192) // the largest token supported ... more than this does not fit in HTTP Header or Cookie
					) { // this must cover support for the supported password hash types but also the accepted length of the plain password
						Smart::log_warning(__METHOD__.' # Invalid [I] Area Token ('.(int)$y_algo_pass.') ...');
						return false; // the length or pass hash format is invalid
					} //end if
					break;
				case self::ALGO_PASS_CUSTOM_HASH_PASS:
					if( // just a general check for a supposed password hash ... it's custom, don't know how to validate the format of this hash
						((int)strlen((string)$y_hashpass) < 42) // at least SHA-256 B62
						OR
						((int)strlen((string)$y_hashpass) > 128) // max SHA512 / SHA3-512
					) { // this must cover support for the supported password hash types but also the accepted length of the plain password
						Smart::log_warning(__METHOD__.' # Invalid [I] Area Pass Hash ('.(int)$y_algo_pass.') ...');
						return false; // the length or pass hash format is invalid
					} //end if
					break;
				default:
					Smart::log_warning(__METHOD__.' # Invalid [I] Area Pass Algo ('.(int)$y_algo_pass.') ...');
					return false; // unsupported
			} //end switch
			//--
		} else { // for the admin/task area, the only supported Pass Hash must be provided by SmartHashCrypto::password()
			//--
			if(
				((int)$y_algo_pass != (int)self::ALGO_PASS_SMART_SAFE_SF_PASS)
				AND
				((int)$y_algo_pass != (int)self::ALGO_PASS_SMART_SAFE_SWT_TOKEN)
				AND
				((int)$y_algo_pass != (int)self::ALGO_PASS_SMART_SAFE_OPQ_TOKEN)
			) {
				Smart::log_warning(__METHOD__.' # Invalid [A] Area Pass Algo ('.(int)$y_algo_pass.') ...');
				return false; // can't handle other pass algos, needs Safe Pass necause of SWT ; SWT also contains it
			} //end if
			//--
			if(
				((int)strlen((string)$y_hashpass) != (int)SmartHashCrypto::PASSWORD_HASH_LENGTH) // {{{SYNC-AUTHADM-PASS-LENGTH}}}
				OR
				(SmartHashCrypto::validatepasshashformat((string)$y_hashpass) !== true) // {{{SYNC-AUTH-HASHPASS-FORMAT}}}
			) {
				Smart::log_warning(__METHOD__.' # Invalid [A] Area Pass Hash ...');
				return false; // the length or pass hash format is invalid
			} //end if
			//--
		} //end if else
		//--
		$y_user_email = (string) trim((string)$y_user_email);
		$y_user_fullname = (string) trim((string)$y_user_fullname);
		//--
		$y_user_privileges_list = (array) self::safe_arr_privileges_or_restrictions($y_user_privileges_list, true); // DO NOT CAST ; $y_user_privileges_list can be mixed value (array or string)
		$y_user_privileges_list = (string) Smart::array_to_list((array)$y_user_privileges_list);
		//--
		$y_user_restrictions_list = (array) self::safe_arr_privileges_or_restrictions($y_user_restrictions_list, true); // DO NOT CAST ; $y_user_restrictions_list can be mixed value (array or string)
		$y_user_restrictions_list = (string) Smart::array_to_list((array)$y_user_restrictions_list);
		//--
		$y_user_quota = Smart::format_number_int($y_user_quota); // can be also negative
		if((int)$y_user_quota < -1) {
			$y_user_quota = 0; // fix
		} //end if
		//--
		$the_key = '#'.Smart::random_number(100000000000,999999999999).'#'; // must be at least 7 bytes, have 14 bytes
		//--
		$the_pass = (string) SmartCipherCrypto::encrypt((string)$y_hashpass, (string)$the_key, 'hash/sha3-224');
		//--
		$the_fa2secret = (string) trim((string)($y_keys['fa2sec'] ?? null));
		if((string)$the_fa2secret != '') {
			$the_fa2secret = (string) SmartCipherCrypto::encrypt((string)$the_fa2secret, (string)$the_key, 'hash/sha3-256');
			if((string)trim((string)$the_fa2secret) == '') { // be sure is really empty
				$the_fa2secret = '';
			} //end if else
		} //end if
		//--
		$the_securitykey = (string) trim((string)($y_keys['seckey'] ?? null));
		if((string)$the_securitykey != '') {
			$the_securitykey = (string) SmartCipherCrypto::bf_encrypt((string)$the_securitykey, (string)$the_key);
			if((string)trim((string)$the_securitykey) == '') { // be sure is really empty
				$the_securitykey = '';
			} //end if else
		} //end if
		//--
		$the_privkey = (string) trim((string)($y_keys['privkey'] ?? null));
		if((string)$the_privkey != '') {
			$the_privkey = (string) SmartCipherCrypto::tf_encrypt((string)$the_privkey, (string)$the_key);
			if((string)trim((string)$the_privkey) == '') { // be sure is really empty
				$the_privkey = '';
			} //end if else
		} //end if
		$the_pubkey = (string) trim((string)($y_keys['pubkey'] ?? null));
		//--
		if(Smart::array_type_test($y_user_metadata) != 2) { // requires an associative array
			$y_user_metadata = []; // reset, must be associative
		} //end if
		$y_user_metadata = Smart::json_decode((string)Smart::json_encode((array)$y_user_metadata, false, true, false, 7), true, 7); // {{{SYNC-AUTH-METADATA-MAX-LEVELS}}} ; SAFETY: ensure it does not contain objects ; avoid store any objects here ; max 7 levels only, and so much is allowed just for settings ; ex: settings['a']['b']['c']
		if(!is_array($y_user_metadata)) {
			$y_user_metadata = []; // reset, must be array ; maybe it was not or had more than 7 levels ...
		} //end if
		//--
		if(Smart::array_type_test($y_workspaces) != 2) { // requires an associative array
			$y_workspaces = []; // reset, must be associative
		} //end if
		$y_workspaces = Smart::json_decode((string)Smart::json_encode((array)$y_workspaces, false, true, false, 1), true, 1); // {{{SYNC-AUTH-WORKSPACES-MAX-LEVELS}}} ; SAFETY: ensure it does not contain objects ; avoid store any objects here ; max 1 level only ; ex: workspace['a']
		if(!is_array($y_workspaces)) {
			$y_workspaces = []; // reset, must be array ; maybe it was not or had more than 1 level ...
		} //end if
		//--
		if((string)trim((string)$y_user_id) == '') { // this is mandatory, redundant check
			return false;
		} //end if
		//--
		self::$AuthData['AUTH-METHOD'] 			= (string) $y_method;
		self::$AuthData['AUTH-REALM'] 			= (string) $y_realm;
		self::$AuthData['AUTH-CLUSTER-ID'] 		= (string) $y_cluster;
		self::$AuthData['AUTH-ID'] 				= (string) $y_user_id; 		// auth id ; unique ; for the backend this must be always = AUTH-USERNAME ; on frontend (custom development) it can be set as: AUTH-USERNAME or USER-EMAIL depending on needs
		self::$AuthData['AUTH-USERNAME'] 		= (string) $y_user_name; 	// the auth username ; unique
		self::$AuthData['AUTH-PASSHASH'] 		= (string) $the_pass; 		// the hash of the plain pass
		self::$AuthData['AUTH-PASSALGO'] 		= (int)    $y_algo_pass;
		self::$AuthData['USER-EMAIL'] 			= (string) $y_user_email;
		self::$AuthData['USER-FULL-NAME'] 		= (string) $y_user_fullname;
		self::$AuthData['USER-PRIVILEGES'] 		= (string) $y_user_privileges_list;
		self::$AuthData['USER-RESTRICTIONS'] 	= (string) $y_user_restrictions_list;
		self::$AuthData['USER-2FA-SECRET'] 		= (string) $the_fa2secret;
		self::$AuthData['USER-SECKEY'] 			= (string) $the_securitykey;
		self::$AuthData['USER-PRIVKEY'] 		= (string) $the_privkey;
		self::$AuthData['USER-PUBKEY'] 			= (string) $the_pubkey;
		self::$AuthData['USER-QUOTA'] 			= (int)    $y_user_quota;
		self::$AuthData['USER-METADATA'] 		= (array)  $y_user_metadata;
		self::$AuthData['USER-WORKSPACES'] 		= (array)  $y_workspaces;
		self::$AuthData['SESS-RAND-KEY'] 		= (string) $the_key;
		//--
		return true;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Check the (in-memory) Auth Login Data if the current user is logged-in
	 *
	 * @return 	BOOLEAN		:: TRUE if current user is Logged-in, FALSE if not
	 */
	public static function is_authenticated() : bool {
		//--
		$logged_in = false;
		//--
		if((int)Smart::array_size(self::$AuthData) > 0) {
			if(array_key_exists('AUTH-ID', self::$AuthData)) {
				if((string)trim((string)self::$AuthData['AUTH-ID']) != '') {
					if(array_key_exists('AUTH-USERNAME', self::$AuthData)) {
						if((string)trim((string)self::$AuthData['AUTH-USERNAME']) != '') {
							if(array_key_exists('AUTH-PASSHASH', self::$AuthData)) {
								if((string)trim((string)self::$AuthData['AUTH-PASSHASH']) != '') {
									if(array_key_exists('AUTH-PASSALGO', self::$AuthData)) {
										if(((int)self::$AuthData['AUTH-PASSALGO'] >= 1) && ((int)self::$AuthData['AUTH-PASSALGO'] <= 255)) {
											$logged_in = true;
										} //end if
									} //end if
								} //end if
							} //end if
						} //end if
					} //end if
				} //end if
			} //end if
		} //end if
		//--
		return (bool) $logged_in;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the (in-memory) Auth Auth Data
	 *
	 * @return 	ARRAY		:: a complete array containing all the meta-data of the current auth user
	 */
	public static function get_auth_data(bool $y_skip_sensitive=false) : array {
		//--
		return [
			'status:auth:ok' 		=> (bool)   self::is_authenticated(),
			'auth:area' 			=> (string) self::get_auth_area(),
			'auth:method' 			=> (string) self::get_auth_method(),
			'auth:realm' 			=> (string) self::get_auth_realm(),
			'auth:cluster:id' 		=> (string) self::get_auth_cluster_id(),
			'auth:id' 				=> (string) self::get_auth_id(),
			'auth:username' 		=> (string) self::get_auth_username(),
			'auth:passhash' 		=> (string) ((self::$AuthData['AUTH-PASSHASH'] ?? null) ? ($y_skip_sensitive ? '********[Sensitive:Protected]********' : self::get_auth_passhash()) : ''),
			'auth:passalgo' 		=> (int)    self::get_auth_passalgo(),
			'auth:passalgo:name' 	=> (string) self::get_auth_passalgo_name(),
			'user:email' 			=> (string) self::get_user_email(),
			'user:full-name' 		=> (string) self::get_user_fullname(),
			'user:privileges' 		=> (string) self::get_user_privileges(),
			'user:arr-privileges' 	=> (array)  self::get_user_arr_privileges(),
			'user:restrictions' 	=> (string) self::get_user_restrictions(),
			'user:arr-restrictions' => (array)  self::get_user_arr_restrictions(),
			'user:fa2secret' 		=> (string) ((self::$AuthData['USER-2FA-SECRET'] ?? null)  ? ($y_skip_sensitive ? '........[Sensitive:Protected]........' : self::get_user_fa2secret()) : ''),
			'user:seckey' 			=> (string) ((self::$AuthData['USER-SECKEY'] ?? null)  ? ($y_skip_sensitive ? '........[Sensitive:Protected]........' : self::get_user_seckey()) : ''),
			'user:privkey' 			=> (string) ((self::$AuthData['USER-PRIVKEY'] ?? null) ? ($y_skip_sensitive ? '........[Sensitive:Protected]........' : self::get_user_privkey()) : ''),
			'user:pubkey' 			=> (string) self::get_user_pubkey(),
			'user:quota' 			=> (int)    self::get_user_quota(),
			'user:metadata' 		=> (array)  self::get_user_metadata(),
			'user:workspaces' 		=> (array)  self::get_user_workspaces(),
			'user:path:prefix' 		=> (string) self::get_user_prefixed_path_by_area_and_auth_id(),
		];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the auth area method from (in-memory) Auth Login Data
	 *
	 * @return 	STRING		:: The user auth area: [IDX] or [ADM/TSK]
	 */
	public static function get_auth_area() : string {
		//--
		$area = '[IDX]';
		if(SmartEnvironment::isAdminArea()) {
			$area = '[ADM/TSK]';
		} //end if
		//--
		return (string) $area;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the auth user login method from (in-memory) Auth Login Data
	 *
	 * @return 	STRING		:: The user login method
	 */
	public static function get_auth_method() : string {
		//--
		return (string) (self::$AuthData['AUTH-METHOD'] ?? null);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the auth realm of the current user stored in the (in-memory) Auth Login Data
	 *
	 * @return 	STRING		:: returns the current user auth realm or 'DEFAULT' if not set or empty
	 */
	public static function get_auth_realm() : string {
		//--
		$login_realm = (string) strtoupper((string)trim((string)(self::$AuthData['AUTH-REALM'] ?? null)));
		if((string)$login_realm == '') {
			$login_realm = 'DEFAULT'; // ensure is non-empty
		} //end if
		//--
		return (string) $login_realm;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the current user auth cluster ID from the (in-memory) Auth Login Data
	 *
	 * @return 	STRING		:: returns the current user auth cluster ID or an empty string
	 */
	public static function get_auth_cluster_id() : string {
		//--
		return (string) trim((string)(self::$AuthData['AUTH-CLUSTER-ID'] ?? null));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the current user auth ID from the (in-memory) Auth Login Data
	 *
	 * @return 	STRING		:: if current user is Logged-in will get the user (login) ID which is mandatory, else an empty string
	 */
	public static function get_auth_id() : string {
		//--
		return (string) trim((string)(self::$AuthData['AUTH-ID'] ?? null));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the current user auth username from the (in-memory) Auth Login Data
	 *
	 * @return 	STRING		:: returns the user login username or an empty string if not set
	 */
	public static function get_auth_username() : string {
		//--
		return (string) trim((string)(self::$AuthData['AUTH-USERNAME'] ?? null));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the auth (safe) stored password hash from (in-memory)
	 *
	 * @return 	STRING		:: The plain password if was set or empty string
	 */
	public static function get_auth_passhash() : string {
		//--
		if((!array_key_exists('AUTH-PASSHASH', self::$AuthData)) OR (!array_key_exists('SESS-RAND-KEY', self::$AuthData))) {
			return ''; // no pass or no key
		} elseif((string)trim((string)self::$AuthData['AUTH-PASSHASH']) == '') {
			return ''; // empty pass
		} //end if else
		//--
		return (string) SmartCipherCrypto::decrypt((string)self::$AuthData['AUTH-PASSHASH'], (string)self::$AuthData['SESS-RAND-KEY'], 'hash/sha3-224');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the pass algo
	 *
	 * @return 	INT		:: The pass algo or -1 or -2 if invalid or does not exists
	 */
	public static function get_auth_passalgo() : int {
		//--
		if(!array_key_exists('AUTH-PASSALGO', self::$AuthData)) {
			return -1;
		} //end if
		//--
		if(!is_int(self::$AuthData['AUTH-PASSALGO'])) {
			return -2;
		} //end if
		//--
		return (int) self::$AuthData['AUTH-PASSALGO'];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the pass algo name
	 *
	 * @return 	STRING		:: The pass algo name or Unknown
	 */
	public static function get_auth_passalgo_name() : string {
		//--
		$algo = (int) self::get_auth_passalgo();
		//--
		$name = '?';
		switch((int)$algo) { // {{{SYNC-AUTH-USERS-ALLOWED-ALGOS}}}
			case self::ALGO_PASS_NONE:
				$name = 'None';
				break;
			case self::ALGO_PASS_PLAIN:
				$name = 'Plain';
				break;
			case self::ALGO_PASS_SMART_SAFE_SF_PASS:
				$name = 'SafePass.Smart';
				break;
			case self::ALGO_PASS_SMART_SAFE_ARGON_PASS:
				$name = 'SafePass.Smart.Argon';
				break;
			case self::ALGO_PASS_SMART_SAFE_BCRYPT:
				$name = 'BCrypt';
				break;
			case self::ALGO_PASS_SMART_SAFE_OPQ_TOKEN:
				$name = 'Token.Opaque';
				break;
			case self::ALGO_PASS_SMART_SAFE_WEB_TOKEN:
				$name = 'Token.Signed';
				break;
			case self::ALGO_PASS_SMART_SAFE_SWT_TOKEN:
				$name = 'Token.SWT';
				break;
			case self::ALGO_PASS_CUSTOM_TOKEN:
				$name = 'Custom.Token';
				break;
			case self::ALGO_PASS_CUSTOM_HASH_PASS:
				$name = 'Custom.Pass.Hash';
				break;
			default:
				$name = 'Unknown';
		} //end switch
		//--
		return (string) $name;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the current user email from the (in-memory) Auth Login Data
	 *
	 * @return 	STRING		:: returns the user login email or an empty string if not set
	 */
	public static function get_user_email() : string {
		//--
		return (string) (self::$AuthData['USER-EMAIL'] ?? null);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the auth user (safe) stored 2fa-secret from (in-memory)
	 *
	 * @return 	STRING		:: The plain fa2-secret if was set and valid or empty string
	 */
	public static function get_user_fa2secret() : string {
		//--
		if((!array_key_exists('USER-2FA-SECRET', self::$AuthData)) OR (!array_key_exists('SESS-RAND-KEY', self::$AuthData))) {
			return ''; // no fa2-secret or not key
		} elseif((string)trim((string)self::$AuthData['USER-2FA-SECRET']) == '') {
			return ''; // empty fa2-secret
		} //end if else
		//--
		return (string) SmartCipherCrypto::decrypt((string)self::$AuthData['USER-2FA-SECRET'], (string)self::$AuthData['SESS-RAND-KEY'], 'hash/sha3-256');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the auth user (safe) stored security-key from (in-memory)
	 *
	 * @return 	STRING		:: The plain security-key if was set and valid or empty string
	 */
	public static function get_user_seckey() : string {
		//--
		if((!array_key_exists('USER-SECKEY', self::$AuthData)) OR (!array_key_exists('SESS-RAND-KEY', self::$AuthData))) {
			return ''; // no sec-key or not key
		} elseif((string)trim((string)self::$AuthData['USER-SECKEY']) == '') {
			return ''; // empty sec-key
		} //end if else
		//--
		return (string) SmartCipherCrypto::bf_decrypt((string)self::$AuthData['USER-SECKEY'], (string)self::$AuthData['SESS-RAND-KEY']);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the auth user (safe) stored private-key from (in-memory)
	 *
	 * @return 	STRING		:: The plain private-key if was set and valid or empty string
	 */
	public static function get_user_privkey() : string {
		//--
		if((!array_key_exists('USER-PRIVKEY', self::$AuthData)) OR (!array_key_exists('SESS-RAND-KEY', self::$AuthData))) {
			return ''; // no priv-key or not key
		} elseif((string)trim((string)self::$AuthData['USER-PRIVKEY']) == '') {
			return ''; // empty priv-key
		} //end if else
		//--
		return (string) SmartCipherCrypto::tf_decrypt((string)self::$AuthData['USER-PRIVKEY'], (string)self::$AuthData['SESS-RAND-KEY']);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the auth user (safe) stored public-key from (in-memory)
	 *
	 * @return 	STRING		:: The plain public-key if was set and valid or empty string
	 */
	public static function get_user_pubkey() : string {
		//--
		return (string) trim((string)(self::$AuthData['USER-PUBKEY'] ?? null));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the current user Full Name (First + Last Name) from the (in-memory) Auth Login Data
	 *
	 * @return 	STRING		:: returns the user login full name or an empty string if not set
	 */
	public static function get_user_fullname() : string {
		//--
		return (string) (self::$AuthData['USER-FULL-NAME'] ?? null);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the list of the current user privileges stored in the (in-memory) Auth Login Data
	 *
	 * @return 	STRING		:: returns user login privileges as list-string like: '<privilege-one>,<privilege-two>,...' or an empty string if not set
	 */
	public static function get_user_privileges() : string {
		//--
		return (string) (self::$AuthData['USER-PRIVILEGES'] ?? null);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the list as Array of the current user privileges stored in the (in-memory) Auth Login Data
	 *
	 * @return 	ARRAY		:: returns user login privileges as array like: [ '<privilege-one>', '<privilege-two>', ... ] or an empty array if not set
	 */
	public static function get_user_arr_privileges() : array {
		//--
		return (array) Smart::list_to_array((string)self::get_user_privileges());
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the list of the current user restrictions stored in the (in-memory) Auth Login Data
	 *
	 * @return 	STRING		:: returns user login restrictions as list-string like: '<restriction-one>,<restriction-two>,...' or an empty string if not set
	 */
	public static function get_user_restrictions() : string {
		//--
		return (string) (self::$AuthData['USER-RESTRICTIONS'] ?? null);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the list as Array of the current user restrictions stored in the (in-memory) Auth Login Data
	 *
	 * @return 	STRING		:: returns user login restrictions as array like: [ '<restriction-one>', '<restriction-two>', ... ] or an empty string if not set
	 */
	public static function get_user_arr_restrictions() : array {
		//--
		return (array) Smart::list_to_array((string)self::get_user_restrictions());
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Test if the current user privileges contain the tested one using the (in-memory) Auth Login Data
	 *
	 * @return 	BOOLEAN		:: TRUE if the current user have the tested privilege or FALSE if does not
	 */
	public static function test_login_privilege(?string $y_privilege_to_test, ?string $y_list_to_test=null) : bool {
		//--
		$y_privilege_to_test = (string) trim((string)strtolower((string)$y_privilege_to_test));
		//--
		if(self::validate_privilege_or_restriction_key((string)$y_privilege_to_test) !== true) {
			return false;
		} //end if
		//--
		if($y_list_to_test !== null) {
			$y_list_to_test = (string) strtolower((string)trim((string)$y_list_to_test));
		} else {
			$y_list_to_test = (string) (self::$AuthData['USER-PRIVILEGES'] ?? null);
		} //end if
		//--
		$have_this_privilege = false;
		//--
		if((string)$y_list_to_test != '') {
			if(stripos((string)$y_list_to_test, '<'.$y_privilege_to_test.'>') !== false) {
				$have_this_privilege = true;
			} //end if
		} //end if
		//--
		return (bool) $have_this_privilege;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Test if the current user restrictions contain the tested one using the (in-memory) Auth Login Data
	 *
	 * @return 	BOOLEAN		:: TRUE if the current user have the tested restriction or FALSE if does not
	 */
	public static function test_login_restriction(?string $y_restriction_to_test, ?string $y_list_to_test=null) : bool {
		//--
		$y_restriction_to_test = (string) trim((string)strtolower((string)$y_restriction_to_test));
		//--
		if(self::validate_privilege_or_restriction_key((string)$y_restriction_to_test) !== true) {
			return false;
		} //end if
		//--
		if($y_list_to_test !== null) {
			$y_list_to_test = (string) strtolower((string)trim((string)$y_list_to_test));
		} else {
			$y_list_to_test = (string) (self::$AuthData['USER-RESTRICTIONS'] ?? null);
		} //end if
		//--
		$have_this_restriction = false;
		//--
		if((string)$y_list_to_test != '') {
			if(stripos((string)$y_list_to_test, '<'.$y_restriction_to_test.'>') !== false) {
				$have_this_restriction = true;
			} //end if
		} //end if
		//--
		return (bool) $have_this_restriction;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Test if a Privilege/Restriction key is valid and contain only allowed chars
	 *
	 * @return 	BOOLEAN		:: TRUE if the current user have the tested Privilege/Restriction or FALSE if does not
	 */
	public static function validate_privilege_or_restriction_key(?string $y_key_to_validate) : bool {
		//--
		if((string)trim((string)$y_key_to_validate) == '') {
			return false; // empty
		} //end if
		//-- a valid privilege key can have 2..22 characters and can contain only: `a-z`, `0-9`, `:` and `-` ; must start with `a-z` only ; not end with `:` or `-`
		if(
			((int)strlen((string)$y_key_to_validate) < 2)
			OR
			((int)strlen((string)$y_key_to_validate) > 22)
			OR
			((bool)preg_match((string)self::REGEX_VALID_PRIV_KEY, (string)$y_key_to_validate) !== true)
			OR
			(strpos((string)$y_key_to_validate, '--') !== false) // cannot contain 2 or more successive dashes
			OR
			(strpos((string)$y_key_to_validate, '::') !== false) // cannot contain 2 or more successive colons
		) {
			if(SmartEnvironment::ifDebug()) {
				Smart::log_notice(__METHOD__.' # Invalid Privilege Key: `'.$y_key_to_validate.'`'); // when users are creating tokens this may output too much if they use invalid privileges ; suppress this output if not debug
			} //end if
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the current user quota stored in the (in-memory) Auth Login Data
	 *
	 * @return 	INTEGER		:: returns the user (storage) quota
	 */
	public static function get_user_quota() : int {
		//--
		$login_quota = 0;
		if(array_key_exists('USER-QUOTA', self::$AuthData)) {
			if((int)self::$AuthData['USER-QUOTA'] > 0) {
				$login_quota = (int) self::$AuthData['USER-QUOTA'];
			} elseif((int)self::$AuthData['USER-QUOTA'] < 0) {
				$login_quota = -1;
			} //end if
		} //end if
		//--
		return (int) $login_quota;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the current user metadata stored in the (in-memory) Auth Login Data
	 *
	 * @return 	ARRAY		:: returns an array with all current user metadata
	 */
	public static function get_user_metadata() : array {
		//--
		return (array) (self::$AuthData['USER-METADATA'] ?? null);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the current user workspaces stored in the (in-memory) Auth Login Data
	 *
	 * @return 	ARRAY		:: returns an array with all current user workspaces
	 */
	public static function get_user_workspaces() : array {
		//--
		return (array) (self::$AuthData['USER-WORKSPACES'] ?? null);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the current user path prefix by area and auth user ID
	 * works just for already authenticated user
	 *
	 * @return 	STRING		:: empty string `` on error or unauthenticated ; or path, ex: `idx/ab/abc7.e8` | `adm/d7/8xyz.w0`
	 */
	public static function get_user_prefixed_path_by_area_and_auth_id() : string {
		//--
		if(self::is_authenticated() !== true) {
			return '';
		} //end if
		//--
		$id = (string) trim((string)self::get_auth_id());
		if((string)$id == '') {
			return '';
		} //end if
		//--
		$area = 'idx';
		if(SmartEnvironment::isAdminArea()) {
			$area = 'adm'; // tsk/adm will share the same prefix: adm
		} //end if
		//--
		return (string) self::get_user_prefixed_path_by_area_and_account_id((string)$area, (string)$id);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the user path prefix by area and user ID
	 * works outside of authentication, for management purposes
	 *
	 * @param 	STRING 	$area 		:: Area: `idx` | `adm`
	 * @param 	STRING 	$id 		:: The user ID, must be valid as userName
	 *
	 * @return 	STRING		:: empty string `` on error ; or path, ex: `idx/ab/abc7.e8` | `adm/d7/8xyz.w0`
	 */
	public static function get_user_prefixed_path_by_area_and_account_id(string $area, string $id) : string {
		//--
		$area = (string) trim((string)$area);
		switch((string)$area) {
			case 'idx':
			case 'adm': // tsk/adm will share the same prefix: adm
				break;
			default:
				Smart::log_warning(__METHOD__.' # Invalid Area: `'.$area.'`');
				return '';
		} //end switch
		//--
		$id = (string) trim((string)$id);
		if((string)$id == '') {
			Smart::log_warning(__METHOD__.' # User ID is Empty');
			return '';
		} //end if
		if(self::validate_auth_username((string)$id) !== true) { // check reasonable must be ON because needs at least 5 characters
			Smart::log_warning(__METHOD__.' # Invalid User ID: `'.$id.'` for Area: `'.$area.'`');
			return '';
		} //end if
		//--
		if((int)strlen((string)$id) < 3) {
			Smart::log_warning(__METHOD__.' # Invalid User ID length: `'.$id.'` for Area: `'.$area.'`');
			return '';
		} //end if
		$prefix = (string) trim((string)substr((string)$id, 0, 2));
		if(((string)$prefix == '') OR ((int)strlen((string)$prefix) != 2) OR (!preg_match('/^[a-z0-9]{2}$/', (string)$prefix))) {
			Smart::log_warning(__METHOD__.' # Invalid User ID prefix: `'.$prefix.'` for ID: `'.$id.'` for Area: `'.$area.'`');
			return '';
		} //end if
		//--
		return (string) $area.'/'.$prefix.'/'.Smart::safe_username((string)$id); // {{{SYNC-AUTH-ACCOUNT-ID-PATH-2-CHARS-PREFIX}}}
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe Encrypt a private key using a password, using Twofish+Blowfish CBC
	 * The provided password have to be the same as the login password for the user is being used to avoid decryption of the key by other users
	 * This is completely safe as long as the users login passwords are supposed to be stored as ireversible hashes (by default they are ... but with custom login implementations they can be or not, depending the developer's choice)
	 *
	 * @param 	STRING 	$y_pkey 		:: The private key to be safe encrypted
	 * @param 	STRING 	$y_secret 		:: The encryption secret
	 *
	 * @return 	STRING					:: returns a string with the safe encrypted privacy-key or empty string if was empty
	 */
	public static function encrypt_sensitive_data(?string $y_pkey, ?string $y_secret) : string {
		//--
		if((string)trim((string)$y_pkey) == '') {
			return '';
		} //end if
		$y_secret = (string) $y_secret.chr(0).SmartHashCrypto::crc32b((string)chr(0).$y_secret, true).chr(0).Smart::b64_to_b64s((string)SmartHashCrypto::md5((string)chr(0).$y_secret, true)); // if this is like username and only 3 characters, ensure at least 7 ; {{{SYNC-MIN-KEY-7-ENSURE}}}
		if((int)strlen((string)trim((string)$y_secret)) < 7) { // this is the minimum length accepted
			return ''; // return empty string to avoid return it in unencrypted plain format
		} //end if
		//--
		return (string) SmartCipherCrypto::tf_encrypt((string)$y_pkey, (string)$y_secret, true); // {{{SYNC-ADM-AUTH-KEYS}}} ; Twofish+Blowfish
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Decrypt a private key using a password, using Twofish+Blowfish CBC
	 * The provided password have to be the same as the login password for the user is being used to avoid decryption of the key by other users
	 * This is completely safe as long as the users login passwords are supposed to be stored as ireversible hashes (by default they are ... but with custom login implementations they can be or not, depending the developer's choice)
	 *
	 * @param 	STRING 	$y_pkey 		:: The private key to be decrypted
	 * @param 	STRING 	$y_secret 		:: The encryption secret
	 *
	 * @return 	STRING					:: returns a string with the privacy-key (decrypted, if any, and if valid) which was supposed to be provided as encrypted
	 */
	public static function decrypt_sensitive_data(?string $y_pkey, ?string $y_secret) : string {
		//--
		if((string)trim((string)$y_pkey) == '') {
			return '';
		} //end if
		//--
		$y_secret = (string) $y_secret.chr(0).SmartHashCrypto::crc32b((string)chr(0).$y_secret, true).chr(0).Smart::b64_to_b64s((string)SmartHashCrypto::md5((string)chr(0).$y_secret, true)); // if this is like username and only 3 characters, ensure at least 7 ; {{{SYNC-MIN-KEY-7-ENSURE}}}
		if((int)strlen((string)trim((string)$y_secret)) < 7) { // this is the minimum length accepted
			return '';
		} //end if
		//--
		return (string) SmartCipherCrypto::tf_decrypt((string)$y_pkey, (string)$y_secret, false); // {{{SYNC-ADM-AUTH-KEYS}}} ; no fallback (explicit) !
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Build the associative Array of Auth Privileges or Restrictions
	 *
	 * @param 	STRING/ARRAY 	$y_list 		:: List of Privileges or Restrictions as ARRAY [ 'key-a', 'key-b', ..., 'key-n' ] or STRING '<key-a>, <key-b>, ..., <key-n>'
	 * @param 	BOOL 			$nonassociative :: Default is FALSE ; if TRUE will return non-associative (simple) ; if FALSE (default) will return associative
	 *
	 * @return 	ARRAY							:: returns the associative/non-associative array of auth Privileges or Restrictions as [ 'key-a' => 'Key A', 'key-b' => 'Key B', ..., 'key-n' => 'Key N' ] or [ 'key-a', 'key-b', ..., 'key-n' ]
	 */
	public static function safe_arr_privileges_or_restrictions($y_list, bool $nonassociative=false) : array {
		//--
		if(!is_array($y_list)) {
			$y_list = (array) Smart::list_to_array((string)$y_list);
		} //end if
		if((int)Smart::array_size($y_list) <= 0) {
			return [];
		} //end if
		if((int)Smart::array_type_test($y_list) !== 1) { // expects non-associative array
			return [];
		} //end if
		//--
		$y_list = (array) $y_list;
		//--
		$out_arr = array();
		for($i=0; $i<Smart::array_size($y_list); $i++) {
			//--
			$y_list[$i] = (string) strtolower((string)trim((string)$y_list[$i]));
			if(self::validate_privilege_or_restriction_key((string)$y_list[$i]) === true) {
				$label = (string) trim((string)ucwords((string)strtr((string)$y_list[$i], [ '-' => ' ', ':' => ' :: ' ])));
				if((string)$label == '') {
					Smart::log_notice(__METHOD__.' # Invalid Privilege Key Metamorphose (empty): `'.$y_list[$i].'`');
					$label = (string) $y_list[$i];
				} //end if
				$out_arr[(string)$y_list[$i]] = (string) $label;
			} //end if
			//--
		} //end for
		//--
		if($nonassociative === true) {
			$tmp_arr = (array) array_keys((array)$out_arr);
			$out_arr = (array) Smart::array_sort((array)$tmp_arr, 'sort');
			$tmp_arr = null;
		} //end if
		//--
		return (array) $out_arr;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Validate a safe auth SWT (Smart Web Token)
	 * This token can be used for Bearer Authentication, inside Smart.Framework
	 * The password is stored as a hash and cannot be reversed
	 *
	 * If this method returns an error (message), validation of the SWT Token has failed
	 *
	 * IMPORTANT:
	 * - the IP list restriction is verified by this method and does validate if the current provided client's IP is valid and is in the allowed list provided by the token
	 * - if the Token does not provide a valid IP list, token will be not validated, an error will be returned
	 * - if the IP list is valid but the current visitor IP is not in this list, a validation error will be returned by this method
	 *
	 * NOTICE:
	 * - the Privileges list provided by this method (as the list of privileges available in the token) needs to be INTERSECTED (array intersect, to avoid privilege escalations) with the account's privileges to result the list of account allowed privileges !
	 * - and empty Privileges list should not be allowed !
	 *
	 * This is a hidden functionality that is not intended to be used directly ...
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 	$token 		:: The SWT Token String
	 * @param 	STRING 	$client_ip 	:: The current client IP Address to be compared and validated with the Token (if token contain an IP Bind) ; must be the current visitor's IP IPv4 or IPv6 for the token is validated for
	 *
	 * @return 	ARRAY				:: array of strings as: [ 'error' => 'error if any or empty', 'user-name' => '...', 'pass-hash' => '...', 'restr-ip' => [], 'restr-priv' => [], 'json-arr' => [...] ]
	 */
	public static function swt_token_validate(?string $token, ?string $client_ip) : array {
		//--
		$valid = [
			'error' 		=> '?', 	// error or empty
			'user-name' 	=> '',  	// auth user name
			'pass-hash' 	=> '',  	// auth pass hash (not reversible)
			'restr-ip' 		=> [], 		// allowed IPs
			'restr-priv' 	=> [],  	// restricted privileges array
			'json-arr' 		=> [],  	// the token json
		];
		//--
		if(!defined('SMART_SOFTWARE_NAMESPACE')) {
			$valid['error'] = 'Auth NameSpace is Undefined';
			return (array) $valid;
		} //end if
		if((string)trim((string)SMART_SOFTWARE_NAMESPACE) == '') {
			$valid['error'] = 'Auth NameSpace is Empty';
			return (array) $valid;
		} //end if
		//--
		$realm = 'I';
		if(SmartEnvironment::isAdminArea()) {
			$realm = 'A'; // {{{SYNC-SWT-REALMS}}}
		} //end if
		//--
		$client_ip = (string) trim((string)Smart::ip_addr_compress((string)$client_ip)); // {{{SYNC-IPV6-STORE-SHORTEST-POSSIBLE}}} ; IPV6 addresses may vary .. find a standard form, ex: shortest
		if((string)$client_ip == '') { // the above method outputs an empty string on error
			$valid['error'] = 'Empty or Invalid Client IP Address';
			return (array) $valid;
		} //end if
		//--
		$token = (string) trim((string)$token);
		if((string)$token == '') {
			$valid['error'] = 'Token is Empty';
			return (array) $valid;
		} //end if
		$len_token = (int) strlen((string)$token);
		if(((int)$len_token < 256) OR ((int)$len_token > 2048)) { // {{{SYNC-SWT-AUTH-TOKEN-ALLOWED-LENGTH}}} ; token length is ~ 450 .. 750 characters, but be more flexible, just in case ...
			$valid['error'] = 'Token have an Invalid Length';
			return (array) $valid;
		} //end if
		//--
		if(strpos((string)$token, (string)self::SWT_VERSION_PREFIX.';') !== 0) {
			$valid['error'] = 'Token Prefix is Invalid';
			return (array) $valid;
		} //end if
		$len_suffix = (int) ((int)strlen((string)self::SWT_VERSION_SUFFIX) + 1);
		if((string)substr($token, -1 * (int)$len_suffix, (int)$len_suffix) !== (string)';'.self::SWT_VERSION_SUFFIX) {
			$valid['error'] = 'Token Suffix is Invalid';
			return (array) $valid;
		} //end if
		//--
		$tokarr = (array) explode(';', (string)$token, 4);
		if((string)trim((string)($tokarr[0] ?? null)) !== (string)self::SWT_VERSION_PREFIX) {
			$valid['error'] = 'Token Prefix Part is Invalid';
			return (array) $valid;
		} //end if
		if((string)trim((string)($tokarr[3] ?? null)) !== (string)self::SWT_VERSION_SUFFIX) {
			$valid['error'] = 'Token Suffix Part is Invalid';
			return (array) $valid;
		} //end if
		$token = (string) trim((string)($tokarr[1] ?? null));
		$tksum = (string) trim((string)($tokarr[2] ?? null));
		$tokarr = null;
		if((string)$token == '') {
			$valid['error'] = 'Token Core Part is Invalid';
			return (array) $valid;
		} //end if
		if((string)SmartHashCrypto::checksum((string)self::SWT_VERSION_PREFIX.';'.$token.';'.self::SWT_VERSION_SUFFIX, '') !== (string)$tksum) {
			$valid['error'] = 'Token Core Checksum is Invalid';
			return (array) $valid;
		} //end if
		$tksum = '';
		$json = (string) Smart::b64s_dec((string)$token, true); // B64 STRICT
		$token = '';
		if((string)trim((string)$json) == '') {
			$valid['error'] = 'Base64S decoding Failed';
			return (array) $valid;
		} //end if
		//--
		$arr = Smart::json_decode((string)$json);
		$json = '';
		if(!is_array($arr)) {
			$valid['error'] = 'JSON decoding Failed';
			return (array) $valid;
		} //end if
		if((int)Smart::array_size($arr) != 8) {
			$valid['error'] = 'JSON object size is Invalid';
			return (array) $valid;
		} //end if
		if((int)Smart::array_type_test($arr) != 2) { // requires an associative array
			$valid['error'] = 'JSON object type is Invalid';
			return (array) $valid;
		} //end if
		//--
		$keys = [ '#', 'n', 'r', 'd', 'a', 'p', 'i', 'h' ];
		$err = '';
		for($i=0; $i<Smart::array_size($keys); $i++) {
			if(
				(array_key_exists((string)$keys[$i], (array)$arr) !== true)
				OR
				!is_string($arr[(string)$keys[$i]])
				OR
				((string)trim((string)$arr[(string)$keys[$i]]) == '')
			) {
				$err = 'key `'.$keys[$i].'` is empty or invalid';
				break;
			} //end if
		} //end for
		if((string)$err != '') {
			$valid['error'] = 'JSON object validation: '.$err;
			return (array) $valid;
		} //end if
		//--
		if((string)$arr['#'] !== (string)self::SWT_VERSION_SIGNATURE) {
			$valid['error'] = 'JSON object have an Invalid Version Signature';
			return (array) $valid;
		} //end if
		//--
		$arr['n'] = (string) trim((string)$arr['n']);
		if((string)$arr['n'] != '') {
			$arr['n'] = (string) trim((string)Smart::base_to_hex_convert((string)$arr['n'], 9*2*2));
		} //end if
		if((string)$arr['n'] != '') {
			$arr['n'] = (string) trim((string)Smart::safe_hex_2_bin((string)$arr['n'], false, false)); // do not ignore case ; do not log notices, they are logged in base convert
		} //end if
		if((string)$arr['n'] != '') {
			$arr['n'] = (string) SmartUnicode::utf8_to_iso((string)$arr['n']); // safety
		} //end if
		if((string)$arr['n'] !== (string)SMART_SOFTWARE_NAMESPACE) {
			$valid['error'] = 'JSON object have an Invalid NameSpace';
			return (array) $valid;
		} //end if
		//--
		$arr['r'] = (string) trim((string)$arr['r']);
		if((string)$arr['r'] != '') {
			$arr['r'] = (string) trim((string)Smart::base_to_hex_convert((string)$arr['r'], 29*2));
		} //end if
		if((string)$arr['r'] != '') {
			$arr['r'] = (string) trim((string)Smart::safe_hex_2_bin((string)$arr['r'], false, false)); // do not ignore case ; do not log notices, they are logged in base convert
		} //end if
		if((string)$arr['r'] != '') {
			$arr['r'] = (string) SmartUnicode::utf8_to_iso((string)$arr['r']); // safety
		} //end if
		if((string)trim((string)$arr['r']) !== (string)$realm) {
			$valid['error'] = 'JSON object have an Invalid Realm';
			return (array) $valid;
		} //end if
		//--
		$arr['d'] = (string) trim((string)$arr['d']);
		if((string)$arr['d'] != '') {
			$arr['d'] = (string) trim((string)Smart::base_to_hex_convert((string)$arr['d'], 8*2*2));
		} //end if
		if((string)$arr['d'] != '') {
			$arr['d'] = (string) trim((string)Smart::safe_hex_2_bin((string)$arr['d'], false, false)); // do not ignore case ; do not log notices, they are logged in base convert
		} //end if
		if((string)$arr['d'] != '') {
			$arr['d'] = (string) SmartUnicode::utf8_to_iso((string)$arr['d']); // safety
		} //end if
		if(preg_match((string)SmartValidator::regex_stringvalidation_expression('date-time-tzofs'), (string)$arr['d'])) { // validate date by regex
			$dtnow = (string) gmdate('Y-m-d H:i:s').' +0000';
			$dtswt = (string) gmdate('Y-m-d H:i:s', (int)strtotime((string)$arr['d'])).' +0000'; // be sure is a date, and UTC formatted with +0000
			$dtmax = (string) gmdate('Y-m-d H:i:s', (int)strtotime('+ 1 day')).' +0000';
			if((string)$dtnow < (string)$dtswt) { // current date is higher than token date
				if((string)$dtswt > (string)$dtmax) {
					$valid['error'] = 'JSON object Date is Higher than Expected: `'.$arr['d'].'`';
					return (array) $valid;
				} else {
					// OK
					//Smart::log_notice(__METHOD__.'# OK: `'.$dtnow.'` < `'.$dtswt.'`');
				} //end if else
			} else {
				$valid['error'] = 'JSON object Date is Expired: `'.$arr['d'].'`';
				return (array) $valid;
			} //end if
		} else {
			$valid['error'] = 'JSON object have an Invalid Expiration Date Format: `'.$arr['d'].'`';
			return (array) $valid;
		} //end if else
		//--
		$auth = (array) explode("\n", (string)trim((string)$arr['a']), 3);
		$username = (string) trim((string)($auth[0] ?? null));
		$hashpass = (string) trim((string)($auth[1] ?? null));
		//--
		if((string)$username != '') {
			$username = (string) trim((string)Smart::base_to_hex_convert((string)$username, 17*5));
		} //end if
		if((string)$username != '') {
			$username = (string) trim((string)Smart::safe_hex_2_bin((string)$username, false, false)); // do not ignore case ; do not log notices, they are logged in base convert
		} //end if
		if((string)$username != '') {
			$username = (string) SmartUnicode::utf8_to_iso((string)$username); // safety
		} //end if
		//--
		if((string)$hashpass != '') {
			$hashpass = (string) trim((string)Smart::b64_dec((string)$hashpass, true)); // STRICT
		} //end if
		if((string)$hashpass != '') {
			$hashpass = (string) SmartUnicode::utf8_to_iso((string)$hashpass); // safety
		} //end if
		if((string)$hashpass != '') {
			$hashpass = (string) trim((string)Smart::base_to_hex_convert((string)$hashpass, 23*2*2));
		} //end if
		if((string)$hashpass != '') {
			$hashpass = (string) trim((string)Smart::safe_hex_2_bin((string)$hashpass, false, false)); // do not ignore case ; do not log notices, they are logged in base convert
		} //end if
		if((string)$hashpass != '') {
			$hashpass = (string) SmartUnicode::utf8_to_iso((string)$hashpass); // safety
		} //end if
		//-- pass hash security ; {{{SYNC-SWT-PASS-HASH-SECURITY-CHECK}}} ; to avoid to include by mistake a plain password in the SWT Token, it only allows 2 type of Password Hash: SmartHashCrypto::password() for admin/task area ; SmartAuth::password_hash_create() for index area
		if((string)trim((string)$hashpass) == '') {
			$valid['error'] = 'Password Hash is Empty';
			return (array) $valid;
		} //end if else
		if(SmartEnvironment::isAdminArea()) { // [A] ; expects a password hash provided by SmartHashCrypto::password()
			if(
				((int)strlen((string)$hashpass) != (int)SmartHashCrypto::PASSWORD_HASH_LENGTH) // {{{SYNC-AUTHADM-PASS-LENGTH}}}
				OR
				(SmartHashCrypto::validatepasshashformat((string)$hashpass) !== true) // {{{SYNC-AUTH-HASHPASS-FORMAT}}}
			) {
				$valid['error'] = 'Invalid Password Hash Length or Format [A]';
				return (array) $valid;
			} //end if else
		} else { // [I] ; expects a password hash provided by SmartAuth::password_hash_create()
			if(
				(
					((int)strlen((string)$hashpass) != (int)self::PASSWORD_BHASH_LENGTH) // {{{SYNC-PASS-HASH-AUTH-LEN}}}
					OR
					(self::password_hash_validate_format((string)$hashpass) !== true) // {{{SYNC-PASS-HASH-AUTH-FORMAT}}}
				)
				AND
				(
					((int)strlen((string)$hashpass) != (int)SmartHashCrypto::PASSWORD_HASH_LENGTH) // {{{SYNC-AUTHADM-PASS-LENGTH}}}
					OR
					(SmartHashCrypto::validatepasshashformat((string)$hashpass) !== true) // {{{SYNC-AUTH-HASHPASS-FORMAT}}}
				)
			) {
				$valid['error'] = 'Invalid Password Hash Length or Format [I]';
				return (array) $valid;
			} //end if
		} //end if else
		//--
		if(self::validate_auth_username((string)$username, false) !== true) {
			$valid['error'] = 'Invalid Username: `'.$username.'`';
			return (array) $valid;
		} //end if
		//--
		$arr['p'] = (string) trim((string)$arr['p']);
		if((string)$arr['p'] != '') {
			$arr['p'] = (string) trim((string)Smart::base_to_hex_convert((string)$arr['p'], 23*4));
		} //end if
		if((string)$arr['p'] != '') {
			$arr['p'] = (string) trim((string)Smart::safe_hex_2_bin((string)$arr['p'], false, false)); // do not ignore case ; do not log notices, they are logged in base convert
		} //end if
		if((string)$arr['p'] != '') {
			$arr['p'] = (string) SmartUnicode::utf8_to_iso((string)$arr['p']); // safety
		} //end if
		//--
		if((string)$arr['p'] == '*') {
			$arr_privileges = ['*'];
		} else {
			$arr_privileges = (array) self::safe_arr_privileges_or_restrictions((string)$arr['p'], true);
		} //end if
		//--
		if((int)Smart::array_size($arr_privileges) <= 0) {
			$valid['error'] = 'JSON object have an Invalid Privileges List: `'.$arr['p'].'`';
			return (array) $valid;
		} //end if
		$valid['restr-priv'] = (array) $arr_privileges;
		$arr_privileges = null;
		//--
		$arr['i'] = (string) trim((string)$arr['i']);
		if((string)$arr['i'] != '') {
			$arr['i'] = (string) trim((string)Smart::b64s_dec((string)$arr['i'], true)); // B64 STRICT
		} //end if
		if((string)$arr['i'] != '') {
			$arr['i'] = (string) SmartUnicode::utf8_to_iso((string)$arr['i']); // safety
		} //end if
		if((string)$arr['i'] === '*') {
			//--
			$valid['restr-ip'][] = '*'; // if wildcard is present in the array it means allow any IP ! much better than dealing with mix array/string
			//--
		} else {
			//--
			$arr_ips = [];
			$arr_tmp_ips = (array) Smart::list_to_array((string)$arr['i']);
			foreach($arr_tmp_ips as $key => $val) {
				if((string)trim((string)SmartValidator::validate_filter_ip_address((string)$val)) != '') { // if valid IP address
					$val = (string) trim((string)Smart::ip_addr_compress((string)$val)); // {{{SYNC-IPV6-STORE-SHORTEST-POSSIBLE}}} ; IPV6 addresses may vary .. find a standard form, ex: shortest
					if((string)$val != '') {
						$arr_ips[] = (string) $val;
					} //end if
				} //end if
			} //end foreach
			$arr_tmp_ips = null;
			if((int)Smart::array_size($arr_ips) <= 0) {
				$valid['error'] = 'JSON object have an Invalid IP List: `'.$arr['i'].'`'; // empty or invalid IPs list is not supported !
				return (array) $valid;
			} //end if
			if(
				(!in_array((string)$client_ip, (array)$arr_ips))
				OR // double check: in array and in list
				(stripos((string)$arr['i'], '<'.$client_ip.'>') === false)
			) { // {{{SYNC-IPV6-STORE-SHORTEST-POSSIBLE}}} ; IPV6 addresses may vary .. find a standard form, ex: shortest
				$valid['error'] = 'JSON object IP List does not contain the Client IP ['.$client_ip.']: `'.$arr['i'].'`';
				return (array) $valid;
			} //end if
			$valid['restr-ip'] = (array) $arr_ips;
			$arr_ips = null;
			//--
		} //end if
		//--
		$hash = (string) SmartHashCrypto::checksum(
			(string) self::SWT_VERSION_SIGNATURE."\n".SMART_SOFTWARE_NAMESPACE."\n".$arr['r']."\n".$arr['d']."\n".$username."\n".$hashpass."\n".$arr['p']."\n".$arr['i'],
			'' // use the default key
		);
		if((string)$arr['h'] !== (string)$hash) {
			$valid['error'] = 'JSON object have an Invalid Checksum';
			return (array) $valid;
		} //end if
		//--
		$valid['error'] = ''; // clear
		$valid['user-name'] = (string) $username;
		$valid['pass-hash'] = (string) $hashpass;
		$valid['json-arr']  = (array)  $arr;
		return (array) $valid;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Create a safe auth SWT (Smart Web Token)
	 * This token can be used for Bearer Authentication, inside Smart.Framework
	 * The password is stored as a hash and cannot be reversed
	 *
	 * If the SWT Token failed to be created because the provided parameters are invalid, an error message and an empty json / token is returned
	 *
	 * This is a hidden functionality that is not intended to be used directly ...
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 	$realm 					:: 'A' for adm/tsk (BE) ; 'I' for idx (FE)
	 * @param 	STRING 	$auth_user_name 		:: The auth user name
	 * @param 	STRING 	$auth_hash_pass 		:: The irreversible (one-way) password hash ; NEVER try to provide here a Plain Text Password (unsupported) !! ; for the 'A' (adm/tsk) area supports a hash pass created with SmartHashCrypto::password() ; for the 'I' (FE, idx) area supports a hash created with SmartAuth::password_hash_create() ; it does not support any other type of hashes to avoid embed plain passwords by mistake in a SWT Token ! This is a mandatory security check ... because these 2 types of hashes are validated 100% as length, format, etc...
	 * @param 	INT		$expire 				:: The expiration time in seconds from now ; must be >= 1 and <= 3600*24 (24h)
	 * @param 	ARRAY 	$ip_addr_arr 			:: IP Addresses List [ip1, ip2, ...] ; if empty, will allow from any IP as wildcard `*` ; if non empty the Visitor IP Address must match an entry in this list
	 * @param 	ARRAY 	$privs_arr 				:: Privileges List [ priv1, priv2, ... ] (cannot be empty ! must have at least one entry to be validated) ; SWT Tokens Privileges restrictions bind is mandatory for security reasons ! these should be intersected with existing privileges for the target account and will result a list of privileges that are appearing in both: user's privileges and SWT token only, thus the privileges cannot be overriden !
	 *
	 * @return 	ARRAY							:: array of strings as: [ 'error' => 'error if any or empty', 'json' => '{...}', 'token' => '...' ]
	 */
	public static function swt_token_create(?string $realm, ?string $auth_user_name, ?string $auth_hash_pass, ?int $expire, ?array $ip_addr_arr, ?array $privs_arr=[]) : array {
		//--
		$swt = [
			'error' => '?', // error or empty
			'json'  => '',  // the swt json
			'token' => '',  // the swt token (b64s)
		];
		//--
		if(!defined('SMART_SOFTWARE_NAMESPACE')) {
			$swt['error'] = 'Auth Realm is Undefined';
			return (array) $swt;
		} //end if
		if((string)trim((string)SMART_SOFTWARE_NAMESPACE) == '') {
			$swt['error'] = 'Auth Realm is Empty';
			return (array) $swt;
		} //end if
		//--
		switch((string)$realm) { // // {{{SYNC-SWT-REALMS}}}
			case 'I': // idx
				break;
			case 'A': // adm / tsk ; http cli must be able to access both areas, the restriction between adm/tsk will be made by Task IP List not here, as a password or token should be the same for adm/tsk, thus also the swt token restr.
				break;
			default:
				$swt['error'] = 'Auth Realm is Invalid: `'.$realm.'`';
				return (array) $swt;
		} //end switch
		//--
		if((int)$expire <= 0) {
			$swt['error'] = 'Expiration is Invalid: `'.(int)$expire.'`';
			return (array) $swt;
		} elseif((int)$expire > (int)self::SWT_MAX_LIFETIME) {
			$swt['error'] = 'Expiration is Higher than Expected: `'.(int)$expire.'`';
			return (array) $swt;
		} //end if
		//--
		if(self::validate_auth_username((string)$auth_user_name, false) !== true) {
			$swt['error'] = 'Username is Invalid: `'.$auth_user_name.'`';
			return (array) $swt;
		} //end if
		//-- pass hash security ; {{{SYNC-SWT-PASS-HASH-SECURITY-CHECK}}} ; to avoid to include by mistake a plain password in the SWT Token, it only allows 2 type of Password Hash: SmartHashCrypto::password() for admin/task area ; SmartAuth::password_hash_create() for index area
		if((string)trim((string)$auth_hash_pass) == '') {
			$valid['error'] = 'Password Hash is Empty';
			return (array) $valid;
		} //end if else
		if((string)$realm == 'A') { // [A] ; expects a password hash provided by SmartHashCrypto::password()
			if(
				((int)strlen((string)$auth_hash_pass) != (int)SmartHashCrypto::PASSWORD_HASH_LENGTH) // {{{SYNC-AUTHADM-PASS-LENGTH}}}
				OR
				(SmartHashCrypto::validatepasshashformat((string)$auth_hash_pass) !== true) // {{{SYNC-AUTH-HASHPASS-FORMAT}}}
			) {
				$valid['error'] = 'Invalid Password Hash Length or Format [A]';
				return (array) $valid;
			} //end if else
		} else { // [I] ; expects a password hash provided by SmartAuth::password_hash_create() or SmartHashCrypto::password()
			if(
				(
					((int)strlen((string)$auth_hash_pass) != (int)self::PASSWORD_BHASH_LENGTH) // {{{SYNC-PASS-HASH-AUTH-LEN}}}
					OR
					(self::password_hash_validate_format((string)$auth_hash_pass) !== true) // {{{SYNC-PASS-HASH-AUTH-FORMAT}}}
				)
				AND
				(
					((int)strlen((string)$auth_hash_pass) != (int)SmartHashCrypto::PASSWORD_HASH_LENGTH) // {{{SYNC-AUTHADM-PASS-LENGTH}}}
					OR
					(SmartHashCrypto::validatepasshashformat((string)$auth_hash_pass) !== true) // {{{SYNC-AUTH-HASHPASS-FORMAT}}}
				)
			) {
				$valid['error'] = 'Invalid Password Hash Length or Format [I]';
				return (array) $valid;
			} //end if
		} //end if else
		//--
		$expdate = (string) gmdate('Y-m-d H:i:s', (int)((int)time() + (int)$expire)).' +0000'; // UTC
		//--
		$valid_ips = [];
		if(Smart::array_size($ip_addr_arr) > 0) {
			foreach($ip_addr_arr as $key => $val) {
				if(Smart::is_nscalar($val)) {
					$val = (string) trim((string)$val);
					if((string)$val != '') {
						if((string)trim((string)SmartValidator::validate_filter_ip_address((string)$val)) != '') { // if valid IP address
							$val = (string) trim((string)Smart::ip_addr_compress((string)$val)); // {{{SYNC-IPV6-STORE-SHORTEST-POSSIBLE}}} ; IPV6 addresses may vary .. find a standard form, ex: shortest
							if((string)$val != '') {
								$valid_ips[] = (string) $val;
							} else {
								$swt['error'] = 'IP Address List is Invalid: Contains a Wrong Value: `'.$val.'`';
								return (array) $swt;
							} //end if else
						} else {
							$swt['error'] = 'IP Address List is Invalid: Contains an Invalid Value: `'.$val.'`';
							return (array) $swt;
						} //end if else
					} else {
						$swt['error'] = 'IP Address List is Invalid: Contains an Empty Value';
						return (array) $swt;
					} //end if
				} else {
					$swt['error'] = 'IP Address List is Invalid: Contains a Non-Scalar Value';
					return (array) $swt;
				} //end if
			} //end foreach
		} //end if
		$ip_addr_list = '';
		if(Smart::array_size($valid_ips) > 0) {
			$ip_addr_list = (string) str_replace(' ', '', (string)Smart::array_to_list((array)$valid_ips));
		} //end if
		if((string)trim((string)$ip_addr_list) == '') {
		//	$ip_addr_list = '<>'; // previous behaviour: dissalow empty IP list
			$ip_addr_list = '*'; // new behaviour: re-enable empty IP list, allow from any IP, if no specific ; the time restrictions are more important !
		} //end if
		$valid_ips = null;
		//--
		$valid_privs = [];
		if(Smart::array_size($privs_arr) > 0) {
			foreach($privs_arr as $key => $val) {
				if(Smart::is_nscalar($val)) {
					$val = (string) trim((string)$val);
					if((string)$val != '') {
						if(self::validate_privilege_or_restriction_key((string)$val) === true) { // if valid privilege key name
							$valid_privs[] = (string) $val;
						} else {
							$swt['error'] = 'Privileges List is Invalid: Contains an Invalid Value: `'.$val.'`';
							return (array) $swt;
						} //end if else
					} else {
						$swt['error'] = 'Privileges List is Invalid: Contains an Empty Value';
						return (array) $swt;
					} //end if
				} else {
					$swt['error'] = 'Privileges List is Invalid: Contains a Non-Scalar Value';
					return (array) $swt;
				} //end if
			} //end foreach
		} //end if
		$privs_list = ''; // {{{SYNC-SWT-IMPLEMENT-PRIVILEGES}}}
		if(Smart::array_size($valid_privs) > 0) {
			$privs_list = (string) str_replace(' ', '', (string)Smart::array_to_list((array)$valid_privs));
		} //end if
		if((string)trim((string)$privs_list) == '') {
		//	$privs_list = '<>'; // fix: if no valid Privs list, use a non-empty string ; no error here, just in validator to be able to test invalid Privs List !
			$privs_list = '*';
		} //end if
		$valid_privs = null;
		//--
		$hash = (string) SmartHashCrypto::checksum(
			(string) self::SWT_VERSION_SIGNATURE."\n".SMART_SOFTWARE_NAMESPACE."\n".$realm."\n".$expdate."\n".$auth_user_name."\n".$auth_hash_pass."\n".$privs_list."\n".$ip_addr_list,
			'' // default (empty), will use a derivation of SMART_FRAMEWORK_SECURITY_KEY
		);
		//--
		$obfs_nspace 		= (string) Smart::base_from_hex_convert((string)bin2hex((string)SMART_SOFTWARE_NAMESPACE), 9*2*2);
		$obfs_realm 		= (string) Smart::base_from_hex_convert((string)bin2hex((string)$realm), 29*2);
		$obfs_expdt 		= (string) Smart::base_from_hex_convert((string)bin2hex((string)$expdate), 8*2*2);
		$obfs_user_name 	= (string) Smart::base_from_hex_convert((string)bin2hex((string)$auth_user_name), 17*5);
		$obfs_hash_pass 	= (string) Smart::b64_enc((string)Smart::base_from_hex_convert((string)bin2hex((string)$auth_hash_pass), 23*2*2));
		$obfs_privs_lst 	= (string) Smart::base_from_hex_convert((string)bin2hex((string)$privs_list), 23*4);
		$obfs_ip_adr_lst 	= (string) Smart::b64s_enc((string)$ip_addr_list); // b64s
		//--
		$arr = [
			'#' => (string) self::SWT_VERSION_SIGNATURE, 			// Meta: metainfo (signature)
			'n' => (string) $obfs_nspace, 							// Info: namespace (4 .. 63 chars)
			'r' => (string) $obfs_realm, 							// Info realm: idx | adm (adm is for both: adm/tsk)
			'd' => (string) $obfs_expdt, 							// Date: expiration date UTC or * (no expiration)
			'a' => (string) $obfs_user_name."\n".$obfs_hash_pass, 	// Auth: auth data: user-id \n pass-hash
			'p' => (string) $obfs_privs_lst, 						// Privileges list as: <priv-a>,<priv-b> or * for all privs
			'i' => (string) $obfs_ip_adr_lst, 						// The IP Address list as: '<ip1>,<ip2>' or <> (to bind to a specific IP list ; empty lists will not be validated)
			'h' => (string) $hash, 									// Hash: checksum hash
		];
		//--
		$json = (string) Smart::json_encode((array)$arr, false, true, false);
		if((string)trim((string)$json) == '') {
			$swt['error'] = 'JSON encoding Failed';
			return (array) $swt;
		} //end if
		//--
		$b64u = (string) Smart::b64s_enc((string)$json, false); // B64u
		if((string)trim((string)$b64u) == '') {
			$swt['error'] = 'Base64u encoding Failed';
			return (array) $swt;
		} //end if
		//--
		$cksign = (string) SmartHashCrypto::checksum(
			(string) self::SWT_VERSION_PREFIX.';'.$b64u.';'.self::SWT_VERSION_SUFFIX,
			'' // default (empty), will use a derivation of SMART_FRAMEWORK_SECURITY_KEY
		);
		//--
		$swt['error'] = ''; // clear
		$swt['json']  = (string) $json;
		$swt['token'] = (string) self::SWT_VERSION_PREFIX.';'.$b64u.';'.$cksign.';'.self::SWT_VERSION_SUFFIX;
		return (array) $swt;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Create a safe auth JWT (JSON Web Token)
	 * This token can be used for ApiKey Authentication with External SmartGo Web API
	 * No password or password hash is stored inside, JWT uses a different mechanism, and this will operate in an external environment which is not aware of user/pass combinations
	 *
	 * If the JWT Token failed to be created because the provided parameters are invalid, an error message and an empty json / token is returned
	 *
	 * It only supports non-vulnerable JWT algorithms.
	 * Security: HS512 and HS256 are vulnerable to length attack and are not appropriate to use as signature, they are safe only for hashing purposes.
	 * Instead HS512 and HS256 use SHA-3 equivalents which are safe: H3S512 and H3S256.
	 * The H3S224 and HS224 are too weak, don't use except if used with encrypted JWT mode.
	 *
	 * This is a hidden functionality that is not intended to be used directly ...
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 	$algo 					:: JWT Algo: 'Ed25519' ; 'H3S512' ; 'H3S384' ; 'HS384' ; 'H3S256' ; 'H3S224' ; 'HS224'
	 * @param 	STRING 	$user 					:: The auth user name, as email
	 * @param 	STRING  $ipaddr 				:: IP address or wildcard *
	 * @param 	STRING 	$area 					:: The authentication area
	 * @param 	STRING 	$issuer 				:: The issuer, usually as host:port ; must match what is the other server expecting ...
	 * @param 	INT		$expire 				:: The expiration time in minutes from now ; 1..525600
	 * @param 	ARRAY 	$privs 					:: Privileges List   [ priv1,  priv2, ... ]  (cannot be empty ! must have at least one entry to be validated)
	 * @param 	ARRAY 	$restr 					:: Restrictions List [ restr1, restr2, ... ] (cannot be empty ! must have at least one entry to be validated)
	 * @param 	STRING 	$xtras 					:: JWT Extras Definition for Xtras validations ; Default is Empty ; Example: 'ApiKey.Virtual'
	 * @param 	INT 	$encrypt 				:: DEFAULT 0 = plain ; 1 = BlowFish-448:CBC ; 2 = TwoFish-256:CBC ; 3 = ThreeFish-1024:CBC algo ; all other values are invalid ; for encryption will use the private app key
	 *
	 * @return 	ARRAY							:: array of strings as: [ 'error' => 'error if any or empty', 'token' => '...' ]
	 */
	public static function jwt_token_create(string $algo, string $user, string $ipaddr, string $area, string $issuer, int $expire, array $privs, array $restr, string $xtras='', int $encrypt=0) : array {
		//--
		$jwt = [
			'error' 		=> '?', // error or empty
			'type' 			=> 'JWT',
			'algo' 			=> '',
			'serial' 		=> '',
			'sign' 			=> '',
			'iplist' 		=> '',
			'area' 			=> '',
			'user' 			=> '',
			'expires' 		=> 0,
			'issuer' 		=> '',
			'privs' 		=> '',
			'restr' 		=> '',
			'xtras' 		=> '',
			'token' 		=> '',  // the jwt token (b64u) | encrypted jwt token (b64s)
			'encoding' 		=> '',
			'encryption' 	=> '',
			'#bytes#' 		=> 0,
		];
		//--
		switch((int)$encrypt) {
			case 3:
				$jwt['encoding']   = 'Base64s';
				$jwt['encryption'] = '3Fish.1024:CBC';
				break;
			case 2:
				$jwt['encoding']   = 'Base64s';
				$jwt['encryption'] = '2Fish.256:CBC';
				break;
			case 1:
				$jwt['encoding']   = 'Base64s';
				$jwt['encryption'] = 'BFish.448:CBC';
				break;
			case 0:
				$jwt['encoding']   = 'Base64u';
				$jwt['encryption'] = 'Plain';
				break;
			default:
				$jwt['error'] = 'Invalid Encryption Mode: '.$encrypt;
				return (array) $jwt;
		} //end switch
		//--
		if(!defined('SMART_FRAMEWORK_SECURITY_KEY')) {
			$jwt['error'] = 'Security Key is Undefined';
			return (array) $jwt;
		} //end if
		if((string)trim((string)SMART_FRAMEWORK_SECURITY_KEY) == '') {
			$jwt['error'] = 'Security Key is Empty';
			return (array) $jwt;
		} //end if
		//--
		$algo = (string) trim((string)$algo);
		$hmacAlgo = '';
		$edAlgo = '';
		$dKeyLen = 64; // {{{SYNC-JWT-HS-KEY-LEN}}}
		switch((string)$algo) {
			//-- general purpose
			case 'HS224':  // sha224
				$hmacAlgo = 'sha224';
				break;
			case 'H3S224': // sha3-224
				$hmacAlgo = 'sha3-224';
				break;
			case 'H3S256': // sha3-256
				$hmacAlgo = 'sha3-256';
				break;
			//-- safe, accepted by SmartGo
			case 'HS384':  // sha384
				$hmacAlgo = 'sha384';
				break;
			case 'H3S384': // sha3-384
				$hmacAlgo = 'sha3-384';
				break;
			case 'H3S512': // sha3-512
				$hmacAlgo = 'sha3-512';
				break;
			case 'Ed25519': // Ed25519
				$edAlgo = 'ed25519';
				$dKeyLen = 32;
				break;
			default:
				$jwt['error'] = 'Invalid algo: `'.$algo.'`';
				return (array) $jwt;
		} //end switch
		$hmacAlgo = (string) strtolower((string)trim((string)$hmacAlgo));
		$edAlgo   = (string) strtolower((string)trim((string)$edAlgo));
		if(((string)$hmacAlgo == '') && ((string)$edAlgo == '')) {
			$jwt['error'] = 'Both: HMac and Ed Algos are Empty';
			return (array) $jwt;
		} //end if
		//--
		$user = (string) trim((string)$user);
		if((string)$user == '') {
			$jwt['error'] = 'UserName is Empty';
			return (array) $jwt;
		} //end if
		if(((int)strlen((string)$user) < 5) || ((int)strlen((string)$user) > 72) || ((bool)preg_match((string)self::REGEX_SAFE_AUTH_USER_NAME, (string)$user) !== true)) {
			$jwt['error'] = 'UserName is Invalid';
			return (array) $jwt;
		} //end if
		//--
		$issuer = (string) trim((string)$issuer);
		if((string)$issuer == '') {
			$jwt['error'] = 'Issuer is Empty';
			return (array) $jwt;
		} //end if
		if(((int)strlen((string)$issuer) < 7) || ((int)strlen((string)$issuer) > 69)) {
			$jwt['error'] = 'Issuer is Invalid';
			return (array) $jwt;
		} //end if
		//--
		$area = (string) strtoupper((string)trim((string)$area));
		if((string)$area == '') {
			$jwt['error'] = 'Area is Empty';
			return (array) $jwt;
		} //end if
		if((string)$area == '[DEFAULT]') { // disallow, this is reserved for SmartGo, and if have to be set, must be set as `@`
			$jwt['error'] = 'The [DEFAULT] Area is Disallowed';
			return (array) $jwt;
		} //end if
		if((string)$area != '@') {
			if(((int)strlen((string)$area) < 4) || ((int)strlen((string)$area) > 18) || ((bool)preg_match('/^[A-Z0-9\-]{4,18}$/', (string)$area) !== true)) { // disallow dot ; allow just max 18 here, the rest is reserved for SmartGo as prefix for this ; {{{SYNC-AUTH-EXT-AREA-CHECK}}}
				$jwt['error'] = 'Area is Invalid';
				return (array) $jwt;
			} //end if
		} //end if
		//--
		$expire = (int) $expire; // minutes ; must be between 1..525600 minutes
		if((int)$expire < 1) {
			$jwt['error'] = 'Expire Minutes must be at least 1';
			return (array) $jwt;
		} //end if
		if((int)$expire > 525600) {
			$jwt['error'] = 'Expire Minutes must be no more than 525600';
			return (array) $jwt;
		} //end if
		//--
		if((int)Smart::array_size($privs) > 0) {
			$privs = (array) self::safe_arr_privileges_or_restrictions((array)$privs, true);
			$privs = (string) Smart::array_to_list((array)$privs);
			$privs = (string) str_replace(' ', '', (string)$privs);
			if((int)strlen((string)$privs) > 50) {
				$jwt['error'] = 'Privileges are OverSized';
				return (array) $jwt;
			} //end if
		} else {
			$privs = '@';
		} //end if else
		//--
		if((int)Smart::array_size($restr) > 0) {
			$restr = (array) self::safe_arr_privileges_or_restrictions((array)$restr, true);
			$restr = (string) Smart::array_to_list((array)$restr);
			$restr = (string) str_replace(' ', '', (string)$restr);
			if((int)strlen((string)$privs) > 50) {
				$jwt['error'] = 'Restrictions are OverSized';
				return (array) $jwt;
			} //end if
		} else {
			$restr = '@';
		} //end if else
		//--
		$usrKey = (string) SMART_FRAMEWORK_SECURITY_KEY.chr(0).SmartHashCrypto::crc32b((string)$user."\f".SMART_FRAMEWORK_SECURITY_KEY, false); // make a personalized secret key for the user
		//--
		$safeKey = (string) SmartHashCrypto::pbkdf2DerivedB92Key((string)$usrKey, $user.'#'.$issuer, (int)$dKeyLen, (int)SmartHashCrypto::DERIVE_CENTITER_TK, 'sha3-512'); // b92
		if((int)strlen((string)$safeKey) != (int)$dKeyLen) {
			$jwt['error'] = 'Invalid Derived Key Length';
			return (array) $jwt;
		} //end if
		//--
		$issuedAt  = (int) time(); // {{{SYNC-SMART-JWT-UTC-TIME}}} ; unix time is fixed, does not depend on UTC
		$expiresAt = (int) ((int)$issuedAt + (int)((int)$expire * 60));
		//--
		$serial = (string) Smart::uuid_10_seq().'-'.Smart::uuid_10_str(); // {{{SYNC-JWT-VALID-SERIAL}}}
		//--
		$iplist = '';
		$ipaddr = (string) trim((string)$ipaddr);
		if((string)$ipaddr == '') {
			$jwt['error'] = 'IP Address is Empty, provide a valid IP address or: * = wildcard';
			return (array) $jwt;
		} elseif((string)$ipaddr == '*') { // ok, wildcard
			$iplist = '*';
		} else {
			if((string)trim((string)SmartValidator::validate_filter_ip_address((string)$ipaddr)) == '') { // if not valid IP address
				$jwt['error'] = 'IP Address is Invalid';
				return (array) $jwt;
			} //end if
			$ipaddr = (string) trim((string)Smart::ip_addr_compress((string)$ipaddr)); // {{{SYNC-IPV6-STORE-SHORTEST-POSSIBLE}}} ; IPV6 addresses may vary .. find a standard form, ex: shortest
			if((string)$ipaddr == '') {
				$jwt['error'] = 'IP Address compression Failed';
				return (array) $jwt;
			} //end if
			$iplist = '<'.$ipaddr.'>';
		} //end if
		//--
		$xtras = (string) trim((string)$xtras);
		if((string)$xtras == '') {
			$xtras = '-';
		} //end if
		//--
		$audience = [
			(string) 'I:'.$iplist, // ip list of allowed addresses as `<ip1>,<ip2>` or `<ip>` or wildcard *
			(string) 'A:'.$area,   // area
			(string) 'P:'.$privs,  // privileges
			(string) 'R:'.$restr,  // restrictions
			(string) 'X:'.$xtras,  // extras
		];
		//--
		$checksum = (string) Smart::b64s_enc((string)chr(0).$user.chr(8).$serial.chr(7).$expiresAt."\v".$issuedAt."\f".$issuer.chr(0).implode("\u{FFFD}", (array)$audience).chr(8).SMART_FRAMEWORK_SECURITY_KEY.chr(0));
		//--
		$subject = (string) SmartHashCrypto::crc32b((string)$checksum, true).'-'.SmartHashCrypto::crc32b((string)strrev((string)$checksum), true);
		//--
		$hdr = [
			'typ' => 'JWT',
			'alg' => (string) $algo,
		];
		//--
		$dat = [
			'usr' => (string) $user,      // username or userid
			'iss' => (string) $issuer,    // host:port
			'sub' => (string) $subject,   // CRC32B36-CRC32B36(rev)
			'aud' => (array)  $audience,
			'exp' => (int)    $expiresAt,
			'iat' => (int)    $issuedAt,
			'jti' => (string) $serial,
		];
		//--
		$hdr = (string) Smart::json_encode((array)$hdr, false, true, false);
		$hdr = (string) Smart::b64s_enc((string)$hdr, false);
		//--
		$dat = (string) Smart::json_encode((array)$dat, false, true, false);
		$dat = (string) Smart::b64s_enc((string)$dat, false);
		//--
		$token = (string) $hdr.'.'.$dat;
		//--
		$sign = '';
		if((string)$edAlgo == 'ed25519') {
			$edSignature = (array) SmartHashCrypto::ed25519_sign((string)$token, (string)$safeKey);
			if((string)($edSignature['error'] ?? null) != '') {
				$jwt['error'] = 'JWT Sign Failed: algo `'.$edAlgo.'` ERR: # '.$edSignature['error'];
				return (array) $jwt;
			} //end if
			$sign = (string) trim((string)($edSignature['signature'] ?? null)); // b64
		} else {
			$sign = (string) trim((string)SmartHashCrypto::hmac((string)$hmacAlgo, (string)$safeKey, (string)$token, true)); // b64
		} //end if else
		if((string)$sign == '') {
			$jwt['error'] = 'JWT Sign Failed: Empty';
			return (array) $jwt;
		} //end if
		$sign = (string) Smart::b64_to_b64s((string)$sign, false); // B64u
		//--
		$token .= '.'.$sign;
		//--
		if(
			((int)strlen((string)$token) < 128)  // {{{SYNC-AUTH-JWT-MIN-ALLOWED-LEN}}} ; min size, for plain JWT
			||
			((int)strlen((string)$token) > 1280) // {{{SYNC-AUTH-JWT-MAX-ALLOWED-LEN}}} ; max size, for plain JWT
		) {
			$jwt['error'] = 'Invalid Token Length';
			return (array) $jwt;
		} //end if
		//--
		if((int)$encrypt > 0) {
			//--
			switch((int)$encrypt) {
				case 1: // BF
					$token = (string) SmartCipherCrypto::bf_encrypt((string)strrev((string)$token), (string)SMART_FRAMEWORK_SECURITY_KEY);
					if((string)trim((string)$token) == '') {
						$jwt['error'] = 'BF Encryption Failed';
						return (array) $jwt;
					} //end if
					$token = (string) 'ejwt.1f;'.substr((string)$token, (int)strlen((string)SmartCipherCrypto::SIGNATURE_BFISH_V3));
					break;
				case 2: // 2F
					$token = (string) SmartCipherCrypto::tf_encrypt((string)strrev((string)$token), (string)SMART_FRAMEWORK_SECURITY_KEY);
					if((string)trim((string)$token) == '') {
						$jwt['error'] = '2F Encryption Failed';
						return (array) $jwt;
					} //end if
					$token = (string) 'ejwt.2f;'.substr((string)$token, (int)strlen((string)SmartCipherCrypto::SIGNATURE_2FISH_V1_DEFAULT));
					break;
				case 3: // 3F
				default:
					$token = (string) SmartCipherCrypto::t3f_encrypt((string)strrev((string)$token), (string)SMART_FRAMEWORK_SECURITY_KEY);
					if((string)trim((string)$token) == '') {
						$jwt['error'] = '3F Encryption Failed';
						return (array) $jwt;
					} //end if
					$token = (string) 'ejwt.3f;'.substr((string)$token, (int)strlen((string)SmartCipherCrypto::SIGNATURE_3FISH_1K_V1_DEFAULT));
			} //end switch
			//--
			if(
				((int)strlen((string)$token) < 128) 			// {{{SYNC-AUTH-JWT-MIN-ALLOWED-LEN}}} ; min size, for plain or encrypted JWT
				||
				((int)strlen((string)$token) > (int)(1280 * 2)) // {{{SYNC-AUTH-JWT-MAX-ALLOWED-LEN}}} ; max size x 2, for plain or allow encrypted JWT
			) {
				$jwt['error'] = 'Invalid Encrypted Token Length';
				return (array) $jwt;
			} //end if
			//--
		} //end if
		//--
		$valid = (array) self::jwt_token_validate((string)$algo, (string)$issuer, (string)$token, (string)$ipaddr);
		if((string)($valid['error'] ?? null) != '') {
			$jwt['error'] = 'JWT Validation Failed, ERR: '.($valid['error'] ?? null);
			return (array) $jwt;
		} //end if
		//--
		$jwt['error'] 	= ''; // clear
		$jwt['algo'] 	= (string) $algo;
		$jwt['serial'] 	= (string) $serial;
		$jwt['sign'] 	= (string) $sign;
		$jwt['iplist'] 	= (string) $iplist;
		$jwt['area'] 	= (string) $area;
		$jwt['user'] 	= (string) $user;
		$jwt['expires'] = (int)    $expiresAt;
		$jwt['issuer'] 	= (string) $issuer;
		$jwt['privs'] 	= (string) $privs;
		$jwt['restr'] 	= (string) $restr;
		$jwt['xtras'] 	= (string) $xtras;
		$jwt['token'] 	= (string) $token;
		$jwt['#bytes#'] = (int)    strlen((string)$token);
		//--
		return (array) $jwt;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Validate a JWT Web Token
	 *
	 * If this method returns an error (message), validation of the JWT Token validation has failed
	 *
	 * IMPORTANT:
	 * - the IP list restriction is verified by this method and does validate if the current provided client's IP is valid and is in the allowed list provided by the token
	 * - if the Token does not provide a valid IP list, token will be not validated, an error will be returned
	 * - if the IP list is valid but the current visitor IP is not in this list, a validation error will be returned by this method
	 *
	 * NOTICE:
	 * - the Privileges list provided by this method (as the list of privileges available in the token) needs to be INTERSECTED (array intersect, to avoid privilege escalations) with the account's privileges to result the list of account allowed privileges !
	 * - and empty Privileges list should not be allowed !
	 *
	 * This is a hidden functionality that is not intended to be used directly ...
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 	$algo 		:: The JWT Token Algo
	 * @param 	STRING 	$issuer 	:: The JWT Token Issuer, ussually as basedom:port
	 * @param 	STRING 	$token 		:: The JWT Token String
	 * @param 	STRING 	$client_ip 	:: The current client IP Address to be compared and validated with the Token (if token contain an IP Bind) ; must be the current visitor's IP IPv4 or IPv6 for the token is validated for
	 *
	 * @return 	ARRAY				:: array of strings as: [ 'error' => 'error if any or empty', 'serial' => 'xxx-xxx', 'user-name' => '...', 'area' => '...', 'ip-list' => '<>,<>|*', 'priv' => '<>,<>|@', 'restr' => '<>,<>|@', 'xtras' => '...', 'json-arr' => [ 'head' => [...], 'body' => [...], 'signature' => 'B64' ] ]
	 */
	public static function jwt_token_validate(string $algo, string $issuer, ?string $token, ?string $client_ip) {
		//--
		$valid = [
			'error' 		=> '?', 	// error or empty
			'serial' 		=> '', 		// serial ID
			'user-name' 	=> '',  	// auth user name
			'area' 			=> '', 		// auth area
			'ip-list' 		=> '', 		// allowed IPs
			'priv' 			=> '',  	// privileges
			'restr' 		=> '',  	// restrictions
			'xtras' 		=> '', 		// JWT Xtras
			'sign' 			=> '', 		// token signature
			'token' 		=> '', 		// plain token (even if encrypted)
			'json-arr' 		=> [ 		// the token json, array
				'valid' 	=> false,
				'sign-ok' 	=> false,
				'head' 		=> [],
				'body' 		=> [],
			],
		];
		//--
		if(!defined('SMART_FRAMEWORK_SECURITY_KEY')) {
			$valid['error'] = 'Security Key is Undefined';
			return (array) $valid;
		} //end if
		if((string)trim((string)SMART_FRAMEWORK_SECURITY_KEY) == '') {
			$valid['error'] = 'Security Key is Empty';
			return (array) $valid;
		} //end if
		//--
		$algo = (string) trim((string)$algo);
		$hmacAlgo = '';
		$edAlgo = '';
		$dKeyLen = 64; // {{{SYNC-JWT-HS-KEY-LEN}}}
		switch((string)$algo) {
			//-- general purpose
			case 'HS224':  // sha224
				$hmacAlgo = 'sha224';
				break;
			case 'H3S224': // sha3-224
				$hmacAlgo = 'sha3-224';
				break;
			case 'H3S256': // sha3-256
				$hmacAlgo = 'sha3-256';
				break;
			//-- safe, accepted by SmartGo
			case 'HS384':  // sha384
				$hmacAlgo = 'sha384';
				break;
			case 'H3S384': // sha3-384
				$hmacAlgo = 'sha3-384';
				break;
			case 'H3S512': // sha3-512
				$hmacAlgo = 'sha3-512';
				break;
			case 'Ed25519': // Ed25519
				$edAlgo = 'ed25519';
				$dKeyLen = 32;
				break;
			default:
				$valid['error'] = 'Invalid algo: `'.$algo.'`';
				return (array) $valid;
		} //end switch
		//--
		$issuer = (string) trim((string)$issuer);
		if((string)$issuer == '') {
			$valid['error'] = 'Issuer is Empty';
			return (array) $valid;
		} //end if
		//--
		$token = (string) trim((string)$token);
		if((string)$token == '') {
			$valid['error'] = 'Token is Empty';
			return (array) $valid;
		} //end if
		//--
		if(
			((int)strlen((string)$token) < 128) 			// {{{SYNC-AUTH-JWT-MIN-ALLOWED-LEN}}} ; min size, for plain or encrypted JWT
			||
			((int)strlen((string)$token) > (int)(1280 * 2)) // {{{SYNC-AUTH-JWT-MAX-ALLOWED-LEN}}} ; max size x 2, for plain or allow encrypted JWT
		) {
			$valid['error'] = 'Invalid Encrypted Token Length';
			return (array) $valid;
		} //end if
		//--
		if(strpos((string)$token, 'ejwt.3f;') === 0) { // if starts with this, it is encrypted
			$token = (string) trim((string)substr((string)$token, 8));
			if((string)$token != '') {
				$token = (string) SmartCipherCrypto::t3f_decrypt((string)SmartCipherCrypto::SIGNATURE_3FISH_1K_V1_DEFAULT.$token, (string)SMART_FRAMEWORK_SECURITY_KEY);
				if((string)$token != '') {
					$token = (string) strrev((string)$token);
				} //end if
			} //end if
			if((string)$token == '') {
				$valid['error'] = 'Token 3F Decryption Failed';
				return (array) $valid;
			} //end if
		} else if(strpos((string)$token, 'ejwt.2f;') === 0) { // if starts with this, it is encrypted
			$token = (string) trim((string)substr((string)$token, 8));
			if((string)$token != '') {
				$token = (string) SmartCipherCrypto::tf_decrypt((string)SmartCipherCrypto::SIGNATURE_2FISH_V1_DEFAULT.$token, (string)SMART_FRAMEWORK_SECURITY_KEY);
				if((string)$token != '') {
					$token = (string) strrev((string)$token);
				} //end if
			} //end if
			if((string)$token == '') {
				$valid['error'] = 'Token 2F Decryption Failed';
				return (array) $valid;
			} //end if
		} else if(strpos((string)$token, 'ejwt.1f;') === 0) { // if starts with this, it is encrypted
			$token = (string) trim((string)substr((string)$token, 8));
			if((string)$token != '') {
				$token = (string) SmartCipherCrypto::bf_decrypt((string)SmartCipherCrypto::SIGNATURE_BFISH_V3.$token, (string)SMART_FRAMEWORK_SECURITY_KEY);
				if((string)$token != '') {
					$token = (string) strrev((string)$token);
				} //end if
			} //end if
			if((string)$token == '') {
				$valid['error'] = 'Token BF Decryption Failed';
				return (array) $valid;
			} //end if
		} //end if
		//--
		if(
			((int)strlen((string)$token) < 128)  // {{{SYNC-AUTH-JWT-MIN-ALLOWED-LEN}}} ; min size, for plain JWT
			||
			((int)strlen((string)$token) > 1280) // {{{SYNC-AUTH-JWT-MAX-ALLOWED-LEN}}} ; max size, for plain JWT
		) {
			$valid['error'] = 'Invalid Token Length';
			return (array) $valid;
		} //end if
		//--
		if(self::jwt_valid_format((string)$token) !== true) {
			$valid['error'] = 'Token format is Invalid';
			return (array) $valid;
		} //end if
		//--
		$arr = (array) explode('.', (string)$token, 3); // {{{SYNC-EXPLODE-DOT-JWT}}}
		if((int)Smart::array_size($arr) != 3) {  // {{{SYNC-EXPLODE-DOT-JWT-PARTS}}}
			$valid['error'] = 'Token parts are Invalid';
			return (array) $valid;
		} //end if
		//-- remove signature, before decode, needed for later checks
		$token = (string) $arr[0].'.'.$arr[1];
		$signature = (string) Smart::b64s_dec((string)$arr[2], true);
		//--
		$arr[0] = Smart::json_decode((string)Smart::b64s_dec((string)$arr[0], true)); // mixed ; B64 STRICT
		if((int)Smart::array_size($arr[0]) <= 0) {
			$valid['error'] = 'JWT head is Invalid';
			return (array) $valid;
		} //end if
		//-- verify type, must be JWT
		if((string)($arr[0]['typ'] ?? null) != 'JWT') {
			$valid['error'] = 'JWT head type is Invalid';
			return (array) $valid;
		} //end if
		//-- verify algo, must match with what is expected
		if((string)($arr[0]['alg'] ?? null) != (string)$algo) {
			$valid['error'] = 'JWT head algo is Invalid';
			return (array) $valid;
		} //end if
		//--
		$arr[1] = Smart::json_decode((string)Smart::b64s_dec((string)$arr[1], true)); // mixed ; B64 STRICT
		if((int)Smart::array_size($arr[1]) <= 0) {
			$valid['error'] = 'JWT body is Invalid';
			return (array) $valid;
		} //end if
		//-- verify issuer, must match the expected one
		$arr[1]['iss'] = (string) trim((string)$arr[1]['iss']);
		if((string)$arr[1]['iss'] == '') {
			$valid['error'] = 'JWT Issuer is Empty';
			return (array) $valid;
		} //end if
		if(((int)strlen((string)$arr[1]['iss']) < 7) || ((int)strlen((string)$arr[1]['iss']) > 69)) {
			$valid['error'] = 'JWT Issuer is Invalid';
			return (array) $valid;
		} //end if
		if((string)$arr[1]['iss'] != (string)$issuer) {
			$valid['error'] = 'JWT Issuer does not match';
			return (array) $valid;
		} //end if
		//-- verify issued (created) time
		$arr[1]['iat'] = (int) intval($arr[1]['iat'] ?? null);
		if((int)$arr[1]['iat'] <= 0) {
			$valid['error'] = 'JWT IssuedAt is Zero or Empty';
			return (array) $valid;
		} //end if
		//-- verify expiration time
		$arr[1]['exp'] = (int) intval($arr[1]['exp'] ?? null);
		if((int)$arr[1]['exp'] <= 0) {
			$valid['error'] = 'JWT ExpiresAt is Zero or Empty';
			return (array) $valid;
		} //end if
		//-- verify if expired
		if((int)$arr[1]['exp'] <= (int)time()) { // {{{SYNC-SMART-JWT-UTC-TIME}}} ; unix time is fixed, does not depend on UTC
			$valid['error'] = 'JWT is Expired';
			return (array) $valid;
		} //end if
		//-- verify expiration time vs. issued time
		if((int)$arr[1]['exp'] <= (int)$arr[1]['iat']) {
			$valid['error'] = 'JWT ExpiresAt must be greater than IssuedAt';
			return (array) $valid;
		} //end if
		//-- verify serial
		$arr[1]['jti'] = (string) trim((string)$arr[1]['jti']);
		if((string)$arr[1]['jti'] == '') {
			$valid['error'] = 'JWT Serial is Empty';
			return (array) $valid;
		} //end if
		if((int)strlen((string)$arr[1]['jti']) != 21) {
			$valid['error'] = 'JWT Serial is Invalid';
			return (array) $valid;
		} //end if
		if(!preg_match((string)self::REGEX_VALID_JWT_SERIAL, (string)$arr[1]['jti'])) { // {{{SYNC-JWT-VALID-SERIAL}}}
			$valid['error'] = 'JWT Serial format is Invalid';
			return (array) $valid;
		} //end if
		//-- verify user
		$user = (string) trim((string)($arr[1]['usr'] ?? null));
		if((string)$user == '') {
			$valid['error'] = 'JWT UserName is Empty';
			return (array) $valid;
		} //end if
		if(((int)strlen((string)$user) < 5) || ((int)strlen((string)$user) > 72) || ((bool)preg_match((string)self::REGEX_SAFE_AUTH_USER_NAME, (string)$user) !== true)) {
			$valid['error'] = 'JWT UserName is Invalid';
			return (array) $valid;
		} //end if
		//-- verify audience
		if((int)Smart::array_size($arr[1]['aud']) != 5) {
			$valid['error'] = 'JWT Audience length is Invalid';
			return (array) $valid;
		} //end if
		if((int)Smart::array_type_test($arr[1]['aud']) != 1) {
			$valid['error'] = 'JWT Audience format is Invalid';
			return (array) $valid;
		} //end if
		$arr[1]['aud'][0] = (string) trim((string)$arr[1]['aud'][0]);
		$arr[1]['aud'][1] = (string) trim((string)$arr[1]['aud'][1]);
		$arr[1]['aud'][2] = (string) trim((string)$arr[1]['aud'][2]);
		$arr[1]['aud'][3] = (string) trim((string)$arr[1]['aud'][3]);
		$arr[1]['aud'][4] = (string) trim((string)$arr[1]['aud'][4]);
		if(((string)$arr[1]['aud'][0] == '') || (strpos((string)$arr[1]['aud'][0], 'I:') === false) || ((int)strlen((string)$arr[1]['aud'][0]) < 3) || ((int)strlen((string)$arr[1]['aud'][0]) > 255)) {
			$valid['error'] = 'JWT Audience is Invalid: I';
			return (array) $valid;
		} //end if
		if(((string)$arr[1]['aud'][1] == '') || (strpos((string)$arr[1]['aud'][1], 'A:') === false) || ((int)strlen((string)$arr[1]['aud'][1]) < 3) || ((int)strlen((string)$arr[1]['aud'][1]) > 255)) {
			$valid['error'] = 'JWT Audience is Invalid: A';
			return (array) $valid;
		} //end if
		if(((string)$arr[1]['aud'][2] == '') || (strpos((string)$arr[1]['aud'][2], 'P:') === false) || ((int)strlen((string)$arr[1]['aud'][2]) < 3) || ((int)strlen((string)$arr[1]['aud'][2]) > 255)) {
			$valid['error'] = 'JWT Audience is Invalid: P';
			return (array) $valid;
		} //end if
		if(((string)$arr[1]['aud'][3] == '') || (strpos((string)$arr[1]['aud'][3], 'R:') === false) || ((int)strlen((string)$arr[1]['aud'][3]) < 3) || ((int)strlen((string)$arr[1]['aud'][3]) > 255)) {
			$valid['error'] = 'JWT Audience is Invalid: R';
			return (array) $valid;
		} //end if
		if(((string)$arr[1]['aud'][4] == '') || (strpos((string)$arr[1]['aud'][4], 'X:') === false) || ((int)strlen((string)$arr[1]['aud'][4]) < 3) || ((int)strlen((string)$arr[1]['aud'][4]) > 512)) { // this can be up to 512, can be json
			$valid['error'] = 'JWT Audience is Invalid: X';
			return (array) $valid;
		} //end if
		if((int)strlen((string)$arr[1]['aud'][0]) == 3) {
			if((string)$arr[1]['aud'][0] != 'I:*') {
				$valid['error'] = 'JWT Audience is Wrong: I';
				return (array) $valid;
			} //end if
		} //end if
		if((int)strlen((string)$arr[1]['aud'][1]) == 3) {
			if((string)$arr[1]['aud'][1] != 'A:@') {
				$valid['error'] = 'JWT Audience is Wrong: A';
				return (array) $valid;
			} //end if
		} //end if
		if((int)strlen((string)$arr[1]['aud'][2]) == 3) {
			if((string)$arr[1]['aud'][2] != 'P:@') {
				$valid['error'] = 'JWT Audience is Wrong: P';
				return (array) $valid;
			} //end if
		} //end if
		if((int)strlen((string)$arr[1]['aud'][3]) == 3) {
			if((string)$arr[1]['aud'][3] != 'R:@') {
				$valid['error'] = 'JWT Audience is Wrong: R';
				return (array) $valid;
			} //end if
		} //end if
		// do not check for jwtAudience.Xtras ; may contain: - / + ...
		$iplist = (string) trim((string)substr((string)$arr[1]['aud'][0], 2));
		if((string)$iplist == '') {
			$valid['error'] = 'JWT Audience IP List is Empty';
			return (array) $valid;
		} //end if
		$area = (string) trim((string)substr((string)$arr[1]['aud'][1], 2));
		if((string)$area == '') {
			$valid['error'] = 'JWT Audience Area is Empty';
			return (array) $valid;
		} //end if
		if((string)$area == '[DEFAULT]') { // disallow, this is reserved for SmartGo, and if have to be set, must be set as `@`
			$valid['error'] = 'The [DEFAULT] Area is Disallowed';
			return (array) $valid;
		} //end if
		if((string)$area != '@') {
			if(((int)strlen((string)$area) < 4) || ((int)strlen((string)$area) > 18) || ((bool)preg_match('/^[A-Z0-9\-]{4,18}$/', (string)$area) !== true)) { // disallow dot ; allow just max 18 here, the rest is reserved for SmartGo as prefix for this ; {{{SYNC-AUTH-EXT-AREA-CHECK}}}
				$valid['error'] = 'Area is Invalid';
				return (array) $valid;
			} //end if
		} //end if
		$privs = (string) trim((string)substr((string)$arr[1]['aud'][2], 2));
		if((string)$privs == '') {
			$valid['error'] = 'JWT Audience Privileges List is Empty';
			return (array) $valid;
		} //end if
		if((string)$privs != '@') {
			$arrPrivs = (array) self::safe_arr_privileges_or_restrictions((string)$privs, true);
			if((int)Smart::array_size($arrPrivs) <= 0) {
				$valid['error'] = 'JWT Audience Privileges List is Invalid';
				return (array) $valid;
			} //end if
			$privs = (string) Smart::array_to_list((array)$arrPrivs);
			$privs = (string) str_replace(' ', '', (string)$privs);
			$arrPrivs = null; // free
			if((int)strlen((string)$privs) > 50) {
				$valid['error'] = 'JWT Audience Privileges List is OverSized';
				return (array) $valid;
			} //end if
		} //end if
		$restr = (string) trim((string)substr((string)$arr[1]['aud'][3], 2));
		if((string)$restr == '') {
			$valid['error'] = 'JWT Audience Restrictions List is Empty';
			return (array) $valid;
		} //end if
		if((string)$restr != '@') {
			$arrRestr = (array) self::safe_arr_privileges_or_restrictions((string)$restr, true);
			if((int)Smart::array_size($arrRestr) <= 0) {
				$valid['error'] = 'JWT Audience Restrictions List is Invalid';
				return (array) $valid;
			} //end if
			$restr = (string) Smart::array_to_list((array)$arrRestr);
			$restr = (string) str_replace(' ', '', (string)$restr);
			$arrRestr = null; // free
			if((int)strlen((string)$restr) > 50) {
				$valid['error'] = 'JWT Audience Restrictions List is OverSized';
				return (array) $valid;
			} //end if
		} //end if
		$xtras = (string) trim((string)substr((string)$arr[1]['aud'][4], 2));
		//-- verify ip
		$ipaddr = (string) trim((string)$client_ip);
		if((string)$ipaddr == '') {
			$valid['error'] = 'IP Address to verify is Empty';
			return (array) $valid;
		} //end if
		if((string)$ipaddr != '*') { // if not wildcard must be valid ip
			if((string)trim((string)SmartValidator::validate_filter_ip_address((string)$ipaddr)) == '') { // if not valid IP address
				$valid['error'] = 'IP Address to verify is Invalid';
				return (array) $valid;
			} //end if
			$ipaddr = (string) trim((string)Smart::ip_addr_compress((string)$ipaddr)); // {{{SYNC-IPV6-STORE-SHORTEST-POSSIBLE}}} ; IPV6 addresses may vary .. find a standard form, ex: shortest
			if((string)$ipaddr == '') {
				$valid['error'] = 'IP Address compression Failed for the to verify';
				return (array) $valid;
			} //end if
		} //end if
		if((string)$arr[1]['aud'][0] != 'I:*') { // check if it is not wildcard ; if wildcard match any
			if((string)$ipaddr == '*') { // wildcard
				$valid['error'] = 'JWT IP Address does not match: *';
				return (array) $valid;
			} else { // must be valid IP
				if(strpos(($arr[1]['aud'][0] ?? null), '<'.$ipaddr.'>') === false) {
					$valid['error'] = 'JWT IP Address does not match';
					return (array) $valid;
				} //end if
			} //end if else
		} //end if
		//-- verify subject + checksum
		$checksum = (string) Smart::b64s_enc((string)chr(0).$user.chr(8).$arr[1]['jti'].chr(7).$arr[1]['exp']."\v".$arr[1]['iat']."\f".$arr[1]['iss'].chr(0).implode("\u{FFFD}", (array)$arr[1]['aud']).chr(8).SMART_FRAMEWORK_SECURITY_KEY.chr(0));
		$subject = (string) SmartHashCrypto::crc32b((string)$checksum, true).'-'.SmartHashCrypto::crc32b((string)strrev((string)$checksum), true);
		$arr[1]['sub'] = (string) trim((string)$arr[1]['sub']);
		if((string)$arr[1]['sub'] == '') {
			$valid['error'] = 'JWT Subject is Empty';
			return (array) $valid;
		} //end if
		if((string)$arr[1]['sub'] != (string)$subject) {
			$valid['error'] = 'JWT Subject is Invalid';
			return (array) $valid;
		} //end if
		//--
		$usrKey = (string) SMART_FRAMEWORK_SECURITY_KEY.chr(0).SmartHashCrypto::crc32b((string)$user."\f".SMART_FRAMEWORK_SECURITY_KEY, false); // make a personalized secret key for the user
		$safeKey = (string) SmartHashCrypto::pbkdf2DerivedB92Key((string)$usrKey, $user.'#'.$issuer, (int)$dKeyLen, (int)SmartHashCrypto::DERIVE_CENTITER_TK, 'sha3-512'); // b92
		if((int)strlen((string)$safeKey) != (int)$dKeyLen) {
			$valid['error'] = 'Invalid Derived Key Length';
			return (array) $valid;
		} //end if
		//-- validate signature
		$sign = '';
		if((string)$edAlgo == 'ed25519') {
			$edSignature = (array) SmartHashCrypto::ed25519_sign((string)$token, (string)$safeKey);
			if((string)($edSignature['error'] ?? null) != '') {
				$valid['error'] = 'JWT Sign Failed: algo `'.$edAlgo.'` ERR: # '.$edSignature['error'];
				return (array) $valid;
			} //end if
			$sign = (string) trim((string)($edSignature['signature'] ?? null)); // b64
			if(SmartHashCrypto::ed25519_verify_sign((string)$signature, (string)$token, (string)Smart::b64s_dec((string)($edSignature['public-key'] ?? null), true)) !== true) { // B64 STRICT
				$valid['error'] = 'JWT Signature verification failed';
				return (array) $valid;
			} //end if
		} else {
			$sign = (string) trim((string)SmartHashCrypto::hmac((string)$hmacAlgo, (string)$safeKey, (string)$token, true)); // b64
		} //end if else
		if((string)$sign == '') {
			$valid['error'] = 'JWT Sign Failed: Empty';
			return (array) $valid;
		} //end if
		$sign = (string) Smart::b64_to_b64s((string)$sign, false);
		if((string)$sign != (string)$arr[2]) {
			$valid['error'] = 'JWT Signature does not match';
			return (array) $valid;
		} //end if
		//--
		$b64usign = (string) Smart::b64s_enc((string)$signature, false);
		//--
		$valid['error'] 	= ''; // clear err
		$valid['serial'] 	= (string) ($arr[1]['jti'] ?? null);
		$valid['user-name'] = (string) $user;
		$valid['area'] 		= (string) $area;
		$valid['ip-list'] 	= (string) $iplist;
		$valid['priv']  	= (string) $privs;
		$valid['restr'] 	= (string) $restr;
		$valid['xtras'] 	= (string) $xtras;
		$valid['sign'] 		= (string) $b64usign;
		$valid['token'] 	= (string) $token.'.'.$b64usign; // this is the token, but if encrypted it is the decrypted plain version
		$valid['json-arr']['valid'] 	= true;
		$valid['json-arr']['sign-ok'] 	= true;
		$valid['json-arr']['head'] 		= (array)  $arr[0];
		$valid['json-arr']['body'] 		= (array)  $arr[1];
		//--
		return (array) $valid;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Detects if a token may be valid as JWT Format for Display (Pretty Print)
	 * IMPORTANT:
	 *  - this will only validate the format, will not try to decode it to see if the token is a real JWT
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING $jwtToken 			A Token (JWT or not ...)
	 * @return BOOL 					will return TRUE if JWT format detected ; FALSE otherwise or empty
	 */
	public static function jwt_valid_format(?string $jwtToken) : bool {
		//--
		$jwtToken = (string) trim((string)$jwtToken);
		if((string)$jwtToken == '') {
			return false;
		} //end if
		//--
		if(
			(
				((int)strlen((string)$jwtToken) >= 48)
				AND
				((int)strlen((string)$jwtToken) <= 4096)
			)
			AND
			(
				(strpos((string)$jwtToken, 'ey') === 0) // smartgo
				OR
				(strpos((string)$jwtToken, 'ew') === 0) // others
			)
			AND
			(strpos((string)$jwtToken, '.') !== 0) // must not start with a dot
			AND
			(strpos((string)$jwtToken, '.') !== false)
			AND
			(strpos((string)strrev((string)$jwtToken), '.') !== 0) // must not end with a dot
			AND
			(!!preg_match((string)Smart::REGEX_SAFE_B64S_STR, (string)$jwtToken))
		) {
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Format for Display a JWT Token, Pretty Print
	 * IMPORTANT:
	 *  - if the token is empty will return an empty string (mandatory to preserve empty tokens as empty)
	 *  - if the token is not detected to be JWT will return a pretty print too
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING $jwtToken 				A Token (JWT or not ...)
	 * @param BOOL $returnEmptyIfNotJwt 	Default is FALSE ; if set to TRUE if the token is NOT JWT will return an empty string
	 * @return STRING 						JSON expanded and readable if JWT ; original token string if not
	 */
	public static function jwt_token_display(?string $jwtToken, bool $returnEmptyIfNotJwt=false) : string {
		//--
		$jwtToken = (string) trim((string)$jwtToken);
		if((string)$jwtToken == '') {
			return ''; // mandatory to return empty string if already empty ; this is a requirement to avoid display that a token is set if actually is empty for the environments where the token string is pretty print with this method !
		} //end if
		//--
		$lenToken = (int) strlen((string)$jwtToken);
		//--
		if(self::jwt_valid_format((string)$jwtToken) === true) {
			//--
			$jwtsign = '';
			//--
			$jwtToken = (array) explode('.', (string)$jwtToken, 3); // {{{SYNC-EXPLODE-DOT-JWT}}}
			if((int)Smart::array_size($jwtToken) == 3) {  // {{{SYNC-EXPLODE-DOT-JWT-PARTS}}}
				$jwtsign = (string) trim((string)end($jwtToken));
				array_pop($jwtToken); // remove last element, that is the binary signature
			} //end if
			$expiresAt = 0;
			$issuedAt = 0;
			$notBefore = 0;
			$isHeader = true;
			$bodyParsed = false;
			foreach($jwtToken as $kk => $vv) {
				$jwtToken[(string)$kk] = Smart::json_decode((string)Smart::b64s_dec((string)$vv, true)); // mixed ; B64 STRICT
				if(($isHeader === false) && ($bodyParsed === false)) {
					if(is_array($jwtToken[(string)$kk])) {
						if(isset($jwtToken[(string)$kk]['exp'])) {
							if(is_int($jwtToken[(string)$kk]['exp'])) {
								$expiresAt = (int) $jwtToken[(string)$kk]['exp'];
							} //end if
						} //end if
						if(isset($jwtToken[(string)$kk]['iat'])) {
							if(is_int($jwtToken[(string)$kk]['iat'])) {
								$issuedAt = (int) $jwtToken[(string)$kk]['iat'];
							} //end if
						} //end if
						if(isset($jwtToken[(string)$kk]['nbf'])) {
							if(is_int($jwtToken[(string)$kk]['nbf'])) {
								$notBefore = (int) $jwtToken[(string)$kk]['nbf'];
							} //end if
						} //end if
					} //end if
					$bodyParsed = true; // set to true after 2nd segment
				} //end if
				$isHeader = false; // set to false after 1st segment
			} //end foreach
			//--
			if((string)$jwtsign != '') {
				$jwtToken[] = [
					'JWT:signature' => (string) ((int)strlen((string)$jwtsign)).' bytes',
				];
			} //end if
			//--
			$jwtMetaInfo = [
				'JWT:size' => (string) ((int)$lenToken).' bytes',
			];
			//--
			if((int)$expiresAt > 0) {
				$jwtMetaInfo['JWT:expiresAt'] = (string) date('Y-m-d H:i:s', (int)$expiresAt).' UTC';
			} //end if
			if((int)$issuedAt > 0) {
				$jwtMetaInfo['JWT:issuedAt'] = (string) date('Y-m-d H:i:s', (int)$issuedAt).' UTC';
			} //end if
			if((int)$notBefore > 0) {
				$jwtMetaInfo['JWT:notBefore'] = (string) date('Y-m-d H:i:s', (int)$notBefore).' UTC';
			} //end if
			//--
			$jwtToken[] = (array) $jwtMetaInfo;
			//--
			$jwtToken = (string) Smart::json_encode((array)$jwtToken, true, true, false);
			//--
		} else {
			//--
			if($returnEmptyIfNotJwt === false) {
				//--
				$nonJwtToken = [
					'Token' 		=> (string) Smart::text_cut_by_limit((string)$jwtToken, 15, true, '...'),
					'Token:Type' 	=> 'Opaque / Non-JWT',
					'Token:Size' 	=> (int) (string) ((int)$lenToken).' bytes',
				];
				//--
				$jwtToken = (string) Smart::json_encode((array)$nonJwtToken, true, true, false);
				//--
			} else {
				//--
				$jwtToken = '';
				//--
			} //end if
			//--
		} //end if else
		//--
		return (string) $jwtToken;
		//--
	} //END FUNCTION
	//================================================================


	//##### DEBUG ONLY


	//================================================================
	/**
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function registerInternalCacheToDebugLog() : void {
		//--
		if(SmartEnvironment::ifInternalDebug()) {
			if(SmartEnvironment::ifDebug()) {
				$tmpAuthData = (array) self::$AuthData;
				$tmpAuthData['SESS-RAND-KEY'] = '*******'; // protect the key !
				SmartEnvironment::setDebugMsg('extra', '***SMART-CLASSES:INTERNAL-CACHE***', [
					'title' => 'SmartAuth // Internal Cached Vars',
					'data' => 'Dump of AuthCompleted: ['.print_r(self::$AuthCompleted,1).']'."\n".'Dump of AuthData:'."\n".print_r($tmpAuthData,1)
				]);
			} //end if
		} //end if
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
