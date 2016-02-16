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

class CSS {
	public $url;
	public $enqueue = false;
	public $enqueued = false;

	public function __construct($url) {
		$this->url = $url;
	}
}

class RegisterCSS {

	/**
	 * Stylesheets
	 */
	private $css = [];

	/**
	 * Register a new style name.
	 *
	 * @param string $name CSS name, like: "jquery-ui".
	 * @param string $url CSS url, like "http://example.org/styles/jquery-ui.css".
	 * @param boolean $private_use True: include it with $this->enqueueAll(); false: you have to include it manually. Use CSS::PRIVATE_USE instead of true for semantic reasons.
	 */
	public function register($css_uid, $url) {
		$this->css[$css_uid] = new CSS($url);
	}

	/**
	 * Enqueue a previous registered CSS name.
	 *
	 * @param string $css_uid CSS name.
	 */
	public function enqueue($css_uid) {
		if(!isset($this->css[$css_uid])) {
			DEBUG && error( sprintf(
				_("Il foglio di stile %s non può essere incorporato perchè non è stato ancora registrato."),
				"<em>" . esc_html($css_uid) . "</em>"
			) );
		} else {
			$this->css[$css_uid]->enqueue = true;
			return true;
		}
		return false;
	}

	/**
	 * Enqueue all registered style that are not registered as private use.
	 */
	public function printAll($force = false) {
		foreach($this->css as $css_uid=>$css) {
			if($css->enqueue && !$css->enqueued) {
				self::link($css->url, $css_uid);
				$this->css[$css_uid]->enqueued = true;
			}
		}
	}

	/**
	 * Print the CSS link tag.
	 */
	public static function link($url, $css_uid) {
		echo "\n\t<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" id=\"$css_uid\" href=\"$url\" />";
	}
}
