<?php
/*
 * ZenFusion OAuth - A Google OAuth authentication module for Dolibarr
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

namespace zenfusion\oauth;

/**
 * Class Token
 * @package zenfusion\oauth
 */
class Token
{
    /**
     * @var string JSON token bundle
     */
    protected $token;

    /**
     * @return string JSON token bundle
     */
    public function getTokenBundle()
    {
        return $this->token;
    }

    /**
     * @param string $token JSON token bundle
     */
    public function setTokenBundle($token)
    {
        $this->token = $token;
    }

    /**
     * @param string $token JSON token bundle
     */
    public function __construct($token)
    {
        $this->token = trim($token);
    }

    /**
     * @return string Access token
     */
    public function getAccessToken()
    {
        return json_decode($this->token)->access_token;
    }

    /**
     * @return string Token type
     */
    public function getTokenType()
    {
        return json_decode($this->token)->token_type;
    }

    /**
     * @return string Refresh token
     */
    public function getRefreshToken()
    {
        return json_decode($this->token)->refresh_token;
    }

    /**
     * @return bool Refreshed
     */
    public function refreshIfExpired()
    {
        require_once 'Oauth2Client.class.php';

        $client = new Oauth2Client();
        $client->setAccessToken($this->getTokenBundle());
        if ($client->isAccessTokenExpired()) {
            $client->refreshToken($this->getRefreshToken());
            $this->setTokenBundle($client->getAccessToken());
            return true;
        }
        return false;
    }
}
