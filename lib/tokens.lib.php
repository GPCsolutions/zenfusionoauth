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

// FIXME: move to TokenStorage.class.php and use the CRUD object

/**
 * Refresh the obtained access token if needed
 *
 * This is usefull for client side usage (Javascript)
 * This is not needed for calls using the API Client because the client takes care of it for us
 *
 * @param TokenStorage $tokenstorage Token informations from the database
 */
function refreshTokenIfExpired(&$tokenstorage)
{
    // FIXME: move to Token class ?

    dol_include_once('/zenfusionoauth/class/Oauth2Client.class.php');
    dol_include_once('/zenfusionoauth/class/TokenStorage.class.php');
    dol_include_once('/zenfusionoauth/class/Token.class.php');
    // FIXME: $tokenstorage->token should be a Token object
    $token = new \zenfusion\oauth\Token($tokenstorage->token);
    $client = new \zenfusion\oauth\Oauth2Client();
    $client->setAccessToken($token->getTokenBundle());
    if ($client->isAccessTokenExpired()) {
        $client->refreshToken($token->getRefreshToken());
    }
    $token->setTokenBundle($client->getAccessToken());
    $tokenstorage->token = $token->getTokenBundle();
    // Store the new refresh token in database
    $tokenstorage->update();
}
