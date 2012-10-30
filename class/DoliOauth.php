<?php
/*
 * ZenFusion OAuth - A Google Oauth authorization module for Dolibarr
 * Copyright (C) 2011 Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * Copyright (C) 2011-2012 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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
 * \file class/DoliOauth.php
 * \brief Oauth authentication and requests
 *
 * Defines the ways to communicate with Google contacts API.
 *
 * Manages token manipulation.
 *
 * Allows OAuth authenticated Google contacts API requests.
 *
 * \ingroup oauthgooglecontacts
 * \version development
 * \authors Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 */
/**
 * URI for the request token
 */
define('GOOGLE_OAUTH_REQUEST_TOKEN_API',
	'https://www.google.com/accounts/OAuthGetRequestToken');
/**
 * URI to authorize the request token
 */
define('GOOGLE_OAUTH_AUTHORIZE_API',
	'https://www.google.com/accounts/OAuthAuthorizeToken');
/**
 * URI to exchange the request token with an access token
 */
define('GOOGLE_OAUTH_ACCESS_TOKEN_API',
	'https://www.google.com/accounts/OAuthGetAccessToken');
/**
 * Login URI
 */
define('GOOGLE_SERVICE_LOGIN_URL',
	'https://www.google.com/accounts/ServiceLogin');
/**
 * Gdata version is mandatory
 */
define('GDATA_VERSION', '3.0');
/**
 * Can be one of full, base or thin
 */
define('GOOGLE_PROJECTION', 'full');
/**
 * Google Contacts and Contacts Groups scope
 */
define('GOOGLE_CONTACTS_SCOPE', 'https://www.google.com/m8/feeds');
/**
 * Gmail single contact feed URI
 */
define('GOOGLE_CONTACTS_URI', GOOGLE_CONTACTS_SCOPE . '/contacts');
/**
 * Gmail group feed URI
 */
define('GOOGLE_CONTACTS_GROUPS_URI', GOOGLE_CONTACTS_SCOPE . '/groups');
/**
 * Gmail batch contact feed URI
 */
define('GOOGLE_CONTACTS_BATCH_URI',
	GOOGLE_CONTACTS_URI . '/' . GOOGLE_USERID . '/' . GOOGLE_PROJECTION . '/batch');
/**
 * Gmail revoke token URI
 */
define('GOOGLE_CONTACTS_REVOQUE_TOKEN',
	'https://www.google.com/accounts/AuthSubRevokeToken');
/**
 * Gmail revoke token URI
 */
define('GOOGLE_CONTACTS_TOKEN_INFO',
	'https://www.google.com/accounts/AuthSubTokenInfo');

dol_include_once("/googlecontacts/class/EntreeGroupes.php");
dol_include_once("/googlecontacts/class/EntreeTiers.php");

/**
 * \class DoliOauth
 * \brief Manages OAuth authentification and requests
 */
class DoliOauth
{

	const OAUTH_CONSUMER_KEY = "anonymous"; ///< Key
	const OAUTH_CONSUMER_SECRET = "anonymous"; ///< Secret

	/**
	 * Handles the API request for third party creation
	 * \param Entree_Tiers $object Contact XML representation
	 * \param string $usermail User's email address
	 */

	public function newContact(EntreeTiers $object, $usermail)
	{
		$this->setAuthType(OAUTH_AUTH_TYPE_AUTHORIZATION);
		$this->fetch(GOOGLE_CONTACTS_URI . '/' . $usermail . '/' . GOOGLE_PROJECTION,
			$object->saveXML(), OAUTH_HTTP_METHOD_POST,
			array("Content-Type" => "application/atom+xml", "GData-Version" => GDATA_VERSION));
		//TODO store content type in a constant and use it instead
	}

