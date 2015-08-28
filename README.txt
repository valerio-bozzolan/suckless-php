Symlink installation
====================
This framework is intended to be used as a shared library: one installation is enough for many sites.
Simply create a symling called "includes" to the place where you downloaded the code.

E.g. manually download my code in a shared place as "/usr/share/valerio-bozzolan-framework" and then
go to your site (as /var/www/blog) and create a symlink to that folder called "includes" using:
   ln -s /usr/share/valerio-bozzolans-framework/ includes

So in /var/www/blog or whatever you can see something as:
.
└── includes -> /usr/share/valerio-bozzolans-framework/

Database installation
=====================
Import the database schema and fill the example config file with your MySQL/MariaDB credentials.

Configuration file
==================
Move the "load-example.php" as "load.php" in your site.
Something as:
  cp /usr/share/valerio-bozzolans-framework/load-example.php /var/www/blog/load.php
  cp /usr/share/valerio-bozzolans-framework/load-post-example.php /var/www/blog/load-post.php

So you must have:
.
├── load.php
├── load-post.php
└── includes -> /usr/share/valerio-bozzolans-framework/

See load.php and load-post.php and fill what do you know.

Constants
=========

ABSPATH
 string
 The absolute pathname of your site
 NO TRAILING SLASH
 E.g. "/var/www/blog"

ROOT
 string
 The HTTP request to obtain the root of your site
 E.g. "/blog"

INCLUDES
 string
 The include folder (the root of the framework) (E.g. "includes" that is a symlink to the source)

PROTOCOL
 string
 You can override the protocol
 E.g. "https://"
 Default: the protocol of the request

DEBUG
 bool

Pathnames and constants
=======================
If you have two folder constants and a file, use the "slash" constant between folders:
	ABSPATH . _ . INCLUDES . '/file.php';

NEVER DO THIS:
	ABSPATH . _ . INCLUDES . _ . 'file.php';

I don't want to add support for alien backslashes ("\").
It's only to avoid to write ABSPATH . "/" . etc.
Please respect insert the slash in the file name in order
to NEVER USE AND NEVER ALLOW MICROSOFT WINDOWS AS SERVER.

If you work with sensitive data don't trash the freedom
of your users. GNU/Linux please.
