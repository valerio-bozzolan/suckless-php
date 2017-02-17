<?php
class EnfatizeSubstr {

	/**
	 * Enfatize a substring.
	 *
	 * @param $s string Heystack
	 * @param $q string Needle
	 * @param $pre string HTML before query (bold tag as default)
	 * @param $post string HTML after query (bold tag as default)
	 * @return string Enfatized string
	 * @todo Move in it's own class
	 */
	static function get($s, $q, $pre = "<b>", $post = "</b>") {
		$s_length = strlen($s);
		$q_length = strlen($q);
		$offset = 0;
		do {
			// Find position
			$pos = stripos($s, $q, $offset);
			if($pos === false) {
				break;
			}

			// Enfatize query
			$enfatized = $pre . substr($s, $pos, $q_length) . $post;
			$enfatized_length = strlen($enfatized);

			// Pre-query and post-query strings
			$s_pre = substr($s, 0, $pos);
			$s_post = substr($s, $pos + $q_length);

			// Save
			$s = $s_pre . $enfatized . $s_post;

			$offset = $pos + $enfatized_length;
		} while($offset < $s_length);

		return $s;
	}
}
