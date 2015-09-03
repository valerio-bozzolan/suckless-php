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

if(!defined('ABSPATH')) {
	die("ABSPATH is not specified");
}

if(!defined('ROOT')) { // Request installation pathname (after the domain name) e.g.: /cms
	define('ROOT', dirname( $_SERVER['PHP_SELF'] ));
}
if(!defined('DEBUG')) {
	define('DEBUG', false);
}
if(!defined('_')) { // Pathname slash
	define('_', DIRECTORY_SEPARATOR);
}
if(!defined('CHMOD_WRITABLE_DIRECTORY')) {
	define('CHMOD_WRITABLE_DIRECTORY', 0777);
}
if(!defined('CHMOD_WRITABLE_FILE')) {
	define('CHMOD_WRITABLE_FILE', 0666);
}

if( ! isset($GLOBALS['IMAGE_EXTENSIONS']) ) {
	$GLOBALS['IMAGE_EXTENSIONS'] = array(
		'gif', 'jpg', 'jpef', 'png', 'svg'
	);
}

if( ! isset($GLOBALS['VIDEO_EXTENSIONS']) ) {
	$GLOBALS['VIDEO_EXTENSIONS'] = array(
		'ogg', 'mp4', 'avi'
	);
}

if( ! isset($GLOBALS['AUDIO_EXTENSIONS']) ) {
	$GLOBALS['AUDIO_EXTENSIONS'] = array(
		'flac', 'mp3', 'ogg'
	);
}

// Default "constants"
if( ! isset($GLOBALS['ALLOWED_UPLOAD_EXTENSIONS']) ) {
	$GLOBALS['ALLOWED_UPLOAD_EXTENSIONS'] = array_merge(
		$GLOBALS['IMAGE_EXTENSIONS'],
		$GLOBALS['VIDEO_EXTENSIONS'],
		$GLOBALS['AUDIO_EXTENSIONS'],
		array(
			'doc', 'docx', 'odp', 'ods', 'odt', 'pdf', 'ppt', 'pptx', 'xls', 'xlsx'
		)
	);
}

if( ! isset($GLOBALS['UPLOAD_ALLOWED_MIME_TYPES']) ) {
	$GLOBALS['ALLOWED_UPLOAD_MIME_TYPES'] = array(
		'jpg' => 'image/jpeg',
		'png' => 'image/png',
		'gif' => 'image/gif'
	);

	// @see is_allowed_mimetype() @ functions.php
}

define('HERE', dirname(__FILE__) );

// Sbabababam!
require HERE . '/functions.php';
require HERE . '/class-file-uploader.php';
require HERE . '/class-db.php';
require HERE . '/class-permissions.php';
require HERE . '/class-register-js-css.php';
require HERE . '/class-register-module.php';
require HERE . '/class-session.php';
require HERE . '/class-html.php';

// Dependents from functions above ↑
if(!defined('PROTOCOL')) {
	define('PROTOCOL', get_protocol());
}
if(!defined('DOMAIN')) {
	define('DOMAIN', get_domain());
}

// Start stopwatch
get_page_load();

// Test the database connection (or die!)
$db = new DB(@$username, @$password, @$location, @$database, @$prefix);
unset($username, $password, $location, $database, $prefix);

// Default DB options
define_default(
	'URL',
	'url',
	get_site_root() // @ functions.php see "ROOT"
);
define_default(
	'SITE_NAME',
	'site-name',
	'Boz PHP - Another PHP framework'
);
define_default(
	'SITE_DESCRIPTION',
	'site-description',
	'A simple framework'
);
define_default(
	'CHARSET',
	'charset',
	'UTF-8'
);

// Related to DB options
define('URL_', append_dir_to_URL(URL)); // Same as 'URL' but forced to have a slash ('/')

// Important global vars
$GLOBALS['javascript'] = new RegisterJavascriptLibs();
$GLOBALS['css'] = new RegisterCSSLibs();
$GLOBALS['module'] = new RegisterModule();
$GLOBALS['permissions'] = new Permissions();

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

// Callback
require ABSPATH . '/load-post.php';
