<?php
# Copyright (C) 2015, 2016, 2017, 2018 Valerio Bozzolan
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

// Useful e.g. in Table::NAME . DOT . STAR query.
define('DOT',  '.');
define('STAR', '*');

function define_default( $constant_name, $default_value ) {
	defined( $constant_name ) or define( $constant_name, $default_value );
}

function selected( $helper = PHP_INT_MAX, $current = PHP_INT_MAX, $force = false ) {
	return html_property_when_matching( 'selected', 'selected', $helper, $current, $force);
}

function checked( $helper = PHP_INT_MAX, $current = PHP_INT_MAX, $force = false ) {
	return html_property_when_matching( 'checked', 'checked', $helper, $current, $force);
}

function disabled( $helper = PHP_INT_MAX, $current = PHP_INT_MAX, $force = false ) {
	return html_property_when_matching( 'disabled', 'disabled', $helper, $current, $force );
}

function _checked( $helper = PHP_INT_MAX, $current = PHP_INT_MAX, $force = false ) {
	echo checked( $helper, $current, $force );
}

function _selected( $helper = PHP_INT_MAX, $current = PHP_INT_MAX, $force = false ) {
	echo selected( $helper, $current, $force );
}

function _disabled( $helper = PHP_INT_MAX, $current = PHP_INT_MAX, $force = false ) {
	echo disabled( $helper, $current, $force );
}

function html_property_when_matching( $property, $value, $helper = PHP_INT_MAX, $current = PHP_INT_MAX, $force = false ) {
	if( $helper === $current || $helper && PHP_INT_MAX === $current || $force ) {
		return HTML::property( $property, $value );
	}
	return '';
}

function _value( $v ) {
	echo HTML::property( 'value', $v );
}

/**
 * Retrieve a required global object.
 * @see G::expect()
 */
function expect( $global_var ) {
	return $GLOBALS['G']->expect( $global_var );
}

/**
 * Register a required global object.
 * @see G::add()
 */
function register_expected( $global_var, $class_name ) {
	$GLOBALS['G']->add( $global_var, $class_name );
}

/**
 * Force something to be an array.
 *
 * @return mixed|array
 * @return array
 */
function force_array( & $a ) {
	if( ! is_array($a) ) {
		$a = [ $a ];
	}
}

/**
 * Enfatize a substring.
 * @see EnfatizeSubstr::get()
 */
function enfatize_substr($heystack, $needle, $pre = '<b>', $post = '</b>') {
	return EnfatizeSubstr::get($heystack, $needle, $pre, $post);
}

/**
 * SQL query escape string
 * @see DB#escapeString()
 */
function esc_sql($s) {
	return expect('db')->escapeString($s);
}

/**
 * Same as esc_sql() but also avoid '%'s.
 *
 * @param string $s
 * @return string
 */
function esc_sql_like($s) {
	$s = str_replace('%', '\%', $s);
	return esc_sql($s);
}

/**
 * HTML escape
 *
 * @param string $s
 * @return string
 */
function esc_html($s) {
	return htmlspecialchars($s);
}

/**
 * HTML escape print
 *
 * @param string
 * @return void
 */
function _esc_html($s) {
	echo htmlentities($s);
}

/**
 * Execute a simple query.
 * @see DB#getResults()
 */
function query($query) {
	return expect('db')->query($query);
}

/**
 * Execute a query and return an array of objects.
 *
 * @param string $query SQL query
 * @param string $class Class name to encapsulate the result set
 * @return array
 * @see DB#getResults()
 */
function query_results($query, $class = null, $args = [] ) {
	return expect('db')->getResults($query, $class, $args);
}

/**
 * Execute a query and return an object.
 *
 * @param string $query SQL query
 * @param string $class Class name to encapsulate the result set
 * @return null|Object
 * @see DB#getRow()
 */
function query_row($query, $class = null, $args = [] ) {
	return expect('db')->getRow($query, $class, $args);
}

/**
 * Execute a query and return a single value.
 * @see DB#getValue()
 */
function query_value($query, $value, $class = null) {
	return expect('db')->getValue($query, $value, $class);
}

/**
 * Database table full with prefix.
 *
 * @param string $t Table name (as 'test')
 * @return string Table name with prefix (as '`site01_test`')
 * @see DB#getTable()
 */
function T($t, $as = false) {
	return expect('db')->getTable($t, $as);
}

define('T', 'T');
define('JOIN', 'JOIN');
// Stupid shurtcut for string context
$GLOBALS[T] = function($t, $as = false) {
	return T($t, $as = false);
};

// Stupid shortcut for string context for listing tables
$GLOBALS[JOIN] = function($t) {
	return expect('db')->getTables( func_get_args() );
};

/**
 * Insert a row in the specified database table.
 * @param string $table
 * @param DBCols[]
 * @see DB#insertRow()
 */
function insert_row($table, $cols) {
	return expect('db')->insertRow($table, $cols);
}

/**
 * If the table has an AUTOINCREMENT you can get the last inserted index
 * after an insert_row().
 * @return int
 * @see DB#getLastInsertedID()
 */
