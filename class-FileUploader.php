<?php
# Copyright (C) 2015, 2018, 2019 Valerio Bozzolan
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

// error defaults
define('UPLOAD_EXTRA_ERR_INVALID_REQUEST',    101);
define('UPLOAD_EXTRA_ERR_GENERIC_ERROR',      102);
define('UPLOAD_EXTRA_ERR_CANT_READ_MIMETYPE', 103);
define('UPLOAD_EXTRA_ERR_UNALLOWED_MIMETYPE', 104);
define('UPLOAD_EXTRA_ERR_UNALLOWED_FILE',     105);
define('UPLOAD_EXTRA_ERR_OVERSIZE',           106);
define('UPLOAD_EXTRA_ERR_FILENAME_TOO_SHORT', 107);
define('UPLOAD_EXTRA_ERR_FILENAME_TOO_LONG',  108);
define('UPLOAD_EXTRA_ERR_CANT_SAVE_FILE',     109);

// default permissions for new directories
define_default( 'CHMOD_WRITABLE_DIRECTORY', 0755 );

/**
 * Manage uploads with security in mind
 */
class FileUploader {

	/**
	 * File entry
	 *
	 * E.g. 'image' (the name of the input type upload)
	 *
	 * @var string
	 */
	private $fileEntry;

	/**
	 * Upload arguments to change the upload behaviour
	 *
	 * @var array
	 */
	private $args;

	/**
	 * Available mimetypes
	 *
	 * @var MimeTypes
	 */
	private $mimeTypes;

	/**
	 * Last upload error status
	 *
	 * @var null|int
	 */
	private $lastStatus;

	/**
	 * Create a FileUploader object.
	 *
	 * @param string $fileEntry See it as $_POST[ $fileEntry ].
	 * @param array $args Arguments.
	 * @see FileUploader::setArgs()
	 */
	function __construct( $fileEntry, $args = [], $mimeTypes = null ) {
		$this->fileEntry = $fileEntry;
		$this->setArgs( $args );
		$this->mimeTypes = $mimeTypes ? $mimeTypes : MimeTypes::instance();
	}

	/**
	 * Set upload options
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

		// remember the last upload status
		$this->lastStatus = &$status;

		if( ! $this->uploadRequestOK() ) {
			$status = UPLOAD_EXTRA_ERR_INVALID_REQUEST;
			return false;
		}

		$status = $this->getFileInfo( 'error' );
		switch( $status ) {
			case UPLOAD_ERR_NO_FILE:
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
			case UPLOAD_ERR_PARTIAL:
			case UPLOAD_ERR_NO_TMP_DIR:
			case UPLOAD_ERR_CANT_WRITE:
			case UPLOAD_ERR_EXTENSION:
				// Return the same error
				return false;
			case UPLOAD_ERR_OK:
				// It's OK
				break;
			default:
				error( "unexpected FileUploader error status: $status" );
				$status = UPLOAD_EXTRA_ERR_GENERIC_ERROR;
				return false;
		}

		// Check filesize (too much or zero)
		if( $this->args['max-filesize'] !== null && $this->getFileInfo( 'size' ) > $this->args['max-filesize'] || ! $this->getFileInfo( 'size' ) ) {
			$status = UPLOAD_EXTRA_ERR_OVERSIZE;
			return false;
		}

		// Check original MIME type
		$mime = MimeTypes::fileMimetype( $this->getFileInfo( 'tmp_name' ) );
		if( ! $mime ) {
			$status = UPLOAD_EXTRA_ERR_CANT_READ_MIMETYPE;
			return false;
		}

		// Check if MIME type it's allowed
		if( ! $this->mimeTypes->isMimetypeInCategory( $mime, $this->args['category'] ) ) {
			$status = UPLOAD_EXTRA_ERR_UNALLOWED_MIMETYPE;
			return false;
		}

		// Check original file extension
		$ext = $this->mimeTypes->getFileExtensionFromExpectations(
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
		if( $this->args[ 'dont-overwrite' ] ) {
			$filename = self::searchFreeFilename( $pathname . __ , $filename, $ext, $this->args );
		}

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
			$pathname . __ . $complete_filename
		);

		if( ! $moved ) {
			error( sprintf(
				"unable to move file %s to position %s",
				$this->getFileInfo( 'tmp_name' ),
				$pathname . __ . $complete_filename
			) );
			$status = UPLOAD_EXTRA_ERR_CANT_SAVE_FILE;
			return false;
		}

		$status = UPLOAD_ERR_OK;
		return $complete_filename;
	}

	/**
	 * Check if the last FileUploader#uploadTo() was done successfully
	 *
	 * @return boolean
	 */
	public function isLastUploadSuccess() {
		return $this->lastStatus === UPLOAD_ERR_OK;
	}

