<?php
# Copyright (C) 2015, 2017, 2019 Valerio Bozzolan
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
 * To enqueue JS libraries
 */
class RegisterJS {

	/**
	 * Script names
	 */
	private $javascript = [];

	private $generated = [ false, false ];

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
	 * Register a new script name
	 *
	 * @param string $name JS name, like: "jquery"
	 * @param string $url Script url, like "http://example.org/lib/jquery.js"
	 */
	public function register( $uid, $url ) {
		if( isset( $this->javascript[ $uid ] ) ) {
			$this->javascript[ $uid ]->url = $url;
		} else {
			$this->javascript[ $uid ] = new JS( $url );
		}
	}

	/**
	 * Enqueue a previous registered JS name
	 *
	 * @param string $name JS name
	 * @param bool $position Place it in the head of the page or not
	 */
	public function enqueue( $uid, $position = null ) {
		$position = JS::filterPosition( $position );
		if( isset( $this->javascript[ $uid ] ) ) {
			$this->javascript[ $uid ]->enqueue( $position );
		} else {
			error( "unregistered JS $uid" );
		}
	}

	public function printAll( $position ) {
		$cache_burster = CACHE_BUSTER;
		foreach( $this->javascript as $uid => $javascript ) {
			if( $javascript->enqueue && $javascript->position === $position ) {
				echo "\n";
				if( $position === JS::HEADER ) {
					echo "\t";
				}
				$url = $javascript->url;
				if( CACHE_BUSTER ) {
					$url .= false === strpos( $url, '?' ) ? '?' : '&amp;';
					$url .= CACHE_BUSTER;
				}
				echo "<script src=\"$url\"></script>";
				if( DEBUG ) {
					echo "<!-- $uid -->";
				}
			}
		}
		$this->generated[ (int) $position ] = true;
	}

	public function isGenerated( $position ) {
		return $this->generated[ (int) $position ];
	}
}

class JS {
	const HEADER = true;
	const FOOTER = false;

	public $url;
	public $position = self::HEADER;
	public $enqueue = false;

	public function __construct( $url ) {
		$this->url = $url;
	}

	/**
	 * @param boolean $in_head {JS::HEADER, JS::FOOTER}
	 */
	public function enqueue( $position = self::HEADER ) {
		$this->position = self::filterPosition( $position );
		$this->enqueue = true;
	}

	public static function filterPosition( $position ) {
		if( $position === null ) {
			return self::HEADER;
		}
		return $position;
	}
}
