<?php
# Copyright (C) 2015, 2016, 2017, 2018 Valerio Bozzolan
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
 * Enfatize a substring
 */
class EnfatizeSubstr {

	/**
	 * Enfatize a substring
	 *
	 * @param $s string Heystack
	 * @param $q string Needle
	 * @param $pre string HTML before query (bold tag as default)
	 * @param $post string HTML after query (bold tag as default)
	 * @return string Enfatized string
	 * @todo Move in it's own class
	 */
	static function get( $s, $q, $pre = "<b>", $post = "</b>" ) {

		// no needle? that's quick
		if( empty( $q ) ) {
			return $s;
		}

		$s_length = strlen( $s );
		$q_length = strlen( $q );
		$offset = 0;
		do {
			// find occurrence
			$pos = stripos( $s, $q, $offset );
			if($pos === false) {
				break;
			}

			// enfatize query
			$enfatized = $pre . substr( $s, $pos, $q_length ) . $post;
			$enfatized_length = strlen( $enfatized );

			// pre-query and post-query strings
			$s_pre  = substr( $s, 0, $pos );
			$s_post = substr( $s, $pos + $q_length );

			// save
			$s = $s_pre . $enfatized . $s_post;

			$offset = $pos + $enfatized_length;
		} while( $offset < $s_length );

		return $s;
	}
}
