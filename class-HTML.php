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
 * Class useful to create some HTML tags
 *
 * Use: esc_attr()
 */
class HTML {

	/**
	 * Tagname
	 *
	 * @var string
	 */
	private $tagname;

	/**
	 * Associative array of attributes and their values
	 *
	 * @var array
	 */
	private $attrs;

	/**
	 * Text inside the tag
	 *
	 * @var string
	 */
	private $text;

	/**
	 * Constructor
	 *
	 * @param string $tagname    Tag name in lower case
	 * @param array  $attributes Associative array of attributes
	 */
	public function __construct( $tagname, $attributes = [] ) {
		$this->tagname = $tagname;
		$this->attrs = $attributes;
	}

	/**
	 * Set an attribute
	 *
	 * @param string $attr  Attribute name
	 * @param string $value Attribute value
	 * @return self
	 */
	public function setAttr( $attr, $value ) {
		$this->attrs[ $attr ] = $value;
		return $this;
	}

	/**
	 * Set the tag text
	 *
	 * @param string $text Text
	 * @return self
	 */
	public function setText( $text ) {
		$this->text = $text;
		return $this;
	}

	/**
	 * Get the tag to string
	 *
	 * @return
	 */
	public function render() {
		$tagname = $this->tagname;
		$intag = '';
		foreach( $this->attrs as $attr => $value ) {
			$intag .= self::property( $attr, $value );
		}
		if( $tagname === 'img' ) {
			return self::tagc( $tagname, $intag );
		}
		return self::tag( $tagname, $this->text, $intag );
	}

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
		if( $value !== null ) {
			$value = esc_attr( $value );
			$s = " $name=\"$value\"";
		}
		return $s;
	}

	/**
	 * Return an <input>
	 *
	 * @param string $type  Input type
	 * @param string $name  Input name
	 * @param string $value Input value
	 * @param string $intag Other stuff into the tag
	 */
	public static function input( $type, $name, $value = '', $intag = '' ) {
		$s  =  self::property( 'type',  $type  );
		$s .=  self::property( 'name',  $name  );
		$s .=  self::property( 'value', $value );
		$s .=  self::spaced( $intag );
		return self::tagc( 'input', $s );
	}

	/**
	 * Return a <label>
	 *
	 * @param string $text
	 * @param string $for
	 * @return string
	 */
	public static function label( $text, $for = null, $intag = '' ) {
		$s = self::property( 'for', $for );
		return self::tag( 'label', $text, $s );
	}
}
