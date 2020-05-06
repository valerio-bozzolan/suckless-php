<?php
# Copyright (C) 2015, 2018, 2019, 2020 Valerio Bozzolan
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

// the default algorithm for the User fingerprint
define_default( 'SESSION_FINGERPRINT_ALGO', 'sha256' );

/**
 * Session handler
 *
 * Note that actually we do NOT need server sessions <3
 */
class Session {

	/*
	 * A login( & $status ) value.
	 *
	 * This is the value for a correct login (correct username/password and active account).
	 */
	const OK = 0;

	/**
	 * A login( & $status ) value.
	 *
	 * This is the value for a login failure (user/password match is wrong).
	 */
	const LOGIN_FAILED = 1;

	/**
	 * A login( & $status ) value.
	 *
	 * This happens when you are already logged-in. Don't know why you have retried the login.
	 */
	const ALREADY_LOGGED = 2;

	/**
	 * A login( & $status ) value.
	 *
	 * This happens when you forgot to send the User UID (your username).
	 */
	const EMPTY_USER_UID = 4;

	/**
	 * A login( & $status ) value.
	 *
	 * This happens when you forgot to send the password.
	 */
	const EMPTY_USER_PASSWORD = 8;

	/**
	 * A login( & $status ) $value.
	 *
	 * This happen when the User is not active and so cannot login.
	 * You may want to send him a confirmation email before allowing him to login.
	 *
	 * This happen when the 'user_active' field is not 1.
	 */
	const USER_DISABLED = 64;

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
	 * Last CSRF generated
	 *
	 * @var string
	 */
	private $csrf;

	/**
	 * Constructor
	 */
	public function __construct() {
		// remember the existing CSRF if available
		if( isset( $_COOKIE['csrf'] ) ) {
			$this->csrf = $_COOKIE['csrf'];
		}
	}

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
		$this->setCookie( 'user_uid', $user->getSessionuserUID(),              false );
		$this->setCookie( 'token',    $user->generateSessionuserCookieToken(), true  );

		// it's a good moment to renew the anti-CSRF token
		$this->renewCSRF();

		return true;
	}

	/**
	 * Set a cookie
	 *
	 * @param string  $name     Cookie name
	 * @param string  $value    Cookie value
	 * @param boolean $httponly When true do not expose via JavaScript
	 * @param int     $duration Duration in milliseconds since now (or zero for the end of the browser session) (as default, is SESSION_DURATION)
	 */
	public function setCookie( $name, $value, $httponly = false, $duration = null ) {
		if( $duration === null ) {
			$duration = SESSION_DURATION;
		}
		if( $duration !== 0 ) {
			$duration += time();
		}
		$path        = ROOT . _;
		$force_https = PROTOCOL === 'https://';
		setcookie( $name, $value, $duration, $path, '', $force_https, $httponly );
	}

	/**
	 * Validate the session
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

		// try to destroy the cookies (leave the csrf that is useful anyway)
		if( !headers_sent() ) {
			setcookie( 'user_uid', 'asd', $invalidate, $path );
			setcookie( 'token',    'asd', $invalidate, $path );
		}

		// logout
		$this->user = null;
		$this->mustValidate = false;
	}

	/**
	 * Get the CSRF token (or send a new one)
	 *
	 * @return string
	 */
	public function getCSRF() {
		if( empty( $this->csrf ) ) {
			$this->renewCSRF();
		}
		return $this->csrf;
	}

	/**
	 * Print a form action field with the anti-CSRF token
	 *
	 * @param string $action Form action (e.g. 'save-user')
	 */
	public function formActionWithCSRF( $action ) {
		echo HTML::input( 'hidden', 'csrf',   $this->getCSRF() );
		echo HTML::input( 'hidden', 'action', $action          );
	}

	/**
	 * Check if a form action is valid (with the related anti-CSRF token)
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
	 * Generate a new anti-CSRF token
	 *
	 * This will also send a new COOKIE.
	 *
	 * @param  int    $bytes How much random bytes for the anti-CSRF token
	 * @return string
	 */
	public function renewCSRF( $bytes = 8 ) {
		$this->csrf = bin2hex( openssl_random_pseudo_bytes( $bytes ) );
		$this->setCookie( 'csrf', $this->csrf, true );
		return $this->csrf;
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
