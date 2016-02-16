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
 * Allows you to be sure that a global object exists.
 */
class G {
	/**
	 * Associative array of: expected global variable name => it's class
	 */
	private $added = [];

	/**
	 * Register an expected global variable name to it's class.
	 *
	 * If it doesn't exists it will be created as global and istantiated.
	 */
	public function add($name, $class) {
		$this->added[ $name ] = $class;
	}

	/**
	 * Be sure that a global object exists.
	 *
	 * This method allows you to use the power of PHP and spl_audoload()
	 * loading resources only when you request them.
	 *
	 * E.g. if you create a database connection using something as
	 * $GLOBAL['db'] = new DB(), and we do it, you can avoid starting a
	 * database connection even in pages that don't require database connections
	 * simply saying that you "expect" an initialized global '$db' object.
	 * Let's say expect('db').
	 *
	 * As you can see, in the best case it's only an isset() away from your code.
	 *
	 * @param string $name A global var name.
	 */
	public function expect($name) {
		if( isset( $GLOBALS[ $name ] ) ) {
			return;
		}

		// Die if class was not registered
		isset( $this->added[ $name ] ) || error_die( sprintf(
			_("Variabile globale '%s' attesa ma mai registrata. L'hai scritta correttamente?"),
			esc_html($name)
		) );

		$class_name = $this->added[ $name ];

		class_exists($class_name, true) || error_die( sprintf(
			_("La classe '%s' non esiste!"),
			esc_html($class_name)
		) );

		$GLOBALS[ $name ] = new $class_name();
	}
}
