<?php
# Copyright (C) 2015, 2016, 2018 Valerio Bozzolan
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

	private $i = -1;
	private $languages = [];

	/**
	 * Associative map of alias=>index of $this->languages
	 */
	private $aliases = [];

	private $gettextDomain;
	private $gettextDirectory;
	private $gettextDefaultEncode;

	/**
	 * Use native GNU Gettext (lot of quicker but more unreliable)
	 *
	 * @var bool
	 */
	private $native;

	/**
	 * Latest language applied
	 */
	private $latest = null;

	/**
	 * Default language (don't apply GNU Gettext in this case).
	 *
	 * Index of self#languages[]
	 */
	private $default = null;

	/**
	 * Constructor
	 *
	 * @param string $domain GNU Gettext domain
	 * @param string $directory GNU Gettext directory
	 * @param string $default_encode GNU Gettext default language encode
	 */
	public function __construct( $domain = null, $directory = null, $default_encode = null, $native = null ) {
		if( ! $domain ) {
			$domain = GETTEXT_DOMAIN;
		}
		if( ! $directory ) {
			$directory = GETTEXT_DIRECTORY;
		}
		if( ! $default_encode ) {
			 $default_encode = GETTEXT_DEFAULT_ENCODE;
		}
		if( null === $native ) {
			$native = defined( 'GETTEXT_NATIVE' ) ? GETTEXT_NATIVE : false;
		}
		$this->gettextDomain = $domain;
		$this->gettextDirectory = $directory;
		$this->gettextDefaultEncode = $default_encode;
		$this->native = $native;
	}

	/**
	 * Register a language
	 *
	 * @string string $code GNU Gettext code language
	 * @string array $aliases You can specify language aliases (in lower case)
	 * @string string $encode Override the default GNU Gettext language encode
	 */
	public function registerLanguage( $code, $aliases = [], $encode = null, $iso = null ) {
		if( ! $encode ) {
			$encode = $this->gettextDefaultEncode;
		}

		if( ! $encode ) {
			DEBUG && error( sprintf(
				___("Non hai specificato una codifica per la lingua '%s' e non ce n'è una predefinita. Impostala con la costante %s."),
				esc_html($code),
				'GETTEXT_DEFAULT_ENCODE'
			) );
			return false;
		}

		$this->languages[ ++$this->i ] = new BozPHPLanguage( $code, $encode, $iso );

		// Yes, the language code it's an alias to itself. That's a lazy hack!
		$this->aliases[ self::normalize($code) ] = $this->i;

		// Each alias is associated to it's language code
		force_array( $aliases );
		foreach( $aliases as $alias ) {
			$this->aliases[ $alias ] = $this->i;
		}
	}

	/**
	 * Set the default language.
	 *
	 * A default language allow less high-level pain.
	 *
	 * @param string $default
	 */
	public function setDefaultLanguage( $default ) {
		$code = self::normalize($default);
		if( ! isset( $this->aliases[ $code ] ) ) {
			error_die( sprintf(
				___("Default language '%s' have to be registered previously"),
				esc_html($default)
			) );
		}
		$this->default = $this->languages[ $this->aliases[ $code ] ];
	}

	/**
	 * Get a Language object from a language alias.
	 *
	 * @param string $language_alias Language alias, null for browser preferences
	 * @return false|object Language object or false if it isn't registered
	 */
	public function getLanguage( $language_alias = null ) {
		if( $language_alias ) {
			$language_alias = self::normalize( $language_alias );
			if( isset( $this->aliases[ $language_alias ] ) ) {
				return $this->languages[ $this->aliases[ $language_alias ] ];
			}
		} else {
			return $this->getLanguageFromHTTP();
		}
		return false;
	}

	/**
	 * Is a native GETTEXT implementation?
	 *
	 * @return bool
	 */
	public function isNative() {
		return $this->native;
	}

	/**
	 * Set the GNU Gettext environment from a language alias or from browser preferences.
	 *
	 * @param string $language_alias Language alias, null for browser preferences
	 * @return mixed
	 */
	public function applyLanguage( $language_alias = null ) {
		$language = $this->getLanguage( $language_alias );
		if( ! $language ) {
			return false;
		}
		$this->latest = $language;
		self::GNUGettextEnvironment( $language->getCode(), $language->getEncode(), $this->gettextDomain, $this->gettextDirectory, $this->isNative() );
		return $language->getCode();
	}

	/**
	 * @return BozPHPLanguage|null
	 */
	function getLatestLanguageApplied() {
		return $this->latest ? $this->latest : $this->default;
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
		preg_match_all( '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER[ 'HTTP_ACCEPT_LANGUAGE' ], $langs );
		if( 0 === count( $langs[ 1 ] ) ) {
			return false;
		}
		$langs = array_combine( $langs[ 1 ], $langs[ 4 ] );

		// To respect RFC if no priority is specified (q=;)
		foreach( $langs as $lang => $val ){
			$langs[ $lang ] = ( '' === $val ) ? 1.0 : (float) $val;
		}

		// Order by priority
		arsort( $langs, SORT_NUMERIC );

		// Search and use a known language
		foreach( $langs as $lang => $val ) {
			$language = $this->getLanguage( $lang );

			if( false !== $language ) {
				// Found a language that match browser preferences!
				return $language;
			}
		}
		return false;
	}

	/**
	 * Fill the GNU Gettext Environment
	 *
	 * @param $native bool Use or not the native implementation
	 */
	public static function GNUGettextEnvironment( $code, $encode, $domain, $directory, $native = false ) {
		if( $native ) {
			putenv( "LANG=$code.$encode" );
			$ok = setlocale( LC_MESSAGES, "$code.$encode" );
			bindtextdomain( $domain, $directory );
			textdomain( $domain );
			bind_textdomain_codeset( $domain, $encode );
		} else {
			$loader = MoLoader::getInstance();
			$loader->setlocale( "$code.$encode" );
			$loader->textdomain( $domain );
			$loader->bindtextdomain( $domain, $directory );
		}
	}

	/**
	 * Normalize a language code for internal use
	 *
	 * @param $code string
	 * @return string
	 */
	private static function normalize( $code ) {
		return strtolower( $code );
	}
}

class BozPHPLanguage {
	private $code;
	private $encode;
	private $iso;

	public function __construct($code, $encode, $iso) {
		$this->code   = $code;
		$this->encode = $encode;
		$this->iso    = $iso;
	}

	public function getCode() {
		return $this->code;
	}

	public function getEncode() {
		return $this->encode;
	}

	public function getISO() {
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
	public function guessISO( $code = null, $fallback = 'en' ) {
		if($code === null) {
			$code = $this->code;
		}
		$p = strpos( $code, '_' );
		if( false === $p ) {
			DEBUG && error( sprintf(
				___("Can't guess the ISO language from language code '%s'. Falling on default '%s'."),
				esc_html($code),
				$fallback
			) );
			return $fallback;
		}
		return substr($code, 0, $p);
	}
}