function last_inserted_ID() {
	isset( $GLOBALS['db'] )
		|| error_die( __("Manca la connessione al database. Come ottenere l'ultimo indice?") );

	return expect('db')->getLastInsertedID();
}

/**
 * Insert multiple values in the specified database table
 * @param string $table
 * @param array $cols
 * @param array $values
 * @see DB#insert()
 */
function insert_values($table, $cols, $values) {
	return expect('db')->insert($table, $cols, $values);
}

/**
 * Update rows in the specified database table
 * @param string $table
 * @param DBCol[] $cols
 * @param string $condition
 * @see DB#update()
 */
function query_update($table, $cols, $condition, $after = '') {
	expect('db')->update($table, $cols, $condition, $after);
}

/**
 * Alias for htmlspecialchars().
 * @param string $s
 * @return string
 */
function esc_attr($s) {
	return htmlspecialchars($s);
}

/**
 * @param string
 * @return void
 */
function _esc_attr($s) {
	echo htmlspecialchars($s);
}

/*
 * Friendly symlinks
 */
function register_mimetypes($category, $mimetypes) {
	expect('mimeTypes')->registerMimetypes($category, $mimetypes);
}
function get_mimetypes($category = null) {
	return expect('mimeTypes')->getMimetypes($category);
}
/**
 * @param string $role User role
 * @param string|string[] $permissions Permissions
 */
function register_permissions($role, $permissions) {
	expect('permissions')->registerPermissions($role, $permissions);
}
/**
 * @param string $role_to New role
 * @param string $role_from Existing role
 */
function inherit_permissions($role_to, $role_from, $other_permissions = []) {
	expect('permissions')->inheritPermissions($role_to, $role_from, $other_permissions);
}
function register_js($javascript_uid, $url, $position = null) {
	return expect('javascript')->register( $javascript_uid, $url, $position );
}
function enqueue_js($javascript_uid, $position = null) {
	expect('javascript');
	return $GLOBALS['javascript']->enqueue( $javascript_uid, $position );
}
function register_css($css_uid, $url) {
	return expect('css')->register($css_uid, $url);
}
function enqueue_css($css_uid) {
	return expect('css')->enqueue($css_uid);
}
function add_menu_entries($menuEntries) {
	expect('menu')->add($menuEntries);
}
function get_menu_entry($uid) {
	return expect('menu')->getMenuEntry($uid);
}
function get_children_menu_entries($parentUid) {
	return expect('menu')->getChildrenMenuEntries($parentUid);
}
function register_module($module_uid) {
	return expect('module')->register($module_uid);
}
function inject_in_module($module_uid, $callback) {
	return expect('module')->injectFunction($module_uid, $callback);
}
function load_module($module_uid) {
	return expect('module')->loadModule($module_uid);
}
function get_table_prefix() {
	return expect('db')->getPrefix();
}
function register_option($option_name) {
	return expect('db')->registerOption($option_name);
}
function get_option($option_name, $default_value = '') {
	return expect('db')->getOption($option_name, $default_value);
}
function set_option($option_name, $option_value, $option_autoload = true) {
	return expect('db')->setOption($option_name, $option_value, $option_autoload);
}
function remove_option($option_name) {
	return expect('db')->removeOption($option_name);
}
/**
 * Get the current logged user.
 *
 * @param null|string $property Property name
 * @return mixed|Sessionuser Property, or entire Sessionuser object.
 */
function get_user( $property = null ) {
	$user = expect('session')->getUser();
	if( null === $property ) {
		return $user;
	}
	return $user ? $user->get( $property ) : null;
}
/**
 * Try to login using $_POST['user_uid'] and $_POST['user_password'].
 *
 * @param int $status
 * @see Session::login()
 */
function login(& $status = null, $user_uid = null, $user_password = null) {
	return expect('session')->login($status, $user_uid, $user_password);
}
function logout() {
	return expect('session')->destroy();
}
function register_language($code, $aliases = [], $encode = null, $iso = null, $human = null) {
	return expect('registerLanguage')->registerLanguage($code, $aliases, $encode, $iso, $human);
}
function register_default_language($default) {
	return expect('registerLanguage')->setDefaultLanguage($default);
}
function find_language($language_alias) {
	return expect('registerLanguage')->getLanguage($language_alias);
}
function apply_language($language_alias = null) {
	return expect('registerLanguage')->applyLanguage($language_alias);
}
function latest_language() {
	return expect('registerLanguage')->getLatestLanguageApplied();
}
function get_num_queries() {
	if( isset( $GLOBALS['db'] ) ) {
		return $GLOBALS['db']->getNumQueries();
	}
	return 0;
}
function is_logged() {
	return expect('session')->isLogged();
}

defined('DEFAULT_USER_ROLE')
	or define('DEFAULT_USER_ROLE', 'UNREGISTERED');

/**
 * @param string $permission Permission uid
 * @param User|null $user Specified user
 * @return bool
 */
