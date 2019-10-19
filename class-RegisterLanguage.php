<?php
# Copyright (C) 2015, 2016, 2018, 2019 Valerio Bozzolan
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

// default gettext domain for this project
define_default( 'GETTEXT_DOMAIN', 'core' );

// default encoding for your .mo files (file suffix for it_IT.UTF-8 directory)
define_default( 'GETTEXT_DEFAULT_ENCODE', 'UTF-8' );

// default directory for your it_IT.UTF-8/LC_MESSAGES/domain.mo files
define_default( 'GETTEXT_DIRECTORY', ABSPATH . __ . 'l10n' );

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
	 * Get the singleton instance
	 *
	 * @return self
	 */
	public static function instance() {
		static $me = false;
		if( ! $me ) {
			$me = new self();
		}
		return $me;
	}

	/**
	 * Constructor
	 *
	 * @param string $domain GNU Gettext domain
	 * @param string $directory GNU Gettext directory
	 * @param string $default_encode GNU Gettext default language encode
	 */
	public function __construct( $domain = null, $directory = null, $default_encode = null, $native = null ) {
		if( ! $domain ) {
			// I know why you are here. That warning, you know.
			// PLEASE create this damn constant in your load-post.php
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
	 * @param $code string GNU Gettext code language
	 * @param $aliases array You can specify language aliases (in lower case)
	 * @param $encode string Override the default GNU Gettext language encode
	 * @param $iso string
	 * @param $human string e.g. 'English'
	 */
	public function registerLanguage( $code, $aliases = [], $encode = null, $iso = null, $human = null, $humanL10n = null ) {
		if( ! $encode ) {
			$encode = $this->gettextDefaultEncode;
		}

		if( ! $encode ) {
			return error( 'please specify encoding for language $code, or define GETTEXT_DEFAULT_ENCODE' );
		}

		$this->languages[ ++$this->i ] = new SucklessPHPLanguage( $code, $encode, $iso, $human, $humanL10n );

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
		$code = self::normalize( $default );
		if( ! isset( $this->aliases[ $code ] ) ) {
			error_die( "the default language $default have to be registered previously" );
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
	 * Get the language registered as the default one
	 *
	 * @return SucklessPHPLanguage|null
	 */
	public function getDefault() {
		return $this->default;
	}

	/**
	 * Get the latest language applied
	 *
	 * If you declared a default language, this never returns null.
	 *
	 * @return SucklessPHPLanguage|null
	 */
	public function getLatestLanguageApplied() {
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
	 * Get all the registered languages
	 *
	 * @return array
	 */
	public function getAll() {
		return $this->languages;
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
			$loader = MoLoader::instance();
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

/**
 * When using the 'latest_language()' you will get a 'SucklessPHPLanguage' object.
 *
 * Rappresent a language
 */
class SucklessPHPLanguage {

	/**
	 * Language code (e.g. 'it_IT')
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Language encoding (e.g. 'UTF-8')
	 *
	 * @var string
	 */
	private $encode;

	/**
	 * Language ISO code (e.g. 'it')
	 *
	 * @var string
	 */
	private $iso;

	/**
	 * Language name (untranslated)
	 *
	 * @var string
	 */
	private $human;

	/**
	 * Language name (translated)
	 */
	private $humanL10n;

	/**
	 * Constructor
	 *
	 * @param string $code      Language code e.g. 'it_IT'
	 * @param string $encode    Language encoding e.g. 'UTF-8' (or NULL for the default)
	 * @param string $iso       Language iso code e.g. 'en' (or NULL to guess)
	 * @param string $human     Language human name untranslated (e.g. 'Italian')
	 * @param string $humanL10n Language human name translated (e.g. 'Italiano')
	 */
	public function __construct( $code, $encode, $iso, $human, $humanL10n ) {
		$this->code   = $code;
		$this->encode = $encode;
		$this->iso    = $iso;
		$this->human  = $human;
		$this->humanL10n = $humanL10n;
	}

	/**
	 * Get the language code (e.g. 'it_IT')
	 *
	 * @return string
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * Get the language encoding (e.g. 'UTF-8')
	 *
	 * @return string
	 */
	public function getEncode() {
		return $this->encode;
	}

	/**
	 * Get the language ISO code (e.g. 'it')
	 *
	 * @return string
	 */
	public function getISO() {
		if( $this->iso === null ) {
			$this->iso = self::guessISO( $this->code );
		}
		return $this->iso;
	}

	/**
	 * Get the language human name untranslated (if provided)
	 *
	 * @return string
	 */
	public function getHuman() {
		return $this->human;
	}

	/**
	 * Get the language human name translated (if provided)
	 *
	 * @return string
	 */
	public function getHumanL10n() {
		return $this->humanL10n;
	}

	/**
	 * Guess the language ISO code from a code
	 *
	 * E.g. get 'it' from 'it_IT' or from 'it_IT.utf8' etc.
	 *
	 * Can be called statically if you provide $code.
	 *
	 * @param  string $code Language code
	 * @return string
	 */
	private static function guessISO( $code = null ) {

		$p = strpos( $code, '_' );
		if( $p !== false ) {
			return substr( $code, 0, $p );
		}

		// just return that string
		return $code;
	}
}
