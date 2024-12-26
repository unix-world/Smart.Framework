<?php
// [LIB - Smart.Framework / Webdav / Library Utils]
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Webdav\DavUtils
// Type: Module Library

namespace SmartModExtLib\Webdav;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================

/**
 * Dav Utils
 * @ignore
 */
final class DavUtils {

	// ::
	// v.20240116


	//============================================================
	public static function getFolderIcon(?string $path) : string {
		//--
		$icon = 'folder.svg';
		//--
		return (string) $icon;
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	public static function getFileIcon(?string $path) : string {
		//--
		$icon = 'file'.self::getFileTypeSuffixIcon($path).'.svg';
		//--
		return (string) $icon;
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	public static function getFileTypeSuffixIcon(?string $path) : string {
		//--
		$suffix = (string) self::getFileTypeIcon($path);
		if((string)$suffix != '') {
			$suffix = '-'.$suffix;
		} //end if
		//--
		return (string) $suffix;
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	public static function getFileTypeIcon(?string $path) : string {
		//--
		$file = (string) \SmartFileSysUtils::extractPathFileName((string)$path);
		//--
		if(\in_array((string)\strtolower((string)$file), [
			'.htaccess',
			'.htpasswd'
		])) {
			return 'webinf';
		} //end if
		//--
		if(\in_array((string)\strtolower((string)$file), [
			'makefile',
			'cmake',
			'meson.build'
		])) {
			return 'mk';
		} //end if
		//--
		if(\in_array((string)\strtolower((string)$file), [
			'license',
			'readme',
			'changelog'
		])) {
			return 'text';
		} //end if
		//--
		$ext = (string) \SmartFileSysUtils::extractPathFileExtension((string)$path);
		$type = '';
		switch((string)\strtolower((string)$ext)) {
			case 'text': // text
			case 'txt': // text
			case 'log': // log file
				$type = 'text';
				break;
			case 'md5':
			case 'sha1':
			case 'sha256':
			case 'sha512':
				$type = 'checksum';
				break;
			case 'vala': // Vala
			case 'vapi': // Vala Vapi
			case 'deps': // Vala Deps
				$type = 'vala';
				break;
			case 'cs': // C#
			case 'csproj':
			case 'xaml':
				$type = 'cs';
				break;
			case 'c': // C
			case 'y': // Yacc source code file
			case 'cpp': // C++
			case 'ypp': // Bison source code file
			case 'cxx': // C++
			case 'yxx': // Bison source code file
			case 'm': // Objective-C Method
			case 'pro': // QT project file
				$type = 'c';
				break;
			case 'h': // C header
			case 'hpp': // C++ header
			case 'hxx': // C++ header
				$type = 'h';
				break;
			case 'kml':
			case 'kmz':
			case 'gpx':
				$type = 'map';
				break;
			case 'swift':
				$type = 'swift';
				break;
			case 'lua':
				$type = 'lua';
				break;
			case 'md':
			case 'markdown':
				$type = 'md';
				break;
			case 'yaml':
			case 'yml':
				$type = 'yaml';
				break;
			case 'svg':
				$type = 'svg';
				break;
			case 'xhtml':
			case 'xml':
			case 'xsl':
			case 'dtd':
			case 'glade': // gnome glade
			case 'ui': // qt ui
				$type = 'xml';
				break;
			case 'htm':
			case 'html':
			case 'tpl':
			case 'mtpl':
			case 'twist':
			case 'twig':
			case 't3fluid':
				$type = 'html';
				break;
			case 'css':
			case 'less':
			case 'scss':
			case 'sass':
				$type = 'css';
				break;
			case 'ini':
			case 'cfg':
			case 'conf':
				$type = 'config';
				break;
			case 'po': // source
			case 'pot': // source template
			case 'mo': // binary
				$type = 'po'; // gettext translations
				break;
			case 'json':
				$type = 'json';
				break;
			case 'jsx':
			case 'ts':
			case 'typescript':
			case 'cofee':
			case 'cofeescript':
				$type = 'jsx';
				break;
			case 'js':
			case 'javascript':
				$type = 'js';
				break;
			case 'php':
				$type = 'php';
				break;
			case 'go':
				$type = 'go';
				break;
			case 'pl':
			case 'pm':
				$type = 'perl';
				break;
			case 'py':
				$type = 'python';
				break;
			case 'pem':
			case 'gpg':
			case 'asc':
				$type = 'certificate';
				break;
			case 'java':
			case 'jsp':
				$type = 'java';
				break;
			case 'sql':
				$type = 'sql';
				break;
			case 'sqlite':
				$type = 'sqlite';
				break;
			case 'db':
			case 'dba':
				$type = 'db';
				break;
			case 'png':
			case 'gif':
			case 'jpg':
			case 'jpe':
			case 'jpeg':
			case 'webp':
				$type = 'image';
				break;
			case 'xcf':
			case 'psd':
			case 'heic':
			case 'bmp':
			case 'wmf':
			case 'tif':
			case 'tiff':
				$type = 'photo';
				break;
			case 'ogv': // theora video
			case 'webm': // google vp8
			case 'mp4':
				$type = 'video';
				break;
			case 'mpeg':
			case 'mpg':
			case 'mpe':
			case 'mpv':
			case 'mov':
			case 'avi':
			case 'wm':
			case 'wmv':
			case 'wmx':
			case 'wvx':
				$type = 'movie';
				break;
			case 'aac':
			case 'aif':
			case 'aifc':
			case 'aiff':
			case 'mp2':
			case 'mp3':
			case 'mp4a':
			case 'mid':
			case 'midi':
				$type = 'audio';
				break;
			case 'pdf':
				$type = 'pdf';
				break;
			case 'eps':
			case 'ps':
				$type = 'eps';
				break;
			case 'ico':
			case 'icns':
				$type = 'icns';
				break;
			case 'ttf':
				$type = 'ttf';
				break;
			case 'woff':
			case 'woff2':
				$type = 'woff';
				break;
			case '7z':
			case 'zip':
			case 'tbz':
			case 'bz2':
			case 'tgz':
			case 'gz':
			case 'xz':
			case 'z':
			case 'tar':
			case 'rar':
				$type = 'archive';
				break;
			case 'csh': // C-Shell script
			case 'sh':  // shell script
			case 'awk': // AWK script
			case 'cmd': // windows command file
			case 'bat': // windows batch file
			case 'tcl': // tcl script
				$type = 'shell';
				break;
			case 'rb':
				$type = 'ruby';
				break;
			case 'exe':
			case 'dll':
			case 'com':
			case 'bin':
			case 'pyc':
				$type = 'bin';
				break;
			case 'app':
			case 'apk':
			case 'pkg':
			case 'deb':
			case 'rpm':
			case 'dmg':
			case 'msi':
			case 'jar':
				$type = 'pkg';
				break;
			case 'csv':
			case 'tab':
				$type = 'csv';
				break;
			case 'eml':
			case 'msg':
				$type = 'eml';
				break;
			case 'ical':
			case 'ics':
				$type = 'ical';
				break;
			case 'icard':
			case 'vcard':
			case 'vcf':
				$type = 'icard';
				break;
			case 'rtf':
			case 'odt':
			case 'fodt':
			case 'sxw':
			case 'ott':
			case 'stw':
			case 'otm':
			case 'oth':
			case 'doc':
			case 'dot':
			case 'docx':
			case 'dotx':
				$type = 'odt';
				break;
			case 'gnumeric':
			case 'ods':
			case 'fods':
			case 'sxc':
			case 'ots':
			case 'stc':
			case 'xla':
			case 'xlc':
			case 'xlm':
			case 'xls':
			case 'xlt':
			case 'xlw':
			case 'xlsx':
			case 'xltx':
				$type = 'ods';
				break;
			case 'odp':
			case 'fodp':
			case 'sxi':
			case 'otp':
			case 'sti':
			case 'pot':
			case 'pps':
			case 'ppt':
			case 'potx':
			case 'ppsx':
			case 'pptx':
				$type = 'odp';
				break;
			case 'odg':
			case 'fodg':
			case 'sxd':
				$type = 'odg';
				break;
			case 'otf':
				$type = 'otf'; // math formula
				break;
			case 'odb':
			case 'mdb':
				$type = 'odb';
				break;
			case 'dxf':
			case 'dwg':
			case 'dwf':
				$type = 'cad';
				break;
			case 'obj':
			case 'stl':
			case 'jscad':
				$type = '3d';
				break;
			default:
				$type = ''; // not recognized or icon n/a
		} //end if
		//--
		return (string) $type;
		//--
	} //END FUNCTION
	//============================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
