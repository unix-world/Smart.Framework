<?php
// [LIB - Smart.Framework / Authentication Support]
// (c) 2006-2023 unix-world.org - all rights reserved
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
 * @depends 	Smart, SmartEnvironment, SmartCipherCrypto, SmartCryptoCipherBlowfishCBC
 * @version 	v.20231018
 * @package 	@Core:Authentication
 *
 */
final class SmartAuth {

	// ::

	public const REGEX_VALID_PRIV_NAME = '/^[a-z\-]{3,28}$/'; // valid name for one privilege from list of privileges

	public const SWT_VERSION_PREFIX 	= 'SWT'; // {{{SYNC-AUTH-TOKEN-SWT}}}
	public const SWT_VERSION_SUFFIX 	= 'v1.2';
	public const SWT_VERSION_SIGNATURE 	= 'swt:1.2';

	private static $AuthCompleted 	= false;	// prevent re-authentication, ... the results may be unpredictable !!
	private static $AuthData 		= []; 		// register Auth Data


	//================================================================
	/**
	 * Validate an Auth User Name
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
	 * @param 	STRING 	$auth_user_pass  	:: The Auth Password (plain text) to be validated ; min length is 7 ; max length is 88
	 * @param 	BOOL 	$check_complexity 	:: Check for password complexity ; if set to TRUE will allow just complex passwords
	 *
	 * @return 	BOOLEAN						:: TRUE if the password is valid or FALSE if not
	 */
	public static function validate_auth_password(?string $auth_user_pass, bool $check_complexity=false) : bool { // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
		//--
		$check_pass = (string) SmartUnicode::deaccent_str((string)$auth_user_pass);
		$check_pass = (string) Smart::normalize_spaces((string)$auth_user_pass);
		//--
		if(
			((string)trim((string)$auth_user_pass) == '') OR // cannot be empty
			((int)SmartUnicode::str_len((string)$auth_user_pass) < 7) OR ((int)strlen((string)trim((string)$check_pass)) < 7) OR // min length is 7, checked twice against unicode or non-unicode version
			((int)SmartUnicode::str_len((string)$auth_user_pass) > 88) OR ((int)strlen((string)$check_pass) > 88) OR // don't trim the max check
			((string)substr((string)$check_pass, 0, 1) == ' ') OR // cannot start with a space (after space normalizations)
			((string)substr((string)$check_pass, -1, 1) == ' ') OR // cannot end with a space (after space normalizations)
			((int)substr_count((string)$check_pass, ' ') > (int)(floor((int)SmartUnicode::str_len((string)$auth_user_pass) / 3))) // cannot contain more dots than 33% from all characters
		) {
			return false;
		} //end if
		//--
		if($check_complexity !== false) {
			if(
				((int)SmartUnicode::str_len((string)$auth_user_pass) < 8) OR ((int)strlen((string)trim((string)$check_pass)) < 8) OR // min length is 8 for complex passwords, checked twice against unicode or non-unicode version
				(!preg_match('/[A-Z]/', (string)$check_pass)) OR // must have at least one caps letter
				(!preg_match('/[a-z]/', (string)$check_pass)) OR // must have at least one small letter
				(!preg_match('/[0-9]/', (string)$check_pass)) OR // must have at least one digit
				(!preg_match('/[^A-Za-z0-9]/', (string)$check_pass)) // must have at least one special character
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
	 * Set the (in-memory) Auth Login Data
	 * It can be used just once per execution (session) as it stores the data using constants,
	 * and the data cannot be changed after a successful or failed authentication has set.
	 *
	 * @param 	STRING 			$y_realm 					:: *OPTIONAL* The user Authentication Realm(s)
	 * @param 	ENUM 			$y_method 					:: *OPTIONAL* The authentication method used, as description only: HTTP-BASIC / OTHER / ...
	 * @param 	STRING 			$y_pass						:: *OPTIONAL* The user login password hash (will be stored in memory as encrypted to avoid exposure)
	 * @param 	STRING 			$y_user_id 					:: The user (login) ID ; can be the Username or Email (on backend this should be always set with the same value as Username)
	 * @param 	STRING 			$y_user_name				:: The user username ; Mandatory ; must be valid safe username
	 * @param 	STRING 			$y_user_email 				:: *OPTIONAL* The user Email ; if email is used as login ID this may be redundant !
	 * @param 	STRING 			$y_user_fullname 			:: *OPTIONAL* The user Full Name (First Name + Last Name)
	 * @param 	ARRAY/STRING 	$y_user_privileges_list 	:: *OPTIONAL* The user Privileges List as string '<priv-a>,<priv-b>,...' or array ['priv-a','priv-b'] that list all the current user privileges ; a privilege key must have 3..28 characters and can contain only: a-z -
	 * @param   ARRAY/STRING 	$y_user_restrictions_list 	:: *OPTIONAL* The user Restrictions List as string '<restr-a>,<restr-b>,...' or array ['restr-a','restr-b'] that list all the current user restrictions ; a restriction key must have 3..28 characters and can contain only: a-z -
	 * @param 	STRING 			$y_user_quota 				:: *OPTIONAL* The user (storage) Quota
	 * @param 	ARRAY 			$y_user_metadata 			:: *OPTIONAL* The user metainfo, associative array key => value ; Ex: [ 'auth-safe' => 101 ]
	 * @param 	STRING 			$y_keys 					:: *OPTIONAL* The user Private Key (will be stored in memory as encrypted to avoid exposure)
	 *
	 * @return 	BOOLEAN									:: TRUE if all data is OK, FALSE if not or try to reauthenticate under the same execution (which is not allowed ; must be just once per execution)
	 */
	public static function set_login_data(?string $y_realm, ?string $y_method, ?string $y_pass, ?string $y_user_id, ?string $y_user_name, ?string $y_user_email='', ?string $y_user_fullname='', $y_user_privileges_list=['none','no-privilege'], $y_user_restrictions_list=['none','no-restriction'], int $y_user_quota=-1, array $y_user_metadata=[], ?string $y_keys='') : bool {
		//--
		// IMPORTANT: $y_user_privileges_list and $y_user_restrictions_list can be STRING or ARRAY, do not cast !
		// v.20231018
		//--
		if(self::$AuthCompleted !== false) { // avoid to re-auth
			Smart::log_warning(__METHOD__.' # Re-Authentication is not allowed ...');
			return false;
		} //end if
		self::$AuthCompleted = true;
		//--
		self::$AuthData = array(); // reset the auth data
		//--
		$y_realm = (string) strtoupper((string)trim((string)$y_realm));
		if((string)$y_realm == '') {
			$y_realm = 'DEFAULT';
		} //end if
		//--
		$y_user_id = (string) trim((string)$y_user_id);
		if((string)$y_user_id == '') {
			Smart::log_warning(__METHOD__.' # Invalid UserName ...');
			return false;
		} //end if
		$y_user_name = (string) trim((string)$y_user_name);
		if(self::validate_auth_username((string)$y_user_name, false) !== true) {
			Smart::log_warning(__METHOD__.' # Invalid UserName ...');
			return false;
		} //end if
		//--
		$y_pass = (string) trim((string)$y_pass);
		if((int)strlen((string)$y_pass) < (int)SmartHashCrypto::PASSWORD_HASH_LENGTH) { // {{{SYNC-AUTH-HASHPASS-LENGTH}}}
			Smart::log_warning(__METHOD__.' # Invalid Pass Hash ...');
			return false;
		} //end if
		//--
		$y_user_email = (string) trim((string)$y_user_email);
		$y_user_fullname = (string) trim((string)$y_user_fullname);
		//-- {{{SYNC-ARR-BUILD-PRIVS-LIST-TO-ARR}}}
		$arr_privileges = (array) self::safe_arr_privileges_or_restrictions($y_user_privileges_list); // DO NOT CAST ; $y_user_privileges_list can be mixed value (array or string)
		$y_user_privileges_list = (array) array_keys((array)$arr_privileges);
		$arr_privileges = null;
		$y_user_privileges_list = (array) Smart::array_sort((array)$y_user_privileges_list, 'sort');
		//-- #end sync
		$y_user_privileges_list = (string) Smart::array_to_list((array)$y_user_privileges_list);
		//-- ##
		$arr_restrictions = (array) self::safe_arr_privileges_or_restrictions($y_user_restrictions_list); // DO NOT CAST ; $y_user_restrictions_list can be mixed value (array or string)
		$y_user_restrictions_list = (array) array_keys((array)$arr_restrictions);
		$arr_restrictions = null;
		$y_user_restrictions_list = (array) Smart::array_sort((array)$y_user_restrictions_list, 'sort');
		$y_user_restrictions_list = (string) Smart::array_to_list((array)$y_user_restrictions_list);
		//--
		$y_user_quota = Smart::format_number_int($y_user_quota); // can be also negative
		//--
		$the_key = '#'.Smart::random_number(100000000000,999999999999).'#'; // must be at least 7 bytes, have 14 bytes
		//--
		$the_pass = (string) SmartCipherCrypto::encrypt('hash/sha256', (string)$the_key, (string)$y_pass);
		//--
		$the_privkey = '';
		if((string)trim((string)$y_pass) != '') {
			$the_privkey = (string) trim((string)$y_keys);
			if((string)$the_privkey != '') {
				$the_privkey = (string) SmartCipherCrypto::encrypt('hash/sha512', (string)$the_key, (string)$the_privkey);
				if((string)trim((string)$the_privkey) == '') { // be sure is really empty
					$the_privkey = '';
				} //end if else
			} else {
				$the_privkey = '';
			} //end if
		} //end if
		//--
		if((string)$y_user_id != '') {
			//--
			self::$AuthData['AUTH-METHOD'] 			= (string) $y_method;
			self::$AuthData['AUTH-REALM'] 			= (string) $y_realm;
			self::$AuthData['AUTH-ID'] 				= (string) $y_user_id; 		// auth id ; unique ; for the backend this must be always = AUTH-USERNAME ; on frontend (custom development) it can be set as: AUTH-USERNAME or USER-EMAIL depending on needs
			self::$AuthData['AUTH-USERNAME'] 		= (string) $y_user_name; 	// the auth username ; unique
			self::$AuthData['AUTH-PASSHASH'] 		= (string) $the_pass; 		// the hash of the plain pass
			self::$AuthData['USER-EMAIL'] 			= (string) $y_user_email;
			self::$AuthData['USER-FULL-NAME'] 		= (string) $y_user_fullname;
			self::$AuthData['USER-PRIVILEGES'] 		= (string) $y_user_privileges_list;
			self::$AuthData['USER-RESTRICTIONS'] 	= (string) $y_user_restrictions_list;
			self::$AuthData['USER-METADATA'] 		= (array)  $y_user_metadata;
			self::$AuthData['USER-PRIVKEY'] 		= (string) $the_privkey;
			self::$AuthData['USER-QUOTA'] 			= (int)    $y_user_quota;
			self::$AuthData['SESS-RAND-KEY'] 		= (string) $the_key;
			//--
			return true;
			//--
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Check the (in-memory) Auth Login Data if the current user is logged-in
	 *
	 * @return 	BOOLEAN		:: TRUE if current user is Logged-in, FALSE if not
	 */
	public static function check_login() : bool {
		//--
		$logged_in = false;
		//--
		if(array_key_exists('AUTH-ID', self::$AuthData)) {
			if((string)self::$AuthData['AUTH-ID'] != '') {
				if(array_key_exists('AUTH-USERNAME', self::$AuthData)) {
					if((string)self::$AuthData['AUTH-USERNAME'] != '') {
						if(array_key_exists('AUTH-PASSHASH', self::$AuthData)) {
							if((string)self::$AuthData['AUTH-PASSHASH'] != '') {
								$logged_in = true;
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
	 * Get the (in-memory) Auth Login Data
	 *
	 * @return 	ARRAY		:: a complete array containing all the meta-data of the current auth user
	 */
	public static function get_login_data(bool $y_skip_sensitive=false) : array {
		//--
		return [
			'auth:method' 		=> self::get_auth_method(),
			'auth:realm' 		=> self::get_auth_realm(),
			'auth:id' 			=> self::get_auth_id(),
			'auth:username' 	=> self::get_auth_username(),
			'auth:passhash' 	=> ($y_skip_sensitive ? '********[Sensitive:Protected]********' : self::get_auth_passhash()),
			'user:email' 		=> self::get_user_email(),
			'user:full-name' 	=> self::get_user_fullname(),
			'user:privileges' 	=> self::get_user_privileges(),
			'user:restrictions' => self::get_user_restrictions(),
			'user:metadata' 	=> self::get_user_metadata(),
			'user:privkey' 		=> ($y_skip_sensitive ? '........[Sensitive:Protected]........' : self::get_user_privkey()),
			'user:quota' 		=> self::get_user_quota(),
			'status:auth:ok' 	=> self::check_login(),
		];
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
		if(array_key_exists('AUTH-METHOD', self::$AuthData)) {
			return (string) self::$AuthData['AUTH-METHOD'];
		} else {
			return '';
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the auth realm of the current user stored in the (in-memory) Auth Login Data
	 *
	 * @return 	STRING		:: returns the current user auth realm or an empty string if not set
	 */
	public static function get_auth_realm() : string {
		//--
		$login_realm = 'DEFAULT';
		//--
		if(array_key_exists('AUTH-REALM', self::$AuthData)) {
			if((string)self::$AuthData['AUTH-REALM'] != '') {
				$login_realm = (string) strtoupper((string)self::$AuthData['AUTH-REALM']);
			} //end if
		} //end if
		//--
		return (string) $login_realm;
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
		if(!array_key_exists('AUTH-ID', self::$AuthData)) {
			return '';
		} //end if
		//--
		return (string) self::$AuthData['AUTH-ID'];
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
		if(!array_key_exists('AUTH-USERNAME', self::$AuthData)) {
			return '';
		} //end if
		//--
		return (string) self::$AuthData['AUTH-USERNAME'];
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
		} elseif((string)self::$AuthData['AUTH-PASSHASH'] == '') {
			return ''; // empty pass
		} else {
			return (string) SmartCipherCrypto::decrypt('hash/sha256', (string)self::$AuthData['SESS-RAND-KEY'], (string)self::$AuthData['AUTH-PASSHASH']);
		} // end if else
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
		if(!array_key_exists('USER-EMAIL', self::$AuthData)) {
			return '';
		} //end if
		//--
		return (string) self::$AuthData['USER-EMAIL'];
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
		} else {
			return (string) SmartCipherCrypto::decrypt('hash/sha512', (string)self::$AuthData['SESS-RAND-KEY'], (string)self::$AuthData['USER-PRIVKEY']);
		} // end if else
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
		if(!array_key_exists('USER-FULL-NAME', self::$AuthData)) {
			return '';
		} //end if
		//--
		return (string) self::$AuthData['USER-FULL-NAME'];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the list of the current user privileges stored in the (in-memory) Auth Login Data
	 *
	 * @return 	STRING		:: returns user login privileges as a list-string like: '<privilege-one>,<privilege-two>,...' or an empty string if not set
	 */
	public static function get_user_privileges() : string {
		//--
		if(!array_key_exists('USER-PRIVILEGES', self::$AuthData)) {
			return '';
		} //end if
		//--
		return (string) self::$AuthData['USER-PRIVILEGES'];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the list of the current user restrictions stored in the (in-memory) Auth Login Data
	 *
	 * @return 	STRING		:: returns user login restrictions as a list-string like: '<restriction-one>,<restriction-two>,...' or an empty string if not set
	 */
	public static function get_user_restrictions() : string {
		//--
		if(!array_key_exists('USER-RESTRICTIONS', self::$AuthData)) {
			return '';
		} //end if
		//--
		return (string) self::$AuthData['USER-RESTRICTIONS'];
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
	public static function validate_privilege_or_restriction_key(?string $y_key_to_validate) {
		//--
		if((string)trim((string)$y_key_to_validate) == '') {
			return false; // empty
		} //end if
		if((string)trim((string)$y_key_to_validate, '-') != (string)$y_key_to_validate) {
			return false; // cannot contain only - or start/end with a -
		} //end if
		//--
		return (bool) preg_match((string)self::REGEX_VALID_PRIV_NAME, (string)$y_key_to_validate);
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
		$login_quota = -1;
		//--
		if(array_key_exists('USER-QUOTA', self::$AuthData)) {
			if((int)self::$AuthData['USER-QUOTA'] >= 0) {
				$login_quota = (int) self::$AuthData['USER-QUOTA'];
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
		if(!array_key_exists('USER-METADATA', self::$AuthData)) {
			return array();
		} //end if
		//--
		return (array) self::$AuthData['USER-METADATA'];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe Encrypt a private key using a password, using Blowfish CBC
	 * The provided password have to be the same as the login password for the user is being used to avoid decryption of the key by other users
	 * This is completely safe as long as the users login passwords are supposed to be stored as ireversible hashes (by default they are ... but with custom login implementations they can be or not, depending the developer's choice)
	 *
	 * @param 	STRING 	$y_pkey 		:: The private key to be safe encrypted
	 * @param 	STRING 	$y_secret 		:: The encryption secret
	 *
	 * @return 	STRING					:: returns a string with the safe encrypted privacy-key or empty string if was empty
	 */
	public static function encrypt_privkey(?string $y_pkey, ?string $y_secret) : string {
		//--
		if((string)trim((string)$y_pkey) == '') {
			return '';
		} //end if
		$y_secret = (string) $y_secret.chr(0).SmartHashCrypto::crc32b((string)chr(0).$y_secret, true).chr(0).Smart::b64_to_b64s((string)SmartHashCrypto::md5((string)chr(0).$y_secret, true)); // if this is like username and only 3 characters, ensure at least 7 ; {{{SYNC-MIN-KEY-7-ENSURE}}}
		if((int)strlen((string)trim((string)$y_secret)) < 7) { // this is the minimum enforced by SmartCipherCrypto !
			return ''; // return empty string to avoid return it in unencrypted plain format
		} //end if
		//--
		return (string) SmartCipherCrypto::blowfish_encrypt((string)$y_secret, (string)$y_pkey); // {{{SYNC-ADM-AUTH-KEYS}}} ; avoid use smart utils here to avoid circular dependency as smart utils depends on smart auth ; more, keep always the internal blowfish.cbc here and not depend on external openssl blowfish cbc which could change or dissapear
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Decrypt a private key using a password, using Blowfish CBC
	 * The provided password have to be the same as the login password for the user is being used to avoid decryption of the key by other users
	 * This is completely safe as long as the users login passwords are supposed to be stored as ireversible hashes (by default they are ... but with custom login implementations they can be or not, depending the developer's choice)
	 *
	 * @param 	STRING 	$y_pkey 		:: The private key to be decrypted
	 * @param 	STRING 	$y_secret 		:: The encryption secret
	 *
	 * @return 	STRING					:: returns a string with the privacy-key (decrypted, if any, and if valid) which was supposed to be provided as encrypted
	 */
	public static function decrypt_privkey(?string $y_pkey, ?string $y_secret) : string {
		//--
		if((string)trim((string)$y_pkey) == '') {
			return '';
		} //end if
		//--
		$y_secret = (string) $y_secret.chr(0).SmartHashCrypto::crc32b((string)chr(0).$y_secret, true).chr(0).Smart::b64_to_b64s((string)SmartHashCrypto::md5((string)chr(0).$y_secret, true)); // if this is like username and only 3 characters, ensure at least 7 ; {{{SYNC-MIN-KEY-7-ENSURE}}}
		if((int)strlen((string)trim((string)$y_secret)) < 7) { // this is the minimum enforced by SmartCipherCrypto !
			return '';
		} //end if
		//--
		return (string) SmartCipherCrypto::blowfish_decrypt((string)$y_secret, (string)$y_pkey); // {{{SYNC-ADM-AUTH-KEYS}}} ; avoid use smart utils here to avoid circular dependency as smart utils depends on smart auth ; more, keep always the internal blowfish.cbc here and not depend on external openssl blowfish cbc which could change or dissapear
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Build the associative Array of Auth Privileges or Restrictions
	 *
	 * @param 	MIXED 	$y_list 				:: List of Privileges or Restrictions as ARRAY [ 'key-a', 'key-b', ..., 'key-n' ] or STRING '<key-a>, <key-b>, ..., <key-n>'
	 *
	 * @return 	ARRAY							:: returns the associative array of auth Privileges or Restrictions as [ 'key-a' => 'Key A', 'key-b' => 'Key B', ..., 'key-n' => 'Key N' ]
	 */
	public static function safe_arr_privileges_or_restrictions($y_list) : array {
		//--
		if(!is_array($y_list)) {
			$y_list = (array) Smart::list_to_array((string)$y_list);
		} //end if
		//--
		$y_list = (array) $y_list;
		//--
		$out_arr = array();
		for($i=0; $i<Smart::array_size($y_list); $i++) {
			//--
			$y_list[$i] = (string) strtolower((string)trim((string)$y_list[$i]));
			if(self::validate_privilege_or_restriction_key((string)$y_list[$i]) === true) {
				$out_arr[(string)$y_list[$i]] = (string) trim((string)ucwords((string)str_replace('-', ' ', (string)$y_list[$i])));
			} //end if
			//--
		} //end for
		//--
		return (array) $out_arr;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Validate a safe auth SWT (Smart Web Token)
	 * This token can be used for Bearer Authentication
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
	 * - if the Privileges list provided by this method is empty, the default account privileges must be used, otherwise only the privileges provided by this method are available for the given token
	 *
	 * This is a hidden functionality that is not intended to be used directly but via Short Tokens only ...
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 	$token 		:: The SWT Token String
	 * @param 	STRING 	$client_ip 	:: The current client IP Address to be compared and validated with the Token (if token contain an IP Bind) ; must be the current visitor's IP IPv4 or IPv6 for the token is validated for
	 *
	 * @return 	ARRAY				:: array of strings as: [ 'error' => 'error if any or empty', 'user-name' => '...', 'pass-hash' => '...', 'restr-ip' => [], 'restr-priv-arr' => [], 'json-arr' => [...] ]
	 */
	public static function swt_token_validate(?string $token, ?string $client_ip) : array {
		//--
		$valid = [
			'error' 			=> '?', 		// error or empty
			'user-name' 		=> '',  		// auth user name
			'pass-hash' 		=> '',  		// auth pass hash (not reversible)
			'restr-ip' 			=> [], 			// allowed IPs
			'restr-priv-lst' 	=> '<none>', 	// restricted privileges list  ; be sure is non-empty (in case of premature exit), this will be checked later
			'restr-priv-arr' 	=> [ 'none' ],  // restricted privileges array ; be sure is non-empty (in case of premature exit), this will be checked later
			'json-arr' 			=> [],  		// the token json
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
			$valid['error'] = 'Invalid Client IP';
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
		if((string)substr($token, -1 * $len_suffix, $len_suffix) !== ';'.self::SWT_VERSION_SUFFIX) {
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
		$json = (string) Smart::b64s_dec((string)$token);
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
		if((int)Smart::array_type_test($arr) != 2) {
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
			$arr['n'] = (string) trim((string)hex2bin((string)$arr['n']));
		} //end if
		if((string)$arr['n'] != '') {
			$arr['n'] = (string) SmartUnicode::utf8_to_iso((string)$arr['n']); // safety
		} //end if
		if((string)$arr['n'] !== (string)SMART_SOFTWARE_NAMESPACE) {
			$valid['error'] = 'JSON object have an Invalid Realm';
			return (array) $valid;
		} //end if
		//--
		$arr['r'] = (string) trim((string)$arr['r']);
		if((string)$arr['r'] != '') {
			$arr['r'] = (string) trim((string)Smart::base_to_hex_convert((string)$arr['r'], 29*2));
		} //end if
		if((string)$arr['r'] != '') {
			$arr['r'] = (string) trim((string)hex2bin((string)$arr['r']));
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
			$arr['d'] = (string) trim((string)hex2bin((string)$arr['d']));
		} //end if
		if((string)$arr['d'] != '') {
			$arr['d'] = (string) SmartUnicode::utf8_to_iso((string)$arr['d']); // safety
		} //end if
		if((string)$arr['d'] !== '*') {
			if(preg_match((string)SmartValidator::regex_stringvalidation_expression('date-time-tzofs'), (string)$arr['d'])) {// validate date by regex
				$dtnow = (string) gmdate('Y-m-d H:i:s O');
				$dtswt = (string) gmdate('Y-m-d H:i:s O', (int)strtotime((string)$arr['d'])); // be sure is a date, and UTC formatted with +0000
				if((string)$dtnow < (string)$dtswt) { // current date is higher than token date
					// OK
					//Smart::log_notice(__METHOD__.'# OK: `'.$dtnow.'` < `'.$dtswt.'`');
				} else {
					$valid['error'] = 'JSON object have an Invalid Expiration Date';
					return (array) $valid;
				} //end if
			} else {
				$valid['error'] = 'JSON object have an Invalid Expiration Date Format';
				return (array) $valid;
			} //end if else
		} //end if
		//--
		$auth = (array) explode("\n", (string)trim((string)$arr['a']), 3);
		$username = (string) trim((string)($auth[0] ?? null));
		$hashpass = (string) trim((string)($auth[1] ?? null));
		//--
		if((string)$username != '') {
			$username = (string) trim((string)Smart::base_to_hex_convert((string)$username, 17*5));
		} //end if
		if((string)$username != '') {
			$username = (string) trim((string)hex2bin((string)$username));
		} //end if
		if((string)$username != '') {
			$username = (string) SmartUnicode::utf8_to_iso((string)$username); // safety
		} //end if
		//--
		if((string)$hashpass != '') {
			$hashpass = (string) trim((string)base64_decode((string)$hashpass));
		} //end if
		if((string)$hashpass != '') {
			$hashpass = (string) SmartUnicode::utf8_to_iso((string)$hashpass); // safety
		} //end if
		if((string)$hashpass != '') {
			$hashpass = (string) trim((string)Smart::base_to_hex_convert((string)$hashpass, 23*2*2));
		} //end if
		if((string)$hashpass != '') {
			$hashpass = (string) trim((string)hex2bin((string)$hashpass));
		} //end if
		if((string)$hashpass != '') {
			$hashpass = (string) SmartUnicode::utf8_to_iso((string)$hashpass); // safety
		} //end if
		if((string)$hashpass != '') {
			$hashpass = (string) str_pad((string)$hashpass, (int)SmartHashCrypto::PASSWORD_HASH_LENGTH, '*', STR_PAD_RIGHT); // {{{SYNC-AUTHADM-PASS-PADD}}} ; {{{SYNC-AUTH-HASHPASS-LENGTH}}}
		} //end if
		//--
		if(
			((int)strlen((string)$hashpass) < 64)
			OR
			((int)strlen((string)$hashpass) > 256)
		) { // {{{SYNC-PASS-HASH-LENGTH-VALIDATE}}}
			$valid['error'] = 'Invalid Password Hash';
			return (array) $valid;
		} //end if else
		//--
		if(self::validate_auth_username((string)$username, false) !== true) {
			$valid['error'] = 'Invalid Username';
			return (array) $valid;
		} //end if
		//--
		$arr['p'] = (string) trim((string)$arr['p']);
		if((string)$arr['p'] != '') {
			$arr['p'] = (string) trim((string)Smart::base_to_hex_convert((string)$arr['p'], 23*4));
		} //end if
		if((string)$arr['p'] != '') {
			$arr['p'] = (string) trim((string)hex2bin((string)$arr['p']));
		} //end if
		if((string)$arr['p'] != '') {
			$arr['p'] = (string) SmartUnicode::utf8_to_iso((string)$arr['p']); // safety
		} //end if
		if((string)$arr['p'] === '*') { // {{{SYNC-SWT-IMPLEMENT-PRIVILEGES}}}
			$valid['restr-priv-lst'] = '*';
			$valid['restr-priv-arr'] = []; // reset ; this is mandatory as it has pre-defined as none in case of premature exit
		} else {
			//-- {{{SYNC-ARR-BUILD-PRIVS-LIST-TO-ARR}}}
			$arr_privileges = (array) self::safe_arr_privileges_or_restrictions((string)$arr['p']);
			$arr_privileges = (array) array_keys((array)$arr_privileges);
			$arr_privileges = (array) Smart::array_sort((array)$arr_privileges, 'sort');
			//-- #end sync
			if((int)Smart::array_size($arr_privileges) <= 0) {
				$valid['error'] = 'JSON object have an Invalid Privileges list';
				return (array) $valid;
			} //end if
			$valid['restr-priv-lst'] = (string) $arr['p'];
			$valid['restr-priv-arr'] = (array) $arr_privileges;
			$arr_privileges = null;
		} //end if
		//--
		$arr['i'] = (string) trim((string)$arr['i']);
		if((string)$arr['i'] != '') {
			$arr['i'] = (string) trim((string)Smart::b64s_dec((string)$arr['i']));
		} //end if
		if((string)$arr['i'] != '') {
			$arr['i'] = (string) SmartUnicode::utf8_to_iso((string)$arr['i']); // safety
		} //end if
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
			$valid['error'] = 'JSON object have an Invalid IP List'; // empty or invalid IPs list is not supported !
			return (array) $valid;
		} //end if
		if(
			(!in_array((string)$client_ip, (array)$arr_ips))
			OR // double check: in array and in list
			(stripos((string)$arr['i'], '<'.$client_ip.'>') === false)
		) { // {{{SYNC-IPV6-STORE-SHORTEST-POSSIBLE}}} ; IPV6 addresses may vary .. find a standard form, ex: shortest
			$valid['error'] = 'JSON object IP List does not contain the Client IP';
			return (array) $valid;
		} //end if
		$valid['restr-ip'] = (array) $arr_ips;
		$arr_ips = null;
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
	 * This token can be used for Bearer Authentication
	 * The password is stored as a hash and cannot be reversed
	 *
	 * If the SWT Token failed to be created because the provided parameters are invalid, an error message and an empty json / token is returned
	 *
	 * This is a hidden functionality that is not intended to be used directly but via Short Tokens only ...
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 	$realm 					:: 'I' for idx (FE) ; 'A' for adm/tsk (BE)
	 * @param 	STRING 	$auth_user_name 		:: The user name
	 * @param 	STRING 	$auth_hash_pass 		:: The irreversible hash of the password ; NEVER provide here the Plain Text Password !!
	 * @param 	INT		$expire 				:: The expiration time in seconds from now
	 * @param 	ARRAY 	$ip_addr_arr 			:: IP Addresses List [ip1, ip2, ...] (cannot be empty ! must have at least one entry to be validated) ; SWT Tokens IP address bind is mandatory for security reasons !
	 * @param 	ARRAY 	$privs_arr 				:: Privileges List [ priv1, priv2, ... ] (or empty, to allow default) ; these will intersect with existing privileges and will result a list of privileges that are appearing in both: user's privileges and swt token, thus the privileges cannot be overriden !
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
		switch((string)$realm) {
			case 'I': // idx
			case 'A': // adm / tsk ; http cli must be able to access both areas, the restriction between adm/tsk will be made by Task IP List not here, as a password or token should be the same for adm/tsk, thus also the swt token restr.
				break;
			default:
				$swt['error'] = 'Auth Realm is Invalid';
				return (array) $swt;
		} //end switch
		//--
		if((int)$expire < 0) {
			$swt['error'] = 'Expiration is Invalid';
			return (array) $swt;
		} //end if
		//--
		if(self::validate_auth_username((string)$auth_user_name, false) !== true) {
			$swt['error'] = 'Username is Invalid';
			return (array) $swt;
		} //end if
		//--
		if((int)strlen((string)$auth_hash_pass) != (int)SmartHashCrypto::PASSWORD_HASH_LENGTH) { // {{{SYNC-AUTH-HASHPASS-LENGTH}}}
			$swt['error'] = 'Password Hash is Invalid';
			return (array) $swt;
		} //end if
		//--
		$expdate = '*'; // no expiration
		if((int)$expire > 0) {
			$expdate = (string) gmdate('Y-m-d H:i:s O', (int)strtotime((string)gmdate('Y-m-d H:i:s').' +'.(int)$expire.' seconds')); // UTC
		} //end if
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
			$ip_addr_list = '<>'; // fix ; if no valid IPs list, use a non-empty string ; leave here like this to be able to test invalid IPs List !
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
		$obfs_hash_pass 	= (string) base64_encode((string)Smart::base_from_hex_convert((string)bin2hex((string)rtrim((string)$auth_hash_pass, '*').'*'), 23*2*2));
		$obfs_privs_lst 	= (string) Smart::base_from_hex_convert((string)bin2hex((string)$privs_list), 23*4);
		$obfs_ip_adr_lst 	= (string) Smart::b64s_enc((string)$ip_addr_list); // b64s
		//--
		$arr = [
			'#' => (string) self::SWT_VERSION_SIGNATURE, 			// Meta: metainfo (signature)
			'n' => (string) $obfs_nspace, 							// Info: namespace (4 .. 63 chars)
			'r' => (string) $obfs_realm, 							// Info realm: idx | adm (adm is for both: adm/tsk)
			'd' => (string) $obfs_expdt, 							// Date: expiration date UTC or * (no expiration)
			'a' => (string) $obfs_user_name."\n".$obfs_hash_pass, 	// Auth: auth data: user-id \n pass-hash[rtrim:*]* {{{SYNC-AUTHADM-PASS-PADD}}}
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
		$b64s = (string) Smart::b64s_enc((string)$json);
		if((string)trim((string)$b64s) == '') {
			$swt['error'] = 'Base64S encoding Failed';
			return (array) $swt;
		} //end if
		//--
		$cksign = (string) SmartHashCrypto::checksum(
			(string) self::SWT_VERSION_PREFIX.';'.$b64s.';'.self::SWT_VERSION_SUFFIX,
			'' // default (empty), will use a derivation of SMART_FRAMEWORK_SECURITY_KEY
		);
		//--
		$swt['error'] = ''; // clear
		$swt['json']  = (string) $json;
		$swt['token'] = (string) self::SWT_VERSION_PREFIX.';'.$b64s.';'.$cksign.';'.self::SWT_VERSION_SUFFIX;
		return (array) $swt;
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
