<?php

/*
 * ZenFusion OAuth - A Google Oauth authorization module for Dolibarr
 * Copyright (C) 2011 Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * Copyright (C) 2011 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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
 * \file htdocs/oauthgooglecontacts/admin/statistiques.php
 * \ingroup oauthgooglecontacts
 * \brief Google contacts module statistiques page
 * \version development
 */
$res = 0;
// from standard dolibarr install
if ( ! $res && file_exists("../../main.inc.php"))
	$res = @include("../../main.inc.php");
// from custom dolibarr install
if ( ! $res && file_exists("../../../main.inc.php"))
	$res = @include("../../../main.inc.php");
if ( ! $res) die("Main include failed");

dol_include_once("/oauthgooglecontacts/lib/zf_oauth.lib.php");

$langs->load("oauthgooglecontacts@oauthgooglecontacts");
$langs->load("admin");
$langs->load("help");

// only readable by admin
if (!$user->admin)
  accessforbidden();

// Getting google contacts users and contacts count per user
$sql = "SELECT u.rowid, u.firstname, u.name, u.email, g.id_dolibarr_user, count(g.rowid) as tiers ";
$sql.= "FROM " . MAIN_DB_PREFIX . "user as u ," . MAIN_DB_PREFIX . "google_contacts_records as g ";
$sql.= "WHERE g.id_dolibarr_user = u.rowid GROUP BY u.rowid";

$result = $db->query($sql);

/*
 * View
 */

// Little folder on the html page
llxHeader();
/// Navigation in the modules
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
// Folder icon title
print_fiche_titre("ZenFusion", $linkback, 'setup');

$head = zf_prepare_head();

dol_fiche_head($head, 'statistiques', $langs->trans("Stat"), 0);

print_titre($langs->trans("SynchronizedContactsNumber"));

print "<table class=\"noborder\" width=\"40%\">\n";
print "<tr class=\"liste_titre\">\n";
print '  <td>' . $langs->trans("FirstName") . '</td>';
print '  <td>' . $langs->trans("Name") . '</td>';
print '  <td>' . $langs->trans("Email") . '</td>';
print '  <td align="center">' . $langs->trans("SynchronizedContacts") . '</td>';
print "</tr>\n";

if ($result) {
  $num = $db->num_rows($result);
  $i = 0;
  while ($i < $num) {
    $obj = $db->fetch_object($result);
    $total = $obj->tiers;

    // Getting socpeople records(contacts rattachés) per user TODO factoriser les requêtes sql
    $sqlbis = "SELECT count(id_dolibarr_user) as contacts ";
    $sqlbis.= "FROM " . MAIN_DB_PREFIX . "google_socpeople_records ";
    $sqlbis.= "WHERE id_dolibarr_user = '" . $obj->rowid . "'";

    $resultbis = $db->query($sqlbis);

    if ($resultbis) {
      $objbis = $db->fetch_object($resultbis);
      $total+= $objbis->contacts;
    }

    print "<tr>";
    print "<td>" . $obj->firstname . "</td>";
    print "<td>" . $obj->name . "</td>";
    print "<td>" . $obj->email . "</td>";
    print "<td align=\"center\">" . $total . "</td>";
    print "</tr>";
    $i++;
  }
}

else {
  dol_syslog("statistiques::select error", LOG_ERR);
}
print "</table>";

$db->close();
llxFooter();
?>
