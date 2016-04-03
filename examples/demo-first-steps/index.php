<?php
# Copyright (C) 2015 Valerio Bozzolan
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

// And now a lot of example doing the same thing in multiple way.

/**********************************************************************************
 * First require your configuration file to start the framework. That's it!
 * Note: the page obviusly dies here if you have not configured you database access.
 **********************************************************************************/
require 'load.php';


// You can use `query` for a pure standard `mysqli` result.
$results = query('SELECT NOW() AS miao');
while( $row = $results->fetch_object() ) {
	printf("The database says that now it's '%s'", $row->miao);
}



// You can use `query_results` for an array of objects
$results = query_results('SELECT NOW() AS miao');
foreach($results as $result) {
	printf("The database says that now it's '%s'", $row->miao);
}



// You can use `query_result` for an object with only the first row
$row = query_row('SELECT NOW() AS miao');
printf("The database says that now it's '%s'", $row->miao);



// You can use `query_value` for only a single field from a single row:
$miao = query_value('SELECT NOW() AS miao', 'miao');
printf("The database says that now it's '%s'", $miao);



/*
 * Note that the last parameter of `query_results`, `query_row`, and `query_value`
 * can be a custom class name. To to something as this:
 *
 * @see MyClass.php
 */
require 'MyClass.php';
$row = query_row('SELECT NOW() AS miao', 'MyClass');
printf(
	"The database says that now it's the year <b>%d</b> and the day is <b>%d</b>! " .
	"Time: %s. That is a clean PHP DateTime object retrieved with one line query!",
	$row->miao->format('Y'),  // Year
	$row->miao->format('d'),  // Day
	$row->miao->format('H:i') // Time
);
