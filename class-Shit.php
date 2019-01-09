<?php
# Copyright (C) 2015, 2016, 2017, 2019 Valerio Bozzolan
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
 * This file is completly full of shit
 */
class Shit {

	/**
	 * Spawn the white screen of death
	 *
	 * @param $msg string
	 */
	public static function WSOD( $msg ) {
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
				__("Ci dispiace! C'è qualche piccolo problema tecnico! (DEBUG: %s)"),
				DEBUG ? __("sì") : __("no")
			) ?></h1>
			<?php self::error( $msg ) ?>
			<p><?php _e("Potrebbe essere utile comunicarci questo problema affinchè non si ripresenti in futuro. Grazie per la collaborazione.") ?></p>
		</body>
		</html>
		<?php
		exit( 1 );
	}

	/**
	 * Soap of 503 headers
	 */
	public static function header503() {
		if( headers_sent() ) {
			return false;
		}
		header( 'HTTP/1.1 503 Service Temporarily Unavailable' );
		header( 'Status: 503 Service Temporarily Unavailable' );
		header( 'Retry-After: 300' );
	}

	/**
	 * A shitty phrase logged in the syslog and eventually printed when DEBUG
	 *
	 * @param $msg string
	 * @return void
	 */
	public static function error( $msg ) {
		error_log( $msg );
		if( DEBUG ) {
			echo "\n<!-- ERROR: -->\n";
			echo '<p style="color:red;">' . esc_html( $msg ) . '</p>';
		}
	}

}
