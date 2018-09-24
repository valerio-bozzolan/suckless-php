<?php
# Copyright (C) 2015, 2018 Valerio Bozzolan
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

define('UPLOAD_EXTRA_ERR_INVALID_REQUEST', 101);
define('UPLOAD_EXTRA_ERR_GENERIC_ERROR', 102);
define('UPLOAD_EXTRA_ERR_CANT_READ_MIMETYPE', 103);
define('UPLOAD_EXTRA_ERR_UNALLOWED_MIMETYPE', 104);
define('UPLOAD_EXTRA_ERR_UNALLOWED_FILE', 105);
define('UPLOAD_EXTRA_ERR_OVERSIZE', 106);
define('UPLOAD_EXTRA_ERR_FILENAME_TOO_SHORT', 107);
define('UPLOAD_EXTRA_ERR_FILENAME_TOO_LONG', 108);
define('UPLOAD_EXTRA_ERR_CANT_SAVE_FILE', 109);
define('UPLOAD_EXTRA_ERR_POST_MAX_SIZE', 110);

defined('MAGIC_MIME_FILE')
	or define('MAGIC_MIME_FILE', null); // Fifo system default

/**
 * Manage upload exceptions.
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
	function __construct( $fileEntry, $args = [], $mimeTypes = null ) {
		$this->fileEntry = $fileEntry;
		$this->setArgs($args);
		if( null === $mimeTypes ) {
			$this->mimeTypes = expect( 'mimeTypes' );
		} else {
			$this->mimeTypes = $mimeTypes;
		}
	}

	/**
	 * Set options.
	 *
	 * @param array $args Arguments.
	 *	'i' => int
	 *		If the user uploaded multiple filenames, this is the index.
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
	public function setArgs( $args ) {
		$this->args = array_replace( [
			'i' => null,
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
		], $args );
	}

	/**
	 * Have this request any sense?
	 *
	 * @return bool
	 */
	public function uploadRequestOK() {
		$err = $this->getFileInfo( 'error' );
		return null !== $err && ! is_array( $err );
	}

	/**
	 * Check if the user "is uploading" a file.
	 *
	 * This obviusly see if this request have sense.
	 *
	 * @return bool If the user have sent a valid file or not.
	 */
	public function fileChoosed() {
		return $this->uploadRequestOK() && $this->getFileInfo( 'error' ) !== UPLOAD_ERR_NO_FILE;
	}

	/**
	 * Get a file property
	 */
	public function getFileInfo( $property ) {
		$v = @ $_FILES[ $this->fileEntry ][ $property ];
		$i = $this->args[ 'i' ];
		if( isset( $i ) ) {
			$v = @ $v[ $i ];
		}
		return $v;
	}

	/**
	 * Upload the filename to a filepath.
	 *
	 * @param string $pathname The absolute folder WITHOUT trailing slash
	 * @param int $status Exception code
	 * @param string $filename File name without extension
	 * @param string $ext File extension with dot
	 * @param string $mime MIME type
	 */
	public function uploadTo( $pathname, & $status, & $filename = null, & $ext = null, & $mime = null ) {
		if( ! $this->uploadRequestOK() ) {
			$status = UPLOAD_EXTRA_ERR_INVALID_REQUEST;
			return false;
		}

		$max_size = (int) ini_get( 'post_max_size' ) * 1024 * 1024;
		if( isset ( $_SERVER[ 'CONTENT_LENGTH' ] ) && $_SERVER[ 'CONTENT_LENGTH' ] > $max_size ) {
			$status = UPLOAD_EXTRA_ERR_POST_MAX_SIZE;
			return false;
		}

		switch( $this->getFileInfo( 'error' ) ) {
			case UPLOAD_ERR_NO_FILE:
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
			case UPLOAD_ERR_PARTIAL:
			case UPLOAD_ERR_NO_TMP_DIR:
			case UPLOAD_ERR_CANT_WRITE:
			case UPLOAD_ERR_EXTENSION:
				// Return the same error
				$status = $this->getFileInfo( 'error ' );
				return false;
			case UPLOAD_ERR_OK:
				// It's OK
				break;
			default:
				DEBUG && error( sprintf(
					__("FileUploader ha ottenuto un errore sconosciuto: %d."),
					$this->getFileInfo( 'error' )
				) );
				$status = UPLOAD_EXTRA_ERR_GENERIC_ERROR;
				return false;
		}

		// Check filesize
		if( $this->args['max-filesize'] !== null && $this->getFileInfo( 'size' ) > $this->args['max-filesize'] ) {
			$status = UPLOAD_EXTRA_ERR_OVERSIZE;
			return false;
		}

		// Check original MIME type
		$mime = self::fileMimetype( $this->getFileInfo( 'tmp_name' ) );
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
			$this->getFileInfo( 'name' ),
			$this->args['category'],
			$mime
		);
		if( ! $ext ) {
			$status = UPLOAD_EXTRA_ERR_UNALLOWED_FILE;
			return false;
		}

		if( $this->args['override-filename'] === null ) {
			// Strip original complete file name from extension
			$filename = substr( $this->getFileInfo( 'name' ), 0, - ( strlen( $ext ) + 1 ) );
		} else {
			// Override file name
			$filename = $this->args['override-filename'];
		}

		// Append prefix (if any)
		$filename = $this->args['pre-filename'] . $filename;

		if( $this->args['slug'] ) {
			$filename = generate_slug( $filename );
		}

		// Make sure that the destination folder exists
		create_path( $pathname );

		// Append a suffix to the filename if it already exists
		$filename = self::searchFreeFilename( $pathname . _ , $filename, $ext, $this->args );

		// File name with extension
		$complete_filename = "$filename.$ext";

		// File name length
		if( strlen($complete_filename) < $this->args['min-length-filename'] ) {
			$status = UPLOAD_EXTRA_ERR_FILENAME_TOO_SHORT;
			return false;
		}

		// File name length
		if( strlen($complete_filename) > $this->args['max-length-filename'] ) {
			$status = UPLOAD_EXTRA_ERR_FILENAME_TOO_LONG;
			return false;
		}

		$moved = move_uploaded_file(
			$this->getFileInfo( 'tmp_name' ),
			$pathname . _ . $complete_filename
		);

		if( ! $moved ) {
			$status = UPLOAD_EXTRA_ERR_CANT_SAVE_FILE;
			return false;
		}

		$status = UPLOAD_ERR_OK;
		return $complete_filename;
	}

	/**
	 * Prefilled error messages.
	 *
	 * @param int $status The $status var from FileUploader::uploadTo()
	 * @return string The proper error message.
	 */
	public function getErrorMessage( $status ) {
		switch( $status ) {
			case UPLOAD_ERR_OK:
				// You should avoid this. Is not an error!
				return __("Upload completato con successo.");
			case UPLOAD_ERR_NO_FILE:
				return __("Non è stato selezionato alcun file.");
			case UPLOAD_ERR_INI_SIZE:
				return __("Il file eccede i limiti di sistema.");
			case UPLOAD_ERR_FORM_SIZE:
				DEBUG && error( __("Non affidarti a UPLOAD_ERR_FORM_SIZE!") );
				return __("Il file eccede i limiti imposti.");
			case UPLOAD_EXTRA_ERR_OVERSIZE:
				return sprintf(
					__("Il file pesa %s. Non può superare %s."),
					human_filesize( $this->getFileInfo( 'size' ) ),
					human_filesize( $this->args['max-filesize'] )
				);
			case UPLOAD_EXTRA_ERR_CANT_SAVE_FILE:
				return __("Impossibile salvare il file.");
			case UPLOAD_EXTRA_ERR_CANT_READ_MIMETYPE:
				return __("Il MIME del file non è validabile.");
			case UPLOAD_EXTRA_ERR_UNALLOWED_MIMETYPE:
				$mime = self::fileMimetype( $this->getFileInfo( 'tmp_name' ) );

				return sprintf(
					__("Il file é di un <em>MIME type</em> non concesso: <em>%s</em>."),
					esc_html( $mime )
				);
			case UPLOAD_EXTRA_ERR_UNALLOWED_FILE:
				$mime = self::fileMimetype( $this->getFileInfo( 'tmp_name' ) );

				$allowed_filetypes = $this->mimeTypes->getFiletypes(
					$this->args['category'],
					$mime
				);

				return multi_text(
					count( $allowed_filetypes ),
					sprintf(
						__("Il file ha un'estensione non valida. Estensioni attese: <em>%s</em>."),
						esc_html( implode(', ', $allowed_filetypes ) )
					),
					sprintf(
						__("Il file ha un'estensione non valida. Estensione attesa: <em>%s</em>."),
						esc_html( $allowed_filetypes[0] )
					)
				);
			case UPLOAD_EXTRA_ERR_FILENAME_TOO_SHORT:
				return __("Il file ha un nome troppo breve.");
			case UPLOAD_EXTRA_ERR_FILENAME_TOO_LONG:
				return __("Il file ha un nome troppo lungo.");
			case UPLOAD_EXTRA_ERR_GENERIC_ERROR:
				return __("Errore di caricamento.");
			case UPLOAD_EXTRA_ERR_POST_MAX_SIZE:
				return sprintf(
					__( "Superato il limite massimo di dati POST accettabili per ogni richiesta (%s)" ),
					human_filesize( (int) ini_get( 'post_max_size' ) * 1024 * 1024 )
				);
		}
		DEBUG && error( sprintf(
			__("Stato di errore non previsto: '%d'"),
			$status
		) );
		return __("Errore durante l'upload.");
	}

	/**
	 * When you want a not-taken file name WITHOUT extension.
	 *
	 * @param string $filepath Absolute directory with trailing slash
	 * @param string $filename 1° arg of FileUploader::buildFilename()
	 * @param string $ext 2° arg of FileUploader::buildFilename()
	 * @param string $args 3° args of FileUploader::buildFilename()
	 * @param null|string $build_filename NULL for 'FileUploader::buildFilename'
	 * @return string
	 */
	public static function searchFreeFilename( $filepath, $filename, $ext, $args, $build_filename = null ) {
		if( null === $build_filename ) {
			$build_filename = 'FileUploader::buildFilename';
		}

		if( ! is_callable( $build_filename ) ) {
			error_die( sprintf(
				__("Il 5° argomento di %s dovrebbe essere il nome di una funzione ma non esiste alcuna funzione '%s'."),
				__FUNCTION__,
				esc_html( $build_filename )
			) );
		}

		$i = null;
		while( file_exists( $filepath . call_user_func($build_filename, $filename, $ext, $args, $i) . ".$ext" ) ) {
			// http://php.net/manual/en/language.operators.increment.php
			// «Decrementing NULL values has no effect too, but incrementing them results in 1.»
			$i++;
			if($i === 30) {
				exit;
			}
		}
		return call_user_func( $build_filename, $filename, $ext, $args, $i );
	}

	/**
	 * Default mode to build a file name WITHOUT extension.
	 * It's called multiple times in FileUploader::searchFreeFilename().
	 *
	 * Create your own but NEVER get two equal strings if $i changes.
	 *
	 * @param string $filename File name without extension
	 * @param string $ext File name extension without dot
	 * @param array $args Custom stuff
	 * @param int $i Received from FileUploader::searchFreeFilename() as
	 *	auto increment if the precedent file name already exists.
	 *	To be used to-get (or not-to-get) a suffix.
	 *	It's NULL during the first call.
	 * @return string File name (with extension)
	 */
	public static function buildFilename( $filename, $ext, $args, $i = null ) {
		if( ! isset( $args['autoincrement'] ) ) {
			$args['autoincrement'] = '-%d';
			DEBUG && error( sprintf(
				__("Arg [autoincrement] atteso in %s. Assunto '%s'."),
				__FUNCTION__,
				'-%d'
			) );
		}
		if( ! isset( $args['post-filename']  ) ) {
			$args['post-filename'] = '';
			DEBUG && error( sprintf(
				__("Arg [post-filename] atteso in %s. Assunto vuoto."),
				__FUNCTION__
			) );
		}
		$suffix = ( $i === null ) ? '' : sprintf( $args['autoincrement'], $i );
		return $filename . $suffix . $args['post-filename'];
	}

	/**
	 * Guess the MIME type from a file.
	 * It requires the existence of the MAGIC_MIME_FILE.
	 *
	 * @param string $filepath The file path.
	 * @param bool $pure true for 'image/png; something'; or false for 'image/png'.
	 * @return string|false
	 */
	public static function fileMimetype( $filepath, $pure = false ) {
		$finfo = finfo_open(FILEINFO_MIME, MAGIC_MIME_FILE);
		if( ! $finfo ) {
			DEBUG and error( sprintf(
				__("Errore aprendo il database fileinfo situato in '%s'."),
				MAGIC_MIME_FILE
			) );
			return false;
		}
		$mime = finfo_file( $finfo, $filepath );
		if( ! $mime ) {
			DEBUG && error( sprintf(
				__("Impossibile ottenere il MIME del file '%s'."),
				esc_html( $filepath )
			) );
			return false;
		}
		if( ! $pure ) {
			$mime = explode(';', $mime, 2); // Split "; charset"
			$mime = $mime[0];
		}
		return $mime;
	}

	/**
	 * Generate multiple FileUploader constructors
	 *
	 * @generator
	 */
	public static function multiple( $name, $args = [] ) {
		$files = isset( $_FILES[ $name ] ) ? $_FILES[ $name ] : [];
		foreach( $files[ 'name' ] as $i => $_ ) {
			yield new self( $name, array_replace( $args, [
				'i' => $i
			] ) );
		}
	}
}