	/**
	 * Handles the API request for group creation
	 * \param Entree_Groupe $object Group XML representation
	 * \param string $usermail User's email address
	 */
	public function newGroupe(EntreeGroupes $object, $usermail)
	{
		$this->setAuthType(OAUTH_AUTH_TYPE_AUTHORIZATION);
		$this->fetch(GOOGLE_CONTACTS_GROUPS_URI . '/' . $usermail . '/' . GOOGLE_PROJECTION,
			$object->saveXML(), OAUTH_HTTP_METHOD_POST,
			array("Content-Type" => "application/atom+xml", "GData-Version" => GDATA_VERSION));
		//TODO store content type in a constant and use it instead
	}

	/**
	 * Revoke OAuth token
	 */
	public function revokeToken()
	{
		// Authorization header is mandatory on AuthSub endpoint but we're doing an OAuth request so let's keep it empty !
		$this->fetch(GOOGLE_CONTACTS_REVOQUE_TOKEN, "", OAUTH_HTTP_METHOD_GET,
			array("Authorization" => ""));
	}

	/**
	 * Get OAuth token info
	 */
	public function tokenInfo()
	{
		$this->fetch(GOOGLE_CONTACTS_TOKEN_INFO, "", OAUTH_HTTP_METHOD_GET);
	}

	/**
	 * Delete contact
	 * \param string $idrecordgoogle Gmail id
	 */
	public function deleteContact($idrecordgoogle)
	{
		$this->setAuthType(OAUTH_AUTH_TYPE_AUTHORIZATION);
		$this->fetch($idrecordgoogle, "", OAUTH_HTTP_METHOD_DELETE,
			array("If-Match" => "*", "GData-Version" => GDATA_VERSION));
		$response = $this->getLastResponseInfo();
		if ($response['http_code'] == 200) {
			return 1;
		} // If firewalls troubleshooting try
		/* Else {
		  $this->deleteContactOverride($idrecordgoogle)
		  } */
	}

	/**
	 * Delete contact Overriding firewall
	 * \param string $idrecordgoogle Gmail id
	 */
	public function deleteContactOverride($idrecordgoogle)
	{
		$this->setAuthType(OAUTH_AUTH_TYPE_AUTHORIZATION);
		$this->fetch($idrecordgoogle, "", OAUTH_HTTP_METHOD_POST,
			array("X-HTTP-Methode-Override" => "DELETE", "If-Match" => "*", "GData-Version" => GDATA_VERSION));
		$response = $this->getLastResponseInfo();
		if ($response['http_code'] == 200) {
			return 1;
		}
	}

	/**
	 * Update contact
	 * \param EntreeTiers $object EntreeTiers instance
	 * \param string $idrecordgoogle Gmail id
	 */
	public function updateContact(EntreeTiers $object, $idrecordgoogle)
	{
		// Replace base by full in the contact gmail id
		$editurl = str_replace('base', 'full', $idrecordgoogle);
		$this->setAuthType(OAUTH_AUTH_TYPE_AUTHORIZATION);
		$this->fetch($editurl, $object->saveXML(), OAUTH_HTTP_METHOD_PUT,
			array("Content-Type" => "application/atom+xml", "If-Match" => "*", "GData-Version" => GDATA_VERSION));
		$response = $this->getLastResponseInfo();
		if ($response['http_code'] == 200) {
			return 1;
		} // If firewalls troubleshooting try
		/* Else {
		  $this->updateContactOverride(EntreeTiers $object, $idrecordgoogle)
		  } */
	}

	/**
	 * Update contact Overriding firewall
	 * \param EntreeTiers $object EntreeTiers instance
	 * \param string $idrecordgoogle Gmail id
	 */
	public function updateContactOverride(EntreeTiers $object, $idrecordgoogle)
	{
		// Replace base by full in the contact gmail id
		$editurl = str_replace('base', 'full', $idrecordgoogle);
		$this->setAuthType(OAUTH_AUTH_TYPE_AUTHORIZATION);
		$this->fetch($editurl, $object->saveXML(), OAUTH_HTTP_METHOD_PUT,
			array("Content-Type" => "application/atom+xml", "X-HTTP-Methode-Override" => "DELETE", "If-Match" => "*", "GData-Version" => GDATA_VERSION));
		$response = $this->getLastResponseInfo();
		if ($response['http_code'] == 200) {
			return 1;
		}
	}

}

?>
