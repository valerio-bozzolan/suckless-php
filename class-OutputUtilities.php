<?php
# Copyright (C) 2015, 2016, 2017, 2019, 2020, 2021 Valerio Bozzolan
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

/**
 * Collection of utilities
 */
class OutputUtilities {

	/**
	 * Generate a slug
	 *
	 * @param string $s Input string e.g. 'what the hell'
	 * @param int $max_length Max. length limit
	 * @param string $glue Slug glue
	 * @param bool $truncated Flag to indicate if the string was truncated by the $max_length limit
	 * @return 'something-as-this'
	 */
	public static function slug( $s, $max_length = 0, $glue = '-', & $truncated ) {
		$truncated = false;
		$s = strtolower( self::stripAccents($s) );
		if( $glue !== '_' ) {
			$s = str_replace( '_',' ', $s );
		}
		$glue_safe = preg_quote( $glue );
		$s = preg_replace( "/[^a-z0-9\s\\$glue_safe]/", '',  $s );
		$s = preg_replace( "/[\s\\$glue_safe]+/",       ' ', $s );
		$s = trim( $s, ' ' );
		if( $max_length ) {
			$len = strlen( $s );
			$s = substr( $s, 0, $max_length );
			$truncated = $len !== strlen( $s );
		}
		$s = preg_replace( '/\s/', $glue, $s );
		return rtrim( $s, $glue );
	}

	/**
	 * Enfatize a substring
	 *
	 * The input string is automatically sanitized.
	 *
	 * @param  string $s    Heystack
	 * @param  string $q    Needle, probably the direct user input
	 * @param  string $pre  HTML before query (bold tag as default)
	 * @param  string $post HTML after query (bold tag as default)
	 * @return string       Enfatized string HTML-escaped
	 */
	public static function enfatizeSubstr( $s, $q, $pre = '<b>', $post = '</b>' ) {

		$q_length = mb_strlen( $q );
		$s_length = mb_strlen( $s );

		// no needle? that's quick
		if( empty( $q_length ) ) {
			return esc_html( $s );
		}

		$out = '';
		$offset = 0;
		do {
			// find occurrence
			$pos = mb_stripos( $s, $q, $offset );
			$match = $pos !== false;
			if( $match ) {

				// pre-query
				$pre_found = mb_substr( $s, $offset, $pos - $offset );
				$pre_found = esc_html( $pre_found );
				$out .= $pre_found;

				// enfatize found query
				$found = mb_substr( $s, $pos, $q_length );
				$found_length = mb_strlen( $found );
				$enfatized = $pre . esc_html( $found ) . $post;
				$out .= $enfatized;

				// do not process again this part
				$offset = $pos + $found_length;
			} else {
				$end = mb_substr( $s, $offset );
				$end = esc_html( $end );
				$out .= $end;

				$offset = $s_length;
			}

		} while( $offset < $s_length );

		return $out;
	}

	/**
	 * Compress some data
	 *
	 * To optimize data-transfer, falsy values are unset.
	 *
	 * @param string $data
	 * @return mixed
	 */
	public static function compressData( $data ) {
		$is_array = is_array( $data );
		if( $is_array || is_object( $data ) ) {
			foreach( $data as $k => $v ) {
				if( is_array( $v ) || is_object( $v ) ) {
					if( $is_array ) {
						$data[ $k ] = static::compressData( $v );
					} else {
						// call JsonSerialize() prematurely (or it may break ->get() methods)
						if( $v instanceof JsonSerializable ) {
							$v = $v->jsonSerialize();
						}

						$data->{ $k } = static::compressData( $v );
					}
				} elseif( $v === null || $v === false ) {
					if( $is_array ) {
						unset( $data[ $k ] );
					} else {
						unset( $data->{ $k } );
					}
				}
	   		}
		}
		return $data;
	}

