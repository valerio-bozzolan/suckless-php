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
 * Handle MIME and file types (extensions).
 *
 * @use MAGIC_MIME_FILE, functions.php
 */
class MimeTypes {

	/**
	 * array(
	 * 	'category-1' => array(
	 * 		'mime' => array('filetype1', 'filetype2'),
	 *		...
	 * 	),
	 * 	...
	 * )
	 *
	 * @var array
	 */
	private $mimeTypes;

	/**
	 *
	 * @param string $category Category e.g. 'audio'
	 * @param mixed $mimeTypes Array of MIME => file types.
	 * 	E.g.:
	 * 	array(
	 *		'image/png' => 'png',
	 *		'image/jpeg' => array('jpg', 'jpeg'),
	 *		...
	 *	)
	 */
	public function registerMimetypes($category, $mimeTypes) {
		// Register category if not exists
		if( ! isset( $this->mimeTypes[ $category ] ) ) {
			$this->mimeTypes[ $category ] = array();
		}
		foreach($mimeTypes as $mime => $filetypes) {
			if( ! is_array( $filetypes ) ) {
				$filetypes = array($filetypes);
			}

			if( ! isset( $this->mimeTypes[ $category ][ $mime ] ) ) {
				$this->mimeTypes[ $category ][ $mime ] = $filetypes;
				continue; // No need next lines.
			}

			$this->mimeTypes[ $category ][ $mime ] = array_merge(
				$this->mimeTypes[ $category ][ $mime ],
				$filetypes
			);
		}
	}

	/**
	 * Get an array of MIME of the selected category. If category is NULL, all the MIME are returned.
	 *
	 * @param array|string|null $category Category e.g. 'audio'
	 * @return array Array of MIME.
	 */
	public function getMimetypes($categories = null) {
		$allMimeTypes = array();

		if( $categories === null ) {
			// All MIME types
			foreach($this->mimeTypes as $mimeTypes) {
				foreach($mimeTypes as $mime => $filetypes) {
					$allMimeTypes[] = $mime;
				}
			}
		} else {
			if( ! is_array($categories) ) {
				$categories = array($categories);
			}

			// MIME types from selected categories
			foreach($categories as $category) {
				if( ! isset( $this->mimeTypes[ $category ]  ) ) {
					DEBUG && self::printErrorUnknownCategory( $category );
					continue;
				}

				foreach($this->mimeTypes[ $category ] as $mime => $filetypes) {
					$allMimeTypes[] = $mime;
				}
			}

			if( count($categories) > 0 ) {
				$allMimeTypes = array_unique( $allMimeTypes );
			}
		}

		return $allMimeTypes;
	}

	/**
	 * Check if a MIME belongs to a certain category.
	 *
	 * @param string $mime_value E.g.: 'audio/vorbis'.
	 * @param mixed $category E.g.: 'document'
	 * @return bool
	 */
	public function isMimetypeInCategory($mimetype, $category = null ) {
		return in_array(
			$mimetype,
			$this->getMimetypes($category),
			true // strict
		);
	}

	/**
	 * Get the file types of a MIME.
	 *
	 * @param string|null $category
	 * 	If NULL it search in all the categories.
	 * @param string|null $mimetype The MIME type.
	 * @return mixed FALSE if the MIME is not registered or an array of file types.
	 */
	public function getFiletypes($categories = null, $mimetype = null) {
		$all_types = array();

		if( $categories === null ) {
			// Search in all the categories.
			foreach($this->mimeTypes as $mimeTypes) {
				foreach($mimeTypes as $mime => $types) {
					if($mimetype === null || $mime === $mimetype) {
						$all_types = array_merge($all_types, $types);
					}
				}
			}
		} else {
			if( ! is_array($categories) ) {
				$categories = array($categories);
			}

			// Search *that* category if exists
			foreach($categories as $category) {
				if( ! isset( $this->mimeTypes[ $category ] ) ) {
					DEBUG && self::printErrorUnknownCategory($category);
					continue;
				}

				foreach($this->mimeTypes[ $category ] as $mime => $types) {
					if($mimetype === null || $mime === $mimetype) {
						$all_types = array_merge($all_types, $types);
					}
				}
			}
		}

		return array_unique( $all_types );
	}

	/**
	 * Check (and get) the known file extension.
	 *
	 * @param string $filename The file name.
	 * @param array|string|null $categories MIME categories.
	 * @param string|null $mimetype The MIME type.
	 * @return string|false FALSE or the file extension e.g.: 'png'
	 */
	public function getFileExtensionFromExpectations($filename, $categories = null, $mimetype = null) {
		$expected_filetypes = $this->getFiletypes($category, $mimetype);
		foreach($expected_filetypes as $filetype) {
			$dotted = ".$filetype";
			$strlen = strlen($dotted);
			if( substr( $filename, -$strlen, $strlen) === $dotted ) {
				return $filetype;
			}
		}
		return false;
	}

	private static function printErrorUnknownCategory($category) {
		error( sprintf(
			_("Categoria di MIME <em>%s</em> non registrata."),
			esc_html( $category )
		) );
	}
}
