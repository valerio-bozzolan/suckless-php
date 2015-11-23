<?php
/*
 * Require this file to start the framework.
 */

// Database info. You know it!
$username = 'insert-here-username';
$password = 'insert-here-password';
$database = 'insert-here-database-name';
$location = 'localhost';

// Database table prefix, if any.
// E.g. 'asd_'
$prefix = '';

// Specify the folder of your site after the domain name.
// NO TRAILING SLASH
// E.g. '/blog/01'
define('ROOT', '');

// It enable extra verbose framework errors
define('DEBUG', true);

// Very very very debuggly!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
define('SHOW_EVERY_SQL', true);

// Leave this as is. It is this folder.
// NO TRAILING SLASH
// E.g. '/var/www/blog/01'
define('ABSPATH', __DIR__);

// Where is the framework? Change it!
require '/usr/share/boz-php-another-php-framework/load.php';
