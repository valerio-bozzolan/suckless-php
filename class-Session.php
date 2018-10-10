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

defined('SESSION_DURATION')  || define('SESSION_DURATION', 604800);   // Just something 60s * 60m * 24h * 7d
defined('SESSIONUSER_CLASS') || define('SESSIONUSER_CLASS', 'Sessionuser');

/**
 * Session handler class
 */
class Session {

	/**
	 * Is the login verified?
	 *
	 * @var bool
	 */
	private $loginVerified = false;

	/**
	 * User currently logged
	 *
	 * @var Sessionuser
	 */
	private $user = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->validate();
	}

	/**
	 * Is the user logged?
	 *
	 * @return bool
	 */
	public function isLogged() {
		return null !== $this->getUser();
	}

	/**
	 * Get the currently logged-in user
	 *
	 * @return Sessionuser
	 */
	public function getUser() {
		if( ! $this->loginVerified ) {
			$this->validate();
		}
		return $this->user;
	}

	/*
	 * Login statuses
	 */
	const OK                  = 0;
	const LOGIN_FAILED        = 1;
	const ALREADY_LOGGED      = 2;
	const EMPTY_USER_UID      = 4;
	const EMPTY_USER_PASSWORD = 8;
	const USER_DISABLED       = 64;

	/**
	 * Do a login
	 *
	 * @param $status int Login status
	 * @param $user_uid User UID
	 * @param $user_password User password
	 * @return bool
	 */
	public function login( & $status = null, $user_uid = null, $user_password = null ) {
		if( $this->isLogged() ) {
			$status = self::ALREADY_LOGGED;
			return true;
		}

		if( null === $user_uid && isset( $_POST['user_uid'] )  ) {
			$user_uid = $_POST['user_uid'];
		}
		if( null === $user_password && isset( $_POST['user_password'] ) ) {
			$user_password = $_POST['user_password'];
		}

		if( empty( $user_uid ) ) {
			$status = self::EMPTY_USER_UID;
			return false;
		}
		if( empty( $user_password ) ) {
			$status = self::EMPTY_USER_PASSWORD;
			return false;
		}

		// PHP bug
		$userClass = SESSIONUSER_CLASS;
		$user = $userClass::factoryFromLogin( $user_uid, $user_password )
			->queryRow();

		if( ! $user ) {
			$status = self::LOGIN_FAILED;
			error_log( sprintf( "Login failed by '%s' using POST", $userClass::sanitizeUID( $user_uid ) ) );
			return false;
		}

		if( ! $user->isSessionuserActive() ) {
			$status = self::USER_DISABLED;
			return false;
		}

		$this->loginVerified = true;

		$this->user = $user;

		$time     = time();
		$duration = $time + SESSION_DURATION;

		$force_https = is_https();
		setcookie( 'user_uid',   $user->getSessionuserUID(),              $duration, ROOT, '', $force_https, false );
		setcookie( 'login_time', $time,                                   $duration, ROOT, '', $force_https, false );
		setcookie( 'token',      $user->generateSessionuserCookieToken(), $duration, ROOT, '', $force_https, true  );

		$status = self::OK;
		return true;
	}

	/**
	 * Validate the user session
	 *
	 * @return bool
	 */
	private function validate() {
		if( $this->loginVerified ) {
			return true;
		}

		if( ! isset( $_COOKIE['user_uid'], $_COOKIE['token'] ) ) {
			return false;
		}

		// PHP Bug
		$userClass = SESSIONUSER_CLASS;

		$user = $userClass::factoryFromUID( $_COOKIE['user_uid'] )
			->queryRow();

		$this->loginVerified = true;

		if( ! $user ) {
			$this->user = null;
			return false;
		}

		if( ! $user->isSessionuserActive() ) {
			return false;
		}

		if( $_COOKIE['token'] !== $user->generateSessionuserCookieToken() ) {
			error_log( sprintf( "Login failed by '%s' using cookies", $userClass::sanitizeUID( $_COOKIE['user_uid'] ) ) );
			return false;
		}

		// @TODO check also $_COOKIE['login_time']

		$this->user = $user;

		return true;
	}

	/**
	 * Destroy the session
	 */
	public function destroy() {
		$invalidate = time() - 8000;

		setcookie( 'user_uid',   'lol', $invalidate );
		setcookie( 'token',      'lol', $invalidate );
		setcookie( 'login_time', 'lol', $invalidate );

		$this->loginVerified = true;
		$this->user = null;
	}
}
