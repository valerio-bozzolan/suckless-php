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

/**
 * For efficience reasons this file contains these classes:
 *
 * MoLoader
 * MoTranslator
 * MoStringReader
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

/*
    Copyright (c) 2003, 2009 Danilo Segan <danilo@kvota.net>.
    Copyright (c) 2005 Nico Kaiser <nico@siriux.net>
    Copyright (c) 2016 Michal Čihař <michal@cihar.com>
    Copyright (c) 2018 Valerio Bozzolan <gnu@linux.it>

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

//use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Provides a simple gettext replacement that works independently from
 * the system's gettext abilities.
 * It can read MO files and use them for translating strings.
 *
 * It caches all strings and translations to speed up the string lookup.
 */
class MoTranslator
{
    /**
     * None error.
     */
    const ERROR_NONE = 0;
    /**
     * File does not exist.
     */
    const ERROR_DOES_NOT_EXIST = 1;
    /**
     * File has bad magic number.
     */
    const ERROR_BAD_MAGIC = 2;
    /**
     * Error while reading file, probably too short.
     */
    const ERROR_READING = 3;

    /**
     * Big endian mo file magic bytes.
     */
    const MAGIC_BE = "\x95\x04\x12\xde";
    /**
     * Little endian mo file magic bytes.
     */
    const MAGIC_LE = "\xde\x12\x04\x95";

    /**
     * Parse error code (0 if no error).
     *
     * @var int
     */
    public $error = self::ERROR_NONE;

    /**
     * Array with original -> translation mapping.
     *
     * @var array
     */
    private $cache_translations = array();

    /**
     * Constructor.
     *
     * @param string $filename Name of mo file to load
     */
    public function __construct($filename)
    {

        if (!is_readable($filename)) {
            $this->error = self::ERROR_DOES_NOT_EXIST;
            return;
        }

        $stream = new MoStringReader($filename);

        try {
            $magic = $stream->read(0, 4);
            if (strcmp($magic, self::MAGIC_LE) == 0) {
                $unpack = 'V';
            } elseif (strcmp($magic, self::MAGIC_BE) == 0) {
                $unpack = 'N';
            } else {
                $this->error = self::ERROR_BAD_MAGIC;

                return;
            }

            /* Parse header */
            $total = $stream->readint($unpack, 8);
            $originals = $stream->readint($unpack, 12);
            $translations = $stream->readint($unpack, 16);

            /* get original and translations tables */
            $table_originals = $stream->readintarray($unpack, $originals, $total * 2);
            $table_translations = $stream->readintarray($unpack, $translations, $total * 2);

            /* read all strings to the cache */
            for ($i = 0; $i < $total; ++$i) {
                $original = $stream->read($table_originals[$i * 2 + 2], $table_originals[$i * 2 + 1]);
                $translation = $stream->read($table_translations[$i * 2 + 2], $table_translations[$i * 2 + 1]);
                $this->cache_translations[$original] = $translation;
            }
        } catch (ReaderException $e) {
            $this->error = self::ERROR_READING;

            return;
        }
    }

    /**
     * Translates a string.
     *
     * @param string $msgid String to be translated
     *
     * @return string translated string (or original, if not found)
     */
    public function gettext($msgid)
    {
		// if $msgid is NULL it gets the entire .po header .___.
		if( ! $msgid ) {
			return $msgid;
		}

        if (array_key_exists($msgid, $this->cache_translations)) {
            return $this->cache_translations[$msgid];
        }

        return $msgid;
    }

    /**
     * Check if a string is translated.
     *
     * @param string $msgid String to be checked
     *
     * @return bool
     */
    public function exists($msgid)
    {
        return array_key_exists($msgid, $this->cache_translations);
    }

    /**
     * Set translation in place
     *
     * @param string $msgid  String to be set
     * @param string $msgstr Translation
     *
     * @return void
     */
    public function setTranslation($msgid, $msgstr)
    {
        $this->cache_translations[$msgid] = $msgstr;
    }
}

/*
Copyright (c) 2003, 2005, 2006, 2009 Danilo Segan <danilo@kvota.net>.
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

/**
 * Simple wrapper around string buffer for
 * random access and values parsing.
 */
class MoStringReader {

	private $str;
	private $len;

	/**
	 * Constructor.
	 *
	 * @param string $filename Name of file to load
	 */
	public function __construct( $filename ) {
		$this->str = file_get_contents( $filename );
		$this->len = strlen( $this->str );
	}

	/**
	 * Read number of bytes from given offset.
	 *
	 * @param int $pos   Offset
	 * @param int $bytes Number of bytes to read
	 *
	 * @return string
	 */
	public function read( $pos, $bytes ) {
		if( $pos + $bytes > $this->len ) {
			throw new Exception( 'not enough bytes' );
		}
		return substr( $this->str, $pos, $bytes );
	}

	/**
	 * Reads a 32bit integer from the stream.
	 *
	 * @param string $unpack Unpack string
	 * @param int	$pos	Position
	 *
	 * @return int Ingerer from the stream
	 */
	public function readint( $unpack, $pos ) {
		$data = unpack( $unpack, $this->read($pos, 4) );
		$result = $data[1];

		/* We're reading unsigned int, but PHP will happily
		 * give us negative number on 32-bit platforms.
		 *
		 * See also documentation:
		 * https://secure.php.net/manual/en/function.unpack.php#refsect1-function.unpack-notes
		 */
		return $result < 0 ? PHP_INT_MAX : $result;
	}

	/**
	 * Reads an array of integers from the stream.
	 *
	 * @param string $unpack Unpack string
	 * @param int	$pos	Position
	 * @param int	$count  How many elements should be read
	 *
	 * @return array Array of Integers
	 */
	public function readintarray( $unpack, $pos, $count ) {
		return unpack( $unpack . $count, $this->read( $pos, 4 * $count ) );
	}
}
