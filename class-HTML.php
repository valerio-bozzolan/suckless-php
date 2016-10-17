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

/*
 * Static classes useful to create some HTML tags.
 * Use: esc_attr()
 */

class HTML {

	private $tabs;

	/**
	 * If it contains a list of tag, it return all the tags with a space before.
	 *
	 * @param string $tags The tag list
	 */
	public static function spaced($tags) {
		return ( empty($tags) ) ? '' : " $tags";
	}

	/**
	 * Closed tag.
	 */
	public static function tag($name, $intag = '', $properties = '') {
		return "<$name$properties>$intag</$name>";
	}

	/**
	 * Self closed tag.
	 */
	public static function tagc($name, $properties = null) {
		return "<$name$properties />";
	}

	public static function a($href, $text = null, $title = null, $class= null, $intag = null) {
		return self::tag('a', $text, self::property('href', $href) . self::property('title', $title) . self::property('class', $class) . self::spaced($intag) );
	}

	public static function img($src, $alt = null, $title = null, $class = null, $intag = null) {
		return self::tagc('img', self::property('src', $src) . self::property('title', $title) . self::property('alt', $alt) . self::property('class', $class) . self::spaced($intag) );
	}

	/**
	 * Return the property of a tag, only if the value isn't NULL.
	 *
	 * @param string $name Property's name
	 * @param string $value Property's value
	 * @return string $name="$value" (or NULL if $value = '')
	 */
	public static function property($name, $value) {
		return (empty($value)) ? '' : ' ' . $name . '="' . esc_attr( $value ) . '"';
	}
}

class Table extends HTML {

    public static function start($intag = '') {
	return '<table' . self::spaced($intag);
    }

    public static function stop() {
	return '</table>';
    }

    public static function tr($intag = '') {
	return '<tr' . self::spaced($intag) . '>';
    }

    public static function tr_stop() {
	return '</tr>';
    }

    public static function th($content, $intag = '') {
	return '<th' . self::spaced($intag) . '>' . $content . '</th>';
    }

    public static function th_stop($content, $intag = '') {
	return '<td' . self::spaced($intag) . '>' . $content . '</td>';
    }
}

class Form extends HTML {

	public static function start($action = '', $method = '', $intag = '') {
		if($method != '' && $method != 'get' && $method != 'post') {
			throw new Exception("Param method must be get or post.");
		}
		return '<form' . self::property('action', $action) . self::property('method', $method) . self::spaced($intag) . '>';
	}

	public static function stop() {
		return '</form>';
	}

	public static function input($type, $name, $value = '', $intag = '') {
		return '<input type="' . $type . '"' . self::property('name', $name) . self::property('value', $value) . self::spaced($intag) . '>';
	}

	public static function select($type, $name, $value = '', $intag = '') {
		$tag = '<select' . self::property('name', $name) . self::spaced($intag);
		$opt = '';
		foreach($value as $key=>$val) {
			$opt .= '<option value="' . $key . '">' . $val . ' </option>';
		}
		return $tag . $opt . '</select>';
	}

	public static function label($for, $text, $intag = '') {
		return '<label for=' . $for . '"' . self::spaced($intag) . '>' . $text . '</label>';
	}
}
