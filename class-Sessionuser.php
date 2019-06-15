<?php
# Copyright (C) 2015, 2016, 2018, 2019 Valerio Bozzolan
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

define_default('PASSWD_HASH_ALGO', 'sha1');   // Just something
define_default('PASSWD_HASH_SALT', 'drGth');  // Just something
define_default('PASSWD_HASH_PEPP', 'pw72kP'); // Just something
define_default('COOKIE_HASH_ALGO', 'sha256'); // Just something
define_default('COOKIE_HASH_SALT', 'daiads'); // Just something
define_default('COOKIE_HASH_PEPP', '30s3-f'); // Just something

/**
 * Sessionuser class
 */
class Sessionuser extends Queried {

	/**
	 * Database table name
	 */
	const T = 'user';

	/**
	 * Database ID column name
	 */
	const ID = 'user_ID';

	/**
	 * Database UID column name
	 */
	const UID = 'user_uid';

	/**
	 * Database UID length in characters
	 */
	const UID_MAXLEN = 128;

	/**
	 * Database activation status column name
	 */
	const IS_ACTIVE = 'user_active';

	/**
	 * Database password column name
	 */
	const PASSWORD = 'user_password';

	/**
	 * Database role column name
	 */
	const ROLE = 'user_role';

	/**
	 * Construct
	 */
	public function __construct() {
		$this->normalizeSessionuser();
	}

	/**
	 * Object normalization
	 */
	protected function normalizeSessionuser() {
		$this->integers( self::ID )
		     ->booleans( self::IS_ACTIVE );
	}

	/**
	 * Factory from login
	 *
	 * @param $uid      string
	 * @param $password string
	 * @return Query
	 */
	public static function factoryFromLogin( $uid, $password ) {
		return self::factoryFromUID( $uid )
			->whereStr( self::PASSWORD, static::encryptPassword( $password ) );
	}

	/**
	 * Get Sessionuser ID
	 *
	 * @return int
	 */
	public function getSessionuserID() {
		return $this->get( self::ID );
	}

	/**
	 * Get Sessionuser UID
	 *
	 * @return string
	 */
	public function getSessionuserUID() {
		return $this->get( self::UID );
	}

	/**
	 * Get the role
	 *
	 * @return string
	 */
	public function getSessionuserRole() {
		return $this->get( self::ROLE );
	}

	/**
	 * Is active?
	 *
	 * @return bool
	 */
	public function isSessionuserActive() {
		return $this->get( self::IS_ACTIVE );
	}

	/**
	 * Generate the cookie token
	 *
	 * @return string
	 */
	public function generateSessionuserCookieToken() {
		return hash( COOKIE_HASH_ALGO, COOKIE_HASH_SALT . $this->get( self::PASSWORD ) . COOKIE_HASH_PEPP );
	}

	/**
	 * Encrypt the password
	 *
	 * @param $password string
	 * @return string
	 */
	public static function encryptPassword( $password ) {
		$password = luser_input( $password, 500 );
		return hash( PASSWD_HASH_ALGO, PASSWD_HASH_SALT . $password . PASSWD_HASH_PEPP );
	}

}