	/**
	 * Get the upload error message
	 *
	 * This method should be called after FileUploader#uploadTo()
	 * and after checking FileUploader#isLastUploadSuccess().
	 *
	 * @param  int    $status The $status variable from FileUploader::uploadTo() (or none for the last one)
	 * @return string         The error message
	 */
	public function getErrorMessage( $status = null ) {

		// if the status is not specified, use the last one
		if( $status === null ) {
			$status = $this->lastStatus;
		}

		switch( $status ) {
			case UPLOAD_ERR_OK:
				// You should avoid this. Is not an error!
				return __("Upload completato con successo.");

			case UPLOAD_ERR_NO_FILE:
				return __("Non è stato selezionato alcun file.");

			case UPLOAD_ERR_PARTIAL:
				return __( "Per favore ricarica il file. È stato caricato solo parzialmente." );

			case UPLOAD_ERR_INI_SIZE:
				return sprintf(
					__( "Superati i limiti di upload (massimo %s)." ),
					human_filesize( self::uploadMaxFilesize() )
				);

			case UPLOAD_ERR_FORM_SIZE:
				return __("Il file eccede i limiti imposti.");

			case UPLOAD_EXTRA_ERR_OVERSIZE:
				$size = $this->getFileInfo( 'size' );
				return sprintf(
					__( "Il file pesa %s. Non può superare %s."),
					$size ? human_filesize( $this->getFileInfo( 'size' ) ) : __( "troppo" ),
					human_filesize( $this->args['max-filesize'] )
				);

			case UPLOAD_EXTRA_ERR_CANT_SAVE_FILE:
				return __("Impossibile salvare il file.");

			case UPLOAD_EXTRA_ERR_CANT_READ_MIMETYPE:
				return __("Il MIME del file non è validabile.");

			case UPLOAD_EXTRA_ERR_UNALLOWED_MIMETYPE:
				$mime = MimeTypes::fileMimetype( $this->getFileInfo( 'tmp_name' ) );
				return sprintf(
					__( "Il file é di un MIME type non concesso: \"%s\"." ),
					esc_html( $mime )
				);

			case UPLOAD_EXTRA_ERR_UNALLOWED_FILE:
				$mime = MimeTypes::fileMimetype( $this->getFileInfo( 'tmp_name' ) );
				$allowed_filetypes = $this->mimeTypes->getFiletypes(
					$this->args['category'],
					$mime
				);
				return sprintf(
					__( "Il file ha un'estensione non valida. Estensioni accettate: %s." ),
					implode( ', ', $allowed_filetypes )
				);
			case UPLOAD_EXTRA_ERR_FILENAME_TOO_SHORT:
				return __("Il file ha un nome troppo breve.");

			case UPLOAD_EXTRA_ERR_FILENAME_TOO_LONG:
				return __("Il file ha un nome troppo lungo.");

			case UPLOAD_EXTRA_ERR_GENERIC_ERROR:
				return __("Errore di caricamento sconosciuto.");

			case UPLOAD_ERR_NO_TMP_DIR:
				error( "FileUploader error: UPLOAD_ERR_NO_TMP_DIR (6)" );
				return __( "Manca una cartella temporanea. Contattare l'amministratore." );
		}

		error( "unexpected FileUploader error status: $status" );
		return __("Errore di caricamento alquanto sconosciuto.");
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
		if( empty( $args['autoincrement'] ) ) {
			$args['autoincrement'] = '-%d';
		}
		if( empty( $args['post-filename']  ) ) {
			$args['post-filename'] = '';
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
			error( sprintf(
				'error opening fileinfo database placed in %s',
				MAGIC_MIME_FILE
			) );
			return false;
		}
		$mime = finfo_file( $finfo, $filepath );
		if( ! $mime ) {
			error( "can't detect MIME of file $filepath" );
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

	/**
	 * Convert a php.ini name with a file weight value to bytes
	 *
	 * @param $name string php_ini variable name
	 * @return int bytes
	 */
	public static function iniToBytes( $name ) {
		$v = trim( ini_get( $name ) );
		$last = strtolower( $v[ strlen( $v ) - 1 ] );
		switch( $last ) {
			case 'g': $v *= 1024;
			case 'm': $v *= 1024;
			case 'k': $v *= 1024;
		}
		return (int) $v;
	}

	/**
	 * Get the max_post_size in bytes
	 *
	 * @return int
	 */
	public static function maxPOSTSize() {
	    return self::iniToBytes( 'post_max_size' );
    }

	/**
	 * Get the upload_max_filesize in bytes
	 *
	 * @return int
	 */
	public static function uploadMaxFilesize() {
		return self::iniToBytes( 'upload_max_filesize' );
	}

	/**
	 * Check if the request is over the 'max_post_size' PHP.ini limit
	 *
	 * This is useful to be checked because, in this case,
	 * is like *no* file was uploaded.
	 *
	 * @return bool
	 */
	public static function isOverMaxPOSTSize() {
		return isset( $_SERVER[ 'CONTENT_LENGTH' ] )
		           && $_SERVER[ 'CONTENT_LENGTH' ] > self::maxPOSTSize();
	}

	/**
	 * Check if the request is over the 'upload_max_filesize' PHP.init limit
	 *
	 * @return bool
	 */
	public static function isOverMaxUploadFilesize() {
		return empty( $_FILES )
			&& isset( $_SERVER[ 'CONTENT_LENGTH' ] )
			&&        $_SERVER[ 'CONTENT_LENGTH' ] > self::uploadMaxFilesize();
	}

	/**
	 * Message related to the POST Content-Length exceding its limit
	 *
	 * @return string
	 */
	public static function overMaxPOSTSizeMessage() {
		return sprintf(
			__( "La richiesta non è valida (inviati più di %s)." ),
			human_filesize( self::maxPOSTSize() )
		);
	}

	/*
	 * Create a pathname in the filesystem
	 *
	 * @param $path string
	 * @param $chmod string
	 */
	public static function createPath( $path, $chmod = null ) {
		if( null === $chmod ) {
			$chmod = CHMOD_WRITABLE_DIRECTORY;
		}
		if( file_exists( $path ) || mkdir( $path, $chmod, true ) ) {
			return true;
		}
		error( "unable to create path $path" );
	}
}
