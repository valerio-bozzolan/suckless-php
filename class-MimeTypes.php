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
 * Handle MIME and file types (extensions).
 *
 * @use MAGIC_MIME_FILE, functions.php
 */
class MimeTypes {

	/**
	 * [
	 * 	'category-1' => [
	 * 		'mime' => ['filetype1', 'filetype2'],
	 *		...
	 * 	],
	 * 	...
	 * ]
	 *
	 * @var array
	 */
	private $mimeTypes;

	function __construct($defaults = true) {
		$defaults && $this->loadDefaults();
	}

	/**
	 *
	 * @param string $category Category e.g. 'audio'
	 * @param mixed $mimeTypes Array of MIME => file types.
	 * 	E.g.:
	 * 	[
	 *		'image/png' => 'png',
	 *		'image/jpeg' => array['jpg', 'jpeg'],
	 *		...
	 *	]
	 */
	public function registerMimetypes($category, $mimeTypes) {
		// Register category if not exists
		if( ! isset( $this->mimeTypes[ $category ] ) ) {
			$this->mimeTypes[ $category ] = [];
		}
		foreach($mimeTypes as $mime => $filetypes) {
			force_array( $filetypes );

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
	 * Load default MIME types
	 */
	private function loadDefaults() {
		$this->registerMimetypes('image', [
			'image/jpeg' => ['jpg', 'jpeg'],
			'image/png' => 'png',
			'image/gif' => 'gif'
		] );
		$this->registerMimetypes('audio', [
			'audio/x-flac' => 'flac',
			'audio/ogg' => 'ogg',
			'audio/vorbis' => 'ogg',
			'audio/vorbis-config' => 'ogg',
			'audio/mpeg' => 'mp3',
			'audio/MPA' => 'mp4',
			'audio/mpa-robust' => 'mp4'
		] );
		$this->registerMimetypes('video', [
			'video/mp4' => 'mp4',
			'application/ogg' => 'ogg'
		] );
		$this->registerMimetypes('document', [
			'application/pdf' => 'pdf',
			'application/x-pdf' => 'pdf',
			'application/x-bzpdf' => 'pdf',
			'application/x-gzpdf' => 'pdf',
			'application/vnd.oasis.opendocument.text' => 'odt',
			'application/vnd.oasis.opendocument.presentation' => 'odp',
			'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
			'application/vnd.oasis.opendocument.graphics' => 'odg',
			'application/msword' => 'doc',
			'application/vnd.ms-excel' => 'xls',
			'application/vnd.ms-powerpoint' => 'ppt',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx', 'xlsx', 'pptx']
		] );
	}

	/**
	 * Get an array of MIME of the selected category. If category is NULL, all the MIME are returned.
	 *
	 * @param array|string|null $category Category e.g. 'audio'
	 * @return array Array of MIME.
	 */
	public function getMimetypes($categories = null) {
		$allMimeTypes = [];

		if( $categories === null ) {
			// All MIME types
			foreach($this->mimeTypes as $mimeTypes) {
				foreach($mimeTypes as $mime => $filetypes) {
					$allMimeTypes[] = $mime;
				}
			}
		} else {
			force_array( $categories );

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
		return in_array($mimetype, $this->getMimetypes($category), true);
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
		$all_types = [];

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
			force_array( $categories );

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

		// Have to insert an arg to enable this feature?
		// ASD.JPEG => asd.jpeg
		// @TODO
		$filename = strtolower($filename);

		$expected_filetypes = $this->getFiletypes($categories, $mimetype);
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
