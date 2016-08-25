# Boz PHP - Another PHP Framework
To be clear I use to twist this framework very often deprecating and killing stuff how I feel in the morning. It's my custom laser cannon that serves dozen of very-different Content Management Systems "made from zero" in my single Debian GNU/Linux server.

## Benefits as developer
You can create a RDBMS CMS with:

* NO PHP SESSIONS
* Resources loaded only when you request them without thinking too much on them (with [spl-autoload-register](http://php.net/manual/en/function.spl-autoload-register.php) plus a `$GLOBALS` objects orchestrator)
* No pain with DB table prefixes
* No pain with DB associative options
* No pain with login and user capabilities
* No pain with registering and enqueueing JS and CSS
* No pain with menu trees
* No pain with file uploads
* Very small websites with only what you want in the root of your project
* Very small framework with few flat files

## Benefits as sysadmin
* No pain with overweight copy-pasted websites to be hosted with redundant backups and too things to keep updated: The framework is in one place; the websites are in other places; datas are in dabasase/databases. Everything it's how it should be done!
* Do not waste the filesystem file cache feature (feel the pain of your kernel and of your hard-drive cache with `n` stand-alone websites without shared resources!)

## Requirements
Obviously a webserver with PHP and MySQL or MariaDB working. Personally I have a simple GLAMP machine (GNU/Linux + Apache + PHP + MySQL or MariaDB). Extra packages suggested:
* `libmagic-dev`

The `libmagic-dev` package is in the main Debian GNU/Linux repository and so I think it is in all other GNU/Linux distributions. It is needed in order to upload files safetely.

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

// We can wait for this feature..
define('REQUIRE_LOAD_POST', false);

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
* `SHOW_EVERY_SQL` (`bool`) `false`: It does what you think. Only if `DEBUG`.
* `REQUIRE_LOAD_POST` (`bool`) `true`: To require your `ABSPATH . '/load-post.php`.
* `PROTOCOL` (`string`): Default is the protocol of the request (`http://` / `https://`). It builds the `URL` constant.
* `DOMAIN` (`string`): Default is your domain name. You can override it. It builds the `URL` constant.
* `URL` (`string`): Default is `PROTOCOL . DOMAIN . ROOT`.

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
	new MenuEntry('home', URL . '/home.php', _("Home") ),
	new MenuEntry('services', URL . '/services.php', _("Our services") ),
	new MenuEntry('meet', URL . '/meet.php', _("Meet us"), 'services'),
	new MenuEntry('colorado', URL . '/meet.php', _("Meet us in Colorado"), 'meet'),
	new MenuEntry('cast', URL . '/cast.php', _("Cast") )
] );

// Create a submenu in certain conditions
has_permission('do-wonderful-things') && add_menu_entries( [
	new MenuEntry('cast-edit', URL . '/foo.php', _("Cast administration"), 'cast' )
] );

// Use of custom associative options
$visits = get_option('visits', 0);

set_option('visits', ++$visits);
```
Now remove the declaration of `REQUIRE_LOAD_POST` in your `load.php`.

## License
This is a **Free** as in **Freedom** project. It comes with ABSOLUTELY NO WARRANTY. You are welcome to redistribute it under the terms of the **GNU Affero General Public License v3+**.
