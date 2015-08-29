Symlink installation
====================
This framework is intended to be used as a shared library: one installation is enough for many sites.
Simply create a symling called "includes" to the place where you downloaded the code.

E.g. manually download my code in a shared place as "/usr/share/boz-php-framework" and then
go to your site (e.g. /var/www/blog) and create a symlink to that folder called "includes" using:
   ln -s /usr/share/boz-php-framework/ /var/www/blog/includes

So in /var/www/blog or whatever you can see something as:
.
└── includes -> /usr/share/boz-php-framework/

Database installation
=====================
Import the database schema and fill the example config file with your MySQL/MariaDB credentials.

Configuration file
==================
Move the "load-example.php" as "load.php" in your site.
Something as:
  cp /usr/share/boz-php-framework/load-example.php /var/www/blog/load.php
  cp /usr/share/boz-php-framework/load-post-example.php /var/www/blog/load-post.php

So in your site root you must have:
.
├── load.php
├── load-post.php
└── includes -> /usr/share/valerio-bozzolans-framework/

See load.php and load-post.php and fill what do you know.

The load.php and the load-post.php
==================================
The load.php differs from the load-post.php: the first is loaded at startup (so in it you don't have ANY resource of this framework loaded); instead the load-post.php allow you to load your own functions and your own theme or registering JavaScript files using all the framework's resources.

Constants
=========
ABSPATH
 string
 The absolute pathname of your site
 Note: NO TRAILING SLASH - NO TRAILING SLASH - NO TRAILING SLASH
 E.g. "/var/www/blog"

ROOT
 string
 The HTTP request to obtain the root of your site
 Note: NO TRAILING SLASH - NO TRAILING SLASH - NO TRAILING SLASH
 E.g. "/blog"

INCLUDES
 string
 The include folder (the root of the framework)
 Note: IT DON'T END AND DON'T START WITH A SLASH
 E.g. "includes" that is a symlink to the source

PROTOCOL
 string
 You can override the protocol
 E.g. "https://"
 Default: "http://" or "https://" based on the protocol of the request

DEBUG
 bool

Pathnames and constants
=======================
If you have two folder constants and a file, use the "slash" constant between folders.
	ABSPATH . _ . INCLUDES . '/file.php';

You NEVER have to do this:
	ABSPATH . _ . INCLUDES . _ . 'file.php';

I don't want to add support for alien backslashes ("\").
It's only to avoid to write something as: ABSPATH . "/" . $folder

Please respect this choice and insert the slash manually in order to
NEVER USE and NEVER ALLOW MICROSOFT WINDOWS AS SERVER.

Even if you don't work with sensitive data you don't have to trash the freedom
of your users. Choose GNU/Linux.

Global variables
================
$db
 Is the DB object

$css

$javascript

$module

$permissions
