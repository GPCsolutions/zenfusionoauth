<?php
/*
 * ZenFusion OAuth - A Google Oauth authorization module for Dolibarr
 * Copyright (C) 2011 Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * Copyright (C) 2011-2012 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2012 Cédric Salvador <csalvador@gpcsolutions.fr>
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
 * \version development
 * \authors Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * \authors Cédric Salvador <csalvador@gpcsolutions.fr>
 * \todo Implement English (default) and French translations
 */
$res = 0;
// from standard dolibarr install
if (! $res && file_exists("../main.inc.php")) {
		$res = @include("../main.inc.php");
}
// from custom dolibarr install
if (! $res && file_exists("../../main.inc.php")) {
		$res = @include("../../main.inc.php");
}
if (! $res) {
	die("Main include failed");
}

require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';
require_once './class/oauth_google_contacts.class.php';
require_once './class/Zenfusion_Oauth2Client.class.php';
require_once './inc/oauth.inc.php';

$langs->load("oauthgooglecontacts@oauthgooglecontacts");
$langs->load("admin");
$langs->load("users");

// Defini si peux lire/modifier permisssions
$canreaduser = ($user->admin || $user->rights->user->user->lire);

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$state = GETPOST('state', 'int');
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
if ($user->id <> $id && ! $canreaduser) {
	accessforbidden();
}

/*
 * Controller
 */
/// Create a new User instance to display tabs
$doluser = new User($db);
// Load current user's informations
$doluser->fetch($id);
/// Create an object to use llx_oauth_google_contacts table
$oauthuser = new OauthGoogleContacts($db);
/// Google API client
$client = new Oauth2Client();
$client->setScopes(GOOGLE_CONTACTS_SCOPE);

// Actions
switch ($action) {
	case "delete_token":
		// Get token from database
		$oauthuser->fetch($id);
		$token = json_decode($oauthuser->access_token);
		try {
			$client->revokeToken($token->{'refresh_token'});
		} catch (Google_AuthException $e) {
			dol_syslog("Delete token " . $e->getMessage());
		}
		// TODO: Throw an alert if revoking failed
		// Delete token in database
		$result = $oauthuser->delete($id);
		if ($result < 0) {
			dol_print_error($db, $oauthuser->error);
		}
		header(
			"refresh:0;url=" . dol_buildpath(
				"/oauthgooglecontacts/initoauth.php",
				1
			) . "?id=" . $id
		);

		break;
	case "request":
		// Save the current user to the state
		$client->setState($id);
		// Go to Google for authentication
		$auth = $client->createAuthUrl($doluser->email);
		header("Location: {$auth}");
		break;
	case "access":
		// Exchange authorization code for an access token
		if ($callback_error) {
			$retry = true;
		} else {
			try {
				$client->authenticate();
			} catch (Google_AuthException $e) {
				dol_syslog("Access token " . $e->getMessage());
				$retry = true;
			}
			$token = $client->getAccessToken();
			// Save the access token into database
			dol_syslog($script_file . " CREATE", LOG_DEBUG);
			$oauthuser->rowid = $state;
			$oauthuser->access_token = $token;
			$doluser->fetch($state);
			$oauthuser->email = $doluser->email;
			$id = $oauthuser->create($doluser);
			if ($id < 0) {
				dol_print_error($db, $oauthuser->error);
			}
			// Refresh the page to prevent multiple insertions
			header(
				"refresh:0;url=" . dol_buildpath(
					"/oauthgooglecontacts/initoauth.php",
					1
				) . "?id=" . $state
			);
		}
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

// Verify if the user's got an access token
$oauthuser->fetch($id);
try {
	$client->setAccessToken($oauthuser->access_token);
} catch (Google_AuthException $e) {
	$token_good = false;
}

// Prepare token status message
if ($token_good) {
	$message = "Token_ok";
} else {
	$message = "Token_ko";
}

/*
 * Affichage onglets
 */
$head = user_prepare_head($doluser);
$title = $langs->trans("User");

dol_fiche_head($head, 'tab' . $tabname, $title, 0, 'user');

// Verify if user's email adress exists
// If not
if (empty($doluser->email)) {
	$langs->load("errors");
	print '<font class="error">' . $langs->trans("Pb_email") . '</font>';
}
/*
 * Common part of the user's tabs
 */
print '<table class="border" width="100%">';

// Ref
print '<tr><td width="25%" valign="top">' . $langs->trans("Ref") . '</td>';
print '<td colspan="2">';
print $form->showrefnav(
	$doluser,
	'id',
	'',
	$user->rights->user->user->lire || $user->admin
);
print '</td>';
print '</tr>';

// Nom
print '<tr><td width="25%" valign="top">' . $langs->trans("Lastname") . '</td>';
print '<td colspan="2">' . $doluser->nom . '</td>';
print "</tr>\n";

// First name
print '<tr><td width="25%" valign="top">' . $langs->trans("Firstname") . '</td>';
print '<td colspan="2">' . $doluser->prenom . '</td>';
print "</tr>\n";

// Email
print '<tr><td width="25%" valign="top">' . $langs->trans("Email") . '</td>';
print '<td colspan="2">' . $doluser->email . '</td>';


print "</tr>\n";

// Access Token
print '<tr><td width="25%" valign="top">' . $langs->trans("AccessToken") . '</td>';

print '<td colspan="2">' . $langs->trans($message) . '</td>';

print "</tr>\n";

print "</table>\n";

print "<br>\n";

print '<form action="initoauth.php" method="get">';
if (! $retry) {
	// if no error
	if ($client->getAccessToken()) {
		// if access token exists or/and bad propose to delete it
		print '<input type="hidden" name="action" value="delete_token">';
		print '<input type="hidden" name="id" value="' . $id . '">';
		print '<table class="border" width="100%">';
		print '<tr><td colspan="2" align="center">';
		print '<input class="button" type="submit" value="' . $langs->trans("Delete_token") . '">';
	} elseif (! empty($doluser->email)) {
		// if no access token propose to request
		print '<input type="hidden" name="action" value="request">';
		print '<input type="hidden" name="id" value="' . $id . '">';
		print '<table class="border" width="100%">';
		print '<tr><td colspan="2" align="center">';
		print '<input class="button" type="submit" value="' . $langs->trans("Request_token") . '">';
	}
} else {
	// We have errors
	print '<input type="hidden" name="action" value="request">';
	print '<input type="hidden" name="id" value="' . $id . '">';
	print '<table class="border" width="100%">';
	$langs->load("errors");
	print '<font class="error">' . $langs->trans("Op_failed") . '</font>';
	print '<tr><td colspan="2" align="center">';
	print '<input class="button" type="submit" value="' . $langs->trans("Retry_request") . '">';
}

print '</table></form>';
print "</div>\n";

$db->close();
llxFooter();
