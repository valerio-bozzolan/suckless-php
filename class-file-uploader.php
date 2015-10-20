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

define('UPLOAD_EXTRA_ERR_INVALID_REQUEST', 101);
define('UPLOAD_EXTRA_ERR_GENERIC_ERROR', 102);
define('UPLOAD_EXTRA_ERR_CANT_READ_MIMETYPE', 103);
define('UPLOAD_EXTRA_ERR_UNALLOWED_MIMETYPE', 104);
define('UPLOAD_EXTRA_ERR_UNALLOWED_FILE', 105);
define('UPLOAD_EXTRA_ERR_OVERSIZE', 106);
define('UPLOAD_EXTRA_ERR_FILENAME_TOO_SHORT', 107);
define('UPLOAD_EXTRA_ERR_FILENAME_TOO_LONG', 108);
define('UPLOAD_EXTRA_ERR_CANT_SAVE_FILE', 109);

/**
 * Manage upload exceptions.
 *
 */
class FileUploader {
	private $fileEntry;

	private $args;

	private $mimeTypes;

	/**
	 * Create a FileUploader object.
	 *
	 * @param string $fileEntry See it as $_POST[ $fileEntry ].
	 * @param array $args Arguments.
	 * @see FileUploader::setArgs()
	 */
	function __construct($fileEntry, $args = array(), $mimeTypes = null) {
		$this->fileEntry = $fileEntry;
		$this->setArgs($args);
		if($mimeTypes === null) {
			$this->mimeTypes = & $GLOBALS['mimeTypes'];
		} else {
			$this->mimeTypes = $mimeTypes;
		}
	}

	/**
	 * Set options.
	 *
	 * @param array $args Arguments.
	 *	'slug' => bool
	 *		If the filename have to be slugged.
	 *		Default: true
	 *	'override-filename' => string
	 *		The filename is preserved if you don't specify it here.
	 *		E.g.: 'my-pic'
	 *		Default: The original filename.
	 *	'pre-filename' => string
	 *		Filename prefix.
	 *		E.g.: date('Y-m-d-')
	 *		Default: ''
	 *	'post-filename' => string
	 *		Filename suffix.
	 *		E.g.: '_XL'
	 *		Default: ''
	 *	'category' => string|null
	 *		Allowed MIME category/categories.
	 *		Default: null (all the categories)
	 *	'min-length-filename' => int
	 *		Min length of the filename (no extension, no pathname).
	 *		E.g.: 5
	 *		Default: 2
	 *	'max-length-filename' => int
	 *		Max length of the filename (no extension, no pathname).
	 *		E.g.: 40
	 *		Default: 200
	 *	'dont-overwrite' => boolean
	 *		If the filename already exists can append a progressive number.
	 *		E.g.: false (overwrite the file if already exists)
	 *		Default: true
	 */
	public function setArgs($args) {
		$this->args = merge_args_defaults(
			$args,
			array(
				'slug' => true,
				'override-filename' => null,
				'pre-filename' => '',
				'post-filename' => '',
				'autoincrement' => '-%d',
				'category' => null,
				'max-filesize' => null,
				'min-length-filename' => 2,
				'max-length-filename' => 200,
				'dont-overwrite' => true
			)
		);
	}

	/**
	 * Have this request any sense?
	 *
	 * @return bool
	 */
	public function uploadRequestOK() {
		return isset($_FILES[ $this->fileEntry ]['error']) && ! is_array($_FILES[ $this->fileEntry ]['error']);
	}

	/**
	 * Check if the user "is uploading" a file.
	 *
	 * This obviusly see if this request have sense.
	 *
	 * @return bool If the user have sent a valid file or not.
	 */
	public function fileChoosed() {
		return $this->uploadRequestOK() && $_FILES[ $this->fileEntry ]['error'] !== UPLOAD_ERR_NO_FILE;
	}

