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
 * Classes useful to enqueue JS libraries.
 */
class JavascriptLib {
	const HEADER = true;
	const FOOTER = false;

	public $url;
	public $position = self::HEADER;
	public $enqueue = false;

	public function __construct($url) {
		$this->url = $url;
	}

	/**
	 * @param boolean $in_head {JavascriptLib::HEADER, JavascriptLib::FOOTER}
	 */
	public function enqueue($position = self::HEADER) {
		$this->position = $position;
		$this->enqueue = true;
	}
}

class RegisterJavascriptLibs {

	/**
	 * Script names.
	 */
	private $javascript = array();

	private $generated = array(false, false);

	/**
	 * Register a new script name.
	 *
	 * @param string $name JavascriptLib name, like: "jquery".
	 * @param string $url Script url, like "http://example.org/lib/jquery.js".
	 */
	public function register($javascript_uid, $url) {
		if(isset($this->javascript[$javascript_uid])) {
			$this->javascript[$javascript_uid]->url = $url;
		} else {
			$this->javascript[$javascript_uid] = new JavascriptLib($url);
		}
	}

	/**
	 * Enqueue a previous registered JavascriptLib name.
	 *
	 * @param string $name JavascriptLib name.
	 * @param bool $in_head Place it in the head of the page or not.
	 */
	public function enqueue($javascript_uid, $position = JavascriptLib::HEADER) {
		if(isset($this->javascript[$javascript_uid])) {
			$this->javascript[$javascript_uid]->enqueue($position);
			return true;
		} else {
			if(DEBUG) {
				error( printf(
					_('La libreria Javascript <em>%s</em> non può essere incorporata poichè non è ancora stata registrata.'),
					esc_html($javascript_uid)
				));
			}
			return false;
		}
	}

	public function enqueue_all($position) {
		foreach($this->javascript as $javascript_uid=>$javascript) {
			if($javascript->enqueue && $javascript->position === $position) {
				echo "\n";
				if($position === JavascriptLib::HEADER) {
					echo "\t";
				}
				echo "<script type=\"text/javascript\" src=\"$javascript->url\" rel=\"$javascript_uid\"></script>";
			}
		}
		$this->generated[(int) $position] = true;
	}

	public function isGenerated($position) {
		return $this->generated[(int) $position];
	}
}

class CSSLib {
	public $url;
	public $enqueue = false;
	public $enqueued = false;

	public function __construct($url) {
		$this->url = $url;
	}
}

class RegisterCSSLibs {

	/**
	 * Stylesheets
	 */
	private $css = array();

	/**
	 * Register a new style name.
	 *
	 * @param string $name CSSLib name, like: "jquery-ui".
	 * @param string $url CSSLib url, like "http://example.org/styles/jquery-ui.css".
	 * @param boolean $private_use True: include it with $this->enquele_all(); false: you have to include it manually. Use CSSLib::PRIVATE_USE instead of true for semantic reasons.
	 */
	public function register($css_uid, $url) {
		$this->css[$css_uid] = new CSSLib($url);
	}

	/**
	 * Enqueue a previous registered CSSLib name.
	 *
	 * @param string $css_uid CSSLib name.
	 */
	public function enqueue($css_uid) {
		if(!isset($this->css[$css_uid])) {
			if(DEBUG) {
				error( printf(
					_('Il foglio di stile <em>%s</em> non può essere incorporato perchè non è stato ancora registrato.'),
					esc_html($css_uid)
				));
			}
		} else {
			$this->css[$css_uid]->enqueue = true;
			return true;
		}
		return false;
	}

	/**
	 * Enqueue all registered style that are not registered as private use.
	 */
	public function enqueue_all($force = false) {
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
