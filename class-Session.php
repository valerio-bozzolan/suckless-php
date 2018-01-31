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

defined('PASSWD_HASH_ALGO')  || define('PASSWD_HASH_ALGO', 'sha1');   // Just something
defined('PASSWD_HASH_SALT')  || define('PASSWD_HASH_SALT', 'drGth');  // Just something
defined('PASSWD_HASH_PEPP')  || define('PASSWD_HASH_PEPP', 'pw72kP'); // Just something
defined('COOKIE_HASH_ALGO')  || define('COOKIE_HASH_ALGO', 'sha256'); // Just something
defined('COOKIE_HASH_SALT')  || define('COOKIE_HASH_SALT', 'daiads'); // Just something
defined('COOKIE_HASH_PEPP')  || define('COOKIE_HASH_PEPP', '30s3-f'); // Just something
defined('SESSION_DURATION')  || define('SESSION_DURATION', 604800);   // Just something 60s * 60m * 24h * 7d
defined('SESSIONUSER_CLASS') || define('SESSIONUSER_CLASS', 'Sessionuser');

class Session {
	private $loginVerified = false;

	private $user = null;

	private $userClass;

	function __construct() {
		$this->userClass = SESSIONUSER_CLASS;
		$this->isLogged();
	}

	const OK = 0;
	const LOGIN_FAILED = 1;
	const ALREADY_LOGGED = 2;
	const EMPTY_USER_UID = 4;
	const EMPTY_USER_PASSWORD = 8;
	const TOO_LONG_USER_UID = 16; // Deprecated
	const TOO_LONG_USER_PASSWORD = 32; // Deprecated
	const USER_DISABLED = 64;
	public function login(& $status = null, $user_uid = null, $user_password = null) {
		if( $this->isLogged() ) {
			$status = self::ALREADY_LOGGED;
			return true;
		}
		if($user_uid === null) {
			$user_uid = @$_POST['user_uid'];
		}
		if($user_password === null) {
			$user_password = @$_POST['user_password'];
		}

		/// Silently short user input
		$user_uid      = luser_input( $user_uid,      100 );
		$user_password = luser_input( $user_password, 100 );

		if( empty($user_uid) ) {
			$status = self::EMPTY_USER_UID;
			return false;
		}
		if( empty($user_password) ) {
			$status = self::EMPTY_USER_PASSWORD;
			return false;
		}

		// PHP bug
		$userClass = $this->userClass;
		$user = $userClass::querySessionuserFromLogin($user_uid, $user_password);

		if( ! $user ) {
			$status = self::LOGIN_FAILED;
			error_log( sprintf( "Login failed for %s", str_truncate($user_uid, 8, '..') ) );
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

		setcookie('user_uid',   $user->user_uid,              $duration);
		setcookie('token',      $user->generateCookieToken(), $duration);
		setcookie('login_time', $time,                        $duration);

		$status = self::OK;
		return true;
	}

	private function validate() {
		if($this->loginVerified === true) {
			return true;
		}

		if( ! isset( $_COOKIE['user_uid'], $_COOKIE['token'] ) ) {
			return false;
		}

		// PHP Bug
		$userClass = $this->userClass;
		$user = $userClass::querySessionuserFromUid( str_truncate( $_COOKIE['user_uid'], 400 ) );

		$this->loginVerified = true;

		if( ! $user ) {
			$this->user = null;
			return false;
		}

		if( ! $user->isSessionuserActive() ) {
			return false;
		}

		if( $_COOKIE['token'] !== $user->generateCookieToken() ) {
			return false;
		}

		// @TODO check also $_COOKIE['login_time']

		$this->user = $user;

		return true;
	}

	public function getUser() {
		if($this->loginVerified !== true) {
			$this->validate();
		}
		return $this->user;
	}

	public function isLogged() {
		return $this->getUser() !== null;
	}

	public function destroy() {
		$invalidate = time() - 8000;

		setcookie('user_uid',   'lol', $invalidate);
		setcookie('token',      'lol', $invalidate);
		setcookie('login_time', 'lol', $invalidate);

		$this->loginVerified = true;
		$this->user = null;
	}

	public static function encryptUserPassword($password) {
		return hash(PASSWD_HASH_ALGO, PASSWD_HASH_SALT . $password . PASSWD_HASH_PEPP);
	}
}
