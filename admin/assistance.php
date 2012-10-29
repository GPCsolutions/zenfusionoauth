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
 * \file htdocs/oauthgooglecontacts/admin/assistance.php
 * \ingroup oauthgooglecontacts
 * \brief Google contacts module Help center page
 * \version development
 */
require("../../main.inc.php");

require_once(DOL_DOCUMENT_ROOT."/oauthgooglecontacts/lib/zf_oauth.lib.php");

$langs->load("oauthgooglecontacts@oauthgooglecontacts");
$langs->load("admin");
$langs->load("help");

// only readable by admin
if (!$user->admin)
  accessforbidden();

/*
 * View
 */

// Little folder on the html page
llxHeader();
/// Navigation in the modules
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
// Folder icon title
print_fiche_titre("ZenFusion", $linkback, 'setup');

$head =  zf_prepare_head();

dol_fiche_head($head, 'help', $langs->trans("HelpCenter"),0);



print '<a target="_blank" href="http://assistance.gpcsolutions.fr"><img src="../img/logo_assist.png" alt="'.$langs->trans("HelpCenter").'"></a>';

print "<br>\n";

print '';

llxFooter();
?>

