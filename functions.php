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
    if ( $img ) {
        $url = '<img src="' . $url . '"';
        foreach ( $atts as $key => $val )
            $url .= ' ' . $key . '="' . $val . '"';
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
 * HTML escape
 */
function esc_html($str) {
	return htmlentities($str);
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
function register_javascript($javascript_uid, $url, $position = JavascriptLib::HEADER) {
	return $GLOBALS['javascript']->register($javascript_uid, $url, $position);
}
function enqueue_javascript($javascript_uid, $position = JavascriptLib::HEADER) {
	return $GLOBALS['javascript']->enqueue($javascript_uid, $position);
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

function require_permission($permission) {
	use_session();

	if( ! has_permission($permission) ) :
		if( is_logged() ) :
?>
	<p><?php _e("Non hai permessi a sufficienza.") ?></p>
<?php
		else :
			http_redirect(site_page(
				'login.php?redirect=' .
					urlencode( site_page($_SERVER['REQUEST_URI']) )
			));
		endif;

		get_footer();
		exit; // Yes!
	endif;
}

function get_user_complete_name() {
	use_session();

	$user = get_user();
	if(!$user) {
		if(DEBUG) {
			error( _("Non sei autenticato") );
		}
		return;
	}
	return "{$user->user_name} {$user->user_surname}";
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

function site_page($page) {
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

function get_media_URL($media_path) {
	return append_dir_to_URL(MEDIA_URL, $media_path);
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

/*
 * Check if a file have an extension or from a list of extensions
 *
 * @param $file_name string
 * @param $exts array of lower case strings
 */
function is_file_extension_allowed($file_name, $exts = null) {
	if( $exts === null ) {
		$exts = $GLOBALS['ALLOWED_UPLOAD_EXTENSIONS'];
	}
	if( ! is_array($exts) ) {
		$exts = array($exts);
	}
	$n = count($exts);
	for($i=0; $i<$n; $i++) {
		$ext = '.' . $exts[$i];
		$file_ext = strtolower( substr( $file_name, - strlen($ext) ) );
		if( $ext === $file_ext ) {
			return $exts[$i];
		}
	}
	return false;
}

function is_image($file_name) {
	$exts = array('png', 'gif', 'svg', 'jpeg', 'jpg');
	return check_file_extension($file_name, $exts);
}

function is_audio($file_name) {
	$exts = array('mp3');
	return check_file_extension($file_name, $exts);
}

function is_video($file_name) {
	$exts = array('ogg', 'mp4', 'avi', '3gp');
	return check_file_extension($file_name, $exts);
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
 * Check the mime type of a file
 *
 * @see http://php.net/manual/en/features.file-upload.php
 */
function is_mimetype_allowed($file_name, $mime_types = null) {
	if($mime_types === null) {
		$mime_types = $GLOBALS['ALLOWED_UPLOAD_MIME_TYPES'];
	}
	$finfo = new finfo(FILEINFO_MIME_TYPE);
	return array_search(
		$finfo->file( $file_name ),
		$mime_types,
		true
	) !== false;
}

/*
 * Create a pathname in the filesystem
 */
function create_path($path, $umask = UMASK_WRITABLE_DIRECTORY) {
	file_exists($path) || mkdir($path, $umask, true);
}

define('UPLOAD_EXTRA_ERR_UNDEFINED', 3);
define('UPLOAD_EXTRA_ERR_INVALID_REQUEST', 5);
define('UPLOAD_EXTRA_ERR_GENERIC_ERROR', 7);
define('UPLOAD_EXTRA_ERR_UNALLOWED_EXT', 9);
define('UPLOAD_EXTRA_ERR_UNALLOWED_MIMETYPE', 11);
define('UPLOAD_EXTRA_ERR_FILENAME_TOO_SHORT', 13);
define('UPLOAD_EXTRA_ERR_CANT_SAVE_FILE', 15);

/**
 * Upload files.
 *
 * @param $file_entry string See it as $_FILES[ $file_entry ]
 * @param $pathname string the folder WITHOUT trailing slash
 * @param $status int To know the error
 * @param $args array Options
 */
function upload_file_to($file_entry, $pathname, & $status, $args = array()) {
	$args = merge_args_defaults(
		$args,
		array(
			'slug' => true,
			'override-filename' => null,
			'pre-filename' => '',
			'post-filename' => '',
			'max-filesize' => null,
			'allowed-ext' => null,
			'allowed-mimetypes' => null,
			'min-length-filename' => 2,
			'dont-overwrite' => true
		)
	);

	if( ! isset( $_FILES[ $file_entry ] ) ) {
		$status = UPLOAD_EXTRA_ERR_UNDEFINED;
		return false;
	}

	// Undefined | Multiple Files | $_FILES Corruption Attack
	// If this request falls under any of them, treat it invalid
	if ( ! isset($_FILES[ $file_entry ]['error']) || is_array($_FILES[ $file_entry ]['error']) ) {
		UPLOAD_EXTRA_ERR_INVALID_REQUEST;
		return false;
	}

	switch($_FILES[ $file_entry ]['error']) {
		case UPLOAD_ERR_OK:
			break;
		case UPLOAD_ERR_NO_FILE:
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			// Return the same error
			$status = $_FILES[ $file_entry ]['error'];
			return false;
		default:
			$status = UPLOAD_EXTRA_ERR_GENERIC_ERROR;
			return false;
	}

	// Check filesize
	if($args['max-filesize'] !== null && $_FILES[ $file_entry ]['size'] > $args['max-filesize']) {
		$status = UPLOAD_EXTRA_ERR_OVERSIZE;
		return false;
	}

	// Get ext and check
	$ext = is_file_extension_allowed( $_FILES[ $file_entry ]['name'], $args['allowed-ext'] );
	if( ! $ext ) {
		$status = UPLOAD_EXTRA_ERR_UNALLOWEED_EXTENSION;
		return false;
	}

	// Check mimetype
	if( is_mimetype_allowed( $_FILES[ $file_entry ]['tmp_name'], $args['allowed-mimetypes'] ) ) {
		$status = UPLOAD_EXTRA_ERR_UNALLOWEED_MIMETYPE;
		return false;
	}

	// Get filename
	if($args['override-filename'] === null) {
		$filename = substr($_FILES[ $file_entry ]['name'], 0, - strlen( $ext ));
	} else {
		$filename = $args['override-filename'];
	}

	// Append prefix (if any)
	$filename = $args['pre-filename'] . $filename;

	if($args['slug']) {
		$filename = generate_slug( $filename );
		$args['post-filename'] = generate_slug( $args['post-filename'] );
	}

	// Create destination
	create_path( $pathname );

	// Can be appended a progressive number
	if( $args['dont-overwrite'] && file_exists( $pathname . "/$filename.$ext" )  ) {
		$i = 1;
		while( file_exists( $pathname . "/$filename-$i.$ext" ) ) {
			$i++;
		}
		$filename = "$filename-$i";
	}

	// Append suffix (if any)
	$filename = $filename . $args['post-filename'];

	// Filename length
	if( strlen($filename) < $args['min-length-filename'] ) {
		$status = UPLOAD_EXTRA_ERR_FILENAME_TOO_SHORT;
		return false;
	}

	$moved = move_uploaded_file(
		$_FILES[ $file_entry ]['tmp_name'],
		$pathname . "/$filename.$ext"
	);

	if(! $moved) {
		$status = UPLOAD_EXTRA_ERR_CANT_SAVE_FILE;
		return false;
	}

	return $filename;
}