function has_permission($permission, $user = null) {
	$session = expect('session');

	if( $user === null ) {
		$user = $session->getUser();
	}

	$user_role = false;
	if($user) {
		$user_role = $user->user_role;
	}
	if( ! $user_role ) {
		$user_role = DEFAULT_USER_ROLE;
	}
	return expect('permissions')->hasPermission($user_role, $permission);
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
function site_page($page, $url = null, $base = null) {
	$first = @$page[0];
	if( $first === '#' ) return $page;
	if( $first === '/' ) {
		if( @$page[1] === '/' ) {
			return $page;
		}
		if($base === null) {
			$base = PROTOCOL . DOMAIN . PORT;
		}
		return $base . $page;
	}
	if( preg_match('#^[a-z]+://#', $page) === 1 ) {
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
 * @deprecated Use mb_strimwidth
 */
function str_truncate($s, $max_length, $blabla = '', $encoding = null ) {
	if( ! $encoding ) {
		$encoding = mb_internal_encoding();
	}
	return mb_strimwidth($s, 0, $max_length, $blabla, $encoding);
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
function http_redirect($url, $http_response_code = 0) {
	header("Location: $url", true, $http_response_code);
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
 * @see use PROTOCOL
 */
function URL_protocol() {
	return is_https() ? 'https://' : 'http://';
}

/**
 * Get the domain of the request
 * @see DOMAIN
 */
function URL_domain() {
	return empty( $_SERVER['SERVER_NAME'] ) ? 'localhost' : $_SERVER['SERVER_NAME'];
}

/**
 * Get the explicit port of the request
 * @see PORT
 * @return string
 */
function URL_port() {
	if( isset( $_SERVER[ 'SERVER_PORT' ] ) ) {
		$p = (int) $_SERVER[ 'SERVER_PORT' ];
		if( 80 !== $p && 443 !== $p ) {
			return ":$p";
		}
	}
	return '';
}

function URL_root() {
	$root = this_folder();
	if( $root === _ ) {
		return '';
	}
	return $root;
}

/**
 * Get a secured version of a string
 */
function generate_slug($s, $max_length = -1, $glue = '-', & $truncated = false) {
	return Slug::get($s, $max_length, $glue, $truncated);
}

function http_build_get_query($url, $data) {
	$data = http_build_query($data);
	if( $data ) {
		return "$url?$data";
	}
	return $url;
}

/**
 * HTTP 503 headers.
 * @see Shit::header503()
 */
function http_503() {
	return Shit::header503();
}

/**
 * It scares the user with an error message (and dies).
 * @see Shit::SWOD()
 */
function error_die( $msg ) {
	Shit::WSOD( $msg );
}

function error( $msg ) {
	echo Shit::getErrorMessage( $msg );
}

/**
 * Translates a string
 *
 * @param string $msgid String to be translated
 * @return string translated string (or original, if not found)
 */
function __( $msgid ) {

	// does the user want a native GNU GETTEXT implementation? cache it.
	static $native = null;
	if( null === $native ) {
		$native = expect( 'registerLanguage' )->isNative();
	}

	if( $native ) {
		// low-level GNU Gettext call (quicker but the locales must be installed by a sysadmin, and other cache problems)
		return _( $msgid );
	}

	// high-level GNU Gettext (simpler but slower)
	return MoLoader::getInstance()->getTranslator()->gettext( $msgid );
}

/**
 * Shortcut for echoing a translated string
 */
function _e( $s ) {
	echo __( $s );
}

function http_json_header($charset = null) {
	if( $charset === null ) {
		$charset = CHARSET;
	}
	header('Content-Type: application/json; charset=' . $charset);
}

/**
 * Get the MIME type of a file.
 * @see MimeTypes::fileMimetype()
 */
function get_mimetype($filepath, $pure = false) {
	return MimeTypes::fileMimetype( $filepath, $pure = false );
}

/**
 * Know if a file belongs to a certain category
 * @see MimeTypes::isMimetypeInCategory()
 */
function is_file_in_category($filepath, $category) {
	$mime = get_mimetype($filepath);
	return expect('mimeTypes')->isMimetypeInCategory($mime , $category);
}

/**
 * Get the file extension
 */
function get_file_extension_from_expectations($filename, $category) {
	return expect('mimeTypes')->getFileExtensionFromExpectations($filename, $category);
}

function is_image($filepath) {
	return is_file_in_category($filepath, 'image');
}

function is_audio($filepath) {
	return is_file_in_category($filepath, 'audio');
}

function is_video($filepath) {
	return is_file_in_category($filepath, 'video');
}

function is_document($filepath) {
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
		return __("NaN");
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
	if( file_exists($path) || mkdir($path, $chmod, true) ) {
		return true;
	}
	DEBUG && error( sprintf(
		__("Impossibile scrivere il percorso '%s'."),
		esc_html( $path )
	) );
	return false;
}

/**
 * @see FileUploader::searchFreeFilename()
 */
function search_free_filename( $filepath, $filename, $ext, $args, $build_filename = null ) {
	return FileUploader::searchFreeFilename( $filepath, $filename, $ext, $args, $build_filename = null );
}

/**
 * I use this to clean user input before DB#insert()
 *
 * @param string $s Input string
 * @param int $max Max length
 */
function luser_input($s, $max) {
	$s = trim($s);
	return mb_strimwidth($s, 0, $max, '');
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
