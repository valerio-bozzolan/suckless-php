<?php
# Copyright (C) 2015, 2016, 2017, 2018, 2019 Valerio Bozzolan
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <http://www.gnu.org/licenses/>.

/**
 * Check if the user do not want to be tracked
 *
 * See the HTTP 'DNT' header.
 *
 * @return boolean
 */
function do_not_track() {
	return isset( $_SERVER['HTTP_DNT'] )
	           && $_SERVER['HTTP_DNT'] === '1';
}


/*
 * Configuration helpers
 *
 * Shortcuts useful when you declare a configuration file.
 */

/**
 * Define a constant if it does not exist
 *
 * @param string $name  Constant name
 * @param string $value Constant value
 */
function define_default( $name, $value ) {
	defined( $name ) or
	define(  $name, $value );
}


/*
 * Template helpers
 *
 * Shortcuts useful when you build a page
 */

/**
 * Return a ' selected="selected"' HTML attribute under some circumstances
 *
 * @param mixed $helper  If this is the only arg, return
 * @param mixed $current If this matches $helper, return
 * @param bool  $force   If this is true, return
 */
function selected( $helper = PHP_INT_MAX, $current = PHP_INT_MAX, $force = false ) {
	return html_attribute_when_matching( 'selected', 'selected', $helper, $current, $force);
}

/**
 * Return a ' checked="checked"' HTML attribute under some circumstances
 *
 * This is useful for the <input type="checkbox" /> HTML tag.
 *
 * @param mixed $helper  If this is the only arg, return
 * @param mixed $current If this matches $helper, return
 * @param bool  $force   If this is true, return
 */
function checked( $helper = PHP_INT_MAX, $current = PHP_INT_MAX, $force = false ) {
	return html_attribute_when_matching( 'checked', 'checked', $helper, $current, $force);
}

/**
 * Return a ' disabled="disabled"' HTML attribute under some circumstances
 *
 * @param mixed $helper  If this is the only arg, return
 * @param mixed $current If this matches $helper, return
 * @param bool  $force   If this is true, return
 */
function disabled( $helper = PHP_INT_MAX, $current = PHP_INT_MAX, $force = false ) {
	return html_attribute_when_matching( 'disabled', 'disabled', $helper, $current, $force );
}

/**
 * Return an HTML attribute under some circumstances
 *
 * @param string $attribute HTML attribute name e.g. 'disabled'
 * @param string $value     HTML attribute value e.g.
 * @param mixed  $helper    If this is the only arg, return
 * @param mixed  $current   If this matches $helper, return
 * @param bool   $force     If this is true, return
 */
function html_attribute_when_matching( $attribute, $value, $helper = PHP_INT_MAX, $current = PHP_INT_MAX, $force = false ) {
	if( $helper === $current || $helper && PHP_INT_MAX === $current || $force ) {
		return HTML::property( $attribute, $value );
	}
	return '';
}

/**
 * Get an HTML attribute ' value="$v"'
 *
 * The value will be sanitized.
 *
 * @param string $v
 * @return string
 */
function value( $v ) {
	return HTML::property( 'value', $v );
}

/**
 * Shortcut for htmlentities()
 *
 * Return an HTML-sanitized untrusted string to be safe from XSS.
 *
 * @param  string $s e.g. 'Hello<script>...'
 * @return string e.g. 'Hello&lt;script&gt;...'
 */
function esc_html( $s ) {
	return htmlentities( $s );
}

/**
 * Enfatize a sub-string
 *
 * This is helpful when highlighting search results
 *
 * @param  string $heystack e.g. 'The quick brown fox'
 * @param  string $needle   e.g. 'quick'
 * @param  string $pre      HTML markup put before the found $needle
 * @param  string $post     HTML markup put after fhe found $needle
 * @return string e.g. 'The <b>quick</b> brown fox'
 */
function enfatize_substr( $heystack, $needle, $pre = '<b>', $post = '</b>' ) {
	return OutputUtilities::enfatizeSubstr( $heystack, $needle, $pre, $post );
}


