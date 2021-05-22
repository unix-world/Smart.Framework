#!/bin/sh

#####
# build the appcode unpack
# version: 20210521
# (c) 2013-2021 unix-world.org
#####

THE_STANDALONE_PHP_INIT_FILE="./appcodeunpack/appcodeunpack-init.php"
THE_STANDALONE_PHP_FILE="./appcodeunpack/appcodeunpack.php"

#######

add_php_file_to_standalone_php() {
	if [[ "$#" -ne 1 ]]; then
		echo "ERROR: Invalid Arguments for: add_php_file_to_standalone_php"
		exit 1
	fi
	fpath="../${1}"
	if [ ! -f "${fpath}" ]; then
		echo "ERROR: PHP File NOT Found: ${fpath}"
		exit 2
	fi
	echo "Adding PHP File: ${fpath}"
	echo "// ####### [ PHP FILE: ${fpath} ] #######" >> ${THE_STANDALONE_PHP_FILE}
	echo "" >> ${THE_STANDALONE_PHP_FILE}
	cat "${fpath}" | sed -E 's/^(<\?php)$/\/\/ \# php code start/' | sed -E 's/^(\/\/( |    )+\[@\[#\[!NO-STRIP!\]#\]@\])$/\/\/ nostrip tag/' >> ${THE_STANDALONE_PHP_FILE}
	echo "" >> ${THE_STANDALONE_PHP_FILE}
}

add_plain_file_to_standalone_php() {
	if [[ "$#" -ne 1 ]]; then
		echo "ERROR: Invalid Arguments for: add_plain_file_to_standalone_php"
		exit 3
	fi
	fpath="../${1}"
	if [ ! -f "${fpath}" ]; then
		echo "ERROR: File NOT Found: ${fpath}"
		exit 4
	fi
	echo "Adding File: ${fpath}"
	cat "${fpath}" >> ${THE_STANDALONE_PHP_FILE}
	echo "" >> ${THE_STANDALONE_PHP_FILE}
}

echo ""

if [ ! -f "../etc/appcodepack/appcodeunpack-init.php" ]; then
	echo "ERROR: File NOT Found: appcodeunpack-init.php"
	exit 5
fi
cat "../etc/appcodepack/appcodeunpack-init.php" > ${THE_STANDALONE_PHP_INIT_FILE}

echo "===== [START]: ${THE_STANDALONE_PHP_FILE}"

echo "<?php" > ${THE_STANDALONE_PHP_FILE}
echo "" >> ${THE_STANDALONE_PHP_FILE}
echo "// #START: ${THE_STANDALONE_PHP_FILE}" >> ${THE_STANDALONE_PHP_FILE}
echo "" >> ${THE_STANDALONE_PHP_FILE}
echo "const APP_CUSTOM_LOG_PATH = '#APPCODE-UNPACK#/'; // {{{SYNC-APPCODEUNPACK-FOLDER}}}" >> ${THE_STANDALONE_PHP_FILE}
echo "require('appcodeunpack-init.php');" >> ${THE_STANDALONE_PHP_FILE}
echo "" >> ${THE_STANDALONE_PHP_FILE}

for f in smart-error-handler.php lib_unicode.php lib_security.php lib_registry.php lib_smart.php lib_crypto.php lib_cryptos.php lib_filesys.php lib_http_cli.php lib_auth.php lib_valid_parse.php lib_utils.php lib_caching.php lib_templating.php ; do
	add_php_file_to_standalone_php "lib/framework/${f}"
done

add_php_file_to_standalone_php "modules/mod-app-release/lib/AppNetUnPackager.php"

