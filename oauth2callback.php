<?php

/*
 * ZenFusion OAuth - A Google Oauth authorization module for Dolibarr
 * Copyright (C) 2011-2013 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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
 * \brief callback page for OAuth
 *
 * \ingroup zenfusionoauth
 * \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * \authors Cédric Salvador <csalvador@gpcsolutions.fr>
 */
 
/**
 *
 * \brief handles GET requests
 * \param string $uri
 * \param Oauth2Client $client
 * \return string $gmail
 */
function getRequest($uri, $client)
{
    $get = new Google_HttpRequest($uri, 'GET');
    $val = $client->getIo()->authenticatedRequest($get);
    if ($val->getResponseHttpCode() == 401) {
        $_SESSION['warning'] = 'Error HTTP 401: Unauthorized';
    }
    //FIX ME use a library to handle these errors separetely
    else if($val->getResponseHttpCode() != 401 &&
            $val->getResponseHttpCode() != 200){
        $_SESSION['warning'] = 'Unknown HTTP Error';
    }
    $rep = $val->getResponseBody();
    // FIXME: validate response, it might not be what we expect
    $gmail = json_decode($rep);

    return $gmail;
}
    
$res = 0;
// from standard dolibarr install
if (! $res && file_exists('../main.inc.php')) {
        $res = @include('../main.inc.php');
}
// from custom dolibarr install
if (! $res && file_exists('../../main.inc.php')) {
        $res = @include('../../main.inc.php');
}
if (! $res) {
    die("Main include failed");
}

require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';
require_once './class/ZenFusionOAuth.class.php';
require_once './class/Zenfusion_Oauth2Client.class.php';
require_once './lib/scopes.lib.php';
require_once './inc/oauth.inc.php';

$langs->load('zenfusionoauth@zenfusionoauth');
$langs->load('admin');
$langs->load('users');

// Defini si peux lire/modifier permisssions
//$canreaduser = ($user->admin || $user->rights->user->user->lire);

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$state = GETPOST('state', 'int');
$ok = GETPOST('ok', 'alpha');
$callback_error = GETPOST('error', 'alpha');
$retry = false; // Do we have an error ?
// On callback, the state is the user id
if (! $id) {
    $id = $state;
}
/*
 * Controller
 */
// Create a new User instance to display tabs
$doluser = new User($db);
// Load current user's informations
$doluser->fetch($id);
// Create an object to use llx_zenfusion_oauth table
$oauth = new ZenFusionOAuth($db);
$oauth->fetch($id);
// Google API client
try {
    $client = new Oauth2Client();
} catch (Oauth2Exception $e) {
    // Ignore
}
if ($callback_error) {
            $retry = true;
        } else {
            try {
                $cback= dol_buildpath('/zenfusionoauth/oauth2callback.php', 2);
                $client->setRedirectUri($cback);
                $client->authenticate();
            } catch (Google_AuthException $e) {
                dol_syslog("Access token " . $e->getMessage());
                $retry = true;
            }
            $token = $client->getAccessToken();
            // Save the access token into database
            dol_syslog($script_file . " CREATE", LOG_DEBUG);
            $oauth->token = $token;
            $info = getRequest('https://www.googleapis.com/oauth2/v1/userinfo?access_token='.$token->token, $client);
            $oauth->oauth_id = $info->id;
            $db_id = $oauth->update($doluser);
            if ($db_id < 0) {
                dol_print_error($db, $oauth->error);
            }
            // Refresh the page to prevent multiple insertions
            header(
                'refresh:0;url=' . dol_buildpath(
                    '/zenfusionoauth/initoauth.php',
                    1
                ) . '?id=' . $id. '&ok=true'
            );
            exit;
        }
$db->close();
llxFooter();