/*
 * Conversion shortcuts
 */


/**
 * Force a variable to be an array
 *
 * @return mixed|array $a
 */
function force_array( & $a ) {
	if( ! is_array( $a ) ) {
		$a = [ $a ];
	}
}


/*
 * Database shourtcuts
 */

/**
 * Sanitize an SQL value to be safe from SQL injections
 *
 * @param  string $s
 * @return string
 */
function esc_sql( $s ) {
	return DB::instance()->escapeString( $s );
}

/**
 * Sanitize an SQL value to be safe from SQL injections and escape also '%'
 *
 * @param  string $s
 * @return string
 */
function esc_sql_like( $s ) {
	$s = str_replace( '%', '\%', $s );
	return esc_sql( $s );
}

/**
 * Execute whatever query
 *
 * @param string $query SQL query
 * @see DB#query()
 * @return object
 */
function query( $query ) {
	return DB::instance()->query( $query );
}

/**
 * Execute a query and return an array of objects
 *
 * Note: Use query_generator() if you do not need the entire array.
 *
 * @param  string $query      SQL query
 * @param  string $class_name Class name to encapsulate the result set
 * @param  array  $args       Arguments to be passed to the constructor of $class_name
 * @return array
 */
function query_results( $query, $class_name = null, $args = [] ) {
	return DB::instance()->getResults( $query, $class_name, $args );
}

/**
 * Execute a query and return a generator
 *
 * Note: Use query_results() if you need the entire array.
 *
 * @param  string $query      SQL query
 * @param  string $class_name Class name to encapsulate the result set
 * @param  array  $args       Arguments to be passed to the constructor of $class_name
 * @return array
 */
function query_generator( $query, $class_name = null, $args = [] ) {
	return DB::instance()->getGenerator( $query, $class_name, $args );
}

/**
 * Execute a query and return a single row, as an object
 *
 * @param  string       $query SQL  query
 * @param  string       $class_name Class name to encapsulate the result set
 * @return object|null              Arguments to be passed to the constructor of $class_name
 */
function query_row( $query, $class_name = null, $args = [] ) {
	return DB::instance()->getRow( $query, $class_name, $args );
}

/**
 * Execute a query and return a single column from a single row
 *
 * @param string $query SQL query
 * @param string $field Field to be returned
 * @see DB#getValue()
 */
function query_value( $query, $field, $class_name = null ) {
	return DB::instance()->getValue( $query, $field, $class_name );
}

/**
 * Executes multiple queries concatenated by a semicolon
 *
 * @param string $queries SQL queries
 */
function multiquery( $queries ) {
	return DB::instance()->multiQuery( $queries );
}

/**
 * Get a database table name, full with its prefix, eventually aliased
 *
 * Note that the prefix is declared in your configuration file as $prefix.
 *
 * @param  string  $table Table name e.g. 'test'
 * @param  boolean        If true, eventually strip the prefix with an alias
 * @return string         Table name e.g. 'site01_test'
 */
function T( $table, $as = false ) {
	return DB::instance()->getTable( $table, $as );
}

/**
 * Insert a row in a database table
 *
 * @param string  $table  Table name
 * @param DBCol[] $cols   Array of DBCol objects (with column, value and type)
 * @param array   $args   Associative arguments for the query
 *                          * 'replace-into' boolean Run a REPLACE INTO instead of just an INSERT INTO
 */
function insert_row( $table, $cols, $args = [] ) {
	return DB::instance()->insertRow( $table, $cols, $args );
}

/**
 * Get the last AUTOINCREMENT value created after an INSERT query
 *
 * @return int
 */
function last_inserted_ID() {
	if( ! DB::instanced() ) {
		throw new SucklessException( 'cannot obtain last inserted ID without database connection' );
	}
	return DB::instance()->getLastInsertedID();
}

