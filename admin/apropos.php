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
 * \file htdocs/oauthgooglecontacts/admin/apropos.php
 * \ingroup oauthgooglecontacts
 * \brief Google contacts module About page
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

$head=  zf_prepare_head();

dol_fiche_head($head, 'about', $langs->trans("About"),0);

print '<h3>' . $langs->trans("Version") . ' ' . $conf->global->OAUTHGOOGLECONTACTS_VERSION . '</h3>';

print '<a target="_blank" href="http://www.zenfusion.net/"><img src="../img/logo_zf.png" alt="Logo ZenFusion"></a>';

print '<h3>' . $langs->trans("OauthGoogleContactsProjectContact") . '</h3>';
print '<a target="_blank" href="http://www.gpcsolutions.fr"><img src="../img/logo_gpc.png" alt="GPC.solutions"></a>';
print '<address>Pau Cité Multimédia - Bâtiment A<br>2 rue Thomas Edison<br>64054 PAU CEDEX 9<br>+33 (0)5 35 53 97 12</address>';

print '<h3>' . $langs->trans("DolibarrLicense") . '</h3>';
print '&copy;2011 GPC.solutions<br>';
print '<a target="_blank" href="http://www.gnu.org/licenses/gpl-3.0.html"><img src="../img/logo_gpl.png" alt="GPL v.3"></a>';

print '<h3>' . $langs->trans("OauthGoogleContactsCredits") . '</h3>';

print '<h4>' . $langs->trans("OauthGoogleContactsDev") . '</h4>';

print '<ul>';
print '<li>Cédric Salvador, Software Engineer</li>';
print '<li>Sebastien Bodrero, Software Engineer</li>';
print '<li>Raphaël Doursenaud, Project Manager</li>';
print '</ul>';

print '<h4>' . $langs->trans("OauthGoogleContactsRsc") . '</h4>';

print '
<ul>
    <li>OAuth logo<br>
        &copy; <a target="_blank" href="http://factoryjoe.com/">Chris Messina</a><br>
        <a target="_blank" href="http://creativecommons.org/licenses/by-sa/3.0/legalcode"><img src="../img/ccbysa.png" alt="Creative Commons Attribution Share Alike 3.0 license"></a>
    </li>

    <li>Contacts logo<br>
        &copy; <a target="_blank" href="http://www.gnome.org">GNOME Project</a><br>
        <a target="_blank" href="http://www.gnu.org/licenses/lgpl.html"><img src="../img/lgplv3.png" alt="LGPLv3"></a>
        <a target="_blank" href="http://creativecommons.org/licenses/by-sa/3.0/legalcode"><img src="../img/ccbysa.png" alt="Creative Commons Attribution Share Alike 3.0 license"></a>
    </li>

    <li>GPLv3 logo<br>
        &copy;2007, 2008 <a target="_blank" href="http://fsf.org">Free Software Foundation</a>
    </li>

    <li>ZenFusion logo<br>
        &copy;2011 GPC.solutions<br>
        Trade Mark Pending
    </li>

    <li>GPC.solutions logo<br>
        &copy;2010 GPC.solutions
    </li>
</ul>
';
llxFooter();
?>
