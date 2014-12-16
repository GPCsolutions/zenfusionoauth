<?php
/*
 * ZenFusion OAuth - A Google OAuth authentication module for Dolibarr
 * Copyright (C) 2011 Sebastien Bodrero <sbodrero@gpcsolutions.fr>
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
 * \file initoauth.php
 * User card setup tab
 *
 * Creates a new tab in each user's card
 * allowing OAuth credentials management :
 * - token creation and authorization,
 * - token revocation and deletion.
 *
 * \ingroup zenfusionoauth
 * \authors Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * \authors Cédric Salvador <csalvador@gpcsolutions.fr>
 */

// TODO: allow selecting services permissions

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
require_once './class/TokenStorage.class.php';
require_once './class/Oauth2Client.class.php';
require_once './lib/scopes.lib.php';
require_once './inc/oauth.inc.php';

use \zenfusion\oauth\TokenStorage;
use \zenfusion\oauth\Oauth2Client;

global $db, $conf, $user, $langs;

$langs->load('zenfusionoauth@zenfusionoauth');
$langs->load('admin');
$langs->load('users');

// Access rights
$canreaduser = ($user->admin || $user->rights->zenfusionoauth->use);

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$callback_error = GETPOST('error', 'alpha');
$retry = false; // Do we have an error ?
$mesg = GETPOST('mesg', 'alpha');
// On callback, the state is the user id

// Security check
$socid = 0;
if ($user->societe_id > 0) {
    $socid = $user->societe_id;
}
$feature2 = (($socid && $user->rights->user->self->creer) ? '' : 'user');
// A user can always read its own card
if ($user->id == $id) {
    $feature2 = '';
    $canreaduser = 1;
}
$result = restrictedArea($user, 'user', $id, '&user', $feature2);
if (!$conf->global->MAIN_MODULE_ZENFUSIONOAUTH
    || ($user->id <> $id && !$canreaduser)
) {
    accessforbidden();
}

/*
 * Controller
 */
// Create a new User instance to display tabs
$doluser = new User($db);
// Load current user's informations
$doluser->fetch($id);
// Create an object to use llx_zenfusion_oauth table
$tokenstorage = new TokenStorage($db);
$tokenloaded = $tokenstorage->fetch($id);
// Cleanup bad tokens
if ($tokenloaded && is_null($tokenstorage->oauth_id)) {
	$tokenstorage->delete($id);
	$tokenloaded = false;
}
// Google API client
try {
    $client = new Oauth2Client();
} catch (Google_Auth_Exception $e) {
    // Ignore
}

// Actions
switch ($action) {
    case 'delete_token':
        try {
            $client->revokeToken($tokenstorage->token->getRefreshToken());
        } catch (Google_Auth_Exception $e) {
            dol_syslog("Delete token " . $e->getMessage());
            // TODO: print message and user panel URL to manually revoke access
        }
        // Delete token in database
        $result = $tokenstorage->delete($id);
        if ($result < 0) {
            dol_print_error($db, $tokenstorage->error);
        }
        header(
            'refresh:0;url=' . dol_buildpath(
                '/zenfusionoauth/initoauth.php',
                1
            ) . '?id=' . $id . '&ok=1'
        );

        break;
    case 'request':
        // Save the current user to the state
        $tokenstorage->delete($id);
        $tokenstorage->id = $id;
        $tokenstorage->scopes = json_encode($client->getScopes());
        $tokenstorage->email = $doluser->email;
        $tokenstorage->oauth_id = '';
        $req = $tokenstorage->create($doluser);
        if ($req < 0) {
            dol_print_error($db, $tokenstorage->error);
        }
        $client->setState($id);
        $cback = dol_buildpath('/zenfusionoauth/oauth2callback.php', 2);
        $client->setRedirectUri($cback);
        // Go to Google for authentication
        $auth = $client->createAuthUrl($doluser->email);
        header('Location: ' . $auth);
        break;
}
/*
 * View
 */

// Create new form
$form = new Form($db);
$tabname = "Google";
llxHeader("", $tabname);
// Token status for the form
$token_good = true;
// Services for the form
$enabledservices = array();
if ($tokenloaded) {
	$tokenstorage->token->getTokenBundle();
    $enabledservices = readScopes(json_decode($tokenstorage->scopes));
}
$availableservices = array_diff(readScopes(json_decode($conf->global->ZF_OAUTH2_SCOPES)), $enabledservices);

// Verify if the user's got an access token
if ($client && is_a($tokenstorage->token, '\zenfusion\oauth\Token')) {
    try {
        $client->setAccessToken($tokenstorage->token->getTokenBundle());
    } catch (Google_Auth_Exception $e) {
        $token_good = false;
    }

    // Prepare token status message
    if ($token_good) {
        $token_status = "TokenOk";
    } else {
        $token_status = "TokenKo";
    }
} else {
    $token_status = "NotConfigured";
}

/*
 * Tab display
 */
$head = user_prepare_head($doluser);
$title = $langs->trans("User");

dol_fiche_head($head, strtolower($tabname), $title, 0, 'user');

