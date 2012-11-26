<?php
/*
 * ZenFusion OAuth - A Google Oauth authorization module for Dolibarr
 * Copyright (C) 2012 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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

/**
 * \file lib/tokens.lib.php
 * \ingroup oauthgooglecontacts
 * \brief Oauth tokens functions library
 */

/**
 * Return all tokens eventually with the corresponding scope.
 *
 * \param dbhandler $db
 * \param string $scope
 * \return array:
 */
function getAllTokens($db, $scope = null)
{
	$db_tokens = array();
	$all_tokens = array();

	$sql = 'SELECT rowid, token, email, scopes ';
	$sql .= 'FROM ' . MAIN_DB_PREFIX . 'oauth_google_contacts';
	$resql = $db->query($sql);
	if ($resql) {
		if ($db->num_rows($resql)) {
			$num = $db->num_rows($resql);
			for ($i = 0; $i < $num; $i ++) {
				$obj = $db->fetch_object($resql);
				array_push($db_tokens, $obj);
			}
		}
	}

	// Filter by scope
	if ($scope === null) {
		$all_tokens = $db_tokens;
	} else {
		foreach ($db_tokens as $token) {
			$token_scopes = json_decode($token->scopes);
			if (in_array($scope, $token_scopes)) {
				array_push($all_tokens, $token);
			}
		}
	}

	return $all_tokens;
}

/**
 * Returns the token associated with the user
 *
 * \param int $user_id
 * \return stdObject or false
 */
function getToken($db, $user_id)
{
	$sql = 'SELECT rowid, token, email, scopes ';
	$sql .= 'FROM ' . MAIN_DB_PREFIX . 'oauth_google_contacts ';
	$sql .= 'WHERE rowid=' . $user_id;
	$resql = $db->query($sql);
	if ($resql) {
		if ($db->num_rows($resql)) {
			$num = $db->num_rows($resql);
			for ($i = 0; $i < $num; $i ++) {
				$token = $db->fetch_object($resql);
				return $token;
			}
		}
	}

	return false;
}
