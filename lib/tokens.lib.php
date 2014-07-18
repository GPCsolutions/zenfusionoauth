<?php
/*
 * ZenFusion OAuth - A Google Oauth authorization module for Dolibarr
 * Copyright (C) 2012-2014 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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
 * \ingroup zenfusionoauth
 * Oauth tokens functions library
 */

// FIXME: move to zenfusionoauth.class.php and use the CRUD object

/**
 * Return all tokens eventually with the corresponding scope.
 *
 * @param DoliDB $db Database
 * @param null|string $scope Scope filter
 * @param null|string $filter SQL filter
 *
 * @return stdClass[] Tokens
 */
function getAllTokens($db, $scope = null, $filter = null)
{
    $db_tokens = array();
    $all_tokens = array();

    $sql = 'SELECT rowid, token, email, scopes ';
    $sql .= 'FROM ' . MAIN_DB_PREFIX . 'zenfusion_oauth';
    if ($filter) {
        $sql .= ' WHERE ' . $filter;
    }
    $resql = $db->query($sql);
    if ($resql) {
        if ($db->num_rows($resql)) {
            $num = $db->num_rows($resql);
            for ($i = 0; $i < $num; $i++) {
                $obj = $db->fetch_object($resql);
                if (json_decode($obj->token)) {
                    array_push($db_tokens, $obj);
                }
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
 * @param DoliDB $db Database
 * @param int $user_id The user ID
 * @param bool $fresh Request a fresh token (For client side usage, not needed if you use the API client)
 *
 * @return Object or false
 */
function getToken($db, $user_id, $fresh = false)
{
    $sql = 'SELECT rowid, token, email, scopes ';
    $sql .= 'FROM ' . MAIN_DB_PREFIX . 'zenfusion_oauth ';
    $sql .= 'WHERE rowid=' . $user_id;
    $resql = $db->query($sql);
    if ($resql) {
        if ($db->num_rows($resql)) {
            $num = $db->num_rows($resql);
            if ($num == 1) {
                $token_infos = $db->fetch_object($resql);
                if ($fresh === true) {
                    refreshTokenIfExpired($token_infos);
                }
                return $token_infos;
            }
            // We didn't get the expected number of results, bail out
            return false;
        }
    }
    return false;
}

/**
 * Refresh the obtained access token if needed
 *
 * This is usefull for client side usage (Javascript)
 * This is not needed for calls using the API Client because the client takes care of it for us
 *
 * @param stdClass $token_infos Token informations from the database
 */
function refreshTokenIfExpired(&$token_infos)
{
    global $db;
    dol_include_once('/zenfusionoauth/class/Zenfusion_Oauth2Client.class.php');
    dol_include_once('/zenfusionoauth/class/ZenFusionOAuth.class.php');
    $client = new Oauth2Client();
    $client->setAccessToken($token_infos->token);
    if ($client->isAccessTokenExpired()) {
        $client->refreshToken(json_decode($token_infos->token)->refresh_token);
    }
    $token_infos->token = $client->getAccessToken();
    // Store the new refresh token in database
    $database = new ZenFusionOAuth($db);
    // FIXME: avoid a second fetch by using a CRUD object in caller function
    $database->fetch($token_infos->rowid);
    $database->token = $token_infos->token;
    $database->update();
}