	/**
	 * Get the human filesize from bytes
	 *
	 * @param $filesize int Size in bytes
	 * @param $glue string
	 * @param $round int Round
	 * @return string
	 */
	public static function humanFilesize( $filesize, $glue = ' ', $round = 2 ){
		if( !is_numeric( $filesize ) ) {
			return __( "NaN" );
		}
		$decr = 1024;
		$step = 0;
		$prefixes = [ 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' ];
		$n = count( $prefixes ) - 1;
		while( $filesize / $decr > 0.9 && $step < $n ) {
			$filesize /= $decr;
			$step++;
		}
		return round( $filesize, $round ) . $glue . $prefixes[ $step ];
	}

	/**
	 * Strip accents.
	 *
	 * @param $s string
	 * @return string
	 */
	public static function stripAccents( $s ) {
		$a = ['À','Á','Â','Ã','Ä','Å','Æ', 'Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','ß','à','á','â','ã','ä','å','æ', 'ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','ÿ','Ā','ā','Ă','ă','Ą','ą','Ć','ć','Ĉ','ĉ','Ċ','ċ','Č','č','Ď','ď','Đ','đ','Ē','ē','Ĕ','ĕ','Ė','ė','Ę','ę','Ě','ě','Ĝ','ĝ','Ğ','ğ','Ġ','ġ','Ģ','ģ','Ĥ','ĥ','Ħ','ħ','Ĩ','ĩ','Ī','ī','Ĭ','ĭ','Į','į','İ','ı','Ĳ','ĳ','Ĵ','ĵ','Ķ','ķ','Ĺ','ĺ','Ļ','ļ','Ľ','ľ','Ŀ','ŀ','Ł','ł','Ń','ń','Ņ','ņ','Ň','ň','ŉ','Ō','ō','Ŏ','ŏ','Ő','ő','Œ','œ','Ŕ','ŕ','Ŗ','ŗ','Ř','ř','Ś','ś','Ŝ','ŝ','Ş','ş','Š','š','Ţ','ţ','Ť','ť','Ŧ','ŧ','Ũ','ũ','Ū','ū','Ŭ','ŭ','Ů','ů','Ű','ű','Ų','ų','Ŵ','ŵ','Ŷ','ŷ','Ÿ','Ź','ź','Ż','ż','Ž','ž','ſ','ƒ','Ơ','ơ','Ư','ư','Ǎ','ǎ','Ǐ','ǐ','Ǒ','ǒ','Ǔ','ǔ','Ǖ','ǖ','Ǘ','ǘ','Ǚ','ǚ','Ǜ','ǜ','Ǻ','ǻ','Ǽ','ǽ','Ǿ','ǿ','Ά','ά','Έ','έ','Ό','ό','Ώ','ώ','Ί','ί','ϊ','ΐ','Ύ','ύ','ϋ','ΰ','Ή','ή'];
		$b = ['A','A','A','A','A','A','AE','C','E','E','E','E','I','I','I','I','D','N','O','O','O','O','O','O','U','U','U','U','Y','s','a','a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','o','u','u','u','u','y','y','A','a','A','a','A','a','C','c','C','c','C','c','C','c','D','d','D','d','E','e','E','e','E','e','E','e','E','e','G','g','G','g','G','g','G','g','H','h','H','h','I','i','I','i','I','i','I','i','I','i','IJ','ij','J','j','K','k','L','l','L','l','L','l','L','l','l','l','N','n','N','n','N','n','n','O','o','O','o','O','o','OE','oe','R','r','R','r','R','r','S','s','S','s','S','s','S','s','T','t','T','t','T','t','U','u','U','u','U','u','U','u','U','u','U','u','W','w','Y','y','Y','Z','z','Z','z','Z','z','s','f','O','o','U','u','A','a','I','i','O','o','U','u','U','u','U','u','U','u','U','u','A','a','AE','ae','O','o','Α','α','Ε','ε','Ο','ο','Ω','ω','Ι','ι','ι','ι','Υ','υ','υ','υ','Η','η'];
		return str_replace( $a, $b, $s );
	}

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
			<title><?= __("Errore") ?></title>
			<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET ?>" />
			<meta name="robots" content="noindex, nofollow" />
		</head>
		<body>
			<h1><?php printf(
				__("Ci dispiace! C'è qualche piccolo problema tecnico! (DEBUG: %s)"),
				DEBUG ? __("sì") : __("no")
			) ?></h1>
			<?php self::error( $msg ) ?>
			<p><?= __("Potrebbe essere utile comunicarci questo problema affinchè non si ripresenti in futuro. Grazie per la collaborazione.") ?></p>
		</body>
		</html>
		<?php
		exit( 1 );
	}

	/**
	 * A shitty phrase logged in the syslog and eventually printed when DEBUG
	 *
	 * @param $msg string
	 * @return void
	 */
	public static function error( $msg ) {
		error_log( "$msg from {$_SERVER['SCRIPT_FILENAME']}" );
		if( DEBUG ) {
			echo "\n<!-- ERROR: -->\n";
			echo '<p style="color:red;">' . esc_html( $msg ) . '</p>';
		}
	}

	/**
	 * Send HTTP 503 headers
	 */
	public static function header503() {
		if( headers_sent() ) {
			error( 'cannot send headers if there is earlier output' );
		} else {
			header( 'HTTP/1.1 503 Service Temporarily Unavailable' );
			header( 'Status: 503 Service Temporarily Unavailable' );
			header( 'Retry-After: 300' );
		}
	}

}
