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
 * \file class/Zenfusion_Oauth2Client.class.php
 * \brief Oauth2 client for Zenfusion
 *
 * \ingroup oauthgooglecontacts
 * \version development
 * \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 */

dol_include_once('/oauthgooglecontacts/lib/google-api-php-client/src/Google_Client.php');
dol_include_once('/oauthgooglecontacts/inc/oauth.inc.php');

/**
 * \class Oauth2Client
 * \brief Manages Oauth tokens and requests
 */
class Oauth2Client extends Google_Client
{

	public function __construct()
	{
		global $conf;
		$callback = dol_buildpath("/oauthgooglecontacts/initoauth.php", 2) . "?action=access";
		parent::__construct();
		$this->setApplicationName('ZenFusion');
		$this->setClientId($conf->global->OAUTH2_CLIENT_ID);
		$this->setClientSecret($conf->global->OAUTH2_CLIENT_SECRET);
		$this->setRedirectUri($callback);
		// We want to be able to access the user's data even if he's not connected
		$this->setAccessType('offline');
	}

	public function validateToken()
	{
		if ($this->isAccessTokenExpired()) {
			$this->refreshToken(self::$auth->token['refresh_token']);
		}
		// TODO: use CURL instead of FGC
		return file_get_contents(GOOGLE_TOKEN_INFO . self::$auth->token['access_token']);
	}

	public function createAuthUrl($email = null)
	{
		$url = parent::createAuthUrl();

		if ($email) {
			// Hack to have the email pre-populated
			// TODO: move url and parameters to an include
			$url = 'https://accounts.google.com/ServiceLogin' . '?service=lso&ltmpl=popup&Email=' . $email . '&continue=' . urlencode($url);
		}

		return $url;
	}
}
