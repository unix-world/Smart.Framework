#!/bin/sh

# PAK: Combine all required Smart.Framework Javascript Source scripts from lib/js/framework/src/*.js into one package JS file: lib/js/framework/smart-framework.pak.js # r.20250214

THE_FILE=../lib/js/framework/smart-framework.pak.js

echo "Regenerating Smart.Framework Javascript Package: ${THE_FILE}"

echo "" > ${THE_FILE}
echo "// # JS Package: smart-framework.pak.js :: #START# :: @ generated from lib/js/framework/src/*.js" >> ${THE_FILE}
echo "// Included Files: core_utils.js ; date_utils.js ; crypt_utils.js ; arch_utils.js ; ifmodalbox.js ; browser_check.js ; browser_utils.js ; ifmodalbox_scanner.js #" >> ${THE_FILE}
echo "" >> ${THE_FILE}
echo "// ### DO NOT EDIT THIS FILE AS IT WILL BE OVERWRITTEN EACH TIME THE INCLUDED SCRIPTS WILL CHANGE !!! ###" >> ${THE_FILE}
echo "" >> ${THE_FILE}

echo "// ===== core_utils.js" >> ${THE_FILE}
cat ../lib/js/framework/src/core_utils.js >> ${THE_FILE}
echo "" >> ${THE_FILE}

echo "// ===== date_utils.js" >> ${THE_FILE}
cat ../lib/js/framework/src/date_utils.js >> ${THE_FILE}
echo "" >> ${THE_FILE}

echo "// ===== crypt_utils.js" >> ${THE_FILE}
cat ../lib/js/framework/src/crypt_utils.js >> ${THE_FILE}
echo "" >> ${THE_FILE}

echo "// ===== arch_utils.js" >> ${THE_FILE}
cat ../lib/js/framework/src/arch_utils.js >> ${THE_FILE}
echo "" >> ${THE_FILE}

echo "// ===== ifmodalbox.js" >> ${THE_FILE}
cat ../lib/js/framework/src/ifmodalbox.js >> ${THE_FILE}
echo "" >> ${THE_FILE}

echo "// ===== browser_check.js" >> ${THE_FILE}
cat ../lib/js/framework/src/browser_check.js >> ${THE_FILE}
echo "" >> ${THE_FILE}

echo "// ===== browser_utils.js" >> ${THE_FILE}
cat ../lib/js/framework/src/browser_utils.js >> ${THE_FILE}
echo "" >> ${THE_FILE}

echo "// ===== ifmodalbox_scanner.js" >> ${THE_FILE}
cat ../lib/js/framework/src/ifmodalbox_scanner.js >> ${THE_FILE}
echo "" >> ${THE_FILE}

echo "// ===== [#]" >> ${THE_FILE}
echo "" >> ${THE_FILE}
echo "// # JS Package: smart-framework.pak.js :: #END#" >> ${THE_FILE}
echo "" >> ${THE_FILE}

echo "[DONE !]"

# END
