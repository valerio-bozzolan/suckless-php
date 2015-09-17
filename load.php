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

if(!defined('DEBUG')) {
	define('DEBUG', false);
}
if(!defined('_')) { // Pathname slash
	define('_', DIRECTORY_SEPARATOR);
}
if(!defined('ROOT')) { // Request installation pathname (after the domain name) e.g.: /cms
	if( dirname( $_SERVER['PHP_SELF'] ) === _ ) {
		define('ROOT', '');
	} else {
		define('ROOT', dirname( $_SERVER['PHP_SELF'] ));
	}
}
if(!defined('CHMOD_WRITABLE_DIRECTORY')) {
	define('CHMOD_WRITABLE_DIRECTORY', 0777);
}
if(!defined('CHMOD_WRITABLE_FILE')) {
	define('CHMOD_WRITABLE_FILE', 0666);
}
if(!defined('MAGIC_MIME_FILE')) { // Fifo
	define('MAGIC_MIME_FILE', null); // System default
}

// @see is_allowed_mimetype() @ functions.php

define('HERE', dirname(__FILE__) );

// Sbabababam!
require HERE . '/functions.php';
require HERE . '/class-mimetypes.php';
require HERE . '/class-file-uploader.php';
require HERE . '/class-db.php';
require HERE . '/class-permissions.php';
require HERE . '/class-register-js-css.php';
require HERE . '/class-register-module.php';
require HERE . '/class-session.php';
require HERE . '/class-html.php';

// Start stopwatch
get_page_load();

// Dependents from functions above ↑
if(!defined('PROTOCOL')) {
	define('PROTOCOL', get_protocol());
}
if(!defined('DOMAIN')) {
	define('DOMAIN', get_domain());
}

// Test the database connection (or die!)
$GLOBALS['db'] = new DB(@$username, @$password, @$location, @$database, @$prefix);
unset($username, $password, $location, $database, $prefix);

// Important global vars
$GLOBALS['mimeTypes'] = new MimeTypes();
$GLOBALS['javascript'] = new RegisterJavascriptLibs();
$GLOBALS['css'] = new RegisterCSSLibs();
$GLOBALS['module'] = new RegisterModule();
$GLOBALS['permissions'] = new Permissions();

// Default DB options
define_default(
	'URL',
	'url',
	get_site_root() // This SHOULD NOT END with a slash
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

register_mimetypes(
	'image',
	array(
		'image/jpeg' => array('jpg', 'jpeg'),
		'image/png' => 'png',
		'image/gif' => 'gif',
		'image/svg+xml' => 'svg'
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

// Callback
require ABSPATH . '/load-post.php';
