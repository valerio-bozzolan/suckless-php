<?php
# Copyright (C) 2015, 2019 Valerio Bozzolan
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
 * Dynamically inject functions in modules ("places").
 *
 * @require DEBUG, error(), esc_html()
 */
class RegisterModule {

	/**
	 * Assoc functions to modules
	 */
	private $module = [];

	/**
	 * Constructor
	 *
	 * @param $defaults boolean Load the defaults modules
	 */
	function __construct( $defaults = true ) {
		if( $defaults ) {
			$this->loadDefaults();
		}
	}

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
	 * Load the default modules
	 */
	private function loadDefaults() {
		$this->register( 'header' );
		$this->register( 'footer' );

		// Append scripts and styles
		$this->injectFunction( 'header', function () {
			RegisterJS ::instance()->printAll( 'header' );
			RegisterCSS::instance()->printAll();
		} );
		$this->injectFunction( 'footer', function () {
			RegisterJS::instance()->printAll( 'footer' );
		} );
	}

	/**
	 * Formally register a module (a place where you can inject functions)
	 *
	 * @param string $name
	 */
	public function register( $name ) {
		if( ! isset( $this->module[ $name ] ) ) {
			$this->module[ $name ] = [];
		}
	}

	/**
	 * Inject a callback into a module
	 *
	 * @param string $name Module name
	 * @param string $callback The callback
	 */
	public function injectFunction( $name, $callback ) {
		if( ! isset( $this->module[ $name ] ) ) {
			return error( "can't inject in unknown module $name" );
		}
		$this->module[ $name ][] = $callback;
	}

	/**
	 * Load all the functions related to this module name.
	 *
	 * @param string $name Module name
	 */
	public function loadModule( $name ) {
		if( ! isset( $this->module[ $name ] ) ) {
			return error( "can't load unregistered module $name" );
		}
		$status = true;
		foreach( $this->module[ $name ] as $callback ) {
			$status = $status && $callback();
		}
		return $status;
	}
}
