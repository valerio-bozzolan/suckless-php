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
 * Start the CMS loading DB stuff
 */

if(!defined('ABSPATH')) {
	die("No ABSPATH specified");
}

// Fill default costants
if(!defined('INCLUDES')) {
	define('INCLUDES', 'includes');
}
if(!defined('CONTENT')) {
	define('CONTENT', 'content');
}
if(!defined('LOAD_THEME')) {
	define('LOAD_THEME', true);
}
if(!defined('DEBUG')) {
	define('DEBUG', false);
}
if(!defined('_')) { // Pathname slash
	define('_', DIRECTORY_SEPARATOR);
}
if(!defined('UMASK_WRITABLE_DIRECTORY')) {
	define('UMASK_WRITABLE_DIRECTORY', 0750);
}
if(!defined('UMASK_WRITABLE_FILE')) {
	define('UMASK_WRITABLE_FILE', 0640);
}
if(!isset($ALLOWED_UPLOAD_EXTENSIONS)) {
	$ALLOWED_UPLOAD_EXTENSIONS = array(
		'doc', 'docx', 'gif', 'jpg', 'jpeg', 'mp3', 'mp4', 'odp', 'ods', 'odt', 'ogg', 'pdf', 'png', 'ppt', 'pptx', 'svg', 'xls', 'xlsx'
	);
}
// Dependents from above ↑
define('THEMES', CONTENT . _ . 'themes');
define('MEDIA', INCLUDES . _ . 'media');

// Sbabababam!
require ABSPATH . _ . INCLUDES . '/functions.php';
require ABSPATH . _ . INCLUDES . '/class-db.php';
require ABSPATH . _ . INCLUDES . '/class-permissions.php';
require ABSPATH . _ . INCLUDES . '/class-register-js-css.php';
require ABSPATH . _ . INCLUDES . '/class-register-module.php';
require ABSPATH . _ . INCLUDES . '/class-session.php';
require ABSPATH . _ . INCLUDES . '/class-html.php';

// Dependents from functions above ↑
if(!defined('PROTOCOL')) {
	define('PROTOCOL', get_protocol());
}
if(!defined('DOMAIN')) {
	define('DOMAIN', get_domain());
}
if(!defined('ROOT')) { // Request installation pathname (after the domain name) e.g.: /cms
	define('ROOT', dirname( $_SERVER['PHP_SELF'] ));
}

// Start stopwatch
get_page_load();

// Test the database connection (or die)
$db = new DB(@$username, @$password, @$location, @$database, @$prefix);
unset($username, $password, $location, $database, $prefix);

// Default DB options
define_default(
	'URL',
	'url',
	get_site_root() // @ includes/functions.php see "ROOT"
);
define_default(
	'THEME_NAME',
	'theme',
	'materialize'
);
define_default(
	'SITE_NAME',
	'site-name',
	'Landscapefor'
);
define_default(
	'SITE_DESCRIPTION',
	'site-description',
	_('Content Management System per la geolocalizzazione dei POI (punti di interesse).')
);
define_default(
	'CHARSET',
	'charset',
	'UTF-8'
);

// The theme-name can't be a subpath (e.g. '../malware')
if(strpos(THEME_NAME, DIRECTORY_SEPARATOR) !== false) {
	error_die( sprintf(
		_('Il nome del tema attivo <em>%s</em> contiene caratteri non previsti.'),
		esc_html(THEME_NAME)
	));
}

// Related to DB options
define('THEME', THEMES . DIRECTORY_SEPARATOR . THEME_NAME);
define('URL_', append_dir_to_URL(URL)); // Same as 'URL' but forced to have a slash ('/')
define('INCLUDES_URL', URL_ . INCLUDES);
define('MEDIA_URL', URL_ . MEDIA);
define('THEME_URL', URL_ . THEME);

// Important global vars
$GLOBALS['javascript'] = new RegisterJavascriptLibs();
$GLOBALS['css'] = new RegisterCSSLibs();
$GLOBALS['module'] = new RegisterModule();
$GLOBALS['permissions'] = new Permissions();

require ABSPATH . '/load-post.php';

// Default "constants"
if( ! isset($UPLOAD_ALLOWED_MIME_TYPES) ) {
	$GLOBALS['ALLOWED_UPLOAD_MIME_TYPES'] = array(
		'jpg' => 'image/jpeg',
		'png' => 'image/png',
		'gif' => 'image/gif'
	);

	// @see is_allowed_mimetype() in includes/functions.php
}

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

// Load theme
if(!file_exists(ABSPATH . _ . THEME . '/index.php')) {
	error_die( sprintf(
		_('Error with current active theme <em>%s</em> in path <em>%s</em>.'),
		esc_html(THEME_NAME),
		esc_html(THEME)
	));
}
require ABSPATH . _ . THEME . '/index.php';
