<?php
# Copyright (C) 2015, 2016 Valerio Bozzolan
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
 * Be sure that a global object exists.
 *
 * @see G class
 * @param string $global_var The $GLOBAL[''] arg you are asking for.
 */
function expect($global_var) {
	$GLOBALS['G']->expect($global_var);
}

function register_expected($name, $class) {
	$GLOBALS['G']->add($name, $class);
}

/**
 * Merge user defined arguments into defaults array.
 * It's used in a lot of functions.
 *
 * @param array $args Value to merge with $defaults
 * @param array $defaults Array that serves as the defaults
 * @return array Merged user defined values with defaults
 */
function merge_args_defaults($args, $defaults) {
        if( ! is_array($args) ) {
		DEBUG && error( sprintf(
			_("Errore in %s: l'argomento 1 dovrebbe essere un array."),
			__FUNCTION__
		) );
		return $defaults;
	}
	if( ! is_array($defaults) ) {
		error_die( sprintf(
			_("Errore in %s: l'argomento 2 deve essere un array."),
			__FUNCTION__
		) );
	}
	return array_merge($defaults, $args);
}

/**
 * Force something to be an array.
 * Useful to manage an option that can be something of a set of them (one of them?).
 *
 * @return mixed|array Something
 * @return array
 */
function force_array( & $a ) {
	if( ! is_array($a) ) {
		$a = [ $a ];
	}
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
 * SQL query escape string
 * @see DB#escapeString()
 */
function esc_sql($str) {
	expect('db');
	return $GLOBALS['db']->escapeString($str);
}

/**
 * Escape a LIKE SQL query
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
 * Execute a simple query.
 * @see DB#getResults()
 */
function query($query) {
	expect('db');
	return $GLOBALS['db']->query($query);
}

/**
 * Execute a query and return an array of objects.
 * @see DB#getResults()
 */
function query_results($query, $class = null, $args = [] ) {
	expect('db');
	return $GLOBALS['db']->getResults($query, $class, $args);
}

/**
 * Execute a query and return an object.
 * @see DB#getRow()
 */
function query_row($query, $class = null, $args = [] ) {
	expect('db');
	return $GLOBALS['db']->getRow($query, $class, $args);
}

/**
 * Execute a query and return a single value.
 * @see DB#getValue()
 */
function query_value($query, $value, $class = null) {
	expect('db');
	return $GLOBALS['db']->getValue($query, $value, $class);
}

function T($t, $as = false) {
	expect('db');
	return $GLOBALS['db']->getTable($t, $as);
}

define('T', 'T');
define('JOIN', 'JOIN');
// Stupid shurtcut for string context
$GLOBALS[T] = function($t, $as = false) {
	return T($t, $as = false);
};

// Stupid shortcut for string context for listing tables
$GLOBALS[JOIN] = function($t) {
	expect('db');
	return $GLOBALS['db']->getTables( func_get_args() );
};

/**
 * Insert a row in the specified database table.
 * @see DB#insertRow()
 */
function insert_row($table, $cols) {
	expect('db');
	return $GLOBALS['db']->insertRow($table, $cols);
}

/**
 * If the table has an AUTOINCREMENT you can get the last inserted index
 * after an insert_row().
 * @see DB#getLastInsertedID()
 */
function last_inserted_ID() {
	isset( $GLOBALS['db'] )
		|| error_die( _("Manca la connessione al database. Come ottenere l'ultimo indice?") );

	return $GLOBALS['db']->getLastInsertedID();
}

/**
 * Insert multiple values in the specified database table
 * @see DB#insert()
 */
function insert_values($tables, $cols, $values) {
	expect('db');
	return $GLOBALS['db']->insert($tables, $cols, $values);
}

/**
 * Update rows in the specified database table
 * @see DB#update()
 */
function query_update($table_name, $dbCols, $conditions, $after = '') {
	expect('db');
	$GLOBALS['db']->update($table_name, $dbCols, $conditions, $after);
}

/**
 * Escape form attributes
 */
function esc_attr($s) {
	return htmlspecialchars($s);
}
function _esc_attr($s) {
	echo htmlspecialchars($s);
}

/*
 * Friendly symlinks
 */
function register_mimetypes($category, $mimetypes) {
	expect('mimeTypes');
	$GLOBALS['mimeTypes']->registerMimetypes($category, $mimetypes);
}
function get_mimetypes($category = null) {
	expect('mimeTypes');
	return $GLOBALS['mimeTypes']->getMimetypes($category);
}
function register_permissions($role, $permissions) {
	expect('permissions');
	$GLOBALS['permissions']->registerPermissions($role, $permissions);
}
function inherit_permissions($role_to, $role_from) {
	expect('permissions');
	$GLOBALS['permissions']->inheritPermissions($role_to, $role_from);
}
function register_js($javascript_uid, $url, $position = null) {
	expect('javascript');
	return $GLOBALS['javascript']->register( $javascript_uid, $url, $position );
}
function enqueue_js($javascript_uid, $position = null) {
	expect('javascript');
	return $GLOBALS['javascript']->enqueue( $javascript_uid, $position );
}
function register_css($css_uid, $url) {
	expect('css');
	return $GLOBALS['css']->register($css_uid, $url);
}
function enqueue_css($css_uid) {
	expect('css');
	return $GLOBALS['css']->enqueue($css_uid);
}
function add_menu_entries($menuEntries) {
	expect('menu');
	$GLOBALS['menu']->add($menuEntries);
}
function get_menu_entry($uid) {
	expect('menu');
	return $GLOBALS['menu']->getMenuEntry($uid);
}
function get_children_menu_entries($parentUid) {
	expect('menu');
	return $GLOBALS['menu']->getChildrenMenuEntries($parentUid);
}
function register_module($module_uid) {
	expect('module');
	return $GLOBALS['module']->register($module_uid);
}
function inject_in_module($module_uid, $callback) {
	expect('module');
	return $GLOBALS['module']->injectFunction($module_uid, $callback);
}
function load_module($module_uid) {
	expect('module');
	return $GLOBALS['module']->loadModule($module_uid);
}
function get_table_prefix() {
	expect('db');
	return $GLOBALS['db']->getPrefix();
}
function register_option($option_name) {
	expect('db');
	return $GLOBALS['db']->registerOption($option_name);
}
function get_option($option_name, $default_value = '') {
	expect('db');
	return $GLOBALS['db']->getOption($option_name, $default_value);
}
function set_option($option_name, $option_value, $option_autoload = true) {
	expect('db');
	return $GLOBALS['db']->setOption($option_name, $option_value, $option_autoload);
}
function remove_option($option_name) {
	expect('db');
	return $GLOBALS['db']->removeOption($option_name);
}
function get_user($property = null) {
	expect('session');
	$user = $GLOBALS['session']->getUser();
	if( $property === null ) {
		return $user;
	}
	if( is_logged() && ! isset( $user->{$property} ) ) {
		DEBUG && error( sprintf(
			_("Colonna utente '%s' mancante!"),
			esc_html( $property )
		) );
		return null;
	}
	return $user->{$property};
}
function login(& $status = null, $user_uid = null, $user_password = null) {
	expect('session');
	return $GLOBALS['session']->login($status, $user_uid, $user_password);
}
function logout() {
	expect('session');
	return $GLOBALS['session']->destroy();
}
function register_language($code, $aliases = [], $encode = null, $iso = null) {
	expect('registerLanguage');
	return $GLOBALS['registerLanguage']->registerLanguage($code, $aliases, $encode, $iso);
}
function apply_language($language_alias = null) {
	expect('registerLanguage');
	return $GLOBALS['registerLanguage']->applyLanguage($language_alias);
}
function latest_language() {
	expect('registerLanguage');
	return $GLOBALS['registerLanguage']->getLatestLanguageApplied();
}

function get_num_queries() {
	if( isset( $GLOBALS['db'] ) ) {
		return $GLOBALS['db']->getNumQueries();
	}

	return 0;
}


define('DEFAULT_USER_ROLE', 'UNREGISTERED');

function is_logged() {
	expect('session');
	return $GLOBALS['session']->isLogged();
}

function has_permission($permission) {
	expect('session');

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

/**
 * Add a directory to a base URL or a pathname.
 * If the base URL it is not defined, a slash ('/') is appended to the URL.
 * The base URL could end with a slash ('/') or not.
 *
 * @param string $base_URL Base URL with/without any slash at start
 * @param string $dir Directory without any slash
 * @return string URL / Pathname
*/
function append_dir($base_URL, $dir = _ ) {
	$base_URL = rtrim($base_URL, _);
	$dir = ltrim($dir, _);
	return $base_URL . _ . $dir;
}

/**
 * Full URL or folder from ROOT.
 */
function site_page($page, $url, $base = null) {
	$first = @$page[0];
	if( $first === '#' ) return $page;
	if( $first === '/' ) {
		if($base === null) {
			$base = PROTOCOL . DOMAIN;
		}
		return $base . $page;
	}

	$sub = substr($page, 0, 6);
	if( $sub === 'http:/' || $sub === 'https:' ) {
		return $page;
	}
	if( $url === null ) {
		$url = URL;
	}
	return append_dir(URL, $page);
}

function single_quotes($s) {
	return "'$s'";
}

function double_quotes($s) {
	return '"' . $s . '"';
}

function this_folder() {
	return dirname( $_SERVER['PHP_SELF'] );
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
 * Choose the appropriate string.
 * '%' will be replaced with the input number.
 *
 * @param int $n Input number.
 * @param string $text_multi Text displayed if n > 1
 * @param string $text_one Text displayed if n == 1
 * @param string $text_no Text displayed if n < 1
 */
function multi_text($n, $text_multi, $text_one, $text_no = '') {
	if($n > 1) {
		return str_replace('%', $n, $text_multi);
	} elseif($n == 1) {
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
function URL_protocol() {
	return ( is_https() ) ? 'https://' : 'http://';
}

/**
 * Get the domain of the request
 * (Please use PROTOCOL)
 */
function URL_domain() {
	return ( empty( $_SERVER['SERVER_NAME'] ) ) ? 'localhost' : $_SERVER['SERVER_NAME'];
}

function URL_root() {
	$root = this_folder();
	if( $root === _ ) {
		return '';
	}
	return $root;
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
 * ă -> a, â -> a, ț -> t and so on.
 */
function remove_accents($s) {
	return iconv('utf8', 'ascii//TRANSLIT', $s);
}

/**
 * Get a secured version of a string
 */
function generate_slug($s, $max_length = -1, $glue = '-', & $truncated = false) {
	$s = strtolower( remove_accents($s) );
	if( $glue !== '_' ) {
		$s = str_replace('_', ' ', $s);
	}
	$s = preg_replace("/[^a-z0-9\s\\$glue]/", '', $s);
	$s = preg_replace("/[\s\\$glue]+/", ' ', $s);
	$s = trim($s, ' ');
	if($max_length !== -1) {
		$len = strlen($s);
		$s = substr($s, 0, $max_length);
		$truncated = $len !== strlen($s);
	}
	$s = preg_replace('/\s/', $glue, $s);
	return rtrim($s, $glue);
}

/**
 * It scares the user with an error message.
 *
 * @param string $msg Error message
 */
function error_die($msg) {
	http_503();
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php _e("Errore") ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET ?>" />
</head>
<body>
	<h1><?php printf(
		_("Ci dispiace! C'è qualche piccolo problema! <small>(DEBUG: %s)</small>"),
		DEBUG ? _("sì") : _("no")
	) ?></h1>
	<p><?php _e("Si è verificato il seguente errore durante l'avvio del framework:") ?></p>
	<p>&laquo; <?php echo $msg ?> &raquo;</p>
	<p><?php _e("Sai che cosa significhi tutto ciò...") ?></p>
</body>
</html>
<?php
exit; // Yes!
}

function error($msg) {
	echo "\n\n\t<!-- ERROR: -->\n\t<p style='background:red'>Error: $msg</p>\n\n";
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
 * @return string|false
 */
function get_mimetype($filepath, $pure = false) {
	$finfo = finfo_open(FILEINFO_MIME, MAGIC_MIME_FILE);

	if( ! $finfo ) {
		DEBUG && error( sprintf(
			_("Errore aprendo il database fileinfo situato in '%s'."),
			MAGIC_MIME_FILE
		) );

		return false;
	}

	$mime = finfo_file($finfo, $filepath);

	if( ! $mime ) {
		DEBUG && error( sprintf(
			_("Impossibile ottenere il MIME del file '%s'."),
			esc_html( $filepath )
		) );

		return false;
	}

	if( ! $pure ) {
		$mime = explode(';', $mime, 2); // Split "; charset"
		$mime = $mime[0];
	}

	return $mime;
}

/**
 * Know if a file belongs to a certain category
 *
 * @param string $filepath The file path
 * @param string $category The category
 * @return mixed FALSE if not
 */
function is_file_in_category($filepath, $category) {
	expect('mimeTypes');
	$mime = get_mimetype($filepath);
	return $GLOBALS['mimeTypes']->isMimetypeInCategory($mime , $category);
}

/**
 * Get the file extension
 */
function get_file_extension_from_expectations($filename, $category) {
	expect('mimeTypes');
	return $GLOBALS['mimeTypes']->getFileExtensionFromExpectations($filename, $category);
}

function is_image($filepath) {
	expect('mimeTypes');
	return is_file_in_category($filepath, 'image');
}

function is_audio($filepath) {
	expect('mimeTypes');
	return is_file_in_category($filepath, 'audio');
}

function is_video($filepath) {
	expect('mimeTypes');
	return is_file_in_category($filepath, 'video');
}

function is_document($filepath) {
	expect('mimeTypes');
	return is_file_in_category($filepath, 'document');
}

function is_closure($t) {
	return is_object($t) && ($t instanceof Closure);
}

/*
 * From http://php.net/manual/en/features.file-upload.php#88591
 */
function human_filesize($filesize, $separator = ' '){
	if( ! is_numeric($filesize) ) {
		return _("NaN");
	}
	$decr = 1024;
	$step = 0;
	$prefix = ['Byte', 'KB', 'MB', 'GB', 'TB', 'PB'];
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

	DEBUG && error( sprintf(
		_("Impossibile scrivere il percorso '%s'."),
		esc_html( $path )
	) );

	return false;
}

/**
 * Default mode to build a file name WITHOUT extension.
 * It's called multiple times in search_free_filename().
 *
 * Create your own but NEVER get two equal strings if $i changes.
 *
 * @param string $filename File name without extension
 * @param string $ext File name extension without dot
 * @param array $args Custom stuff
 * @param int $i Received from search_free_filename() as
 *	auto increment if the precedent file name already exists.
 *	To be used to-get (or not-to-get) a suffix.
 *	It's NULL during the first call.
 * @return string File name (with extension)
 */
function build_filename($filename, $ext, $args, $i = null) {
	if( ! isset( $args['autoincrement'] ) ) {
		$args['autoincrement'] = '-%d';
		DEBUG && error( sprintf(
			_("Arg [autoincrement] atteso in %s. Assunto '%s'."),
			__FUNCTION__,
			'-%d'
		) );
	}

	if( ! isset( $args['post-filename']  ) ) {
		$args['post-filename'] = '';
		DEBUG && error( sprintf(
			_("Arg [post-filename] atteso in %s. Assunto vuoto."),
			__FUNCTION__
		) );
	}

	$suffix = ( $i === null ) ? '' : sprintf( $args['autoincrement'], $i );

	return $filename . $suffix . $args['post-filename'];
}

/**
 * When you want a not-taken file name WITHOUT extension.
 *
 * @param string $filepath Absolute directory with trailing slash
 * @param string $filename 1° arg of $build_filename()
 * @param string $ext 2° arg of $build_filename()
 * @param string $args 3° args of $build_filename()
 * @param null|string $build_filename NULL for 'build_filename'
 */
function search_free_filename($filepath, $filename, $ext, $args, $build_filename = null) {
	if($build_filename === null) {
		$build_filename = 'build_filename';
	}

	if( ! function_exists( $build_filename ) ) {
		error_die( sprintf(
			_("Il 5° argomento di %s dovrebbe essere il nome di una funzione ma non esiste alcuna funzione '%s'."),
			__FUNCTION__,
			esc_html( $build_filename )
		) );
	}

	$i = null;
	while( file_exists( $filepath . call_user_func($build_filename, $filename, $ext, $args, $i) . ".$ext" ) ) {
		// http://php.net/manual/en/language.operators.increment.php
		// «Decrementing NULL values has no effect too, but incrementing them results in 1.»
		$i++;

		if($i === 30) {
			exit;
		}
	}
	return call_user_func($build_filename, $filename, $ext, $args, $i);
}

/**
 * I use this to clean user input before DB#insert()
 *
 * @param string $s Input string
 * @param int $max Max length
 */
function luser_input($s, $max) {
	return str_truncate( trim( $s ) , $max );
}

/**
 * Do it on your own!
 *
 * @deprecated
 */
if( ! function_exists('require_permission') ) {

	/**
	 * Do it on your own!
	 * @deprecated
	 */
	function require_permission($permission, $redirect = 'login.php?redirect=', $preFunction = '', $postFunction = '') {
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
}

/**
 * Do it on your own!
 */
if( ! function_exists('get_gravatar') ) {
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
	 * @deprecated
	 */
	function get_gravatar($email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = [] ) {
		$url = '//www.gravatar.com/avatar/';
		$url .= md5( strtolower( trim( $email ) ) );
		$url .= "?s=$s&amp;d=$d&amp;r=$r";
		if($img) {
			$url = '<img src="' . $url . '"';
			foreach($atts as $key => $val) {
				$url .= ' ' . $key . '="' . $val . '"';
			}
			$url .= ' />';
		}
		return $url;
	}
}

/**
 * Used to know much is the page load
 *
 * @return mixed Execution time
 * @deprecated
 */
function get_page_load($decimals = 6) {
	static $start_microtime = 0; // Please let me take advantage of PHP features
	if($start_microtime == 0) {
		$start_microtime = microtime(true);
	}
	return substr(microtime(true) - $start_microtime, 0, 2 + $decimals);
}

/**
 * @deprecated
 */
function get_human_datetime($datetime, $format = 'd/m/Y H:i') {
	if( ! $datetime ) {
		return $datetime;
	}
	$time = strtotime($datetime);
	return date($format, $time);
}
