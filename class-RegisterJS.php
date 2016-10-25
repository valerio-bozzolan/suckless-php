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
 * Classes useful to enqueue JS libraries.
 */
class JS {
	const HEADER = true;
	const FOOTER = false;

	public $url;
	public $position = self::HEADER;
	public $enqueue = false;

	public function __construct($url) {
		$this->url = $url;
	}

	/**
	 * @param boolean $in_head {JS::HEADER, JS::FOOTER}
	 */
	public function enqueue($position = self::HEADER) {
		$this->position = self::filterPosition($position);
		$this->enqueue = true;
	}

	public static function filterPosition($position) {
		if( $position === null ) {
			return self::HEADER;
		}
		return $position;
	}
}

class RegisterJS {

	/**
	 * Script names.
	 */
	private $javascript = [];

	private $generated = [false, false];

	/**
	 * Register a new script name.
	 *
	 * @param string $name JS name, like: "jquery".
	 * @param string $url Script url, like "http://example.org/lib/jquery.js".
	 */
	public function register($javascript_uid, $url) {
		if(isset($this->javascript[$javascript_uid])) {
			$this->javascript[$javascript_uid]->url = $url;
		} else {
			$this->javascript[$javascript_uid] = new JS($url);
		}
	}

	/**
	 * Enqueue a previous registered JS name.
	 *
	 * @param string $name JS name.
	 * @param bool $in_head Place it in the head of the page or not.
	 */
	public function enqueue($javascript_uid, $position = null) {
		$position = JS::filterPosition($position);

		if(isset($this->javascript[$javascript_uid])) {
			$this->javascript[$javascript_uid]->enqueue($position);
			return true;
		} else {
			DEBUG && error( sprintf(
				_("La libreria JavaScript %s non può essere incorporata poichè non è ancora stata registrata."),
				"<em>" . esc_html($javascript_uid) . "</em>"
			) );
			return false;
		}
	}

	public function printAll($position) {
		foreach($this->javascript as $javascript_uid=>$javascript) {
			if($javascript->enqueue && $javascript->position === $position) {
				echo "\n";
				if($position === JS::HEADER) {
					echo "\t";
				}
				echo "<script type=\"text/javascript\" src=\"$javascript->url\"></script>";
				if(DEBUG) {
					echo "<!-- $javascript_uid -->";
				}
			}
		}
		$this->generated[(int) $position] = true;
	}

	public function isGenerated($position) {
		return $this->generated[(int) $position];
	}
}
