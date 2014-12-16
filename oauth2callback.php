<?php
/*
 * ZenFusion OAuth - A Google OAuth authentication module for Dolibarr
 * Copyright (C) 2011-2014 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2012-2013 Cédric Salvador <csalvador@gpcsolutions.fr>
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
 * \file callback.php
 * Callback page for OAuth
 *
 * \ingroup zenfusionoauth
 * \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * \authors Cédric Salvador <csalvador@gpcsolutions.fr>
 */
use \zenfusion\oauth\Oauth2Client;
use \zenfusion\oauth\Oauth2Exception;
use \zenfusion\oauth\TokenStorage;

/**
 *
 * Handles GET requests
 *
 * @param string $uri
 * @param Oauth2Client $client
 *
 * @return string|null Server reply
 */
// FIXME: Factorize with requests.lib.php
function getRequest($uri, $client)
{
    $get = new Google_Http_Request($uri, 'GET');
    $val = $client->getAuth()->authenticatedRequest($get);
    dol_syslog('GET response code: ' . $val->getResponseHttpCode(), LOG_INFO);
    if ($val->getResponseHttpCode() == 401) {
        $_SESSION['warning'] = 'HTTP401Unauthorized';

        return null;
    } elseif ($val->getResponseHttpCode() == 404) {
        //404, no error message
        return null;

    } else {
        if ($val->getResponseHttpCode() != 401
            && $val->getResponseHttpCode() != 200
            && $val->getResponseHttpCode() != 404
        ) {
            // FIXME: use a library to handle these errors separetely

            $_SESSION['warning'] = 'UnknownHTTPError';

            return null;
        }
    }
    $rep = $val->getResponseBody();
    // FIXME: validate response, it might not be what we expect
    return $rep;
}

$res = 0;
// from standard dolibarr install
if (!$res && file_exists('../main.inc.php')) {
    $res = @include '../main.inc.php';
}
// from custom dolibarr install
if (!$res && file_exists('../../main.inc.php')) {
    $res = @include '../../main.inc.php';
}
if (!$res) {
    die("Main include failed");
}

require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once './class/TokenStorage.class.php';
require_once './class/Oauth2Client.class.php';
require_once './lib/scopes.lib.php';
require_once './inc/oauth.inc.php';

global $db, $langs, $user;

$mesg = ""; // User message
$dolibarr_version = versiondolibarrarray();

$langs->load('zenfusionoauth@zenfusionoauth');
$langs->load('admin');
$langs->load('users');

// Defini si peux lire/modifier permisssions
//$canreaduser = ($user->admin || $user->rights->user->user->lire);

$state = GETPOST('state', 'int');
$callback_error = GETPOST('error', 'alpha');
$code = GETPOST('code', 'alpha');
$retry = false; // Do we have an error ?
// On callback, the state is the user id
if ((!$state || !$code || !$user->rights->zenfusionoauth->use) && !$user->admin) {
    accessforbidden();
} else {
    /*
     * Controller
     */
    // Create a new User instance to display tabs
    $doluser = new User($db);
    // Load current user's informations
    $doluser->fetch($state);
    // Create an object to use llx_zenfusion_oauth table
    $tokenstorage = new TokenStorage($db);
    $tokenstorage->fetch($state);
    // Google API client
    try {
        $client = new Oauth2Client();
    } catch (Oauth2Exception $e) {
        // Ignore
    }
    if ($callback_error) {
        $tokenstorage->delete($state);
        header(
            'refresh:0;url=' . dol_buildpath(
                '/zenfusionoauth/initoauth.php',
                1
            ) . '?id=' . $state
        );
    } else {
        try {
            $cback = dol_buildpath('/zenfusionoauth/oauth2callback.php', 2);
            $client->setRedirectUri($cback);
            $client->authenticate($_GET['code']);
        } catch (Google_Auth_Exception $e) {
            dol_syslog("Access token " . $e->getMessage());
            $retry = true;
        }
        $token = $client->getAccessToken();
        // Save the access token into database
        dol_syslog($script_file . " CREATE", LOG_DEBUG);
        $tokenstorage->setTokenFromBundle($token);
        $tokenstorage->oauth_id = null;
        $access_token = $tokenstorage->token->getAccessToken();
        $info = getRequest(GOOGLE_TOKEN_INFO . $access_token, $client);
        $info = json_decode($info);
        $tokenstorage->oauth_id = $info->user_id;
        $ok = false;
        if ($info->verified_email && $info->email == $doluser->email) {
            $db_id = $tokenstorage->update($doluser);
            if ($db_id < 0) {
                dol_print_error($db, $tokenstorage->error);
            } else {
                $ok = true;
            }
        } else {
            if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 7) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.7
                setEventMessages($langs->trans('NotSameEmail'),'' ,'errors');
            } elseif ($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 3) { // DOL_VERSION >= 3.3
                /** @noinspection PhpDeprecationInspection */
                setEventMessage($langs->trans('NotSameEmail'), 'errors');
            } else {
                $mesg = '&mesg=' . urlencode(
                    '<div class="error">' .
                    $langs->trans('NotSameEmail') . '</div>'
                );
            }
            $tokenstorage->delete($state);
        }
        // Refresh the page to prevent multiple insertions
        header(
            'refresh:0;url=' . dol_buildpath(
                '/zenfusionoauth/initoauth.php',
                1
            ) . '?id=' . $state . '&ok=' . (int)$ok . $mesg
        );
        exit;
    }
}
$db->close();
llxFooter();
