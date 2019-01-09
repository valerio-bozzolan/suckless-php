<?php
# Copyright (C) 2015, 2018, 2019 Valerio Bozzolan
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
 * Start the boz-php framework
 */

// URL slash
defined('_')  or define('_', '/');

// directory separator
defined('__') or define('__', DIRECTORY_SEPARATOR);

// boz-php directory
define('BOZ_PHP', __DIR__ );

// load the functions
require BOZ_PHP . __ . 'functions.php';

// start stopwatch
get_page_load();

// default anonymous user role
define_default( 'DEFAULT_USER_ROLE', 'UNREGISTERED' );

// in debug mode you see errors in HTML
define_default( 'DEBUG', false );

// log every SQL query and eventually print them in HTML whenever DEBUG is also enabled
define_default( 'DEBUG_QUERIES', false );

// HTTP protocol e.g. 'https'
define_default( 'PROTOCOL', URL_protocol() );

// domain name e.g. 'reyboz.it'
define_default( 'DOMAIN', URL_domain() );

// HTTP port e.g. ':8080'
define_default( 'PORT', URL_port() );

// absolute URL directory without trailing slash e.g. '/myapp' or ''
define_default( 'ROOT', URL_root() );

// URL without the ROOT
define_default( 'BASE_URL', PROTOCOL . DOMAIN . PORT );

// URL with the ROOT
define_default( 'URL', BASE_URL . ROOT );

// default permissions for new directories
define_default( 'CHMOD_WRITABLE_DIRECTORY', 0777 );

// default permissions for new files
define_default( 'CHMOD_WRITABLE_FILE', 0666 );

// request charset
define_default( 'CHARSET', 'utf-8' );

// trailing JavaScript / CSS parameter e.g. 'v=1' (will be automatically prefixed with &/?)
define_default( 'CACHE_BUSTER', '' );

// autoload boz-php classes
spl_autoload_register( function( $c ) {
	$path = BOZ_PHP . __ . "class-$c.php";
	if( is_file( $path ) ) {
		require $path;
	}
} );

$GLOBALS['G'] = new G();
$GLOBALS['G']->add('mimeTypes',   'MimeTypes');
$GLOBALS['G']->add('javascript',  'RegisterJS');
$GLOBALS['G']->add('css',         'RegisterCSS');
$GLOBALS['G']->add('permissions', 'Permissions');
$GLOBALS['G']->add('menu',        'Menu');
$GLOBALS['G']->add('module',      'RegisterModule');
$GLOBALS['G']->add('registerLanguage', 'RegisterLanguage');

if( ! defined( 'REQUIRE_LOAD_POST' ) ) {
	if( defined( 'ABSPATH' ) ) {
		define( 'REQUIRE_LOAD_POST', ABSPATH . __ . 'load-post.php' );
	} else {
		error_die( "Please define ABSPATH or at least disable REQUIRE_LOAD_POST" );
	}
}

if( REQUIRE_LOAD_POST ) {
	require REQUIRE_LOAD_POST;
}
