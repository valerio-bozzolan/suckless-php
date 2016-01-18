<?php
/*
 * Copyright (C) 2015 Valerio Bozzolan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Allows you to use something as "$db" for DB classes
 */
class G {
	private $added = [];

	public function add($name, $class) {
		$this->added[ $name ] = $class;
	}

	/**
	 * Be sure that a global var exists.
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

		$class = $this->added[ $name ];

		// Die if class don't exists (rember that the spl_autoload_register() is in action)
		class_exists( $class ) || error_die( sprintf(
			_("La classe '%s' non esiste!"),
			esc_html($class)
		) );

		$GLOBALS[ $name ] = new $class();
	}
}
