#!/bin/sh

#####
# Update Repos: SF, SFM, (App) Site
# version: 2019-03-06 18:03:00
# This script is used by AppCodePack
# (c) 2018-2019 unix-world.org
#####

echo "=== Updating Repositories: site ; sf ; sfm ... ==="

if [ ! -d repos/ ]; then
	echo "=== FAIL: repos/ Directory does not exists ==="
	exit 1
fi
cd repos/

if [ ! -d site/ ]; then
	echo "=== FAIL: repos/site/ Directory does not exists ==="
	exit 2
fi
cd site/
echo "##### SVN ### Update (App) Site @@@ Tag: STABLE #####"
svn st
svn --no-auth-cache --username readonly --password readonly up
#svn up
THE_EXIT_CODE=$?
if [ ${THE_EXIT_CODE} != 0 ]; then
	echo "=== FAIL (${THE_EXIT_CODE}): SVN UPDATE ERROR ==="
	exit ${THE_EXIT_CODE}
fi
svn st
cd ..
echo ""

if [ ! -d sf/ ]; then
	echo "=== FAIL: repos/sf/ Directory does not exists ==="
	exit 3
fi
cd sf/
echo "##### GIT ### Update Smart.Framework @@@ HEAD #####"
git status
git pull
THE_EXIT_CODE=$?
if [ ${THE_EXIT_CODE} != 0 ]; then
	echo "=== FAIL (${THE_EXIT_CODE}): GIT UPDATE ERROR ==="
	exit ${THE_EXIT_CODE}
fi
git status
cd ..
echo ""

if [ ! -d sfm/ ]; then
	echo "=== FAIL: repos/sfm/ Directory does not exists ==="
	exit 3
fi
cd sfm/
echo "##### GIT ### Update Smart.Framework.Modules @@@ HEAD #####"
git status
git pull
THE_EXIT_CODE=$?
if [ ${THE_EXIT_CODE} != 0 ]; then
	echo "=== FAIL (${THE_EXIT_CODE}): GIT UPDATE ERROR ==="
	exit ${THE_EXIT_CODE}
fi
git status
cd ..
echo ""

echo "=== Done. ==="
exit 0

#END