/**
 * Insert multiple rows in a database table
 *
 * @param string $table  Table name
 * @param array  $cols   Array of columns with their escape e.g. [ 'id' => 'd', 'name' => 's' ]
 * @param array  $values Array of rows e.g. [ [ 1, 'stallman' ], [ 2, 'torvalds' ]
 */
function insert_values( $table, $cols, $values ) {
	return DB::instance()->insert( $table, $cols, $values );
}

/**
 * Update rows in the specified database table
 *
 * @param string  $table Table name
 * @param DBCol[] $cols  Array of DBCol objects (with column, value and type)
 * @param string  $cond  SQL condition (after the WHERE part)
 * @see DB#update()
 */
function query_update( $table, $cols, $cond, $after = '' ) {
	DB::instance()->update( $table, $cols, $cond, $after );
}

/**
 * Shortcut for htmlspecialchars()
 *
 * Get a sanitized value for an HTML attribute value (in double quotes).
 *
 * @param string $s
 * @return string
 */
function esc_attr( $s ) {
	return htmlspecialchars( $s );
}

/**
 * Shortcut for echoing htmlspecialchars()
 *
 * Print a sanitized value for an HTML attribute value (in double quotes).
 *
 * @param string $s
 */
function _esc_attr( $s ) {
	echo htmlspecialchars( $s );
}

/**
 * Associate some MIME types to a category
 *
 * @param string $category  e.g. 'compressed'
 * @param string $mimetypes e.g. [ 'tgz' => 'application/x-tar', 'gzip' => 'application/x-bzip', ]
 */
function register_mimetypes( $category, $mimetypes ) {
	MimeTypes::instance()->registerMimetypes($category, $mimetypes);
}

/**
 * Get the MIME types of a category, or all the accepteds
 *
 * @param  string $category e.g. 'image'
 * @return string
 */
function get_mimetypes( $category = null ) {
	return MimeTypes::instance()->getMimetypes( $category );
}

/**
 * Register permissions to a role
 *
 * @param string       $role        User role
 * @param array|string $permissions Permissions
 */
function register_permissions($role, $permissions) {
	Permissions::instance()->registerPermissions($role, $permissions);
}

/**
 * Give some permissions to a role, inheriting from an old one
 *
 * @param string       $role_to     Role to give permissions
 * @param string       $role_from   Role from inheriting permissions
 * @param array|string $permissions Extra permissions to be add to $role_to
 */
function inherit_permissions( $role_to, $role_from, $permissions = [] ) {
	Permissions::instance()->inheritPermissions( $role_to, $role_from, $permissions );
}

/**
 * Register a JavaScript file to be enqueued later
 *
 * @param string $uid          Script name e.g. 'jquery'
 * @param string $url          Script URL e.g. '/javascript/jquery/jquery.min.js'
 * @param string $position     Choose between 'header' or 'footer'
 * @param array  $dependencies Array of script names used as dependencies
 */
function register_js( $uid, $url, $position = null, $dependencies = [] ) {
	RegisterJS::instance()->register( $uid, $url, $position, $dependencies );
}

/**
 * Register an inline JavaScript script and attach to a registered script
 *
 * @param string $uid      Dependent script name e.g. 'jquery'
 * @param string $data     Script body e.g. '$(document).find()...'
 * @param string $position Choose between 'after' or 'before' related to $uid execution
 */
function register_js_inline( $uid, $data, $position = 'after' ) {
	RegisterJS::instance()->registerInline($uid, $data, $position);
}

/**
 * Register an inline JavaScript variable and attach to a registered script
 *
 * @param string $uid      Dependent script name e.g. 'my-map'
 * @param string $variable Variable declaration e.g. 'var coordinates'
 * @param mixed  $value    Variable content (can be an array, an object, a callback, etc.)
 * @param string $position Choose between 'after' or 'before' related to $uid execution
 */
