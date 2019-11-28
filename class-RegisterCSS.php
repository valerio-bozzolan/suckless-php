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

class RegisterCSS {

	/**
	 * Stylesheets
	 */
	private $css = [];

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
	 * Register a new stylesheet
	 *
	 * @param string $name CSS name, like: "jquery-ui".
	 * @param string $url CSS url, like "http://example.org/styles/jquery-ui.css".
	 */
	public function register( $uid, $url ) {
		$this->css[ $uid ] = new CSS( $url );
	}

	/**
	 * Enqueue a previously registered stylesheet
	 *
	 * @param string  $uid           CSS name
	 * @param boolean $report_errors In case of errors, report the error?
	 */
	public function enqueue( $uid, $report_errors = true ) {
		if( empty( $this->css[ $uid ] ) ) {
			if( $report_errors ) {
				error( "unknown stylesheet $uid" );
			}
		} else {
			$this->css[ $uid ]->enqueue = true;
		}
	}

	/**
	 * Enqueue all registered style that are not registered as private use
	 */
	public function printAll( $force = false ) {
		foreach( $this->css as $uid => $css ) {
			if( $css->enqueue && !$css->enqueued ) {
				self::link( $css->url, $uid );
				$this->css[ $uid ]->enqueued = true;
			}
		}
	}

	/**
	 * Print the CSS link tag
	 */
	public static function link( $url, $uid ) {
		$url = site_page( $url );
		if( CACHE_BUSTER ) {
			$url .= false === strpos( $url, '?' ) ? '?' : '&amp;';
			$url .= CACHE_BUSTER;
		}
		echo "\n\t<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"$url\" />";
	}
}

class CSS {
	public $url;
	public $enqueue = false;
	public $enqueued = false;

	public function __construct( $url ) {
		$this->url = $url;
	}
}
