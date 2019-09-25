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
	 * User currently logged
	 *
	 * @var Sessionuser
	 */
	private $user = null;

	/**
	 * Are the cookies tobe validated?
	 *
	 * @var bool
	 */
	private $mustValidate = true;

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
	 * Is the user logged?
	 *
	 * @return boolean
	 */
	public function isLogged() {
		return is_object( $this->getUser() );
	}

	/**
	 * Get the currently logged-in user
	 *
	 * @return Sessionuser
	 */
	public function getUser() {
		if( $this->mustValidate ) {
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
	 * @param  int    $status int Login status
	 * @param  string $uid User UID (if not specified is the 'user_uid' POST field
	 * @param  string $pwd User password (if not specified is the 'user_password' POST field
	 * @return bool
	 */
	public function login( & $status = null, $uid = null, $pwd = null ) {

		// already logged
		if( $this->isLogged() ) {
			$status = self::ALREADY_LOGGED;
			return true;
		}

		// UID from function or POST
		if( $uid === null && isset( $_POST['user_uid'] ) ) {
			$uid = $_POST['user_uid'];
		}

		// password from parameter or POST
		if( $pwd === null && isset( $_POST['user_password'] ) ) {
			$pwd = $_POST['user_password'];
		}

		// no uid no party
		if( empty( $uid ) ) {
			$status = self::EMPTY_USER_UID;
			return false;
		}

		// no password no party
		if( empty( $pwd ) ) {
			$status = self::EMPTY_USER_PASSWORD;
			return false;
		}

		// check if the user exists (note that the user class can be customized)
		$userClass = SESSIONUSER_CLASS;
		$user = $userClass::factoryFromLogin( $uid, $pwd )
			->queryRow();

		// check if user exists
		if( ! $user ) {
			$status = self::LOGIN_FAILED;
			self::failed( $userClass::sanitizeUID( $uid ), 'POST' );
			return false;
		}

		// check if user is active
		if( ! $user->isSessionuserActive() ) {
			$status = self::USER_DISABLED;
			return false;
		}

		// mark as logged
		$this->user = $user;

		// mark cookies as already validated
		$this->mustValidate = false;

		// pass login status
		$status = self::OK;

		// set cookies
		$duration    = time() + SESSION_DURATION;
		$path        = ROOT . _;
		$force_https = PROTOCOL === 'https://';
		setcookie( 'user_uid', $user->getSessionuserUID(),              $duration, $path, '', $force_https, false );
		setcookie( 'token',    $user->generateSessionuserCookieToken(), $duration, $path, '', $force_https, true  );
		setcookie( 'csrf',     $this->generateCSRF(),                   $duration, $path, '', $force_https, true  );

		return true;
	}

	/**
	 * Validate the user session from cookies
	 */
	private function validate() {

		// do not call this twice
		$this->mustValidate = false;

		// no cookies no party
		if( !isset( $_COOKIE['user_uid'], $_COOKIE['token'] ) ) {
			return;
		}

		// retrieve the user just from the username (then will check the password)
		$userClass = SESSIONUSER_CLASS;
		$user = $userClass::factoryFromUID( $_COOKIE['user_uid'] )
			->queryRow();

		// missing user or inactive
		if( !$user || !$user->isSessionuserActive() ) {
			return $this->destroy();
		}

		// check if the token is OK
		if( $_COOKIE['token'] !== $user->generateSessionuserCookieToken() ) {
			self::failed( $userClass::sanitizeUID( $_COOKIE['user_uid'] ), 'cookies' );
			return $this->destroy();
		}

		// now the user is logged
		$this->user = $user;
	}

	/**
	 * Destroy the session
	 */
	public function destroy() {
		$invalidate = time() - 8000;
		$path = ROOT . _;

		setcookie( 'user_uid', 'asd', $invalidate, $path );
		setcookie( 'token',    'asd', $invalidate, $path );
		setcookie( 'csrf',     'asd', $invalidate, $path );

		// logout
		$this->user = null;
		$this->mustValidate = false;
	}

	/**
	 * Get the CSRF token of the current user
	 *
	 * If the user is logged-in, the CSRF is a cookie.
	 * If the user is not logged-in, the CSRF is generated from his environment fingerprint.
	 *
	 * @return string
	 */
	public function getCSRF() {
		if( $this->isLogged() ) {
			if( isset( $_COOKIE['csrf'] ) ) {
				return $_COOKIE['csrf'];
			} else {
				error( 'missing CSRF cookie from User ID ' . $this->getSessionuserID() );
			}
		}

		// for non logged-in users the CSRF it's just the fingerprint
		return $this->getUserFingerprint();
	}

	/**
	 * Get the fingerprint of this User
	 *
	 * @return string
	 */
	public function getUserFingerprint() {
		$fingerprint = '';

		// Some browser fields that can identify the client.
		// An attacker should know the exact version of the browser, it's language
		// configuration setting and the IP of the client to generate its fingerprint.
		$infos = [
			'REMOTE_ADDR',
			'HTTP_USER_AGENT',
			'HTTP_ACCEPT_LANGUAGE'
		];
		foreach( $infos as $info ) {
			if( isset( $_SERVER[ $info ] ) ) {
				$fingerprint .= $_SERVER[ $info ];
			}
		}
		return hash( COOKIE_HASH_ALGO, $fingerprint );
	}

	/**
	 * Print a form action field with the CSRF
	 *
	 * @param string $action
	 */
	public function formActionWithCSRF( $action ) {
		echo HTML::input( 'hidden', 'action', $action );
		echo HTML::input( 'hidden', 'csrf', $this->getCSRF() );
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
	 * @param int $bytes How much bytes to generate
	 * @return string
	 */
	private function generateCSRF( $bytes = 8 ) {
		return bin2hex( openssl_random_pseudo_bytes( $bytes ) );
	}

	/**
	 * Log in syslog a login fail
	 *
	 * @param $uid string
	 * @param $from string
	 */
	public static function failed( $uid, $from ) {
		error_log( sprintf(
			"Login failed by '%s' using %s",
			$uid,
			$from
		) );
	}
}
