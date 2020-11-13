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
		if( !$me ) {
			$me = new self();
		}
		return $me;
	}

	/**
	 * Register a new script name
	 *
	 * This will not automatically enqueue it (normally).
	 *
	 * @param string $name         Script name, like: "jquery" (or just true for whatever, to enqueue it)
	 * @param mixed  $url          Script url, like "http://example.org/lib/jquery.js"
	 * @param string $position     header|footer
	 * @param array  $dependencies Dependent script names
	 */
	public function register( $uid, $url, $position = null, $dependencies = [] ) {

		// assume the default position
		if( !$position ) {
			$position = self::$DEFAULT;
		}

		// create the object
		$js = new JS( $uid, $url, $position, $dependencies );

		// append
		$this->js[ $uid ] = $js;
	}

	/**
	 * Register an inline script
	 *
	 * @param string $uid      Script name, like: "jquery"
	 * @param array  $data     Inline JavaScript content (without <script> tags)
	 * @param string $position before|after
	 */
	public function registerInline( $uid, $data, $position ) {

		// no script no party
		if( !isset( $this->js[ $uid ] ) ) {
			throw new SucklessException( "cannot register inline on missing JavaScript '$uid'" );
		}

		$this->js[ $uid ]->inline[ $position ][] = $data;
	}

	/**
	 * Enqueue a previous registered JS name
	 *
	 * Note that if there is a stylesheet with the same UID, it will be
	 * enqueued as well.
	 *
	 * @param $name     string JS name
	 * @param $position string Place it in the head of the page or not
	 */
	public function enqueue( $uid, $position = null ) {

		// no script no party
		if( !isset( $this->js[ $uid ] ) ) {
			throw new SucklessException( "cannot enqueue missing JavaScript '$uid'" );
		}

		$js = $this->js[ $uid ];

		// eventually override script position
		if( $position ) {
			$js->position = $position;
		}

		// eventually mark to be enqueued with also its dependencies
		if( !$js->enqueue ) {

			// also enqueue the stylesheet with the same UID (if it exists)
			RegisterCSS::instance()->enqueue( $uid, false );

			$js->enqueue = true;
			foreach( $js->dependencies as $dependency_uid ) {
				$this->enqueue( $dependency_uid );
			}
		}
	}

	/**
	 * Print all the JS from a specified position
	 *
	 * @param $position string
	 */
	public function printAll( $position ) {
		$glue = $position === 'header' ? "\n\t" : "\n";
		foreach( $this->js as $js ) {
			if( $js->enqueue && $js->position === $position ) {
				$js->printInline( 'before', $glue );
				$js->printNormal( $glue );
				$js->printInline( 'after', $glue );
			}
		}
	}
}

class JS {

	public $uid;

	public $url;

	public $position;

	public $enqueue = false;

	public $inline;

	public $dependencies;

	/**
	 * Construct
	 *
	 * @param $uid string
	 * @param $url string
	 * @param $position string header|footer
	 */
	public function __construct( $uid, $url, $position, $dependencies ) {
		$this->uid = $uid;
		$this->url = $url;
		$this->position = $position;
		$this->dependencies = $dependencies;
		$this->inline = [
			'after'  => [],
			'before' => [],
		];
	}

	/**
	 * Print inline JavaScript parts
	 *
	 * @param $position before|after
	 * @param $glue string
	 */
	public function printInline( $position, $glue ) {
		$parts = $this->inline[ $position ];
		if( $parts ) {
			echo "$glue<script>$glue" .
			     implode( $parts, $glue ) .
			     "$glue</script>";

			if( DEBUG ) {
				echo "<!-- {$this->uid} - $position -->";
			}
		}
	}

	/**
	 * Print the normal JS inclusion
	 *
	 * @param $glue string
	 */
	public function printNormal( $glue ) {
		if( $this->url ) {
			$url = site_page( $this->url );
			if( CACHE_BUSTER ) {
				$url .= false === strpos( $url, '?' ) ? '?' : '&amp;';
				$url .= CACHE_BUSTER;
			}
			echo "$glue<script src=\"$url\"></script>";
			if( DEBUG ) {
				echo "<!-- {$this->uid} -->";
			}
		}
	}
}