function register_js_var( $uid, $variable, $value, $position = 'before' ) {
	if( is_closure( $value ) ) {
		$value = $value();
	}
	$data = json_encode( $value, DEBUG ? JSON_PRETTY_PRINT : 0 );
	register_js_inline( $uid, "$variable = $data;", $position );
}

/**
 * Mark a registered script for usage
 *
 * Note that if there is a related stylesheet, it will be enqueued as well.
 *
 * @param string $uid      Dependent script name e.g. 'my-map'
 * @param string $position Choose between 'header' or 'footer'
 */
function enqueue_js( $uid, $position = null ) {
	return RegisterJS::instance()->enqueue( $uid, $position );
}

/**
 * Register a CSS stylesheet
 *
 * @param string $uid Stylesheet name e.g. 'materializecss'
 * @param string $url Stylesheet URL
 */
function register_css( $uid, $url ) {
	return RegisterCSS::instance()->register( $uid, $url );
}

/**
 * Mark a registered stylesheet for usage
 *
 * @param string $uid Stylesheet name e.g. 'materializecss'
 */
function enqueue_css( $uid ) {
	return RegisterCSS::instance()->enqueue( $uid );
}

/**
 * Register some menu entries
 *
 * @param MenuEntry[] $entries
 */
function add_menu_entries( $entries ) {
	Menu::instance()->add( $entries );
}

/**
 * Get a menu entry
 *
 * @param string $uid
 * @return MenuEntry
 */
function menu_entry( $uid ) {
	return Menu::instance()->getMenuEntry( $uid );
}

function get_children_menu_entries($uid) {
	return Menu::instance()->getChildrenMenuEntries($uid);
}

/**
 * Register a module
 *
 * @param string $uid Module position
 */
function register_module( $uid ) {
	return RegisterModule::instance()->register($uid);
}

/**
 * Inject a closure into a module
 *
 * @param string $uid
 * @param closure $callback
 */
function inject_in_module( $uid, $callback ) {
	return RegisterModule::instance()->injectFunction($uid, $callback);
}

/**
 * Run the actions registered in the specified module
 *
 * @param string $uid
 */
function load_module( $uid ) {
	return RegisterModule::instance()->loadModule($uid);
}

/**
 * Get the database table prefix
 *
 * @return string
 */
function get_table_prefix() {
	return DB::instance()->getPrefix();
}

/**
 * Register an option to be used later
 *
 * The options are stored in the database.
 *
 * @param string $name
 */
function register_option( $name ) {
	return DB::instance()->registerOption( $name );
}

/**
 * Get the value of an option
 *
 * The options are stored in the database.
 *
 * @param string $name Option name
 * @param string $default Option default value
 * @return string Option value
 */
function get_option( $name, $default = '' ) {
	return Options::instance()->get( $name, $default );
}

/**
 * Set the value of an option
 *
 * The options are stored in the database.
 *
 * @param string  $name     Option name
 * @param string  $value    Option value
 * @param boolean $autoload Set to false if this option is not used on the majority of the requests
 */
function set_option( $name, $value, $autoload = true ) {
	return Options::instance()->set( $name, $value, $autoload );
}

/**
 * Remove an option
 *
 * The options are stored in the database.
 *
 * @param string $name Option name
 */
function remove_option( $name ) {
	return Options::instance()->remove( $name );
}

/**
 * Get the current logged user, or just a property
 *
 * @param  string $property Property name or NULL to retrieve the whole object
 * @return object           Value of $property, or entire user (Sessionuser) object if $property is NULL
 */
function get_user( $property = null ) {
	$user = Session::instance()->getUser();
	if( $property === null ) {
		return $user;
	}
	return $user ? $user->get( $property ) : null;
}

/**
 * Try to login using POST 'user_uid' and 'user_password' fields
 *
 * Note that this will not involve sessions.
 *
 * @param  int    $status Login status code
 * @param  string $uid    User uid
 * @param  string $pwd    Password
 * @return boolean        Login successful or not
 */
function login(& $status = null, $uid = null, $pwd = null) {
	return Session::instance()->login($status, $uid, $pwd);
}

