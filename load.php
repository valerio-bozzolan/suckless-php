<?php
# Copyright (C) 2015, 2018 Valerio Bozzolan
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

/*
 * Start the framework
 */

// URL slash
defined('_')  or define('_', '/');

// Directory separator
defined('__') or define('__', DIRECTORY_SEPARATOR);

define('BOZ_PHP', __DIR__ ); // Here
require BOZ_PHP . __ . 'functions.php'; // Some functions

// Start stopwatch
get_page_load();

// Lot of constants with default values
defined('DEBUG')                    or define('DEBUG', false);
defined('PROTOCOL')                 or define('PROTOCOL', URL_protocol() );
defined('DOMAIN')                   or define('DOMAIN', URL_domain() );
defined('PORT')                     or define('PORT', URL_port() );
defined('ROOT')                     or define('ROOT', URL_root() );
defined('URL')                      or define('URL', PROTOCOL . DOMAIN . PORT . ROOT );
defined('SHOW_EVERY_SQL')           or define('SHOW_EVERY_SQL', false);
defined('CHMOD_WRITABLE_DIRECTORY') or define('CHMOD_WRITABLE_DIRECTORY', 0777);
defined('CHMOD_WRITABLE_FILE')      or define('CHMOD_WRITABLE_FILE', 0666);
defined('CHARSET')                  or define('CHARSET', 'utf-8');
defined('CACHE_BUSTER')             or define('CACHE_BUSTER', '');

// On demand requests class-$php ... it's f****** amazing!
spl_autoload_register( function($c) {
	$path = BOZ_PHP . __ . "class-$c.php";
	if( is_file( $path ) ) {
		require $path;
	}
} );

// This is a really stupid thing but it's f****** amazing!
$GLOBALS['G'] = new G();
$GLOBALS['G']->add('db',          'DB');
$GLOBALS['G']->add('mimeTypes',   'MimeTypes');
$GLOBALS['G']->add('javascript',  'RegisterJS');
$GLOBALS['G']->add('css',         'RegisterCSS');
$GLOBALS['G']->add('session',     'Session');
$GLOBALS['G']->add('permissions', 'Permissions');
$GLOBALS['G']->add('menu',        'Menu');
$GLOBALS['G']->add('module',      'RegisterModule');
$GLOBALS['G']->add('registerLanguage', 'RegisterLanguage');

if( ! defined( 'REQUIRE_LOAD_POST' ) ) {
	if( defined( 'ABSPATH' ) ) {
		define( 'REQUIRE_LOAD_POST', ABSPATH . __ . 'load-post.php' );
	} else {
		error_die( __( "Devi definire la costante ABSPATH. Oppure disabilita REQUIRE_LOAD_POST." ) );
	}
}

if( REQUIRE_LOAD_POST ) {
	require REQUIRE_LOAD_POST;
}
