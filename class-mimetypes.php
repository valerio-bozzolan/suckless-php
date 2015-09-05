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
	 * Check if a MIME belongs to a certain category.
	 *
	 * @param string $mime_value E.g.: 'audio/vorbis'.
	 * @param string $category E.g.: 'document'
	 * @return bool
	 */
	public function isMimeInCategory($mime_value, $category) {
		if( ! isset( $this->mimeTypes[ $category ] ) ) {
			if(DEBUG) {
				error( sprintf(
					_("Categoria di MIME <em>%s</em> non registrata."),
					esc_html( $category )
				) );
			}
			return false;
		}
		foreach($this->mimeTypes[ $category ] as $mime => $filetypes) {
			if($mime === $mime_value) {
				return $filetypes;
			}
		}
		return false;
	}

	/**
	 * Check in all the MIME.
	 *
	 * @param string $mime_value The MINE e.g. 'audio/ogg'.
	 * @return mixed FALSE if is not registered. File types if exists.
	 */
	public function isMimeAllowed($mime_value) {
		foreach($this->mimeTypes as $mimeTypes) {
			foreach($mimeTypes as $mime => $filetypes) {
				if($mime_value === $mime) {
					return $filetypes;
				}
			}
		}
		return false;
	}

	/**
	 * Check in all the file types.
	 *
	 * @param string $filetype The file type e.g. 'png'
	 * @return bool If the file type is registered.
	 */
	public function isFiletypeAllowed($filetype) {
		foreach($this->mimeTypes as $mimeTypes) {
			foreach($mimeTypes as $mime => $filetypes) {
				if( in_array($filetype, $filetypes) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get the file types of a MIME.
	 *
	 * @param string $mime_value The MIME.
	 * @return mixed FALSE if the MIME is not registered or an array of file types.
	 */
	public function getFiletypes($mime_value) {
		foreach($this->mimeTypes as $mimeTypes) {
			foreach($mimeTypes as $mime => $types) {
				if($mime_value === $mime) {
					return $types;
				}
			}
		}
		return false;
	}

	/**
	 * Check (and get) the known file extension.
	 *
	 * @param string $filename The file name.
	 * @return mixed FALSE or the file extension e.g.: 'png'
	 */
	public function checkFileExtension($filename) {
		foreach($this->mimeTypes as $mimeTypes) {
			foreach($mimeTypes as $mime => $filetypes) {
				foreach($filetypes as $filetype) {
					$dotted = ".$filetype";
					$strlen = strlen($dotted);
					if( substr( $filename, -$strlen, $strlen) === $dotted ) {
						return $filetype;
					}
				}
			}
		}
		return false;
	}
}
