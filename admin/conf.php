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
 * \file admin/conf.php
 * \ingroup zenfusionoauth
 * \brief Module configuration page
 */
$res = 0;
// from standard dolibarr install
if (! $res && file_exists('../../main.inc.php')) {
        $res = @include('../../main.inc.php');
}
// from custom dolibarr install
if (! $res && file_exists('../../../main.inc.php')) {
        $res = @include('../../../main.inc.php');
}
if (! $res) {
    die("Main include failed");
}

require_once '../lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

$langs->load('zenfusionoauth@zenfusionoauth');
$langs->load('admin');
$langs->load('help');

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$error = 0; // Error counter

/*
 * Actions
 */
if ($action == 'upload') {
    $file = file_get_contents($_FILES['jsonConfig']['tmp_name']);
    $params = json_decode($file, true);
    if ($params === null) {
        $error++;
    } else {
        $client_id = $params['web']['client_id'];
        $client_secret = $params['web']['client_secret'] ;
    }
    if ($error) {
        $mesg = '<font class="error">' . $langs->trans("BadFile") . '</font>';
    }
}

if ($action == 'update') {
    $client_id = GETPOST('clientId', 'alpha');
    $client_secret = GETPOST('clientSecret', 'alpha');
}

// Set constants common to update and upload actions
if (($action == 'upload' || $action == 'update') && ! $error) {
    $res = dolibarr_set_const(
        $db,
        'ZF_OAUTH2_CLIENT_ID',
        $client_id,
        '',
        0,
        '',
        $conf->entity
    );
    if (! $res > 0) {
        $error++;
    }
    $res = dolibarr_set_const(
        $db,
        'ZF_OAUTH2_CLIENT_SECRET',
        $client_secret,
        '',
        0,
        '',
        $conf->entity
    );
    if (! $res > 0) {
        $error++;
    }
    if (! $error) {
        $db->commit();
        $mesg = '<font class="ok">' . $langs->trans("Saved") . '</font>';
    } else {
        $db->rollback();
        $mesg = '<font class="error">'
            . $langs->trans("UnexpectedError")
            . '</font>';
    }
}

/**
 * view
 */
llxHeader();
dol_htmloutput_mesg($msg);
$form = new Form($db);
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
// Folder icon title
print_fiche_titre("ZenFusion", $linkback, 'setup');

$head = zfPrepareHead();
dol_fiche_head(
    $head,
    'conf',
    $langs->trans("Module150Name"),
    0,
    'oauth@zenfusionoauth'
);

// Error / confirmation messages
dol_htmloutput_mesg($mesg);

print_titre($langs->trans("GoogleApiConfig"));
echo '<form method="POST" action="', $_SERVER[PHP_SELF], '">';
echo '<input type="hidden" name="token" value="', $_SESSION['newtoken'], '">';
echo '<input type="hidden" name="action" value="update">';
echo '<table class="noborder" width="40%">';
echo '<tr class="liste_titre">';
echo '<td>', $langs->trans("ClientId"), '</td>';
echo '<td>', $langs->trans("ClientSecret"), '</td>';
echo '<td></td>';
echo '</tr>';
echo '<tr>';
echo '<td>',
    '<input type="text" name ="clientId" value="',
    $conf->global->ZF_OAUTH2_CLIENT_ID, '" required="required">',
    '</td>';
echo '<td>',
    '<input type="text" name ="clientSecret" value="',
    $conf->global->ZF_OAUTH2_CLIENT_SECRET . '" required="required">',
    '</td>';
echo '<td>',
    '<input type="submit" class="button" value ="',
    $langs->trans("Save"), '">',
    '</td>';
echo '</table>';
echo '</form>';

// Import configuration from google's api console json file
echo '<form enctype="multipart/form-data" method="POST" ',
    'action="', $_SERVER[PHP_SELF], '">';
echo '<input type="hidden" name="token" value="', $_SESSION['newtoken'], '">';
echo '<input type="hidden" name="action" value="upload">';
echo '<input type="hidden" name="MAX_FILE_SIZE" value="1000">';
echo '<fieldset>';
echo '<legend>', $langs->trans("ImportFrom"), ' ';
echo '<a href="https://code.google.com/apis/console/">';
echo 'Google API Console</a></legend>';
echo  $langs->trans("JSONConfigFile"), ' ';
echo '<input type="file" name = "jsonConfig" required="required">';
echo '<input type="submit" class="button" value ="',
$langs->trans("Upload"), '">';
echo '</fieldset>';
echo '</form>';

llxFooter();
