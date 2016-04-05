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

/**
 * @use SESSIONUSER_CLASS
 */
trait SessionuserTrait {
	private static function prepareSessionuser(& $u) {
		if( ! isset($u->user_ID, $u->user_uid, $u->user_active, $u->user_password, $u->user_role) ) {
			if( DEBUG ) {
				$error = function($s) {
					error( sprintf( _("Campo %s mancante nella tabella utente."), $s ) );
				};
				isset($u->user_ID)       || $error("user_ID");
				isset($u->user_uid)      || $error("user_uid");
				isset($u->user_active)   || $error("user_active");
				isset($u->user_password) || $error("user_password");
				isset($u->user_role)     || $error("user_role");
			}

			error_die( _("Inattesa struttura della tabella utenti.") );
		}

		$u->user_ID     = (int) $u->user_ID;
		$u->user_active = (bool) (int) $u->user_active;
	}

	function isSessionuserActive() { // Deprecated
		return $this->user_active;
	}

	function getSessionuserRole() {
		return $this->user_role;
	}

	function generateCookieToken() {
		return hash(COOKIE_HASH_ALGO, COOKIE_HASH_SALT . $this->user_password . COOKIE_HASH_PEPP);
	}

	function querySessionuserFromUid($uid) {
		return query_row(
			sprintf(
				"SELECT * FROM {$GLOBALS[T]('user')} WHERE user_uid = '%s'",
				esc_sql( $uid )
			),
			SESSIONUSER_CLASS
		);
	}

	function querySessionuserFromLogin($user_uid, $user_password) {
		return query_row(
			sprintf(
				"SELECT * FROM {$GLOBALS[T]('user')} WHERE user_uid = '%s' AND user_password = '%s'",
				esc_sql( $user_uid ),
				esc_sql( self::encryptSessionuserPassword( $user_password ) )
			),
			SESSIONUSER_CLASS
		);
	}

	static function encryptSessionuserPassword($password) {
		return hash(PASSWD_HASH_ALGO, PASSWD_HASH_SALT . $password . PASSWD_HASH_PEPP);
	}

	function isActive() { // Deprecated
		return $this->isSessionuserActive();
	}
}

class Sessionuser {
	use SessionuserTrait;

	function __construct() {
		self::prepareSessionuser( $this );
	}
}
