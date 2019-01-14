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
 * Register and enqueue JS libraries
 */
class RegisterJS {

	/**
	 * Default position
	 */
	public static $DEFAULT = 'header';

	/**
	 * Script names
	 */
	private $js = [];

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
	 * @param mixed $url Script url, like "http://example.org/lib/jquery.js"
	 * @param string $position header|footer
	 */
	public function register( $uid, $url, $position = null ) {
		if( isset( $this->js[ $uid ] ) ) {
			$this->js[ $uid ]->url = $url;
		} else {
			$this->js[ $uid ] = new JS( $url, $position );
		}
	}

	/**
	 * Enqueue a previous registered JS name
	 *
	 * @param $name string JS name
	 * @param $position string Place it in the head of the page or not
	 */
	public function enqueue( $uid, $position = null ) {
		if( isset( $this->js[ $uid ] ) ) {
			$this->js[ $uid ]->enqueue( $position );
		} else {
			error( "unregistered JS $uid" );
		}
	}

	/**
	 * Print all the elements from the specified position
	 *
	 * @param $position string
	 */
	public function printAll( $position ) {
		foreach( $this->js as $uid => $js ) {
			if( $js->enqueue && $js->position === $position ) {
				echo "\n";
				if( $position === 'header' ) {
					echo "\t";
				}
				$url = $js->url;
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
	}
}

class JS {

	public $url;

	public $position;

	public $enqueue = false;

	/**
	 * Construct
	 *
	 * @param $url string
	 * @param $position string header|footer
	 */
	public function __construct( $url, $position = null ) {
		if( ! $position ) {
			$position = RegisterJS::$DEFAULT;
		}
		$this->url = $url;
		$this->position = $position;
	}

	/**
	 * @param $position string header|footer
	 */
	public function enqueue( $position = null ) {
		$this->enqueue = true;
		if( $position !== null ) {
			$this->position = $position;
		}
	}

}