/**
 * Destroy user cookies
 */
function logout() {
	return Session::instance()->destroy();
}

/**
 * To be used inside a form with POST method to contrast CSRF attacks
 *
 * @param string $action Your action name e.g. 'save-user'
 */
function form_action( $action ) {
	Session::instance()->formActionWithCSRF( $action );
}

/**
 * Check if a POST action is the specified one
 *
 * @param string $action Your action name e.g. 'save-user'
 * @return boolean
 */
function is_action( $action ) {
	return Session::instance()->validateActionAndCSRF( $action );
}

/**
 * Require a CSRF token to use the actual page
 */
function require_csrf() {
	Session::instance()->getCSRF();
}

/**
 * Register a language
 *
 * @param string $code    Language code e.g. 'en_US'
 * @param array  $aliases Language aliases e.g. [ 'en_GB' ]
 * @param string $iso     Language iso code e.g. 'en'
 * @param string $human   Human language name e.g. 'English'
 */
function register_language( $code, $aliases = [], $encode = null, $iso = null, $human = null ) {
	return RegisterLanguage::instance()->registerLanguage( $code, $aliases, $encode, $iso, $human );
}

/**
 * Register the default language
 *
 * @param string $lang Language code, language alias, etc. e.g. 'en'
 */
function register_default_language( $lang ) {
	return RegisterLanguage::instance()->setDefaultLanguage( $lang );
}

/**
 * Find a language
 *
 * @param string $lang Language code, language alias, etc. e.g. 'en'
 */
function find_language( $lang ) {
	return RegisterLanguage::instance()->getLanguage( $lang );
}

/**
 * Apply a language to the next translations
 *
 * @param  string $lang Language code, language alias, etc e.g. 'en'
 * @return object       Language object
 */
function apply_language( $lang = null ) {
	return RegisterLanguage::instance()->applyLanguage( $lang );
}

/**
 * Retrieve the latest language applied
 *
 * @return object
 */
function latest_language() {
	return RegisterLanguage::instance()->getLatestLanguageApplied();
}

/**
 * Get a list of all the registered languages
 *
 * @return array
 */
function all_languages() {
	return RegisterLanguage::instance()->getAll();
}

/**
 * Get the number of queries run
 *
 * @return int
 */
function get_num_queries() {
	if( DB::instanced() ) {
		return DB::instance()->queries;
	}
	return 0;
}

/**
 * Check if the user is authenticated
 *
 * @return boolean
 */
function is_logged() {
	return Session::instance()->isLogged();
}

/**
 * Check if the user has a permission
 *
 * @param string $permission Permission code
 * @param object $user       An user object, different from the current one
 * @return bool
 */
function has_permission( $permission, $user = null ) {
	return Permissions::instance()->userHasPermission( $permission, $user );
}

/**
 * Add a directory to a base URL or a pathname
 *
 * If the base URL it is not defined, a slash ('/') is appended to the URL.
 * The base URL could end with a slash ('/') or not.
 *
 * @param  string $base Base URL or pathname with/without any slash at start
 * @param  string $dir  Directory or partial pathname with or without initial slashes
 * @param  string $glue Directory separator (as default it's the '/' for URLs)
 * @return string       The junction between $base and $glue
*/
function append_dir( $base, $dir = '', $glue = null ) {
	if( !$glue ) {
		// pick default URL separator '/'
		$glue = _;
	}
	$base = rtrim( $base, $glue );
	$dir  = ltrim( $dir,  $glue );
	return $base . $glue . $dir;
}

/**
 * Normalize a partial URI to a complete one, or an absolute one
 *
 * Few examples:
 *   * If the URI is an anchor, keep it as-is
 *   * If the URI starts with a protocol, keep it as-is
 *   * If the URI starts with a slash, keep it as-is or, if $full_url, append protocol and domain
 *   * If the URI it's a word like 'page.html', prepend ROOT or, if $full_url, append also protocol and domain
 *
 * @param $page     string  Whatever, a full URL, a relative pathname e.g. 'page.html', an absolute one, etc.
 * @param $full_url boolean Set to true if you want an URI with it's protocol and domain, and not just the pathname
 */
