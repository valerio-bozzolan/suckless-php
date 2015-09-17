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
 * General functions.
 */

/**
 * Merge user defined arguments into defaults array.
 * It's used in a lot of functions.
 *
 * @param array $args Value to merge with $defaults
 * @param array $defaults Array that serves as the defaults
 * @return array Merged user defined values with defaults
 */
function merge_args_defaults($args, $defaults) {
        if(!is_array($args)) {
		if(DEBUG) {
			error_die('Error merge_args_defaults: Parameter 1 ($args) must be an array.');
		}
		return $defaults;
	}
	if(!is_array($defaults)) {
		error_die('Error merge_args_defaults: Parameter 2 ($defaults) must be an array.');
	}
	return array_merge($defaults, $args);
}

/**
 * Get either a Gravatar URL or complete image tag for a specified email address.
 *
 * @param string $email The email address
 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
 * @param boole $img True to return a complete IMG tag False for just the URL
 * @param array $atts Optional, additional key/value attributes to include in the IMG tag
 * @return String containing either just a URL or a complete image tag
 * @source http://gravatar.com/site/implement/images/php/
 */
function get_gravatar($email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() ) {
	$url = 'http://www.gravatar.com/avatar/';
	$url .= md5( strtolower( trim( $email ) ) );
	$url .= "?s=$s&d=$d&r=$r";
	if($img) {
		$url = '<img src="' . $url . '"';
		foreach($atts as $key => $val) {
			$url .= ' ' . $key . '="' . $val . '"';
		}
		$url .= ' />';
	}
	return $url;
}

/**
 * This extremely-stupid function does not use preg_replace()
 * and so it's sooooooooooooooooooooooooooooooo fast.
 *
 * @param $s string Heystack
 * @param $q string Needle
 * @param $pre string HTML before query (bold tag as default)
 * @param $post string HTML after query (bold tag as default)
 * @return string Enfatized string
 */
function enfatize_substr($s, $q, $pre = "<b>", $post = "</b>") {
	$s_length = strlen($s);
	$q_length = strlen($q);

	$offset = 0;
	do {
		// Find position
		$pos = stripos($s, $q, $offset);
		if($pos === false) {
			break;
		}

		// Enfatize query
		$enfatized = $pre . substr($s, $pos, $q_length) . $post;
		$enfatized_length = strlen($enfatized);

		// Pre-query and post-query strings
		$s_pre = substr($s, 0, $pos);
		$s_post = substr($s, $pos + $q_length);

		// Save
		$s = $s_pre . $enfatized . $s_post;

		$offset = $pos + $enfatized_length;

	} while($offset < $s_length);

	return $s;
}

/*
 * Anti-tedium shortcuts
 */

/**
 * SQL escape
 */
function esc_sql($str) {
	return $GLOBALS['db']->escapeString($str);
}

/**
 * Escape a LIKE = '' SQL
 */
function esc_sql_like($s) {
	$s = str_replace('%', '', $s);
	return esc_sql($s);
}

/**
 * HTML escape
 */
function esc_html($s) {
	return htmlentities($s);
}

function _esc_html($s) {
	echo htmlentities($s);
}

/**
 * Escape form attributes
 */
function esc_attr($str) {
	return htmlspecialchars($str);
}

/*
 * Friendly symlinks
 */