echo "const APPCODEUNPACK_HTML_ERRTPL = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "modules/mod-app-release/appcodeunpack/appcodeunpack-tpl-err.htm"
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_HTML_TPL = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "modules/mod-app-release/appcodeunpack/appcodeunpack-tpl.htm"
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_BASE_STYLES = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/core/templates/base-html-styles.inc.htm"
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_TOOLKIT_STYLES = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
echo "<style>" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/css/toolkit/ux-toolkit.css"
add_plain_file_to_standalone_php "lib/css/toolkit/ux-toolkit-responsive.css"
echo "</style>" >> ${THE_STANDALONE_PHP_FILE}
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_DEFAULT_STYLES = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
echo "<style>" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "etc/templates/default/styles.css"
echo "</style>" >> ${THE_STANDALONE_PHP_FILE}
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_NOTIFICATION_STYLES = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
echo "<style>" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/core/css/notifications.css"
echo "</style>" >> ${THE_STANDALONE_PHP_FILE}
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_LOCAL_STYLES = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "modules/mod-app-release/views/partials/app-release-styles.inc.htm"
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_JS_JQUERY = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
echo "<script>" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/js/jquery/jquery.js"
echo "</script>" >> ${THE_STANDALONE_PHP_FILE}
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_JS_SMART_UTILS = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
echo "<script>" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/js/framework/src/core_utils.js"
echo "</script>" >> ${THE_STANDALONE_PHP_FILE}
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_JS_SMART_DATE = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
echo "<script>" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/js/framework/src/date_utils.js"
echo "</script>" >> ${THE_STANDALONE_PHP_FILE}
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_JS_SMART_CRYPTO = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
echo "<script>" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/js/framework/src/crypt_utils.js"
echo "</script>" >> ${THE_STANDALONE_PHP_FILE}
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_CSS_GRITTER = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
echo "<style>" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/js/jquery/growl/jquery.gritter.css"
echo "</style>" >> ${THE_STANDALONE_PHP_FILE}
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_JS_GRITTER = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
echo "<script>" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/js/jquery/growl/jquery.gritter.js"
echo "</script>" >> ${THE_STANDALONE_PHP_FILE}
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_CSS_ALERTABLE = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
echo "<style>" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/js/jquery/jquery.alertable.css"
echo "</style>" >> ${THE_STANDALONE_PHP_FILE}
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_JS_ALERTABLE = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
echo "<script>" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/js/jquery/jquery.alertable.js"
echo "</script>" >> ${THE_STANDALONE_PHP_FILE}
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_LOGO_SVG = <<<'SVG'" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "modules/mod-app-release/views/img/appcodeunpack.svg"
echo "SVG;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_LOGO_APACHE_SVG = <<<'SVG'" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/framework/img/apache-logo.svg"
echo "SVG;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_LOGO_PHP_SVG = <<<'SVG'" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/framework/img/php-logo.svg"
echo "SVG;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_LOGO_NETARCH_SVG = <<<'SVG'" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/framework/img/netarch-logo.svg"
echo "SVG;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_LOGO_SF_SVG = <<<'SVG'" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/framework/img/sf-logo.svg"
echo "SVG;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_LOADING_SVG = <<<'SVG'" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/framework/img/loading-cylon.svg"
echo "SVG;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_HTML_WATCH = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "lib/core/templates/canvas-clock.inc.htm"
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_JS_LOCAL_FX = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
echo "<script>" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "modules/mod-app-release/appcodeunpack/appcodeunpack-functions.js"
echo "</script>" >> ${THE_STANDALONE_PHP_FILE}
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_CSS_LOCAL_FX = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
echo "<style>" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "modules/mod-app-release/appcodeunpack/appcodeunpack-styles.css"
echo "</style>" >> ${THE_STANDALONE_PHP_FILE}
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

echo "const APPCODEUNPACK_HTML_DEPLOY = <<<'HTML'" >> ${THE_STANDALONE_PHP_FILE}
add_plain_file_to_standalone_php "modules/mod-app-release/appcodeunpack/appcodeunpack-tpl-deploy.inc.htm"
echo "HTML;" >> ${THE_STANDALONE_PHP_FILE}

add_php_file_to_standalone_php "modules/mod-app-release/appcodeunpack/appcodeunpack-app.php"

echo "" >> ${THE_STANDALONE_PHP_FILE}
echo "// #END: ${THE_STANDALONE_PHP_FILE}" >> ${THE_STANDALONE_PHP_FILE}
echo "" >> ${THE_STANDALONE_PHP_FILE}

echo "===== [DONE]"
echo ""

#END