function site_page( $page, $full_url = false ) {
	$first = @$page[ 0 ];

	if( $first === '#' ) {
		return $page; // an anchor e.g. '#contacts'
	}

	if( $first === '/' ) {
		if( @$page[ 1 ] === '/' ) {
			return $page; // URI with relative protocol e.g. '//example.org'
		}

		return append_dir( $full_url ? BASE_URL : '', $page );
	} elseif( preg_match( '#^[a-z]+://#', $page ) === 1 ) {
		return $page; // URI with full protocol e.g. 'ftp://example.org'
	}

	// URI not starting with a slash e.g. 'contact.html'
	return append_dir( $full_url ? URL : ROOT, $page );
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
function multi_text( $n, $text_multi, $text_one, $text_no = '' ) {
	if( $n > 1 ) {
		return str_replace( '%', $n, $text_multi );
	}
	return $n == 1 ? $text_one : $text_no;
}

/**
 * Do an HTTP redirects and die
 *
 * @param string $url           URL (can be relative)
 * @param string $response_code HTTP response code (PHP's default it's 302)
 */
function http_redirect( $url, $response_code = 0 ) {
	$url = site_page( $url, true );
	header( "Location: $url", true, $response_code );
	exit;
}

/**
 * Get a search-engine friendly version of any string
 *
 * @param  string  $s          Input string
 * @param  int     $max_length Maximum string length (or zero for no limit)
 * @param  string  $glue       Word separator
 * @param  boolean $truncated  Flag to know if the string was truncated
 * @return string
 */
function generate_slug( $s, $max_length = 0, $glue = '-', & $truncated = false ) {
	return OutputUtilities::slug( $s, $max_length, $glue, $truncated );
}

/**
 * Build an HTTP GET URL from an associative array of arguments
 *
 * Note that NULL arguments will be automatically stripped out.
 *
 * @param  string $url  Input URL e.g. 'https://example.com/page.html'
 * @param  array  $data Associative array of parameters e.g. [ 'p' => '1' ]
 * @return string URL with GET data e.g. 'https://example.com/page.html?p=1'
 */
function http_build_get_query( $url, $data ) {
	$data = http_build_query( $data );
	return $data ? "$url?$data" : $url;
}

/**
 * HTTP 503 header
 *
 * Spawn a 503 HTTP status code
 */
function http_503() {
	OutputUtilities::header503();
}

/**
 * Scare the user with an error message and die
 *
 * @param string $msg
 */
function error_die( $msg ) {
	OutputUtilities::WSOD( $msg );
}

/**
 * Log an error message and eventually print it when DEBUG
 *
 * @param string $msg
 * @return void
 */
function error( $msg ) {
	OutputUtilities::error( $msg );
}

/**
 * Translate a string
 *
 * You should understand the amazing GNU Gettext workflow.
 *
 * @param  string $msgid  String to be translated
 * @param  string $domain Translation domain
 * @return string         Translated string (or original one)
 */
function __( $msgid, $domain = '' ) {
	// is native GNU GETTEXT implementation?
	static $native = null;
	if( null === $native ) {
		$native = RegisterLanguage::instance()->isNative();
	}

	// low-level GNU Gettext call
	if( $native ) {
		return _( $msgid );
	}

	// high-level GNU Gettext (simpler but slower)
	return MoLoader::instance()->getTranslator( $domain )->gettext( $msgid );
}

/**
 * Shortcut for echoing a translated string
 *
 * @param  string $msgid  String to be translated
 * @param  string $domain Translation domain
 * @return string         Translated string (or original one)
 */
function _e( $msgid, $domain = '' ) {
	echo __( $msgid, $domain );
}

/**
 * Declare a JSON document
 *
 * @param string $charset Document charset, if different from the default one
 */
function http_json_header( $charset = null ) {
	if( !$charset ) {
		$charset = CHARSET;
	}
	header( "Content-Type: application/json; charset=$charset" );
}

/**
 * Send a JSON (stripping out unuseful values) and die
 *
 * For performance related to data transfer, falsy elements are stripped out.
 *
 * @param $data mixed
 */
function json( $data, $flags = 0 ) {
	http_json_header();
	$data = OutputUtilities::compressData( $data );
	echo json_encode( $data, $flags );
	exit;
}

/**
 * Send a JSON error and die
 *
 * Note that in the real world it does not exist a JSON error,
 * this method is just a way to standardize it a bit.
 *
 * @param int    $http_code HTTP response status code e.g. '403'
 * @param string $code      Human error code e.g. 'unauthorized'
 * @param string $msg       Human error message e.g. 'You are not authorized to....'
 * @param int    $flags     Flags passed to json_encode()
 */
function json_error( $http_code, $code, $msg = null, $flags = 0 ) {
	http_response_code( $http_code );
	json( [ 'error' => [
		'code'    => $code,
		'message' => $msg,
	] ], $flags );
}

/**
 * Validate a CSRF token or exit with a JSON error
 *
 * @param string $csrf
 */
function json_require_csrf( $csrf = null ) {

	// eventually read the default value
	if( !$csrf && isset( $_POST['csrf'] ) ) {
		$csrf = $_POST['csrf'];
	}

	// no CSRF no party: die
	if( $csrf !== Session::instance()->getCSRF() ) {
		json_error( 403, 'forbidden-invalid-csrf', __( "This request was not executed for security reasons. Eventually try to reload the page and try again (invalid CSRF)." ) );
	}
}

/**
 * Get the MIME type of a file
 *
 * @param string $filepath Filesystem file path
 * @see MimeTypes::fileMimetype()
 */
function get_mimetype( $filepath, $pure = false ) {
	return MimeTypes::fileMimetype( $filepath, $pure = false );
}

/**
 * Check if a file belongs to a certain category
 *
 * @param string $filepath Filesystem file path
 * @param string $category File category e.g. 'image'
 * @see MimeTypes#isMimetypeInCategory()
 */
function is_file_in_category( $filepath, $category ) {
	$mime = get_mimetype( $filepath );
	return MimeTypes::instance()->isMimetypeInCategory( $mime, $category );
}

/**
 * Extract the file extension from a filename (if it respects the file MIME type)
 *
 * @param  string $filename Filesystem file path
 * @param  string $category File category e.g. 'image'
 * @return mixed File extension or false if it was not found
 */
function get_file_extension_from_expectations( $filename, $category ) {
	return MimeTypes::instance()->getFileExtensionFromExpectations( $filename, $category );
}

/**
 * Check if a file is an image
 *
 * @param string $filepath
 * @return boolean
 */
function is_image( $path ) {
	return is_file_in_category( $path, 'image' );
}

/**
 * Check if a file is an audio
 *
 * @param string $path
 * @return boolean
 */
function is_audio( $path ) {
	return is_file_in_category( $path, 'audio' );
}

/**
 * Check if a file is a video
 *
 * @param string $path
 * @return boolean
 */
function is_video( $path ) {
	return is_file_in_category( $path, 'video' );
}

/**
 * Check if a file is a document
 *
 * @param string $path
 */
function is_document( $path ) {
	return is_file_in_category( $path, 'document' );
}

/**
 * Validate a closure
 *
 * @param mixed $t
 * @return boolean
 */
function is_closure( $t ) {
	return is_object( $t ) && $t instanceof Closure;
}

/**
 * Get the human filesize from bytes
 *
 * @param $filesize int bytes
 * @param $glue string
 * @return string
 */
function human_filesize( $filesize, $glue = ' ' ) {
	return OutputUtilities::humanFilesize( $filesize, $glue );
}

/*
 * Create a directory
 *
 * @param $path string
 * @param $chmod string
 */
function create_path( $path, $chmod = null ) {
	return FileUploader::createPath( $path, $chmod );
}

/**
 * @see FileUploader::searchFreeFilename()
 */
function search_free_filename( $filepath, $filename, $ext, $args, $build_filename = null ) {
	return FileUploader::searchFreeFilename( $filepath, $filename, $ext, $args, $build_filename );
}

/**
 * I use this function to clean a stupid user input string before
 * sanitizing and sending it to the database server.
 *
 * This is useful to silently avoid some inappropriate uses:
 *  do not send long LIKE queries to the database server
 *  do not output long «Search results for: xxx»
 *
 * This may be useful for non-important information. For example when
 * you have a search field that should be not longer than 200 chars
 * and instead of throwing an exception you can just drop the surplus.
 *
 * It casts to string on order to avoid a warning when a malicious
 * user try to send something else like an array (using 'foo[]=asd').
 *
 * Do not use this for important stuff. Do more checks.
 *
 * @param string $s Input string
 * @param int $max Max length
 * @return string
 */
function luser_input( $s, $max ) {
	$s = (string) $s;
	return mb_strimwidth( trim( $s ), 0, $max, '' );
}

/**
 * Get the number of milliseconds of this request age
 *
 * @param int $precision number of decimal digits to round to
 * @return Execution time
 */
function get_page_load( $decimals = 6 ) {
	return round( microtime( true ) - $_SERVER[ 'REQUEST_TIME_FLOAT' ], $decimals );
}

// shortcuts to build SELECT * query
define('DOT',  '.');
define('STAR', '*');

/*
 * Deprecated zone
 *
 * @TODO remove the shit below this line
 */

/**
 * Truncate a string if it's over a specific length
 *
 * You can specify the end of the string if it's truncated.
 *
 * @param string $s          Input string
 * @param int    $max_length Max string length
 * @param string $blabla     Optional. If $s length is over $max_length, $blabla it's appended after $s
 * @deprecated Use mb_strimwidth
 */
function str_truncate($s, $max_length, $blabla = '', $encoding = null ) {
	error( "deprecated str_truncate() use mb_strimwidth() instead" );
	if( ! $encoding ) {
		$encoding = mb_internal_encoding();
	}
	return mb_strimwidth($s, 0, $max_length, $blabla, $encoding);
}

define('T', 'T');

define('JOIN', 'JOIN');

// Stupid shurtcut for string context
$GLOBALS[T] = function( $t, $as = false ) {
	return T($t, $as = false);
};

// Stupid shortcut for string context for listing tables
$GLOBALS[JOIN] = function( $t ) {
	return DB::instance()->getTables( func_get_args() );
};

function get_menu_entry( $uid ) {
	error( "deprecated get_menu_entry() use menu_entry() instead" );
	return menu_entry( $uid );
}

function _esc_html( $s ) {
	error( 'deprecated _esc_html' );
	echo htmlentities( $s );
}

function _checked( $helper = PHP_INT_MAX, $current = PHP_INT_MAX, $force = false ) {
	error( 'deprecated _checked() use echo checked()' );
	echo checked( $helper, $current, $force );
}

function _selected( $helper = PHP_INT_MAX, $current = PHP_INT_MAX, $force = false ) {
	error( 'deprecated _selected() use echo selected()' );
	echo selected( $helper, $current, $force );
}

function _disabled( $helper = PHP_INT_MAX, $current = PHP_INT_MAX, $force = false ) {
	error( 'deprecated _disabled() use echo disabled()' );
	echo disabled( $helper, $current, $force );
}

function _required( $helper = PHP_INT_MAX, $current = PHP_INT_MAX, $force = false ) {
	error( 'deprecated _required() print it instead' );
	echo html_attribute_when_matching( 'required', 'required', $helper, $current, $force );
}

function _value( $v ) {
	error( 'deprecated _value() use echo value() insted' );
	echo value( $v );
}
