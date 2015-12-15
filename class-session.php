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

class Session {
	private $loginVerified = false;

	private $user = null;

	private $userClass = 'SessionUser';

	private $db;

	function __construct($db = null) {
		session_start();

		if($db === null) {
			$db = $GLOBALS['db'];
		} else {
			$this->db = $db;
		}

		$this->isLogged();
	}

	const OK = 0;
	const LOGIN_FAILED = 1;
	const ALREADY_LOGGED = 2;
	const EMPTY_USER_UID = 4;
	const EMPTY_USER_PASSWORD = 8;
	const TOO_LONG_USER_UID = 16;
	const TOO_LONG_USER_PASSWORD = 32;
	const USER_DISABLED = 64;
	public function login(& $status = null, $user_uid = null, $user_password = null) {
		if( $this->isLogged() ) {
			$status = self::ALREADY_LOGGED;
			return true;
		}
		if($user_uid === null) {
			$user_uid = @$_POST[ 'user_uid' ];
		}
		if($user_password === null) {
			$user_password = @$_POST[ 'user_password' ];
		}

		$user_uid = trim( $user_uid );
		$user_password = trim( $user_password );

		if(empty($user_uid)) {
			$status = self::EMPTY_USER_UID;
			return false;
		}
		if(empty($user_password)) {
			$status = self::EMPTY_USER_PASSWORD;
			return false;
		}
		if(strlen($user_uid) > 32) { // Todo parametrize as arg
			$status = self::TOO_LONG_USER_UID;
			return false;
		}
		if(strlen($user_password) > 64) { // @Todo parametrize as arg
			$status = self::TOO_LONG_USER_PASSWORD;
			return false;
		}

		$user = $this->db->getRow(
			sprintf(
				"SELECT * FROM {$this->db->getTable('user')} WHERE user_uid = '%s' AND user_password = '%s'",
				esc_sql($user_uid),
				esc_sql($this->encryptUserPassword( $user_password) )
			),
			$this->userClass
		);

		if( ! $user ) {
			$status = self::LOGIN_FAILED;
			return false;
		}

		if( ! $user->isActive() ) {
			$status = self::USER_DISABLED;
			return false;
		}

		$this->loginVerified = true;

		unset( $user->user_password );

		$this->user = $user;

		$_SESSION['user_ID'] = $user->user_ID;
		if( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
		}
		if( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		}

		$status = self::OK;
		return true;
	}

	private function validate() {
		if($this->loginVerified === true) {
			return true;
		}
		if( !isset($_SESSION['user_ID']) ) {
			return false;
		}

		$user = $this->db->getRow(
			sprintf(
				"SELECT * FROM {$this->db->getTable('user')} WHERE user_ID = '%d'",
				$_SESSION['user_ID']
			),
			$this->userClass
		);

		$this->loginVerified = true;

		if( ! $user ) {
			$this->user = null;
			return false;
		}

		unset( $user->user_password );

		if( ! $user->isActive() ) {
			return false;
		}

		// Aggressive browser additional restriction
		if( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			if( @$_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] ) {
				return false;
			}
		}
		if( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			if( @$_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT'] ) {
				return false;
			}
		}

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
		session_unset();
		session_destroy();
		$this->loginVerified = true;
		$this->user = null;
	}

	public static function encryptUserPassword($password) {
		return hash(
			PASSWD_HASH_ALGO,
			PASSWD_HASH_SALT . $password . PASSWD_HASH_PEPP
		);
	}
}

class SessionUser {
	function __construct() {
		$this->user_ID = (int) $this->user_ID;
		$this->user_active = (bool) (int) $this->user_active;
	}

	function isActive() {
		return $this->user_active;
	}
}
