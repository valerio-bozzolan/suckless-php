## Documentation

### `function expect($global_var)`

Retrieve a required global object.

 * **Parameters:** `$global_var` — `string` — Global variable name
 * **Returns:** `Object` — 
 * **See also:** G#expect()

### `function register_expected($name, $class)`

Register a required global object.

 * **Parameters:**
   * `string` — variable name
   * `string` — name

### `function force_array( & $a )`

Force something to be an array.

 * **Returns:**
   * `mixed|array` — 
   * `array` — 

### `function enfatize_substr($s, $q, $pre = "<b>", $post = "<`

Enfatize a substring.

 * **Parameters:**
   * `$s` — Heystack
   * `$q` — Needle
   * `$pre` — HTML before query (bold tag as default)
   * `$post` — HTML after query (bold tag as default)
 * **Returns:** `string` — Enfatized string
 * **To-do:** Move in it's own class

### `function esc_sql($s)`

SQL query escape string

 * **Parameters:** `$str` — `string` — 
 * **Returns:** `string` — 
 * **See also:** DB#escapeString()

### `function esc_sql_like($s)`

Same as esc_sql() but also avoid '%'s.

 * **Parameters:** `$s` — `string` — 
 * **Returns:** `string` — 

### `function esc_html($s)`

HTML escape

 * **Parameters:** `$s` — `string` — 
 * **Returns:** `string` — 

### `function _esc_html($s)`

HTML escape print

 * **Parameters:** `string` — 
 * **Returns:** `void` — 

### `function query($query)`

Execute a simple query.

 * **Parameters:** `$query` — `string` — SQL query
 * **See also:** DB#getResults()

### `function query_results($query, $class = null, $args = [] )`

Execute a query and return an array of objects.

 * **Parameters:**
   * `$query` — `string` — SQL query
   * `$class` — `string` — Class name to encapsulate the result set
 * **Returns:** `array` — 
 * **See also:** DB#getResults()

### `function query_row($query, $class = null, $args = [] )`

Execute a query and return an object.

 * **Parameters:**
   * `$query` — `string` — SQL query
   * `$class` — `string` — Class name to encapsulate the result set
 * **Returns:** `null|Object` — 
 * **See also:** DB#getRow()

### `function query_value($query, $value, $class = null)`

Execute a query and return a single value.

 * **See also:** DB#getValue()

### `function T($t, $as = false)`

Database table full with prefix.

 * **Parameters:** `$t` — `string` — Table name (as 'test')
 * **Returns:** `string` — Table name with prefix (as '`site01_test`')
 * **See also:** DB#getTable()

### `function insert_row($table, $cols)`

Insert a row in the specified database table.

 * **Parameters:**
   * `$table` — `string` — 
   * `DBCols[]` — 
 * **See also:** DB#insertRow()

### `function last_inserted_ID()`

If the table has an AUTOINCREMENT you can get the last inserted index after an insert_row().

 * **Returns:** `int` — 
 * **See also:** DB#getLastInsertedID()

### `function insert_values($table, $cols, $values)`

Insert multiple values in the specified database table

 * **Parameters:**
   * `$table` — `string` — 
   * `$cols` — `array` — 
   * `$values` — `array` — 
 * **See also:** DB#insert()

### `function query_update($table, $cols, $condition, $after = '')`

Update rows in the specified database table

 * **Parameters:**
   * `$table` — `string` — 
   * `$cols` — `DBCol[]` — 
   * `$condition` — `string` — 
 * **See also:** DB#update()

### `function esc_attr($s)`

Alias for htmlspecialchars().

 * **Parameters:** `$s` — `string` — 
 * **Returns:** `string` — 

### `function _esc_attr($s)`

 * **Parameters:** `string` — 
 * **Returns:** `void` — 

### `function register_permissions($role, $permissions)`

 * **Parameters:**
   * `$role` — `string` — User role
   * `$permissions` — `string|string[]` — Permissions

### `function inherit_permissions($role_to, $role_from)`

 * **Parameters:**
   * `$role_to` — `string` — New role
   * `$role_from` — `string` — Existing role

### `function get_user($property = null)`

Get the current logged user.

 * **Parameters:** `$property` — `null|string` — Property name
 * **Returns:** `mixed|Sessionuser` — Property, or entire Sessionuser object.

### `function login(& $status = null, $user_uid = null, $user_password = null)`

Try to login using $_POST['user_uid'] and $_POST['user_password'].

 * **Parameters:** `$status` — `int` — 
 * **See also:** Session::login()

### `function has_permission($permission, $user = null)`

 * **Parameters:**
   * `$permission` — `string` — Permission uid
   * `$user` — `User|null` — Specified user
 * **Returns:** `bool` — 

### `function append_dir($base_URL, $dir = _ )`

