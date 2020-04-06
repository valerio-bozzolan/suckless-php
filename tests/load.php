<?php
// shared stuff for the phpunit
//

declare(strict_types=1);

// load fake configurations
define( 'ROOT', '/test' );
define( 'REQUIRE_LOAD_POST', false );

// load the framework
require __DIR__ . '/../load.php';

/**
 * Create a dummy database with no connection
 */
class DBDummy extends DB {
	function __construct() {}
}
