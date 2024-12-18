<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// App Net Packager
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
 * Packager for Software Releases
 *
 * DEPENDS:
 * SmartUnicode::
 * Smart::
 * SmartUtils::
 * SmartFileSysUtils::
 * SmartFileSystem::
 * SmartHashCrypto::
 *
 * AppNetUnPackager::
 *
 * @depends: PHP ZLIB extension
 *
 * @access 		private
 * @internal
 *
 */
final class AppNetPackager {

	// ->
	// v.20231106

	public const APP_NET_PACKAGER_VERSION = 'z.20231106'; // {{{SYNC-SF-APPCODE-PACK-UNPACK-PACKAGE-VERSION}}}

	//--
	private $error_log = '';
	//--
	private $appid = '';
	private $comment = '';
	private $optimizations_dir = '';
	private $arch_dir = '';
	private $arch_content = '';
	private $archive_file = '';
	private $archive_name = '';
	private $num_dirs = 0;
	private $num_files = 0;
	private $date_time = '';
	private $arr_folders = [];
	private $arr_files = [];
	//--


	//=====================================================================================
	public function __construct() {
		//--
		$this->init_clear();
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	public function start(?string $appid, ?string $y_dir, ?string $y_archive_name, ?string $y_date_time_markup, ?string $comment='') : void {
		//--
		$this->init_clear();
		//--
		$test_err_appid = (string) AppNetUnPackager::unpack_valid_app_id((string)APPCODEPACK_APP_ID);
		if((string)$test_err_appid != '') {
			$this->error_log = 'APP ID ERROR: '.$test_err_appid;
			return;
		} //end if
		$this->appid = (string) $appid;
		//--
		$this->comment = (string) trim((string)$comment);
		if((string)$this->comment == '') {
			$this->comment = '-';
		} else {
			$this->comment = (string) SmartUnicode::deaccent_str((string)$this->comment);
			if(strlen((string)$this->comment) > 255) {
				$this->comment = (string) substr((string)$this->comment, 0, 255);
			} //end if
		} //end if
		$this->date_time = (string) $y_date_time_markup;
		$this->arch_dir = (string) SmartFileSysUtils::addPathTrailingSlash((string)$y_dir);
		$this->archive_name = (string) Smart::safe_filename((string)$y_archive_name);
		$this->archive_file = (string) Smart::safe_pathname((string)$this->arch_dir.$this->archive_name);
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$this->arch_dir);
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$this->archive_file);
		//--
		if(!SmartFileSystem::is_type_dir($this->arch_dir)) {
			SmartFileSystem::dir_create($this->arch_dir);
			if(!SmartFileSystem::is_type_dir($this->arch_dir)) {
				$this->error_log = 'ERROR: Could not create destination dir !';
				return;
			} //end if
		} //end if
		//--
		if(SmartFileSystem::is_type_file($this->archive_file)) {
			SmartFileSystem::delete($this->archive_file);
		} //end if
		//--
		if(SmartFileSystem::path_exists($this->archive_file)) {
			$this->error_log = 'ERROR: OLD Archive is still present / Could not remove it !';
			return;
		} //end if
		//--
		if((string)$this->arch_dir == '') {
			$this->error_log = 'Packager // Empty Folder Name !';
			return;
		} //end if
		//--
		if(!SmartFileSystem::is_type_dir($this->arch_dir)) {
			$this->error_log = 'Packager // Inexistent Folder !';
			return;
		} //end if
		//--
		$test = SmartFileSystem::write(
			(string) $this->archive_file,
			'' // empty init content
		);
		if($test != 1) {
			$this->error_log = 'Packager // Failed to initialize the new archive file !';
			return;
		} //end if
		//--
		$this->arch_content .= '#[AppCodePack-Package//START]'."\n";
		$this->arch_content .= '#AppCodePack-Version: '.$this->conform_column(self::APP_NET_PACKAGER_VERSION)."\n";
		$this->arch_content .= '#Package-Date: '.$this->conform_column($this->date_time)."\n";
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	public function get_archive_file_name() : string {
		//--
		return (string) $this->archive_name;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	public function get_archive_file_path() : string {
		//--
		return (string) $this->archive_file;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	public function pack_dir(?string $y_folder) : void {
		//--
		if((string)$this->error_log == '') {
			$this->optimizations_dir = (string) $y_folder;
			$this->dir_recursive_pack((string)$y_folder);
		} //end if
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	public function save() : string {
		//--
		if((string)$this->error_log != '') {
			return 'AppCodePack.Packager / Save :: Packaging # '.$this->error_log;
		} //end if
		//--
		if(((string)trim((string)$this->appid) == '') OR (!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$this->appid))) {
			return 'AppID is empty or invalid: '.$this->appid;
		} //end if
		//--
		$this->arch_content .= '#Folders: '.$this->conform_column($this->num_dirs)."\n";
		$this->arch_content .= '#Files: '.$this->conform_column($this->num_files)."\n";
		$this->arch_content .= '#[AppCodePack-Package//END]'."\n";
		//--
		$packet = @gzencode((string)$this->arch_content, 8, FORCE_GZIP); // don't make it string, may return false
		if(($packet === false) OR ((string)$packet == '')) { // if error
			return 'AppCodePack.Packager / Save :: ZLib Deflate ERROR ! ... # '.$this->error_log;
		} //end if
		$len_data = strlen((string)$this->arch_content);
		$len_arch = strlen((string)$packet);
		if(($len_data > 0) AND ($len_arch > 0)) {
			$ratio = $len_data / $len_arch;
		} else {
			$ratio = 0;
		} //end if
		if($ratio <= 0) { // check for empty input / output !
			return 'AppCodePack.Packager / Save :: ZLib Data Ratio is zero ! ... # '.$this->error_log;
		} //end if
		if($ratio > 32768) { // check for this bug in ZLib {{{SYNC-GZ-ARCHIVE-ERR-CHECK}}}
			return 'AppCodePack.Packager / Save :: ZLib Data Ratio is higher than 32768 ! ... # '.$this->error_log;
		} //end if
		//--
		$ver_zlib = (string) phpversion('zlib');
		//--
		$packet = (string) base64_encode((string)$packet);
		//--
		$data  = '';
		//--
		$data .= '#AppCodePack-NetArchive'."\n";
		$data .= '#AppCodePack-MetaInfo: '.$this->conform_column((string)self::APP_NET_PACKAGER_VERSION.' * z:gzenc.8.gzip * e:base64 * c:sha3')."\n";
		$data .= '#AppCodePack-License: '.$this->conform_column('(c) 2013-'.date('Y').' <unix-world.org> :: [License BSD] # {Smart.Framework}')."\n";
		$data .= '#Comment: '.$this->conform_column((string)rawurlencode((string)$this->comment))."\n";
		$data .= '#File: '.$this->conform_column((string)$this->archive_name)."\n";
		$data .= '#App-ID: '.$this->conform_column((string)$this->appid)."\n";
		$data .= '#Package-Date: '.$this->conform_column((string)str_replace(' ', 'T', (string)$this->date_time).'Z'.date('O'))."\n";
		$data .= '#Package-Sources-Dir: '.$this->conform_column((string)$this->optimizations_dir)."\n";
		$data .= '#Package-Info-Items: '.$this->conform_column((string)($this->num_dirs + $this->num_files))."\n";
		$data .= '#Package-Info-Dirs: '.$this->conform_column((string)$this->num_dirs)."\n";
		$data .= '#Package-Info-Files: '.$this->conform_column((string)$this->num_files)."\n";
		$data .= '#Package-Signature:'.$this->conform_column((string)SmartHashCrypto::sh3a384((string)$this->appid."\v".$packet, true))."\n"; // {{{SYNC-APP-PAK-CKSUM}}}
		$data .= '#Checksum-Signature:'.$this->conform_column((string)SmartHashCrypto::sh3a512((string)$this->arch_content, true))."\n"; // {{{SYNC-APP-PAK-CONTENT-CKSUM}}}
		$data .= (string) $this->conform_column((string)$packet)."\n";
		$data .= '#PHP-Version: '.$this->conform_column((string)PHP_VERSION.' / ZLib: '.(string)$ver_zlib)."\n";
		$data .= '#Client-IP: '.$this->conform_column((string)SmartUtils::get_ip_client())."\n";
		$data .= '#END-NetArchive';
		//--
		$packet = ''; // free mem
		//--
		$test = SmartFileSystem::write(
			(string) $this->archive_file,
			(string) $data
		);
		if($test != 1) {
			$this->error_log = 'ERROR: Failed to save data to the Archive File !';
		} elseif(!SmartFileSystem::is_type_file($this->archive_file)) {
			$this->error_log = 'ERROR: Archive File could not be found !';
		} //end if
		//--
		if((string)$this->error_log != '') {
			return 'AppCodePack.Packager / Save :: Check # '.$this->error_log;
		} //end if
		//--
		$data = '';
		//--
		$out = (string) trim((string)$this->error_log);
		$saved_arch_fpath = (string) $this->get_archive_file_path();
		$this->init_clear(); // free mem
		//--
		if((string)$out == '') {
			$out = (string) AppNetUnPackager::unpack_netarchive((string)SmartFileSystem::read((string)$saved_arch_fpath), true); // test only
			$out = (string) trim((string)$out);
			if((string)$out != '') {
				$out = 'UNPACK Test Errors: '.$out;
			} //end if
		} //end if
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	private function init_clear() : void {
		//--
		clearstatcache(true); // do a full clear stat cache at the begining
		//--
		$this->error_log = '';
		//--
		$this->appid = '';
		$this->comment = '';
		$this->optimizations_dir = '';
		$this->arch_dir = '';
		$this->arch_content = '';
		$this->archive_file = '';
		$this->archive_name = '';
		$this->num_dirs = 0;
		$this->num_files = 0;
		$this->date_time = '';
		$this->arr_folders = [];
		$this->arr_files = [];
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	private function dir_pack(?string $tmp_path) : string {
		//--
		$out = '';
		//--
		$tmp_path = (string) $tmp_path;
		//--
		if((string)trim((string)$tmp_path) == '') {
			$this->error_log = 'ERROR: Empty Dir Name to Pack !';
			return '';
		} //end if
		if(!SmartFileSysUtils::checkIfSafePath((string)$tmp_path)) {
			$this->error_log = 'ERROR: Invalid Dir Name to Pack: '.$tmp_path;
			return '';
		} //end if
		//--
		$fixed_path = (string) $this->path_take_out_opt_folder($tmp_path);
		if((string)$fixed_path == '') {
			$this->error_log = 'ERROR: Invalid Dir Path to Pack: '.$tmp_path;
			return '';
		} //end if
		//--
		$base_dir = (string) Smart::dir_name((string)$fixed_path);
		$base_sdir = (string) Smart::base_name((string)$fixed_path);
		if((string)substr((string)$base_sdir, 0, 1) == '#') {
			if(!SmartFileSystem::is_type_file((string)SmartFileSysUtils::addPathTrailingSlash((string)$tmp_path).'.htaccess')) {
				$this->error_log = 'ERROR: Protected Dir Security: Missing `.htaccess` in: `'.$fixed_path.'`';
				return '';
			} //end if
		} //end if
		echo '<b>[D]&nbsp;'.Smart::escape_html((string)(($base_dir == '.') ? '' : $base_dir.'/').$base_sdir).'/'.'</b><br>'."\n";
		echo (string) '<script>setTimeout(() => { smartJ$Browser.windwScrollDown(self, -1); }, 150);</script>'."\n";
		Smart::InstantFlush();
		//--
		$this->arr_folders[] = $tmp_path;
		//--
		if(SmartFileSystem::is_type_dir($tmp_path)) { // dir
			//--
			$this->num_dirs += 1;
			//--
			$cksum_name 	= (string) SmartHashCrypto::sha224((string)$fixed_path, true); // {{{SYNC-APP-PAK-DIR-CKSUM}}}
			$tmp_size 		= '0';
			$file_content 	= '';
			$cksum_file 	= '';
			$cksum_arch 	= '';
			//-- dirname[\t]DIR[\t]0[\t]sha224checksumName[\t][\t][\t][\n]
			$out .= $this->conform_column((string)$fixed_path)."\t";
			$out .= $this->conform_column('DIR')."\t";
			$out .= $this->conform_column('0')."\t";
			$out .= $this->conform_column((string)$cksum_name)."\t";
			$out .= "\t";
			$out .= "\t";
			$out .= "\n";
			//--
		} else {
			//--
			$this->error_log = 'ERROR: Invalid Dir to Pack: '.$tmp_path;
			return '';
			//--
		} //end if
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	private function file_pack(?string $tmp_path) : string {
		//--
		$out = '';
		//--
		$tmp_path = (string) $tmp_path;
		//--
		if((string)trim((string)$tmp_path) == '') {
			$this->error_log = 'ERROR: Empty File Name to Pack !';
			return '';
		} //end if
		if(!SmartFileSysUtils::checkIfSafePath((string)$tmp_path)) {
			$this->error_log = 'ERROR: Invalid File Name to Pack: '.$tmp_path;
			return '';
		} //end if
		//--
		$fixed_path = (string) $this->path_take_out_opt_folder($tmp_path);
		if((string)$fixed_path == '') {
			$this->error_log = 'ERROR: Invalid File Path to Pack: '.$tmp_path;
			return '';
		} //end if
		//--
		$base_dir = (string) Smart::dir_name((string)$fixed_path);
		$base_file = (string) Smart::base_name((string)$fixed_path);
		echo (string) '[F]&nbsp;'.Smart::escape_html((string)$base_dir.'/'.$base_file).'<br>'."\n";
		Smart::InstantFlush();
		//--
		$this->arr_files[] = (string) $tmp_path;
		//--
		if(SmartFileSystem::is_type_file((string)$tmp_path)) { // file
			//--
			$this->num_files += 1;
			//--
			$the_fsize = (int) filesize((string)$tmp_path);
			//--
			$cksum_name 	= (string) SmartHashCrypto::sh3a224((string)$fixed_path, true); // {{{SYNC-APP-PAK-FILEPATH-CKSUM}}}
			$tmp_type 		= 'FILE';
			$tmp_size 		= (string) $the_fsize;
			$file_content 	= (string) SmartFileSystem::read($tmp_path); // this reads and return the file as it is
			if((int)strlen((string)$file_content) !== (int)$the_fsize) {
				$this->error_log = 'ERROR: Invalid FileSize ['.$the_fsize.'] to Pack !'.'<br>'.Smart::escape_html((string)$tmp_path);
				return '';
			} //end if
			$cksum_file 	= (string) SmartHashCrypto::sha256((string)$file_content, true); // {{{SYNC-APP-PAK-FILECONTENT-CKSUM}}}
			$file_content 	= (string) bin2hex((string)$file_content);
			$cksum_arch 	= (string) SmartHashCrypto::sh3a256((string)$file_content, true); // {{{SYNC-APP-PAK-FILEARCH-CKSUM}}}
			//-- filename[\t]filetype[\t]filesize[\t]sh3a224b64checksumName[\t]sha256b64checksumFileContent[\t]sh3a256b64checksumArch[\t]filecontent_gzencode-FORCE_GZIP_bin2hex[\n]
			$out .= $this->conform_column((string)$fixed_path)."\t";
			$out .= $this->conform_column((string)$tmp_type)."\t";
			$out .= $this->conform_column((string)$tmp_size)."\t";
			$out .= $this->conform_column((string)$cksum_name)."\t";
			$out .= $this->conform_column((string)$cksum_file)."\t";
			$out .= $this->conform_column((string)$cksum_arch)."\t";
			$out .= $this->conform_column((string)$file_content)."\n";
			//--
		} else {
			//--
			$this->error_log = 'ERROR: Invalid File to Pack: '.$tmp_path;
			return '';
			//--
		} //end if else
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	private function path_take_out_opt_folder(?string $tmp_path) : string {
		//--
		return (string) SmartFileSysUtils::addPathTrailingSlash((string)$this->appid).ltrim((string)substr((string)$tmp_path, (int)strlen((string)$this->optimizations_dir)), '/');
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	// recursive function to copy a folder with all sub folders and files
	private function dir_recursive_pack(?string $dirsource) : void {

	//===================================
	// WARNING: Should Not Copy Destination inside Source to avoid Infinite Loop (anyway there is a loop protection but it is not safe as we don't know if all files were copied) !!!
	// WARNING: Last two params SHOULD NOT be used (they are private to remember the initial dirs...)
	//=================================== Must not end in Slash !!!
	// $dirsource = 'some/folder/one';
	//===================================

	//--
	if((string)$this->error_log != '') {
		return;
	} //end if
	//--

	//-- protection
	SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dirsource);
	//--

	//--
	if((int)strlen((string)$dirsource) <= 0) {
		$this->error_log = 'Packager // ERROR: The Archive FileName and Source DirName must not be empty !';
		return;
	} //end if
	//--

	//--
	if(!SmartFileSystem::is_type_dir((string)$dirsource)) {
		$this->error_log = 'Packager // ERROR: Source is not a Dir ! \''.$dirsource.'\'';
		return;
	} //end if else
	//--

	//--
	if($handle = opendir((string)$dirsource)) {
		//--
		$this->arch_content .= $this->dir_pack((string)$dirsource);
		//--
		if((string)$this->error_log == '') {
			//--
			while(false !== ($file = readdir($handle))) {
				//--
				if(
					((string)$file != '') AND ((string)$file != '.') AND ((string)$file != '..') AND ((string)$file != '/') AND
					(AppNetUnPackager::unpack_valid_file_name((string)$file) === true)
				) { // fix empty
					//--
					$tmp_path = SmartFileSysUtils::addPathTrailingSlash((string)$dirsource).$file;
					SmartFileSysUtils::raiseErrorIfUnsafePath((string)$tmp_path);
					//--
					if(SmartFileSystem::path_exists((string)$tmp_path)) {
						//--
						if(SmartFileSystem::is_type_dir((string)$tmp_path)) {
							$this->dir_recursive_pack((string)$tmp_path);
						} elseif(SmartFileSystem::is_type_file((string)$tmp_path)) {
							$this->arch_content .= $this->file_pack((string)$tmp_path);
						} else {
							$this->error_log = 'Packager // ERROR: A broken Link detected: '.$tmp_path;
						} //end if
						if((string)$this->error_log != '') { // if an error is detected
							break;
						} //end if
						//--
					} else {
						//--
						$this->error_log = 'Packager // ERROR: Invalid File or Dir ! \''.$tmp_path.'\'';
						break;
						//--
					} //end if
					//--
				} //end if
				//--
			} //end while
			//--
		} //end if
		//--
		@closedir($handle);
		//--
	} else {
		//--
		$this->error_log = 'Packager // ERROR: Cannot open the Dir ! \''.$dirsource.'\'';
		//--
	} //end if else
	//--

	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	private function conform_column(?string $y_text) : string {
		//--
		$y_text = (string) Smart::normalize_spaces((string)$y_text);
		$y_text = (string) str_replace(' ', '', (string)$y_text);
		$y_text = (string) trim((string)$y_text);
		//--
		return (string) $y_text;
		//--
	} //END FUNCTION
	//=====================================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
