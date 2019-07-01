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

// 60s * 60m * 24h * 7d
define_default( 'SESSION_DURATION',  604800 );

// the default user logged-in class
define_default( 'SESSIONUSER_CLASS', 'Sessionuser' );

/**
 * Session handler
 *
 * Note that actually we do NOT need server sessions <3
 */
class Session {

	/**
	 * Is the login verified?
	 *
	 * @var bool
	 */
	private $ok = false;

	/**
	 * User currently logged
	 *
	 * @var Sessionuser
	 */
	private $user = null;

	/**
	 * Get the singleton instance
	 *
	 * @return self
	 */
	public static function instance() {
		static $me = false;
		if( ! $me ) {
			$me = new self();
		}
		return $me;
	}

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
		if( ! $this->ok ) {
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
	 * @param $uid User UID
	 * @param $pwd User password
	 * @return bool
	 */
	public function login( & $status = null, $uid = null, $pwd = null ) {

		// already logged
		if( $this->isLogged() ) {
			$status = self::ALREADY_LOGGED;
			return true;
		}

		// UID from function or POST
		if( $uid === null ) {
			if( isset( $_POST['user_uid'] ) ) {
				$uid = $_POST['user_uid'];
			} else {
				return error( 'login without POST-ing user_uid' );
			}
		}

		// password from parameter or POST
		if( $pwd === null ) {
			if( isset( $_POST['user_password'] ) ) {
				$pwd = $_POST['user_password'];
			} else {
				return error( 'login without POST-ing user_password' );
			}
		}

		// no credentials no party
		if( empty( $uid ) ) {
			$status = self::EMPTY_USER_UID;
			return false;
		}
		if( empty( $pwd ) ) {
			$status = self::EMPTY_USER_PASSWORD;
			return false;
		}

		// PHP bug
		$userClass = SESSIONUSER_CLASS;
		$user = $userClass::factoryFromLogin( $uid, $pwd )
			->queryRow();

		if( ! $user ) {
			$status = self::LOGIN_FAILED;
			return self::failed( $userClass::sanitizeUID( $uid ), 'POST' );
		}

		if( ! $user->isSessionuserActive() ) {
			$status = self::USER_DISABLED;
			return false;
		}

		$this->ok = true;

		$this->user = $user;

		$time     = time();
		$duration = $time + SESSION_DURATION;

		$path = ROOT . _;

		$force_https = PROTOCOL === 'https://';
		setcookie( 'user_uid', $user->getSessionuserUID(),              $duration, $path, '', $force_https, false );
		setcookie( 'token',    $user->generateSessionuserCookieToken(), $duration, $path, '', $force_https, true  );
		setcookie( 'csrf',     $this->generateCSRF(),                   $duration, $path, '', $force_https, true  );

		$status = self::OK;
		return true;
	}

	/**
	 * Validate the user session
	 *
	 * @return bool
	 */
	private function validate() {
		if( $this->ok ) {
			return true;
		}

		if( ! isset( $_COOKIE['user_uid'], $_COOKIE['token'] ) ) {
			return false;
		}

		// PHP Bug
		$userClass = SESSIONUSER_CLASS;

		$user = $userClass::factoryFromUID( $_COOKIE['user_uid'] )
			->queryRow();

		$this->ok = true;

		if( ! $user ) {
			$this->user = null;
			return false;
		}

		if( ! $user->isSessionuserActive() ) {
			return false;
		}

		if( $_COOKIE['token'] !== $user->generateSessionuserCookieToken() ) {
			$this->destroy();
			return self::failed( $userClass::sanitizeUID( $_COOKIE['user_uid'] ), 'cookies' );
		}

		$this->user = $user;

		return true;
	}

	/**
	 * Destroy the session
	 */
	public function destroy() {
		$invalidate = time() - 8000;
		$path = ROOT . _;

		setcookie( 'user_uid', 'asd', $invalidate, $path );
		setcookie( 'token',    'asd', $invalidate, $path );

		$this->ok = true;
		$this->user = null;
	}

	/**
	 * Log in syslog a login fail
	 *
	 * @param $uid string
	 * @param $from string
	 * @return false
	 */
	public static function failed( $uid, $from ) {
		error_log( sprintf(
			"Login failed by '%s' using %s",
			$uid,
			$from
		) );
		return false;
	}

	/**
	 * Get the CSRF token of the current user
	 *
	 * Note that it's valid only for logged-in users.
	 *
	 * @return string
	 */
	public function getCSRF() {
		// TODO: remove that '@' put for backward compatibility with old sessions
		return @$_COOKIE['csrf'];
	}

	/**
	 * Print a form action field with the CSRF
	 *
	 * @param string $action
	 */
	public function formActionWithCSRF( $action ) {
		echo HTML::input( 'hidden', 'action', $action );
		echo HTML::input( 'hidden', 'csrf', Session::instance()->getCSRF() );
	}

	/**
	 * Check if a form action is valid (with the related CSRF)
	 *
	 * @param string $action
	 * @return boolean
	 */
	public function validateActionAndCSRF( $action ) {
		$csrf = $this->getCSRF();
		return
			isset( $_POST['action'], $_POST['csrf'] )
			&&     $_POST['action'] === $action
			&&     $_POST['csrf']   === $csrf;
	}

	/**
	 * Generate a new CSRF token
	 *
	 * @return string
	 */
	private function generateCSRF() {
		return bin2hex( openssl_random_pseudo_bytes( 8 ) );
	}
}
