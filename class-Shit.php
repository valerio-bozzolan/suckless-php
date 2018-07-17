<?php
# Copyright (C) 2015, 2016, 2017 Valerio Bozzolan
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <http://www.gnu.org/licenses/>.

/**
 * This file is completly full of shit.
 */
class Shit {
	/**
	 * A shitty phrase.
	 */
	static function getErrorMessage( $msg ) {
		return "\n\n\t<!-- ERROR: -->\n\t<p style='background:red'>Error: $msg</p>\n\n";
	}

	/**
	 * Soap of 503 headers.
	 */
	static function header503() {
		if( headers_sent() ) {
			return false;
		}
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		header('Retry-After: 300');
	}

	/**
	 * Spawn the white screen of death.
	 */
	static function WSOD( $msg ) {
		self::header503();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<title><?php _e("Errore") ?></title>
			<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET ?>" />
			<meta name="robots" content="noindex, nofollow" />
		</head>
		<body>
			<h1><?php printf(
				__("Ci dispiace! C'è qualche piccolo problema! <small>(DEBUG: %s)</small>"),
				DEBUG ? __("sì") : __("no")
			) ?></h1>
			<p><?php _e("Si è verificato il seguente errore durante l'avvio del framework:") ?></p>
			<p>&laquo; <?php echo $msg ?> &raquo;</p>
			<p><?php _e("Sai che cosa significhi tutto ciò...") ?></p>
		</body>
		</html>
		<?php
		exit;
	}
}
