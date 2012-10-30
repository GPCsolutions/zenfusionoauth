<?php

/*
 * ZenFusion OAuth - A Google Oauth authorization module for Dolibarr
 * Copyright (C) 2011 Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * Copyright (C) 2011 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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
 * \file htdocs/oauthgooglecontacts/initoauth.php
 * \brief OAuth setup through user's card
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
if ( ! $res && file_exists("../main.inc.php"))
	$res = @include("../main.inc.php");
// from custom dolibarr install
if ( ! $res && file_exists("../../main.inc.php"))
	$res = @include("../../main.inc.php");
if ( ! $res) die("Main include failed");

require_once(DOL_DOCUMENT_ROOT . "/user/class/user.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/usergroups.lib.php");
dol_include_once("/oauthgooglecontacts/oauth_google_contacts.class.php");
dol_include_once("/oauthgooglecontacts/lib/google-api-php-client/src/apiClient.php");

$langs->load("oauthgooglecontacts@oauthgooglecontacts");
$langs->load("admin");
$langs->load("users");
// Security check
/// $socid = 0; Clean parameters TODO permissions
//if ($user->societe_id > 0) $socid = $user->societe_id;
//$feature2 = (($socid && $user->rights->user->self->lire) ? '' : 'user'); /// LOad user rights
//if ($user->id == $_GET["id"]) /// A user can always read its own card
//{
// $feature2 = '';
//}
//$result = restrictedArea($user, 'user', $_GET["id"], '', $feature2);
/* * ************
 *            *
 * CONTROLEUR *
 *            *
 * *********** */
/// Create a new User instance to display tabs
$doluser = new User($db);
/// Create an object to use llx_oauth_google_contacts table
$oauthuser = new Oauth_google_contacts($db);
/// Create callback address
$callback = dol_buildpath("/oauthgooglecontacts/initoauth.php", 2) . "?action=access";
/// Scope choosen for Google's API (Google Contacts)

// Oauth2 TEST
// -- BEGIN --

define('GOOGLE_CONTACTS_URI', 'https://www.google.com/m8/feeds/contacts/');
define('GOOGLE_CONTACTS_GROUPS_URI', 'https://www.google.com/m8/feeds/groups/');
define('GOOGLE_SHARED_CONTACTS_URI', 'https://www.google.com/m8/feeds/');


$client = new apiClient();
$client->setApplicationName('ZenFusion Contacts');
$client->setClientId($conf->global->OAUTH2_CLIENT_ID);
$client->setClientSecret($conf->global->OAUTH2_CLIENT_SECRET);
$client->setRedirectUri($callback);
//$client->setScopes(GOOGLE_CONTACTS_URI.' '.GOOGLE_CONTACTS_GROUPS_URI. ' '.GOOGLE_SHARED_CONTACTS_URI);
$client->setScopes(GOOGLE_SHARED_CONTACTS_URI);
// -- END --

// Actions

