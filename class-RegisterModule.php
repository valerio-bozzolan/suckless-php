<?php
# Copyright (C) 2015 Valerio Bozzolan
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
 * Dinamically inject functions in modules ("places").
 *
 * @require DEBUG, error(), esc_html()
 */
class RegisterModule {

	/**
	 * Assoc functions to modules
	 */
	private $module = [];

	function __construct($defaults = true) {
		$defaults && $this->loadDefaults();
	}

	private function loadDefaults() {
		$this->register('header');
		$this->register('footer');

		// Append scripts and styles
		$this->injectFunction('header', function() {
			expect('css');
			expect('javascript');
			$GLOBALS['css']->printAll();
			$GLOBALS['javascript']->printAll( JS::HEADER );
		});
		$this->injectFunction('footer', function() {
			expect('javascript');
			$GLOBALS['javascript']->printAll( JS::FOOTER );
		});
	}

	/**
	 * Formally register a module (a place where you can inject functions)
	 *
	 * @param string $module_uid
	 */
	public function register($module_uid) {
		if( isset( $this->module[ $module_uid ] ) ) {
			DEBUG && error( sprintf(
				_("Il modulo %s è già stato registrato."),
				"<code>" . esc_html($module_uid) . "</code>"
			) );
		}
		$this->module[ $module_uid ] = [];
	}

	/**
	 * @param string $module_uid The place name (e.g. 'footer')
	 * @param string $callback The callback
	 */
	public function injectFunction($module_uid, $callback) {
		if( ! isset( $this->module[ $module_uid ] ) ) {
			$this->module[$module_uid] = [];
			DEBUG && error( sprintf(
				_("Il modulo %s non è stato ancora creato. Hai sbagliato a scriverlo?"),
				"<em>" . esc_html($module_uid) . "</em>"
			) );
		}
		$this->module[ $module_uid ][] = $callback;
	}

	/**
	 * Load all the functions related to this module name.
	 *
	 * @param string $callback
	 */
	public function loadModule($module_uid) {
		if( ! isset( $this->module[ $module_uid ] ) ) {
			DEBUG && error( sprintf(
				_("Il modulo %s è già stato registrato."),
				"<code>" . esc_html($module_uid) . "</code>"
			) );
			return false;
		}

		// Short form
		$module = & $this->module[$module_uid];

		// Load every callback
		$tot_status = true;
		$n = count($module);
		for($i=0; $i<$n; $i++) {
			if( is_closure( $module[$i] ) ) {
				$status = $module[$i]();
				if(DEBUG && $status === false) {
					error( sprintf(
						_("Errore caricando il modulo %s (callback %s°)."),
						"<em>" . esc_html($module_uuid) . "</em>",
						"<em>" . $i . "</em>"
					) );
				}
			} else {
				$status = false;
				DEBUG && error( sprintf(
					_("Il callback richiesto per il modulo %s non esiste."),
					"<em>" . esc_html($module_uuid) . "</em>"
				) );
			}
			$tot_status = $tot_status && $status;
		}
		return $tot_status;
	}
}