function register_mimetypes($category, $mimetypes) {
	$GLOBALS['mimeTypes']->registerMimetypes($category, $mimetypes);
}
function register_permission($role, $permission) {
	$GLOBALS['permissions']->registerPermission($role, $permission);
}
function inherit_permissions($role_to, $role_from) {
	$GLOBALS['permissions']->inheritPermissions($role_to, $role_from);
}
function register_javascript($javascript_uid, $url, $position = JavascriptLib::HEADER) {
	return $GLOBALS['javascript']->register( $javascript_uid, $url, $position );
}
function enqueue_javascript($javascript_uid, $position = JavascriptLib::HEADER) {
	return $GLOBALS['javascript']->enqueue( $javascript_uid, $position );
}
function register_css($css_uid, $url) {
	return $GLOBALS['css']->register($css_uid, $url);
}
function enqueue_css($css_uid) {
	return $GLOBALS['css']->enqueue($css_uid);
}
function register_module($module_uid) {
	return $GLOBALS['module']->register($module_uid);
}
function inject_in_module($module_uid, $callback) {
	return $GLOBALS['module']->inject_function($module_uid, $callback);
}
function load_module($module_uid) {
	return $GLOBALS['module']->load_module($module_uid);
}
function get_table_prefix() {
	return $GLOBALS['db']->get_prefix();
}
function register_option($option_name) {
	return $GLOBALS['db']->registerOption($option_name);
}
function get_option($option_name, $default_value = '') {
	return $GLOBALS['db']->getOption($option_name, $default_value);
}
function set_option($option_name, $option_value, $option_autoload = true) {
	return $GLOBALS['db']->setOption($option_name, $option_value, $option_autoload);
}
function remove_option($option_name) {
	return $GLOBALS['db']->removeOption($option_name);
}
function get_user($property = null) {
	use_session();
	$user = $GLOBALS['session']->getUser();
	if( $property === null ) {
		return $user;
	}
	if( is_logged() && ! isset( $user->$property ) ) {
		if(DEBUG) {
			error( sprintf( "Not specified user field called %s", $property) );
		}
		return null;
	}
	return $user->$property;
}

function get_num_queries() {
	return $GLOBALS['db']->get_num_queries();
}

function get_human_datetime($datetime, $format = 'd/m/Y H:i') {
	if( ! $datetime ) {
		return $datetime;
	}
	$time = strtotime($datetime);
	return date($format, $time);
}

define('DEFAULT_USER_ROLE', 'UNREGISTERED');


/**
 * Istantiate a session on demand
 */
function use_session() {
	if( ! isset( $GLOBALS['session'] ) ) {
		$GLOBALS['session'] = new Session(  $GLOBALS['db'] );
	}
	return $GLOBALS['session'];
}

function is_logged() {
	use_session();
	return $GLOBALS['session']->isLogged();
}

function has_permission($permission) {
	use_session();

	$user = $GLOBALS['session']->getUser();
	$user_role = false;
	if($user) {
		$user_role = $user->user_role;
	}
	if( ! $user_role ) {
		$user_role = DEFAULT_USER_ROLE;
	}
	return $GLOBALS['permissions']->hasPermission($user_role, $permission);
}

function require_permission($permission, $redirect = 'login.php?redirect=') {
	use_session();

	if( ! has_permission($permission) ) :
		if( is_logged() ) :
			echo HTML::tag('p', _("Non hai permessi a sufficienza.") );
		else :
			http_redirect( site_page(
				$redirect . urlencode( site_page( $_SERVER['REQUEST_URI'] ) )
			) );
		endif;

		get_footer();
		exit; // Yes!
	endif;
}

/**
 * Add a directory to a base URL.
 * If the base URL it is not defined, a slash ('/') is appended to the URL.
 * The base URL could end with a slash ('/') or not.
 *
 * @param string $base_URL Base URL with/without any slash at start
 * @param string $dir Directory without any slash
 * @return string URL
*/
function append_dir_to_URL($base_URL, $dir = '/') {
	$base_URL = rtrim($base_URL, '/');
	$dir = ltrim($dir, '/');
	return $base_URL . '/' . $dir;
}

/**
 * Full URL or folder from ROOT.
 */
function site_page($page) {
	if( @$page[0] === '#' ) {
		return $page;
	}
	$sub = substr($page, 0, 6);
	if( $sub === 'http:/' || $sub === 'https:' ) {
		return $page;
	}
	return append_dir_to_URL(URL, $page);
}

function single_quotes($s) {
	return "'$s'";
}

function double_quotes($s) {
	return '"' . $s . '"';
}

function get_this_folder() {
	return dirname($_SERVER['PHP_SELF']);
}

function get_URL_folder() {
	return DOMAIN . $_SERVER['REQUEST_URI'];
}

/**
 * Truncate a string if it's over a specific length.
 * You can specify the end of the string if it's truncated.
 *
 * @param string %s Input string
 * @param int $max_length Max string length
 * @param string $blabla Optional. If string length is over $max_length, $blabla it's appended after $string
 */
function str_truncate($s, $max_length, $blabla = '') {
	if(strlen($s) > $max_length) {
		return substr($s, 0, $max_length - strlen($blabla)) . $blabla;
	}
	return $s;
}

