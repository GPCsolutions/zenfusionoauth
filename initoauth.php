<?php
/*
 * ZenFusion OAuth - A Google Oauth authorization module for Dolibarr
 * Copyright (C) 2011 Sebastien Bodrero <sbodrero@gpcsolutions.fr>
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
 * \file initoauth.php
 * \brief User card setup tab
 *
 * Creates a new tab in each user's card
 * allowing OAuth credentials management :
 * - token creation and authorization,
 * - token revocation and deletion.
 *
 * \ingroup oauthgooglecontacts
 * \authors Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * \authors Cédric Salvador <csalvador@gpcsolutions.fr>
 */
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
require_once './class/OauthGoogleContacts.class.php';
require_once './class/Zenfusion_Oauth2Client.class.php';
require_once './lib/scopes.lib.php';
require_once './inc/oauth.inc.php';

$langs->load('oauthgooglecontacts@oauthgooglecontacts');
$langs->load('admin');
$langs->load('users');

// Defini si peux lire/modifier permisssions
$canreaduser = ($user->admin || $user->rights->user->user->lire);

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
if (! $conf->global->MAIN_MODULE_OAUTHGOOGLECONTACTS
        || ($user->id <> $id && ! $canreaduser)) {
    accessforbidden();
}

/*
 * Controller
 */
// Create a new User instance to display tabs
$doluser = new User($db);
// Load current user's informations
$doluser->fetch($id);
// Create an object to use llx_oauth_google_contacts table
$oauth = new OauthGoogleContacts($db);
$oauth->fetch($id);
// Google API client
try {
    $client = new Oauth2Client();
   // var_dump($client);
  //  exit;
} catch (Oauth2Exception $e) {
    // Ignore
}

// Actions
switch ($action) {
    case 'delete_token':
        // Get token from database
        $token = json_decode($oauth->token);
        try {
            $client->revokeToken($token->{'refresh_token'});
        } catch (Google_AuthException $e) {
            dol_syslog("Delete token " . $e->getMessage());
            // TODO: print message and user panel URL to manually revoke access
        }
        // Delete token in database
        $result = $oauth->delete($id);
        if ($result < 0) {
            dol_print_error($db, $oauth->error);
        }
        header(
            'refresh:0;url=' . dol_buildpath(
                '/oauthgooglecontacts/initoauth.php',
                1
            ) . '?id=' . $id . '&ok=true'
        );

        break;
    case 'request':
        // Save the current user to the state
        $oauth->delete($id);
        $oauth->id = $id;
        $oauth->scopes = json_encode($client->getScopes());
        $oauth->email = $doluser->email;
        $req = $oauth->create($doluser);
        if ($req < 0) {
            dol_print_error($db, $oauth->error);
        }
        $client->setState($id);
        $client->setRedirectUri("http://localhost/custom/oauthgooglecontacts/callback.php");
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
$services = readScopes(json_decode($oauth->scopes));

// Verify if the user's got an access token
if ($client) {
    try {
        $client->setAccessToken($oauth->token);
    } catch (Google_AuthException $e) {
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

dol_fiche_head($head, 'tab' . $tabname, $title, 0, 'user');

// Verify that the user's email adress exists
if (empty($doluser->email)) {
    $lock = true;
    $mesg = '<font class="error">' . $langs->trans("NoEmail") . '</font>';
}
if (! $client) {
    $lock = true;
    $mesg = '<font class="error">' . $langs->trans("NotConfigured") . '</font>';
}

/*
 * Common part of the user's tabs
 */
echo '<table class="border" width="100%">';

// Ref
echo '<tr><td width="25%" valign="top">' . $langs->trans("Ref") . '</td>';
echo '<td colspan="2">';
echo $form->showrefnav(
    $doluser,
    'id',
    '',
    $user->rights->user->user->lire || $user->admin
);
echo '</td>';
echo '</tr>';

// Nom
echo '<tr><td width="25%" valign="top">' . $langs->trans("Lastname") . '</td>';
echo '<td colspan="2">' . $doluser->nom . '</td>';
echo '</tr>';

// First name
echo '<tr><td width="25%" valign="top">' . $langs->trans("Firstname") . '</td>';
echo '<td colspan="2">' . $doluser->prenom . '</td>';
echo '</tr>';

// Email
echo '<tr><td width="25%" valign="top">' . $langs->trans("Email") . '</td>';
echo '<td colspan="2">' . $doluser->email . '</td>';
echo '</tr>';

// Scopes
echo '<tr><td width="25%" valign="top">' . $langs->trans("Services") . '</td>';
echo '<td colspan="2">';
foreach ($services as $s) {
    echo $langs->trans($s), '<br>';
}
echo '</td>';
echo '</tr>';

// Access Token
echo '<tr><td width="25%" valign="top">' . $langs->trans("AccessToken") . '</td>';
echo '<td colspan="2">' . $langs->trans($token_status) . '</td>';
echo '</tr>';

echo '</table>';

if ($ok == 'true') {
    $mesg = '<font class="ok">' . $langs->trans("OperationSuccessful") . '</font>';
}

if (! $lock) {
    echo '<br>';
    echo '<form action="initoauth.php" method="get">';
    if (! $retry) {
        // if no error
        if ($client->getAccessToken()) {
            // if access token exists or/and bad propose to delete it
            echo '<input type="hidden" name="action" value="delete_token">';
            echo '<input type="hidden" name="id" value="' . $id . '">';
            echo '<table class="border" width="100%">';
            echo '<tr><td colspan="2" align="center">';
            echo '<input class="button" type="submit" value="' . $langs->trans("DeleteToken") . '">';
        } elseif (! empty($doluser->email)) {
            // if no access token propose to request
            echo '<input type="hidden" name="action" value="request">';
            echo '<input type="hidden" name="id" value="' . $id . '">';
            echo '<table class="border" width="100%">';
            echo '<tr><td colspan="2" align="center">';
            echo '<input class="button" type="submit" value="' . $langs->trans("RequestToken") . '">';
        }
    } else {
        // We have errors
        $langs->load("errors");
        $mesg = '<font class="error">' . $langs->trans("OperationFailed") . '</font>';
        echo '<input type="hidden" name="action" value="request">';
        echo '<input type="hidden" name="id" value="' . $id . '">';
        echo '<table class="border" width="100%">';
        echo '<tr><td colspan="2" align="center">';
        echo '<input class="button" type="submit" value="' . $langs->trans("Retry") . '">';
    }
    echo '</table></form>';
}

// Messages
dol_htmloutput_mesg($mesg);

$db->close();
llxFooter();
