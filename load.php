<?php
/*
 * Copyright (C) 2015 Valerio Bozzolan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * Start the CMS
 */

defined('DEBUG') || define('DEBUG', false);
defined('SHOW_EVERY_SQL') || define('SHOW_EVERY_SQL', false);

defined('_') || define('_', DIRECTORY_SEPARATOR); // Pathname slash
if(!defined('ROOT')) { // Request installation pathname (after the domain name) e.g.: /cms
	if( dirname( $_SERVER['PHP_SELF'] ) === _ ) {
		define('ROOT', '');
	} else {
		define('ROOT', dirname( $_SERVER['PHP_SELF'] ));
	}
}
defined('REQUIRE_LOAD_POST') || define('REQUIRE_LOAD_POST', true);
defined('CHMOD_WRITABLE_DIRECTORY') || define('CHMOD_WRITABLE_DIRECTORY', 0777);
defined('CHMOD_WRITABLE_FILE')|| define('CHMOD_WRITABLE_FILE', 0666);
defined('MAGIC_MIME_FILE') || define('MAGIC_MIME_FILE', null); // Fifo system default
defined('USE_DB_OPTIONS') || define('USE_DB_OPTIONS', true);
defined('CHARSET') || define('CHARSET', 'utf-8');
defined('PASSWD_HASH_ALGO') || define('PASSWD_HASH_ALGO', 'sha1'); // Just something
defined('PASSWD_HASH_SALT') || define('PASSWD_HASH_SALT', 'drGth'); // Just something
defined('PASSWD_HASH_PEPP') || define('PASSWD_HASH_PEPP', 'pw72kP'); // Just something

define('BOZ_PHP', __DIR__ );

// Sbabababam!
require BOZ_PHP . '/functions.php';
require BOZ_PHP . '/class-menu.php';
require BOZ_PHP . '/class-mimetypes.php';
require BOZ_PHP . '/class-file-uploader.php';
require BOZ_PHP . '/class-db.php';
require BOZ_PHP . '/class-permissions.php';
require BOZ_PHP . '/class-register-js-css.php';
require BOZ_PHP . '/class-register-module.php';
require BOZ_PHP . '/class-session.php';
require BOZ_PHP . '/class-html.php';

// Start stopwatch
get_page_load();

// Dependents from functions above ↑
defined('PROTOCOL') || define('PROTOCOL', get_protocol());
defined('DOMAIN') || define('DOMAIN', get_domain());
defined('URL') || define('URL', get_site_root());

// Test the database connection (or die!)
$GLOBALS['db'] = new DB(@$username, @$password, @$location, @$database, @$prefix);
unset($username, $password, $location, $database, $prefix);

// Important global vars
$GLOBALS['mimeTypes'] = new MimeTypes();
$GLOBALS['javascript'] = new RegisterJavascriptLibs();
$GLOBALS['css'] = new RegisterCSSLibs();
$GLOBALS['module'] = new RegisterModule();
$GLOBALS['permissions'] = new Permissions();

// Related to DB options

/**
 * @deprecated
 */
define('URL_', append_dir_to_URL(URL)); // Same as 'URL' but forced to have a slash ('/')

// @see is_allowed_mimetype() @ functions.php
register_mimetypes(
	'image',
	array(
		'image/jpeg' => array('jpg', 'jpeg'),
		'image/png' => 'png',
		'image/gif' => 'gif'
	)
);
register_mimetypes(
	'audio',
	array(
		'audio/x-flac' => 'flac',
		'audio/ogg' => 'ogg',
		'audio/vorbis' => 'ogg',
		'audio/vorbis-config' => 'ogg',
		'audio/mpeg' => 'mp3',
		'audio/MPA' => 'mp4',
		'audio/mpa-robust' => 'mp4'
	)
);
register_mimetypes(
	'video',
	array(
		'video/mp4' => 'mp4',
		'application/ogg' => 'ogg'
	)
);
register_mimetypes(
	'document',
	array(
		'application/pdf' => 'pdf',
		'application/x-pdf' => 'pdf',
		'application/x-bzpdf' => 'pdf',
		'application/x-gzpdf' => 'pdf',
		'application/vnd.oasis.opendocument.text' => 'odt',
		'application/vnd.oasis.opendocument.presentation' => 'odp',
		'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
		'application/vnd.oasis.opendocument.graphics' => 'odg',
		'application/msword' => 'doc',
		'application/vnd.ms-excel' => 'xls',
		'application/vnd.ms-powerpoint' => 'ppt',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => array('docx', 'xlsx', 'pptx')
	)
);

register_module('theme-header');
register_module('theme-footer');

// Append to the theme future scripts and styles
inject_in_module('theme-header', function(){
	$GLOBALS['css']->enqueue_all();
	$GLOBALS['javascript']->enqueue_all( JavascriptLib::HEADER );
});
inject_in_module('theme-footer', function() {
	$GLOBALS['javascript']->enqueue_all( JavascriptLib::FOOTER );
});

$GLOBALS['menu'] = new Menu();

// Callback
if( REQUIRE_LOAD_POST ) {
	defined('ABSPATH') || die("ABSPATH is not specified.");

	require ABSPATH . '/load-post.php';
}