/**
 * Return current Unix timestamp with microseconds.
 * It replicate the PHP 5 behaviour.
 *
 * @return float Microtime
 */
function get_microtime() {
	list($time, $micro) = explode(' ', microtime());
	return (float)$time + (float)$micro;
}

/**
 * Used to know much is the page load
 *
 * @return mixed Execution time
 */
function get_page_load($decimals = 6) {
	static $start_microtime = 0; // Please let me take advantage of PHP features
	if($start_microtime == 0) {
		$start_microtime = get_microtime();
	}
	return substr(get_microtime() - $start_microtime, 0, 2 + $decimals);
}

/**
 * Choose the appropriate string.
 * '%' will be replaced with the input number.
 *
 * @param int $n Input number.
 * @param string $text_multi Text displayed if n > 1
 * @param string $text_one Text displayed if n == 1
 * @param string $text_no Text displayed if n < 1
 */
function multi_text($n, $text_multi, $text_one, $text_no) {
	if($n > 1) {
		return str_replace($text_multi, '%', $n);
	} else if($n == 1) {
		return $text_one;
	}
	return $text_no;
}

/**
 * Simple HTTP redirects.
 */
function http_redirect($url) {
	header("Location: $url");
	exit;
}

/**
 * Check if the request is under HTTPS
 */
function is_https() {
	return ! empty( $_SERVER['HTTPS'] );
}

/**
 * Get the protocol of the request
 * (Please use PROTOCOL)
 */
function get_protocol() {
	return ( is_https() ) ? 'https://' : 'http://';
}

/**
 * Get the domain of the request
 * (Please use PROTOCOL)
 */
function get_domain() {
	return ( empty( $_SERVER['SERVER_NAME'] ) ) ? 'localhost' : $_SERVER['SERVER_NAME'];
}

/**
 * Absolute http root
 */
function get_site_root() {
	return PROTOCOL . DOMAIN . ROOT;
}

/**
 * HTTP 503 header
 */
function http_503() {
	header('HTTP/1.1 503 Service Temporarily Unavailable');
	header('Status: 503 Service Temporarily Unavailable');
	header('Retry-After: 300');
}

/**
 * Define constants from options.
 * Used in load.php
 */
function define_default($constant_name, $option_name, $default_value) {
	if(!defined($constant_name)) {
		if(isset($GLOBALS['db'])) {
			define($constant_name, get_option($option_name, $default_value));
		} else {
			define($constant_name, $default_value);
		}
	} else if(isset($GLOBALS['db'])) {
		$GLOBALS['db']->overrideOption($option_name, constant($constant_name));
	}
}

/**
 * ă -> a, â -> a, ț -> t and so on.
 */
function remove_accents($s) {
	return iconv('utf8', 'ascii//TRANSLIT', $s);
}

/**
 * Get a secured version of a string
 */
function generate_slug($s, $max_length = -1, & $truncated = false) {
	$s = strtolower( remove_accents($s) );
	$s = str_replace('_', ' ', $s);
	$s = preg_replace('/[^a-z0-9\s-]/', '', $s);
	$s = preg_replace('/[\s-]+/', ' ', $s);
	$s = trim($s, ' ');
	if($max_length !== -1) {
		$len = strlen($s);
		$s = substr($s, 0, $max_length);
		if($len !== strlen($s)) {
			$truncated = true;
		}
	}
	$s = preg_replace('/\s/', '-', $s);
	return rtrim($s, '-');
}

/**
 * It scares the user with an error message.
 *
 * @param string $msg Error message
 */
