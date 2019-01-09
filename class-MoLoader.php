<?php
/*
Copyright (c) 2005 Steven Armstrong <sa at c-area dot ch>
Copyright (c) 2009 Danilo Segan <danilo@kvota.net>
Copyright (c) 2016 Michal Čihař <michal@cihar.com>

This file is part of MoTranslator.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

//namespace PhpMyAdmin\MoTranslator;

class MoLoader {
	/**
	 * Loader instance.
	 *
	 * @static
	 *
	 * @var Loader
	 */
	private static $_instance;

	/**
	 * Default gettext domain to use.
	 *
	 * @var string
	 */
	private $default_domain = '';

	/**
	 * Configured locale.
	 *
	 * @var string
	 */
	private $locale = '';

	/**
	 * Loaded domains.
	 *
	 * @var array
	 */
	private $domains = [];

	/**
	 * Bound paths for domains.
	 *
	 * @var array
	 */
	private $paths = [ '' => './' ];

	/**
	 * Returns the singleton Loader object.
	 *
	 * @return Loader object
	 */
	public static function instance() {
		if( ! self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Returns Translator object for domain or for default domain.
	 *
	 * @param string $domain Translation domain
	 *
	 * @return Translator
	 */
	public function getTranslator( $domain = '' ) {
		if( empty($domain) ) {
			$domain = $this->default_domain;
		}
		if(!isset($this->domains[$this->locale])) {
			$this->domains[$this->locale] = array();
		}
		if(!isset($this->domains[$this->locale][$domain])) {
			if (isset($this->paths[$domain])) {
				$base = $this->paths[$domain];
			} else {
				$base = '.' . __;
			}

			$filename = $base . __ . $this->locale . __ . 'LC_MESSAGES' . __ . "$domain.mo";

			// We don't care about invalid path, we will get fallback
			// translator here
			$this->domains[$this->locale][$domain] = new MoTranslator($filename);
		}
		return $this->domains[$this->locale][$domain];
	}

	/**
	 * Sets the path for a domain.
	 *
	 * @param string $domain Domain name
	 * @param string $path   Path where to find locales
	 */
	public function bindtextdomain( $domain, $path ) {
		$this->paths[ $domain ] = $path;
	}

	/**
	 * Sets the default domain.
	 *
	 * @param string $domain Domain name
	 */
	public function textdomain( $domain ) {
		$this->default_domain = $domain;
	}

	/**
	 * Sets a requested locale.
	 *
	 * @param string $locale Locale name
	 *
	 * @return string Set or current locale
	 */
	public function setlocale( $locale ) {
		if( !empty( $locale ) ) {
			$this->locale = $locale;
		}
		return $this->locale;
	}
}
