# Requirements
Obviously a webserver with PHP and MySQL or MariaDB working. Personally I have a simple GLAMP machine (GNU/Linux + Apache + PHP + MariaDB or MySQL). Extra packages suggested:
* `libmagic-dev`

The `libmagic-dev` package is in the main Debian GNU/Linux repository and so I think it is in all other GNU/Linux distributions. It is needed in order to upload files safetely.

# Simplest shared installation
This framework is intended to be used as a "shared library": so one can serve many sites. Start copy-pasting the source-code in a shared folder as `/usr/share`:

    bzr branch lp:boz-php-another-php-framework /usr/share/boz-php-another-php-framework

That's it! Leave that folder as is, you don't have to touch it anymore.

# Use it
Go to your website folder (e.g. `/var/www/blog`), and create your friendly config file (I use to call it `load.php` and please do so...) and write in it something as:
```php
<?php
$username = 'Your database username';
$password = 'The password of your database user';
$database = 'Your database name';
$location = 'localhost';

// Table prefix, if any!
$prefix = 'blog_';

define('ABSPATH', __DIR__);

// We can wait for this feature..
define('REQUIRE_LOAD_POST', false);

// We can wait for this feature..
define('DB_USE_OPTIONS', false);

// That's it! This will load the framework with the above configurations
require '/usr/share/boz-php-another-php-framework/load.php';
```
And now create your first file. E.g. `index.php`:
```php
<?php
require 'load.php';

$row = $db->getRow("SELECT NOW() as time");

echo $row->time;

// If you see the datetime, the database works!
```

# Database installation
You can add support for user *autentication* / user *permissions* and *associative options* importing the database schema into your database. To do it you should have received a file called `example-schema.sql` in the framework folder. Import it into your database. Actually there are only two tables. When you have done it, remember to remove the `DB_USE_OPTIONS` declarations in your config file (`load.php`), or obviously set that constant to `true`.

# What works in `load.php`
Have to specify these:
* `$username` (`string`) The database username.
* `$password` (`string`) The password of the database username.
* `$database` (`string`) The database name.
* `$location` (`string`) The database location.
* `$prefix` (`string`) The database table prefix.
* `ABSPATH` (`string`) The absolute pathname of your site (`__DIR__` is your friend). No trailing slash.
* `ROOT` (`string`) The absolute request pathname (something as `/blog` or simply an empty string). No trailing slash.

Definibles (as  (`type`) `default`):
* `DEBUG` (`bool`) `false`: Enable verbose debugging (use it in your scripts)!
* `SHOW_EVERY_SQL` (`bool`) `false`: It does what you think. Only if `DEBUG`.
* `REQUIRE_LOAD_POST` (`bool`) `true`: To require your `ABSPATH . '/load-post.php`.
* `USE_DB_OPTIONS` (`bool`) `true`: To enable associative database options.
* `PROTOCOL` (`string`): Default is the protocol of the request (`http://` / `https://`). It builds the `URL` constant.
* `DOMAIN` (`string`): Default is your domain name. You can override it. It builds the `URL` constant.
* `URL` (`string`): Default is `PROTOCOL . DOMAIN . ROOT`.

# What you have then
Well. I hope to auto-document it with inline comments. For now you can just see `functions.php` in the framework folder.

# Own functions / stuff
It's normal to have your own custom configuration of this framework. So the `load-post.php` file is made for you. Create it and write in it what you want:
```php
<?php
// This file is automagically called after post.php and before your own file.

use_session();

// Here I place my files
define('MEDIA_FOLDER', '/media');

// Register a jquery file and a stylesheet
register_js('jquery', URL . MEDIA_FOLDER . '/jquery-min.js');
register_css('my-style', URL . MEDIA_FOLDER . '/style.css');

register_permission('unregistered', 'vote-post');
inherit_permissions('subscribed', 'unregistered');
register_permission('subscribed', 'add-comment');

inherit_permissions('superadmin', 'subscribed');
register_permission('superadmin', 'do-wonderful-things');

register_mimetypes(
        'only-pdf',
        array(
                'application/pdf' => 'pdf',
                'application/x-pdf' => 'pdf',
                'application/x-bzpdf' => 'pdf',
                'application/x-gzpdf' => 'pdf'
        )
);

set_option(
    get_option('visits', 0)
);
```
Now remove the declaration of `REQUIRE_LOAD_POST` in your `load.php`.
