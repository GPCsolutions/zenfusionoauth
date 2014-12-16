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
/**
 * \file class/Oauth2Client.class.php
 * Oauth2 client for Zenfusion
 *
 * \ingroup zenfusionoauth
 * \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 */

namespace zenfusion\oauth;

use \Google_Client;

set_include_path(dol_buildpath('/zenfusionoauth/lib/google-api-php-client/src'));
dol_include_once('/zenfusionoauth/lib/google-api-php-client/src/Google/Client.php');
dol_include_once('/zenfusionoauth/lib/google-api-php-client/src/Google/Auth/Exception.php');
dol_include_once('/zenfusionoauth/inc/oauth.inc.php');
require_once('Oauth2Exception.class.php');

/**
 * \class Oauth2Client
 * Manages Oauth tokens and requests
 */
class Oauth2Client extends Google_Client
{
    /**
     * Init an oauth2 client
     */
    public function __construct()
    {
        global $conf;

        // Check if the module is configured
        if ($conf->global->ZF_OAUTH2_CLIENT_ID === null
            || $conf->global->ZF_OAUTH2_CLIENT_SECRET === null
        ) {
            throw new Oauth2Exception("Module not configured");
        }

        $callback = dol_buildpath('/zenfusionoauth/initoauth.php', 2)
            . '?action=access';
        $scopes = json_decode($conf->global->ZF_OAUTH2_SCOPES);
        parent::__construct();
        $this->setApplicationName('ZenFusion');
        $this->setClientId($conf->global->ZF_OAUTH2_CLIENT_ID);
        $this->setClientSecret($conf->global->ZF_OAUTH2_CLIENT_SECRET);
        $this->setRedirectUri($callback);
        // We want to be able to access the user's data
        // even if he's not connected
        $this->setAccessType('offline');
        // We don't get a refresh token unless we set this
        $this->setApprovalPrompt('force');
        // We set the scope against other known modules
        if ($scopes) {
            $this->setScopes($scopes);
        }
    }

    /**
     * Generates the authentication URL
     *
     * @param null $email The email address to authenticate with
     *
     * @return string Authentication URL
     */
    public function createAuthUrl($email = null)
    {
        $url = parent::createAuthUrl();

        if ($email) {
            $url .= '&login_hint=' . $email;
        }

        return $url;
    }
}
