# Boz PHP - Another PHP Framework
This framework is my laser cannon that I used to develope dozen of very-different Content Management Systems made from scratch.

Note that I very often deprecate and kill stuff depending on how I feel in the morning.

## Benefits
I can create a RDBMS CMS with these features:

* efficient (tiny footprint)
* on demand resource loader (e.g. DB connection is instantiated only if you use it, etc.)
* query builder
* associative options
* support database table prefixes
* login and user capabilities (WITHOUT requiring PHP sessions)
* secure file uploads
* multilanguage (using the widely used GNU Gettext native or high-level)

## Requirements
Obviously a webserver with PHP and MySQL or MariaDB working. Personally I have a simple GLAMP machine (GNU/Linux + Apache + PHP + MySQL or MariaDB). Extra packages suggested:
* `libmagic-dev`

The `libmagic-dev` package is in the main Debian GNU/Linux repository and so I think it is in all other GNU/Linux distributions. It is needed only if you need to upload files.

## Simplest shared installation
As a shared library: one can serve many sites with a simple `require`. Start cloning the source-code in a shared folder as `/usr/share`:

    bzr branch lp:boz-php-another-php-framework /usr/share/boz-php-another-php-framework

That's it! Leave that folder as is, you don't have to touch it anymore.

## Use it
Go to your website folder (e.g. `/var/www/blog`), and create your friendly config file. I use to call it `load.php` and please do so. Write in something as:
```php
<?php
$username = 'Your database username';
$password = 'The password of your database user';
$database = 'Your database name';
$location = 'localhost';

// Table prefix, if any!
$prefix = 'blog_';

// To avoid the auto-load of your local load-post.php
// define('REQUIRE_LOAD_POST', false);

// That's it! This will load the framework with the above configurations
require '/usr/share/boz-php-another-php-framework/load.php';
```
And now create your first file e.g. `index.php`:
```php
<?php
require 'load.php';

$row = query_row("SELECT NOW() as time");

echo $row->time;

echo "If you see the datetime, the database works!";
```

## Database installation
You can add support for user *autentication* / user *permissions* and *associative options* importing the database schema into your database. To do it you should have received a file called `example-schema.sql` in the `examples` framework folder. Import it into your database. Actually there are only two tables.

## Stuff in `load.php`
Have to specify these:
* `$username` (`string`) The database username.
* `$password` (`string`) The password of the database username.
* `$database` (`string`) The database name.
* `$location` (`string`) The database location.
* `$prefix` (`string`) The database table prefix.
* `ABSPATH` (`string`) The absolute pathname of your site (`__DIR__` is your friend). No trailing slash.
* `ROOT` (`string`) The absolute request pathname (something as `/blog` or simply an empty string). No trailing slash.

Definibles (as  (`type`) `default`):
* `DEBUG` (`bool`) `false`: Enable verbose debugging.
* `DEBUG_QUERIES` (`bool`) `false`: The queries are logged, and also printed when also `DEBUG`.
* `PROTOCOL` (`string`): Default is the protocol of the request (`http://` / `https://`). It builds the `URL` constant.
* `DOMAIN` (`string`): Default is your domain name. You can override it. It builds the `URL` constant.
* `URL` (`string`): Default is `PROTOCOL . DOMAIN . ROOT`.
* `REQUIRE_LOAD_POST` (`string|false`) `string`: As default it's `ABSPATH . '/load-post.php` and it's a file that describes your CMS.

## What you have then
Well. I hope to auto-document it with inline comments. For now you can just see `functions.php` in the framework folder.

## Own functions / stuff
It's normal to have your own custom configuration of this framework. So the `load-post.php` file is made for you. Create it and write in it what you want:
```php
<?php
// This file is automagically called after load.php

// Custom JavaScript and CSS declaration
register_js('jquery', URL . '/media/jquery-min.js');
register_css('my-style', URL . '/media/style.css');

// Custom permissions declaration
register_permissions('subscribed', [
	'add-comment',
	'page-vote'
] );

inherit_permissions('superadmin', 'subscribed');
register_permissions('superadmin', 'do-wonderful-things');

// Custom mime types declaration
register_mimetypes('pdf', [
	'application/pdf' => 'pdf',
	'application/x-pdf' => 'pdf',
	'application/x-bzpdf' => 'pdf',
	'application/x-gzpdf' => 'pdf'
] );

// Custom menu tree declaration
add_menu_entries( [
	new MenuEntry( 'index',     'home.php',         __("Home") ),
	new MenuEntry( 'services',  'services.php',     __("Our services") ),
	new MenuEntry( 'contact',   'meet.php',         __("Meet us"),             'services'),
	new MenuEntry( 'fsf',       'https://fsf.org/', __("Meet us in Colorado"), 'contact'),
] );

// Use of custom associative options
// $visits = get_option('visits', 0);
// set_option('visits', ++$visits);
```
## License
This is a **Free** as in **Freedom** project. It comes with ABSOLUTELY NO WARRANTY. You are welcome to redistribute it under the terms of the **GNU Affero General Public License v3+**.
