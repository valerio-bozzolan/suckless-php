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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <http://www.gnu.org/licenses/>.

/**
 * Static class useful to create some HTML tags
 *
 * Use: esc_attr()
 */
class HTML {

	/**
	 * If it contains a list of tag, it return all the tags with a space before
	 *
	 * @param string $tags The tag list
	 */
	public static function spaced( $tags ) {
		return empty( $tags ) ? '' : " $tags";
	}

	/**
	 * Generate a closed tag
	 *
	 * @param string $name       Tag name
	 * @param string $intag      Tag attributes
	 * @param string $attributes Tag attributes
	 */
	public static function tag( $name, $intag = '', $attributes = '' ) {
		return "<$name$attributes>$intag</$name>";
	}

	/**
	 * Generate a self closed tag
	 */
	public static function tagc( $name, $attributes = null ) {
		return "<$name$attributes />";
	}

	/**
	 * Generate an <a> tag
	 *
	 * @param string $href  The href="" attribute
	 * @param string $text  Displayed text
	 * @param string $title The title="" attribute
	 * @param string $class The class="" attribute
	 * @param string $intag Something else into the tag
	 * @return string
	 */
	public static function a( $href, $text = null, $title = null, $class= null, $intag = null ) {
		$s  =  self::property( 'href',  $href  );
		$s .=  self::property( 'title', $title );
		$s .=  self::property( 'class', $class );
		$s .=  self::spaced( $intag );
		return self::tag( 'a', $text, $s );
	}

	/**
	 * Generate an <img> tag
	 *
	 * @param string $src   The src="" attribute
	 * @param string $alt   The alt="" attribute
	 * @param string $title The title="" attribute
	 * @param string $class The class="" attribute
	 * @param string $intag Something else into the tag
	 * @return string
	 */
	public static function img( $src, $alt = null, $title = null, $class = null, $intag = null ) {
		$s  =  self::property( 'src',   $src   );
		$s .=  self::property( 'alt',   $alt   );
		$s .=  self::property( 'title', $title );
		$s .=  self::property( 'class', $class );
		$s .=  self::spaced( $intag );
		return self::tagc( 'img', $s );
	}

	/**
	 * Return the property of a tag, only if the value isn't NULL
	 *
	 * @param  string $name  Property's name
	 * @param  string $value Property's value
	 * @return string
	 */
	public static function property( $name, $value ) {
		$s = '';
		if( !empty( $value ) ) {
			$value = esc_attr( $value );
			$s = " $name=\"$value\"";
		}
		return $s;
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
