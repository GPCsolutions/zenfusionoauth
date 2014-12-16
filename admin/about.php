<?php
/*
 * ZenFusion OAuth - A Google OAuth authentication module for Dolibarr
 * Copyright (C) 2011 Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * Copyright (C) 2011-2014 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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
 * \file admin/about.php
 * \ingroup zenfusionoauth
 * Module about page
 */
$res = 0;
// from standard dolibarr install
if (!$res && file_exists('../../main.inc.php')) {
    $res = @include '../../main.inc.php';
}
// from custom dolibarr install
if (!$res && file_exists('../../../main.inc.php')) {
    $res = @include '../../../main.inc.php';
}
if (!$res) {
    die("Main include failed");
}

require_once '../core/modules/modZenFusionOAuth.class.php';
require_once '../lib/admin.lib.php';

global $conf, $db, $user, $langs;

$langs->load('zenfusionoauth@zenfusionoauth');
$langs->load('admin');
$langs->load('help');

// only readable by admin
if (!$user->admin) {
    accessforbidden();
}

$module = new modZenFusionOAuth($db);

/*
 * View
 */

// Little folder on the html page
llxHeader();
/// Navigation in the modules
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
// Folder icon title
print_fiche_titre("ZenFusion", $linkback, 'setup');

$head = zfPrepareHead();

dol_fiche_head(
    $head,
    'about',
    $langs->trans("Module105001Name"),
    0,
    'oauth@zenfusionoauth'
);

echo '<h3>',
$langs->trans("Module105001Name"),
' — ',
$langs->Trans('Module105001Desc'),
'</h3>';
echo '<em>', $langs->trans("Version"), ' ',
$module->version, '</em><br>';
echo '<em>&copy;2011-2014 GPC.solutions<br><em>';
echo '<a target="_blank" href="http://www.zenfusion.net/">',
'<img src="../img/logo_zf.png" alt="Logo ZenFusion"></a>';

echo '<h3>', $langs->trans("Publisher"), '</h3>';
echo '<a target="_blank" href="http://www.gpcsolutions.fr">',
'<img src="../img/logo_gpc.png" alt="GPC.solutions"></a>';
echo '<address>Technopole Hélioparc<br>',
'2 avenue du Président Pierre Angot<br>',
'64053 PAU CEDEX 9<br>',
'FRANCE<br>',
'+33 (0)5 35 53 97 12</address>',
'<a href="mailto:contact@gpcsolutions.fr">contact@gpcsolutions.fr</a>';

echo '<h3>', $langs->trans("License"), '</h3>';
echo '<a target="_blank" href="http://www.gnu.org/licenses/gpl-3.0.html">',
'<img src="../img/logo_gpl.png" alt="GPL v.3"></a>';

echo '<h3>', $langs->trans("Credits"), '</h3>';

echo '<h4>', $langs->trans("Development"), '</h4>';

echo '<ul>';
echo '<li>Raphaël Doursenaud, ', $langs->trans('ProjectManager'), '</li>';
echo '<li>Sebastien Bodrero, ', $langs->trans('SoftwareEngineer'), '</li>';
echo '<li>Cédric Salvador, ', $langs->trans('SoftwareEngineer'), '</li>';
echo '</ul>';

echo '<h4>' . $langs->trans("Libraries") . '</h4>';
echo '<ul>',
'<li>',
'<a href="https://code.google.com/p/google-api-php-client/" target="_blank">',
'Google APIs Client Library for PHP',
'</a>',
'<br>',
'©2008-2014 Google Inc.',
'<br>',
'Apache License 2.0',
'<br>',
$langs->trans("Contains"),
'<ul>',
'<li>',
'<a href="http://code.google.com/p/diso/" target="_blank">',
'XRDS-Simple library',
'</a>',
'<br>',
'Apache License 2.0',
'</li>',
'</ul>',
'</li>',
'<li>',
'<a href="http://zeroclipboard.org" target="_blank">',
'ZeroClipboard',
'</a>',
'<br>',
'©2014 Jon Rohan, James M. Greene',
'<br>',
'MIT License',
'<br>',
'</li>',
'</ul>';

echo '<h4>' . $langs->trans("Ressources") . '</h4>';

echo '<ul>',
'<li>OAuth logo<br>',
'&copy; <a target="_blank" href="http://factoryjoe.com/">Chris Messina</a><br>',
'<a target="_blank" href="http://creativecommons.org/licenses/by-sa/3.0/legalcode">',
'<img src="../img/ccbysa.png" alt="Creative Commons Attribution Share Alike 3.0 license"></a>',
'</li>',
'<li>GPLv3 logo<br>',
'&copy;2007, 2008 ',
'<a target="_blank" href="http://fsf.org">Free Software Foundation</a>',
'</li>',
'<li>ZenFusion logo<br>',
'&copy;2011 GPC.solutions<br>',
'Trademark Pending',
'</li>',
'<li>GPC.solutions logo<br>',
'&copy;2010-2014 GPC.solutions',
'</li>',
'</ul>';
llxFooter();
