<?php
# Copyright (C) 2015, 2016 Valerio Bozzolan
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
 * Handle languages using standard GNU Gettext functionalities
 *
 * @use error_die(), esc_html()
 */
class RegisterLanguage {
	var $i = -1;
	var $languages = [];

	/**
	 * Associative map of alias=>index of $this->languages
	 */
	var $aliases = [];

	var $gettextDomain;
	var $gettextDirectory;
	var $gettextDefaultEncode;

	/**
	 * Latest language applied
	 */
	var $latest = null;

	/**
	 * @param string $domain GNU Gettext domain
	 * @param string $directory GNU Gettext directory
	 * @param string $default_encode GNU Gettext default language encode
	 */
	function __construct($domain = null, $directory = null, $default_encode = null) {
		if($domain === null && defined('GETTEXT_DOMAIN') ) {
			$domain = GETTEXT_DOMAIN;
		}
		if($directory === null && defined('GETTEXT_DIRECTORY') ) {
			$directory = GETTEXT_DIRECTORY;
		}
		if($default_encode === null && defined('GETTEXT_DEFAULT_ENCODE') ) {
			 $default_encode = GETTEXT_DEFAULT_ENCODE;
		}

		if($domain === null || $directory === null) {
			error_die( sprintf(
				_("Devi impostare le costanti %s e %s."),
				'GETTEXT_DOMAIN',
				'GETTEXT_DIRECTORY'
			) );
		}

		$this->gettextDomain = $domain;
		$this->gettextDirectory = $directory;
		$this->gettextDefaultEncode = $default_encode;
	}

	/**
	 * @string string $code GNU Gettext code language
	 * @string array $aliases You can specify language aliases (in lower case)
	 * @string string $encode Override the default GNU Gettext language encode
	 */
	function registerLanguage($code, $aliases = [], $encode = null, $iso = null) {
		if( $encode === null ) {
			$encode = $this->gettextDefaultEncode;
		}

		if( $encode === null ) {
			DEBUG && error( sprintf(
				_("Non hai specificato una codifica per la lingua '%s' e non ce n'è una predefinita. Impostala con la costante %s."),
				esc_html($code),
				'GETTEXT_DEFAULT_ENCODE'
			) );
			return false;
		}

		$this->languages[ ++$this->i ] = new BozPHPLanguage($code, $encode, $iso);

		// Yes, the language code it's an alias to itself. That's a lazy hack!
		$this->aliases[ strtolower($code) ] = $this->i;

		// Each alias is associated to it's language code
		force_array($aliases);
		foreach($aliases as $alias) {
			$this->aliases[ $alias ] = $this->i;
		}
	}

	/**
	 * Get a Language object from a language alias.
	 *
	 * @param string $language_alias Language alias, null for browser preferences
	 * @return false|object Language object or false if it isn't registered
	 */
	function getLanguage($language_alias = null) {
		if($language_alias === null) {
			return $this->getLanguageFromHTTP();
		} else {
			$language_alias = strtolower($language_alias);
			if( isset( $this->aliases[ $language_alias ] ) ) {
				return $this->languages[ $this->aliases[ $language_alias ] ];
			}
		}

		return false;
	}

	/**
	 * Set the GNU Gettext environment from a language alias or from browser preferences.
	 *
	 * @param string $language_alias Language alias, null for browser preferences
	 * @return boolean Is language applied?
	 */
	function applyLanguage($language_alias = null) {
		$language = $this->getLanguage($language_alias);

		if($language === false) {
			return false;
		}

		$this->latest = $language;

		return self::GNUGettextEnvironment($language->code, $language->encode, $this->gettextDomain, $this->gettextDirectory);
	}

	/**
	 * @return falsy of BozPHPLanguage
	 */
	function getLatestLanguageApplied() {
		return $this->latest;
	}

	/**
	 * Determine browser language in preference order, respecting RFC.
	 *
	 * @see http://stackoverflow.com/a/11161193
	 */
	function getLanguageFromHTTP() {
		if( ! isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			return false;
		}

		// Create an associative array of priority=>language
		preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $langs);
		if( count($langs[1]) === 0 ) {
			return false;
		}
		$langs = array_combine($langs[1], $langs[4]);

		// To respect RFC if no priority is specified (q=;)
		foreach($langs as $lang => $val){
			$langs[$lang] = ( $val === '' ) ? 1.0 : (float) $val;
		}

		// Order by priority
		arsort($langs, SORT_NUMERIC);

		// Search and use a known language
		foreach($langs as $lang => $val) {
			$language = $this->getLanguage( $lang );

			if($language !== false) {
				// Found a language that match browser preferences!
				return $language;
			}
		}

		return false;
	}

	/**
	 * Fill the GNU Gettext Environment.
	 *
	 * @see http://php.net/manual/en/book.gettext.php
	 * @return boolean GNU Gettext works for your system?
	 */
	static function GNUGettextEnvironment($code, $encode, $domain, $directory) {
		putenv("LANG=$code.$encode");
		$ok = setlocale(LC_MESSAGES, "$code.$encode");
		bindtextdomain($domain, $directory);
		textdomain($domain);
		bind_textdomain_codeset($domain, $encode);

		if(! $ok && DEBUG) {
			error( sprintf( _("La localizzazione non è implementata nel tuo sistema. Riferimento: %s"), 'http://php.net/manual/en/function.setlocale.php' ) );
		}

		return $ok;
	}
}

class BozPHPLanguage {
	var $code;
	var $encode;
	var $iso;

	function __construct($code, $encode, $iso) {
		$this->code   = $code;
		$this->encode = $encode;
		$this->iso    = $iso;
	}

	function getCode() {
		return $this->code;
	}

	function getEncode() {
		return $this->encode;
	}

	function getISO() {
		if($this->iso === null) {
			$this->iso = self::guessISO($this->iso);
		}
		return $this->iso;
	}

	/**
	 * Gets 'it' from 'it_IT', 'it_IT.utf8' etc.
	 *
	 * Can be called statically
	 */
	function guessISO($code = null, $fallback = 'en') {
		if($code === null) {
			$code = $this->code;
		}
		$p = strpos($code, '_');
		if($p === false) {
			DEBUG && error( sprintf(
				_("Can't guess the ISO language from language code '%s'. Falling on default '%s'."),
				esc_html($code),
				$fallback
			) );
			return $fallback;
		}
		return substr($code, 0, $p);
	}
}