switch ($_GET["action"]) {
  case "delete": // Delete access token
    dol_syslog($script_file . " DELETE", LOG_DEBUG);
    // Get token from base
    $oauthuser->fetch($_GET["id"]);
    // Init current user token
    try { // Exception
      // Sent a get request to revoke token
        $client->revokeToken($oauthuser->access_token);
    } catch (apiAuthException /*OAuthException*/ $e) {
      dol_syslog("Delete token " . $e->getMessage());
      // TODO prévenir le client de supprimer éventuellement le jeton manuelement sur son compte gmail
    }
    // Delete token in bdd
    $result = $oauthuser->delete($_GET["id"]);
    if ($result < 0) {
      $error++;
      dol_print_error($db, $oauthuser->error);
    }
    header("refresh:0;url=" . dol_buildpath("/oauthgooglecontacts/initoauth.php", 1) . "?id=" . $_GET["id"]);

    break;
  case "request": // whole process to ask a request token
    // Start the OAuth process by asking a request token
    $client->setState($_GET["id"]);
    $auth = $client->createAuthUrl();
    header("Location: {$auth}");
    break;
  case "access": // Exchanging request token to an access token
    try {
      $client->authenticate();
      $access = $client->getAccessToken();
    } catch (apiAuthException $e) {
      // Display error
      $e->getMessage();
      dol_syslog("Access token " . $e->getMessage());
      // Create boolean to display error in the form
      $retry = true;
    }
    if (!empty($access)) { // Save access token into BDD
      dol_syslog($script_file . " CREATE", LOG_DEBUG);
      $oauthuser->rowid = $_GET["state"];
      $oauthuser->access_token = $access;
      $doluser->fetch($_GET["state"]);
      $oauthuser->email = $doluser->email;
      $id = $oauthuser->create($doluser);
      //var_dump($id);
      //exit();
      if ($id < 0) {
        $error++;
        dol_print_error($db, $oauthuser->error);
      }
      // Refresh the page
      header("refresh:0;url=" . dol_buildpath("/oauthgooglecontacts/initoauth.php", 1) . "?id=" . $_GET["state"]);
    } else {
      //if ($access['http_code'] == 400)
      $retry = true;
    }
}
/* * *****
 *     *
 * VUE *
 *     *
 * ***** */
// Create new form
$form = new Form($db);
$tabname = "Google Apps";
llxHeader("", $tabname);
// Display token status in the form
$message = "Token_ok";
if ($_GET["id"]) {
  // Load current user's informations
  $doluser->fetch($_GET["id"]);
  // Verify if the user's got an access token
  $oauthuser->fetch($_GET["id"]);
  try{
    $client->setAccessToken($oauthuser->access_token);
 } catch (apiAuthException $e) {
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
  print $form->showrefnav($doluser, 'id', '', $user->rights->user->user->lire || $user->admin);
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

  // Ugly hack to programmatically logout from google account
  print '<style type"text/css"><!-- iframe{display: none;} --></style><iframe src="http://www.google.com/accounts/Logout" width="0" height="0" border="0">Please logout from your google account !</iframe>';

  //if error 400
  if ($retour)
    print '<font class="error">' . $langs->trans("Oauth_error") . ' ' . $retour . '</font>';

  print "<br>\n";

  /*
   * View depending on controleur
   */


  print '<form action="initoauth.php" method="get">';
  if (!$retry) { // if no error in the controleur
    if ($client->getAccessToken()) { // if access token exists or/and bad propose to delete it
      print '<input type="hidden" name="action" value="delete">';
      print '<input type="hidden" name="id" value="' . $_GET["id"] . '">';
      print '<table class="border" width="100%">';
      print '<tr><td colspan="2" align="center"><input class="button" type="submit" value="' . $langs->trans("Delete_token") . '">';

      print '</table></form>';
    } elseif (!empty($doluser->email)) { // if no access token propose to request
      print '<input type="hidden" name="action" value="request">';
      print '<input type="hidden" name="id" value="' . $_GET["id"] . '">';
      print '<table class="border" width="100%">';
      print '<tr><td colspan="2" align="center"><input class="button" type="submit" value="' . $langs->trans("Request_token") . '">';

      print '</table></form>';
    }
  } elseif ($retry) { // if error different from 400 (http)
    print '<input type="hidden" name="action" value="request">';
    print '<input type="hidden" name="id" value="' . $_GET["id"] . '">';
    print '<table class="border" width="100%">';
    $langs->load("errors");
    print '<font class="error">' . $langs->trans("Op_failed") . '</font>';
    print '<tr><td colspan="2" align="center"><input class="button" type="submit" value="' . $langs->trans("Retry_request") . '">';

    print '</table></form>';
  }


  print "</div>\n";
}
// Disconnect database
$db->close();
llxFooter('$Date: 2011/03/05 18:22:47 $ - $Revision: 1.23.4.1 $');
?>
