#!/usr/bin/env bash

# Keep files in a plugin-specific directory to not step over other plugin tests
TMP_DIR="/tmp/datafeedr-product-sets"

if [ -d "$TMP_DIR" ]; then
  echo "Temporary directory exists."
else
  mkdir $TMP_DIR
fi

DB_NAME=${1-datafeedr}
DB_USER=${2-root}
DB_PASS=${3-root}
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}
WP_TESTS_DIR=${WP_TESTS_DIR-$TMP_DIR/wordpress-tests-lib/}
WP_CORE_DIR=${WP_CORE_DIR-$TMP_DIR/wordpress/}

download() {
    if [ `which curl` ]; then
        curl -s "$1" > "$2";
    elif [ `which wget` ]; then
        wget -nv -O "$2" "$1"
    fi
}

if [[ $WP_VERSION =~ [0-9]+\.[0-9]+(\.[0-9]+)? ]]; then
	WP_TESTS_TAG="tags/$WP_VERSION"
elif [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
	WP_TESTS_TAG="trunk"
else
	# http serves a single offer, whereas https serves multiple. we only want one
	download http://api.wordpress.org/core/version-check/1.7/ $TMP_DIR/wp-latest.json
	grep '[0-9]+\.[0-9]+(\.[0-9]+)?' $TMP_DIR/wp-latest.json
	LATEST_VERSION=$(grep -o '"version":"[^"]*' $TMP_DIR/wp-latest.json | sed 's/"version":"//')
	if [[ -z "$LATEST_VERSION" ]]; then
		echo "Latest WordPress version could not be found"
		exit 1
	fi
	WP_TESTS_TAG="tags/$LATEST_VERSION"
fi

echo "VARIABLES:"
echo "TMP_DIR= $TMP_DIR"
echo "DB_NAME= $DB_NAME"
echo "DB_USER= $DB_USER"
echo "DB_PASS= $DB_PASS"
echo "DB_HOST= $DB_HOST"
echo "WP_VERSION= $WP_VERSION"
echo "SKIP_DB_CREATE= $SKIP_DB_CREATE"
echo "WP_TESTS_DIR= $WP_TESTS_DIR"
echo "WP_CORE_DIR= $WP_CORE_DIR"

set -e

install_wp() {

	if [ -d $WP_CORE_DIR ]; then
		return;
	fi

  set -x
	mkdir -p $WP_CORE_DIR

	if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
		mkdir -p $TMP_DIR/wordpress-nightly
		download https://wordpress.org/nightly-builds/wordpress-latest.zip  $TMP_DIR/wordpress-nightly/wordpress-nightly.zip
		unzip -q $TMP_DIR/wordpress-nightly/wordpress-nightly.zip -d$TMP_DIR/wordpress-nightly/
		mv $TMP_DIR/wordpress-nightly/wordpress/* $WP_CORE_DIR
	else
		if [ $WP_VERSION == 'latest' ]; then
			local ARCHIVE_NAME='latest'
		else
			local ARCHIVE_NAME="wordpress-$WP_VERSION"
		fi
		download https://wordpress.org/${ARCHIVE_NAME}.tar.gz  $TMP_DIR/wordpress.tar.gz
		tar --strip-components=1 -zxmf $TMP_DIR/wordpress.tar.gz -C $WP_CORE_DIR
	fi

	download https://raw.github.com/markoheijnen/wp-mysqli/master/db.php $WP_CORE_DIR/wp-content/db.php
  set +x
}

install_test_suite() {

  set -x

	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i .bak'
	else
		local ioption='-i'
	fi

	# set up testing suite if it doesn't yet exist
	if [ ! -d $WP_TESTS_DIR ]; then
		# set up testing suite
		mkdir -p $WP_TESTS_DIR
		svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/ $WP_TESTS_DIR/includes
		svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/ $WP_TESTS_DIR/data
	fi

	if [ ! -f wp-tests-config.php ]; then
		download https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php "$WP_TESTS_DIR"/wp-tests-config.php
		# remove all forward slashes in the end
		WP_CORE_DIR=$(echo $WP_CORE_DIR | sed "s:/\+$::")
		sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR"/wp-tests-config.php
	fi
  set +x
}

install_db() {

	if [ ${SKIP_DB_CREATE} = "true" ]; then
		return 0
	fi

  set -x
	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] ; then
		if [ $(echo $DB_SOCK_OR_PORT | grep -e '^[0-9]\{1,\}$') ]; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [ -z $DB_SOCK_OR_PORT ] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# create database
	mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA
	set +x
}



install_wp
echo "(1/3) WordPress download complete."

install_test_suite
echo "(2/3) Test suite installed."

install_db
echo "(3/3) Database installed."