Add a directory to a base URL or a pathname. If the base URL it is not defined, a slash ('/') is appended to the URL. The base URL could end with a slash ('/') or not.

 * **Parameters:**
   * `$base_URL` — `string` — Base URL with/without any slash at start
   * `$dir` — `string` — Directory without any slash
 * **Returns:** `string` — URL / Pathname

### `function site_page($page, $url, $base = null)`

Full URL or folder from ROOT.

### `function str_truncate($s, $max_length, $blabla = '')`

Truncate a string if it's over a specific length. You can specify the end of the string if it's truncated.

 * **Parameters:**
   * `string` — Input string
   * `$max_length` — `int` — Max string length
   * `$blabla` — `string` — Optional. If string length is over $max_length, $blabla it's appended after $string

### `function multi_text($n, $text_multi, $text_one, $text_no = '')`

Choose the appropriate string. '%' will be replaced with the input number.

 * **Parameters:**
   * `$n` — `int` — Input number.
   * `$text_multi` — `string` — Text displayed if n > 1
   * `$text_one` — `string` — Text displayed if n == 1
   * `$text_no` — `string` — Text displayed if n < 1

### `function http_redirect($url)`

Simple HTTP redirects.

### `function is_https()`

Check if the request is under HTTPS

### `function URL_protocol()`

Get the protocol of the request (Please use PROTOCOL)

### `function URL_domain()`

Get the domain of the request (Please use PROTOCOL)

### `function http_503()`

HTTP 503 header

### `function remove_accents($s)`

Äƒ -> a, Ã¢ -> a, È› -> t and so on.

### `function generate_slug($s, $max_length = -1, $glue = '-', & $truncated = false)`

Get a secured version of a string

### `function error_die($msg)`

It scares the user with an error message.

 * **Parameters:** `$msg` — `string` — Error message

### `function _e($s)`

Support for gettext

### `function get_mimetype($filepath, $pure = false)`

Get the MIME type from a file.

 * **Parameters:**
   * `$filepath` — `string` — The file path.
   * `$pure` — `bool` — TRUE for 'image/png; something';

     FALSE for 'image/png'.
 * **Returns:** `string|false` — 

### `function is_file_in_category($filepath, $category)`

Know if a file belongs to a certain category

 * **Parameters:**
   * `$filepath` — `string` — The file path
   * `$category` — `string` — The category
 * **Returns:** `mixed` — FALSE if not

### `function get_file_extension_from_expectations($filename, $category)`

Get the file extension

### `function build_filename($filename, $ext, $args, $i = null)`

Default mode to build a file name WITHOUT extension. It's called multiple times in search_free_filename().

Create your own but NEVER get two equal strings if $i changes.

 * **Parameters:**
   * `$filename` — `string` — File name without extension
   * `$ext` — `string` — File name extension without dot
   * `$args` — `array` — Custom stuff
   * `$i` — `int` — Received from search_free_filename() as

     auto increment if the precedent file name already exists.

     To be used to-get (or not-to-get) a suffix.

     It's NULL during the first call.
 * **Returns:** `string` — File name (with extension)

### `function search_free_filename($filepath, $filename, $ext, $args, $build_filename = null)`

When you want a not-taken file name WITHOUT extension.

 * **Parameters:**
   * `$filepath` — `string` — Absolute directory with trailing slash
   * `$filename` — `string` — 1Â° arg of $build_filename()
   * `$ext` — `string` — 2Â° arg of $build_filename()
   * `$args` — `string` — 3Â° args of $build_filename()
   * `$build_filename` — `null|string` — NULL for 'build_filename'

### `function luser_input($s, $max)`

I use this to clean user input before DB#insert()

 * **Parameters:**
   * `$s` — `string` — Input string
   * `$max` — `int` — Max length

### `if( ! function_exists('require_permission') )`

Do it on your own!

 * **Deprecated**

### `function require_permission($permission, $redirect = 'login.php?redirect=', $preFunction = '', $postFunction = '')`

Do it on your own!

 * **Deprecated**

### `if( ! function_exists('get_gravatar') )`

Do it on your own!

### `function get_gravatar($email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = [] )`

Get either a Gravatar URL or complete image tag for a specified email address.

 * **Parameters:**
   * `$email` — `string` — The email address
   * `$s` — `string` — Size in pixels, defaults to 80px [ 1 - 2048 ]
   * `$d` — `string` — Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
   * `$r` — `string` — Maximum rating (inclusive) [ g | pg | r | x ]
   * `$img` — `boole` — True to return a complete IMG tag False for just the URL
   * `$atts` — `array` — Optional, additional key/value attributes to include in the IMG tag
 * **Returns:** `String` — containing either just a URL or a complete image tag
 * **Deprecated**

### `function get_page_load($decimals = 6)`

Used to know much is the page load

 * **Returns:** `mixed` — Execution time
 * **Deprecated**

### `function get_human_datetime($datetime, $format = 'd`

 * **Deprecated**