	/**
	 * Upload the filename to a filepath.
	 *
	 * @param string $pathname The folder WITHOUT trailing slash
	 * @param int $status To get the exceptions
	 */
	public function uploadTo($pathname, & $status) {
		if( ! $this->uploadRequestOK() ) {
			$status = UPLOAD_EXTRA_ERR_INVALID_REQUEST;
			return false;
		}

		switch($_FILES[ $this->fileEntry ]['error']) {
			case UPLOAD_ERR_NO_FILE:
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
			case UPLOAD_ERR_PARTIAL:
			case UPLOAD_ERR_NO_TMP_DIR:
			case UPLOAD_ERR_CANT_WRITE:
			case UPLOAD_ERR_EXTENSION:
				// Return the same error
				$status = $_FILES[ $this->fileEntry ]['error'];
				return false;
			case UPLOAD_ERR_OK:
				// It's OK
				break;
			default:
				DEBUG && error( sprintf(
					_("FileUploader ha ottenuto un errore sconosciuto: %d."),
					$_FILES[ $this->fileEntry ]['error']
				) );
				$status = UPLOAD_EXTRA_ERR_GENERIC_ERROR;
				return false;
		}

		// Check filesize
		if( $this->args['max-filesize'] !== null && $_FILES[ $this->fileEntry ]['size'] > $this->args['max-filesize'] ) {
			$status = UPLOAD_EXTRA_ERR_OVERSIZE;
			return false;
		}

		// Check original MIME type
		$mime = get_mimetype( $_FILES[ $this->fileEntry ]['tmp_name'] );
		if( ! $mime ) {
			$status = UPLOAD_EXTRA_ERR_CANT_READ_MIMETYPE;
			return false;
		}

		// Check if MIME type it's allowed
		if( ! $GLOBALS['mimeTypes']->isMimetypeInCategory( $mime, $this->args['category'] ) ) {
			$status = UPLOAD_EXTRA_ERR_UNALLOWED_MIMETYPE;
			return false;
		}

		// Check original file extension
		$ext = $GLOBALS['mimeTypes']->getFileExtensionFromExpectations(
			$_FILES[ $this->fileEntry ]['name'],
			$this->args['category'],
			$mime
		);
		if( ! $ext ) {
			$status = UPLOAD_EXTRA_ERR_UNALLOWED_FILE;
			return false;
		}

		// Get filename
		if( $this->args['override-filename'] === null ) {
			$filename = substr($_FILES[ $this->fileEntry ]['name'], 0, - strlen( $ext ));
		} else {
			$filename = $this->args['override-filename'];
		}

		// Append prefix (if any)
		$filename = $this->args['pre-filename'] . $filename;

		if( $this->args['slug'] ) {
			$filename = generate_slug( $filename );
		}

		// Create destination
		create_path( $pathname );

		$getFilename = function(&$filename, &$args, &$ext, $i = null) {
			if( $i === null ) {
				$autoincrement = '';
			} else {
				$autoincrement = sprintf($this->args['autoincrement'], $i);
			}

			return $filename . $autoincrement . $args['post-filename'] . ".$ext";
		};

		// Can be appended a progressive number
		if( $this->args['dont-overwrite'] && file_exists( $pathname . _ . $getFilename($filename, $this->args, $ext) ) ) {
			$i = 1;
			while( file_exists( $pathname . _ . $getFilename($filename, $this->args, $ext, $i) ) ) {
				$i++;
			}
			$filename = $getFilename($filename, $this->args, $ext, $i);
		} else {
			// Append suffix (if any)
			$filename = $getFilename($filename, $this->args, $ext, $i);
		}

		// Filename length
		if( strlen($filename) < $this->args['min-length-filename'] ) {
			$status = UPLOAD_EXTRA_ERR_FILENAME_TOO_SHORT;
			return false;
		}

		// Filename length
		if( strlen($filename) > $this->args['max-length-filename'] ) {
			$status = UPLOAD_EXTRA_ERR_FILENAME_TOO_LONG;
			return false;
		}

		$moved = move_uploaded_file(
			$_FILES[ $this->fileEntry ]['tmp_name'],
			$pathname . _ . $filename
		);

		if(! $moved) {
			$status = UPLOAD_EXTRA_ERR_CANT_SAVE_FILE;
			return false;
		}

		$status = UPLOAD_ERR_OK;
		return "$filename";
	}

	/**
	 * Prefilled error messages.
	 *
	 * @param int $status The $status var from FileUploader::uploadTo()
	 * @return string The proper error message.
	 */
	public function getErrorMessage($status) {
		switch($status) {
			case UPLOAD_ERR_OK:
				// You should avoid this. Is not an error!
				return _("Upload completato con successo.");
			case UPLOAD_ERR_NO_FILE:
				return _("Non è stato selezionato alcun file.");
			case UPLOAD_ERR_INI_SIZE:
				return _("Il file eccede i limiti di sistema.");
			case UPLOAD_ERR_FORM_SIZE:
				DEBUG && error( _("Non affidarti a UPLOAD_ERR_FORM_SIZE!") );
				return _("Il file eccede i limiti imposti.");
			case UPLOAD_EXTRA_ERR_OVERSIZE:
				return sprintf(
					_("Il file pesa %s. Non può superare %s."),
					get_human_filesize($_FILES[ $this->fileEntry ]['size']),
					get_human_filesize( $this->args['max-filesize'] )
				);
			case UPLOAD_EXTRA_ERR_CANT_SAVE_FILE:
				return _("Impossibile salvare il file.");
			case UPLOAD_EXTRA_ERR_CANT_READ_MIMETYPE:
				return _("Il MIME del file non è validabile.");
			case UPLOAD_EXTRA_ERR_UNALLOWED_MIMETYPE:
				$mime = get_mimetype( $_FILES[ $this->fileEntry ]['tmp_name'] );

				return sprintf(
					_("Il file é di un <em>MIME type</em> non concesso: <em>%s</em>."),
					esc_html( $mime )
				);
			case UPLOAD_EXTRA_ERR_UNALLOWED_FILE:
				$mime = get_mimetype( $_FILES[ $this->fileEntry ]['tmp_name'] );

				$allowed_filetypes = $this->mimeTypes->getFiletypes(
					$this->args['category'],
					$mime
				);

				return multi_text(
					count( $allowed_filetypes ),
					sprintf(
						_("Il file ha un'estensione non valida. Estensioni attese: <em>%s</em>."),
						esc_html( implode(', ', $allowed_filetypes ) )
					),
					sprintf(
						_("Il file ha un'estensione non valida. Estensione attesa: <em>%s</em>."),
						esc_html( $allowed_filetypes[0] )
					)
				);
			case UPLOAD_EXTRA_ERR_FILENAME_TOO_SHORT:
				return _("Il file ha un nome troppo breve.");
			case UPLOAD_EXTRA_ERR_FILENAME_TOO_LONG:
				return _("Il file ha un nome troppo lungo.");
			case UPLOAD_EXTRA_ERR_GENERIC_ERROR:
				return _("Errore di caricamento.");
			default:
				DEBUG && error( sprintf(
					_("Stato di errore non previsto: '%d'"),
					$status
				) );
				return _("Errore durante l'upload.");
		}
	}
}
