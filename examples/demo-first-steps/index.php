<?php
/*
 * Copyright (C) 2015 Valerio Bozzolan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * First require your configuration file to start the framework. That's it!
 * Note: the page obviusly dies here if you have not configured you database access.
 */
require 'load.php';

/*
 * You can do this:
 */
$row = $db->getRow('SELECT NOW() AS miao');
printf(
	"The database says that now it's '%s'. What's wrong with this? That it's a stupid string... boring to change!\n",
	$row->miao
);

/*
 * ... or you can define a custom class and do this:
 */
require 'MyClass.php';
$row = $db->getRow('SELECT NOW() AS miao', 'MyClass');
printf(
	"The database says that now it's the year <b>%d</b> and the day is <b>%d</b>! Time: %s. That's a clean PHP DateTime object retrieved with one line query!",
	$row->miao->format('Y'),  // Year
	$row->miao->format('d'),  // Day
	$row->miao->format('H:i') // Time
);