function error_die($msg) {
	http_503();
	exit(
"<!doctype html>
<html>
<head>
	<title>Error</title>
</head>
<body>
	<h1>Sorry! We have a little problem" . ((DEBUG) ? ' <small>[debug]</small>' : '') . "</h1>
	<p>The following error occurred at startup: </p>
	<p>&laquo; $msg &raquo;</p>
	<p>If you know what it means...</p>
</body>
</html>");
}

function error($msg) {
	echo "\n\n\t<!-- ERROR: -->\n\t<p style='background:red'>Error: $msg</p>\n\n";
}

/**
 * From the results of the DB query it print tags
 */
function _tags($tags) {
	for($i=count($tags)-1; $i>=0; $i--) {
?>
	<a href=""
<?php
	}
}

/**
 * Support for gettext
 */
function _e($s) {
	echo _($s);
}

function http_json_header() {
	header('Content-Type: application/json');
}

/**
 * Get the MIME type from a file.
 *
 * @param string $filepath The file path.
 * @param bool $pure
 * 	TRUE for 'image/png; something';
 * 	FALSE for 'image/png'.
 */
function get_mimetype($filepath, $pure = false) {
	$finfo = finfo_open(FILEINFO_MIME, MAGIC_MIME_FILE);

	if( ! $finfo ) {
		if( DEBUG ) {
			error( sprintf(
				_("Errore aprendo il database fileinfo situato in '%s'."),
				MAGIC_MIME_FILE
			) );
		}

		return false;
	}

	$mime = finfo_file($finfo, $filepath);

	if( ! $mime ) {
		if(DEBUG) {
			error( sprintf(
				_("Impossibile ottenere il MIME del file '%s'."),
				esc_html( $filepath )
			) );
		}
		return false;
	}

	if( ! $pure ) {
		$mime = explode(';', $mime, 2); // Split "; charset"
		$mime = $mime[0];
	}

	return $mime;
}

/**
 * Check if a file name end with an allowed extension.
 *
 * @see MimeTypes::checkFileExtension()
 */
function check_file_extension($filename) {
	return $GLOBALS['mimeTypes']->checkFileExtension($filename);
}

/**
 * Check if a file have a specified / allowed mimetype.
 *
 * @param string $filepath The path to the file. E.g. '/tmp/aakhajkdh'.
 * @return mixed If the MIME is allowed
 */
function check_file_mimetype($filepath) {
	$mime = get_mimetype($filepath);
	if( ! $mime ) {
		// get_mimetype() throwed yet an "exception"
		return false;
	}

	return $GLOBALS['mimeTypes']->isMimeAllowed( $mime );
}

/**
 * Check the MIME and the extension.
 *
 * @param string $filepath The file path.
 * @param string$filename The file name (NULL for same as $filepath).
 * @return mixed FALSE or the extension as string
 */
function is_file_allowed($filepath, $filename = null) {
	if($filename === null) {
		$filename = $filepath;
	}

	$extension = check_file_extension($filename);
	if( ! $extension ) {
		return false;
	}

	$allowed_extensions = check_file_mimetype($filepath);
	if( ! $allowed_extensions ) {
		return false;
	}

	$ok = in_array( $extension, $allowed_extensions );
	if( $ok ) {
		return $extension;
	}

	return false;
}

function is_image($filepath) {
	$mime = get_mimetype($filepath);
	return $GLOBALS['mimeTypes']->isMimeInCategory($mime , 'image');
}

function is_audio($filepath) {
	$mime = get_mimetype($filepath);
	return $GLOBALS['mimeTypes']->isMimeInCategory($mime, 'audio');
}

function is_video($filepath) {
	$mime = get_mimetype($filepath);
	return $GLOBALS['mimeTypes']->isMimeInCategory($mime, 'video');
}

function is_document($filepath) {
	$mime = get_mimetype($filepath);
	return $GLOBALS['mimeTypes']->isMimeInCategory($mime, 'document');
}

function is_closure($t) {
	return is_object($t) && ($t instanceof Closure);
}

/*
 * From http://php.net/manual/en/features.file-upload.php#88591
 */
function get_human_filesize($filesize, $separator = ' '){
	if(!is_numeric($filesize)) {
		return _('NaN');
	}
	$decr = 1024;
	$step = 0;
	$prefix = array('Byte', 'KB', 'MB', 'GB', 'TB', 'PB');
	while(($filesize / $decr) > 0.9) {
		$filesize /= $decr;
		$step++;
	}
	return round($filesize, 2) . $separator . $prefix[$step];
}

/*
 * Create a pathname in the filesystem
 */
function create_path($path, $chmod = CHMOD_WRITABLE_DIRECTORY) {
	if( file_exists($path) ) {
		return true;
	}

	if( mkdir($path, $chmod, true) ) {
		return true;
	}

	if(DEBUG) {
		error( sprintf(
			_("Impossibile scrivere il percorso '%s'."),
			esc_html( $path )
		) );
	}

	return true;
}