// Lock page if required informations are missing
$lock = false;

if (!isValidEmail($doluser->email)) {
    $lock = true;
    $langs->load("errors");
    $mesg = '<div class="error">' . $langs->trans("ErrorBadEMail", $doluser->email) . '</div>';
}
// Verify that the user's email adress exists
if (empty($doluser->email)) {
    $lock = true;
    $mesg = '<div class="error">' . $langs->trans("NoEmail") . '</div>';
}
// Check if there is a scope
if (!$availableservices && !$enabledservices) {
    $lock = true;
    $mesg = '<div class="error">' . $langs->trans("NoScope") . '</div>';
}
if (!$client || !$conf->global->ZF_OAUTH2_CLIENT_ID) {
    $lock = true;
    $mesg = '<div class="error">' . $langs->trans("NotConfigured") . '</div>';
}

/*
 * Common part of the user's tabs
 */

// user->nom and user->prenom are deprecated and won't be supported in the future
// test to insure compatibility
if (isset($doluser->lastname)) {
    $lastname = $doluser->lastname;
} else {
    /** @noinspection PhpUndefinedFieldInspection */
    $lastname = $doluser->nom;
}
if (isset($doluser->firstname)) {
    $firstname = $doluser->firstname;
} else {
    /** @noinspection PhpUndefinedFieldInspection */
    $firstname = $doluser->prenom;
}

echo '<table class="border" width="100%">',

// Ref
'<tr><td width="25%" valign="top">', $langs->trans("Ref"), '</td>',
'<td colspan="2">',
$form->showrefnav(
    $doluser,
    'id',
    '',
    $user->rights->user->user->lire || $user->admin
),
'</td>',
'</tr>',

// Nom
'<tr><td width="25%" valign="top">', $langs->trans("Lastname"), '</td>',
'<td colspan="2">', $lastname, '</td>',
'</tr>',

// First name
'<tr><td width="25%" valign="top">', $langs->trans("Firstname"), '</td>',
    '<td colspan="2">' . $firstname . '</td>',
'</tr>',

// Email
'<tr><td width="25%" valign="top">', $langs->trans("Email"), '</td>',
'<td colspan="2">', $doluser->email, '</td>',
'</tr>',

// TODO: use services icons with tooltip description

// Module declared scopes
'<tr><td width="25%" valign="top">', $langs->trans("AvailableServices"), '</td>',
'<td colspan="2">';
foreach ($availableservices as $as) {
    echo $langs->trans($as), '<br>';
}
echo '</td>',
'</tr>',

// Scopes
'<tr><td width="25%" valign="top">', $langs->trans("EnabledServices"), '</td>',
'<td colspan="2">';
foreach ($enabledservices as $es) {
    echo $langs->trans($es), '<br>';
}
echo '</td>',
'</tr>',

// Access Token
'<tr><td width="25%" valign="top">', $langs->trans("AccessToken"), '</td>',
'<td colspan="2">', $langs->trans($token_status), '</td>',
'</tr>',

'</table>';

if (GETPOST('ok', 'int') > 0) {
    $mesg = '<div class="ok">' . $langs->trans("OperationSuccessful") . '</div>';
} elseif (isset($_GET['ok']) && GETPOST('ok', 'int') == 0) {
    $retry = true;
}

if (!$lock) {
    echo '<br>',
    '<form action="initoauth.php" method="get">';
    if (!$retry) {
        // if no error
        if ($client->getAccessToken()) {
            // if access token exists or/and bad propose to delete it
            echo '<input type="hidden" name="action" value="delete_token">',
            '<input type="hidden" name="id" value="', $id, '">',
            '<table class="border" width="100%">',
            '<tr><td colspan="2" align="center">',
            '<input class="button" type="submit" value="', $langs->trans("DeleteToken"), '"></tr>';
        } elseif (isValidEmail($doluser->email)
            && ($availableservices)
            && $conf->global->ZF_OAUTH2_CLIENT_ID
            && ($user->rights->zenfusionoauth->use || $user->admin)
        ) {
            // if no access token propose to request
            echo '<input type="hidden" name="action" value="request">',
            '<input type="hidden" name="id" value="', $id, '">',
            '<table class="border" width="100%">',
            '<tr><td colspan="2" align="center">',
            '<input class="button" type="submit" value="', $langs->trans("RequestToken"), '"></tr>';
        } else {
            $mesg = '<div class="warning">' . $langs->trans("InvalidConfiguration") . '</div>';
        }
    } else {
        // We have errors
        $langs->load("errors");
        $mesg = '<div class="error">' . $langs->trans("OperationFailed") . '</div>';
        echo '<input type="hidden" name="action" value="request">',
        '<input type="hidden" name="id" value="', $id, '">',
        '<table class="border" width="100%">',
        '<tr><td colspan="2" align="center">',
        '<input class="button" type="submit" value="', $langs->trans("Retry"), '"></tr>';
    }
    echo '</table></form>';
}

// Messages
dol_htmloutput_mesg($mesg);

$db->close();
llxFooter();
