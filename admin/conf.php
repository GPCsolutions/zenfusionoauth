<?php
/*
 * ZenFusion OAuth - A Google OAuth authentication module for Dolibarr
 * Copyright (C) 2011 Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * Copyright (C) 2011-2014 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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
 * Module configuration page
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

require_once '../lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once '../lib/zfcopybutton.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

global $conf, $db, $user, $langs;

$mesg = ""; // User message
// Oauth2 params
$client_id = '';
$client_secret = '';
$callback_url = dol_buildpath('/zenfusionoauth/oauth2callback.php', 2);

// Build javascript origin URI
$javascript_origin = 'http';
if (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == 'on') { // HTTPS?
    $javascript_origin .= 's';
}
$javascript_origin .= '://';
$javascript_origin .= $_SERVER['HTTP_HOST'];
if (
    array_key_exists('SERVER_PORT' ,$_SERVER)
    && $_SERVER['SERVER_PORT'] != 80 // Standard HTTP
    && $_SERVER['SERVER_PORT'] != 443 // Standard HTTPS
) { // Non standard port?
    $javascript_origin .= ':' . $_SERVER['SERVER_PORT'];
}

$langs->load('zenfusionoauth@zenfusionoauth');
$langs->load('admin');
$langs->load('help');

// Access control
if (!$user->admin) {
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
    // TODO: write a file verification function to have better error messages for each case
    if (
        $params === null ||
        ! in_array($callback_url, $params['web']['redirect_uris']) ||
        ! in_array($javascript_origin, $params['web']['javascript_origins'])
    ) {
        $error++;
    } else {
        $client_id = $params['web']['client_id'];
        $client_secret = $params['web']['client_secret'];
    }
    if ($error) {
        $mesg = '<div class="error">' . $langs->trans("BadFile") . '</div>';
    }
}

if ($action == 'update') {
    $client_id = GETPOST('clientId', 'alpha');
    $client_secret = GETPOST('clientSecret', 'alpha');
}

// Set constants common to update and upload actions
if (($action == 'upload' || $action == 'update') && !$error) {
    $res = dolibarr_set_const(
        $db,
        'ZF_OAUTH2_CLIENT_ID',
        $client_id,
        '',
        0,
        '',
        $conf->entity
    );
    if (!$res > 0) {
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
    if (!$res > 0) {
        $error++;
    }
    if (!$error) {
        $db->commit();
        $mesg = '<div class="ok">' . $langs->trans("Saved") . '</div>';
    } else {
        $db->rollback();
        $mesg = '<div class="error">'
            . $langs->trans("UnexpectedError")
            . '</div>';
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
    $langs->trans("Module105001Name"),
    0,
    'oauth@zenfusionoauth'
);

// Error / confirmation messages
dol_htmloutput_mesg($mesg);

print_titre($langs->trans("GoogleApiConfig"));

// Import configuration from google's api console json file
echo '<p>',
$langs->trans("Instructions1");
// TODO: derive table from installed modules
echo '<table class="border">
    <tr class="liste_titre">
        <th>', $langs->trans("Module"), '</th>
        <th>', $langs->trans("APIs"), '</th>
    </tr>
    <tr>
        <td>ZenFusion Contacts</td>
        <td>Contacts API</td>
    </tr>
    <tr>
        <td>ZenFusion Drive</td>
        <td>Drive API<br>Google Picker API</td>
    </tr>
<table>
<br>';
echo $langs->trans("Instructions2");
echo zfInitCopyToClipboardButton();
echo '<form>',
    '<fieldset>',
    '<legend>', $langs->trans('JavascriptOrigin'), '</legend>',
    '<input type="text" disabled="disabled" name="javascript_origin" size="80" value="' . $javascript_origin . '">',
    zfCopyToClipboardButton($javascript_origin, 'javascript_origin'),
    '</fieldset>',
    '</form>',
    '<br>';
echo '<form>',
    '<fieldset>',
    '<legend>', $langs->trans('RedirectURL'), '</legend>',
    '<input type="text" disabled="disabled" name="callback_url" size="80" value="' . $callback_url . '">',
    zfCopyToClipboardButton($callback_url, 'callback_url'),
    '</fieldset>',
    '</form>',
    '<br>';
echo $langs->trans("Instructions3");
echo '<form enctype="multipart/form-data" method="POST" action="', $_SERVER['PHP_SELF'], '">',
    '<fieldset>',
    '<legend>', $langs->trans("JSONConfigFile"), '</legend>',
    '<input type="hidden" name="token" value="', $_SESSION['newtoken'], '">',
    '<input type="hidden" name="action" value="upload">',
    '<input type="hidden" name="MAX_FILE_SIZE" value="1000">',
    '<input type="file" name = "jsonConfig" required="required">',
    '<input type="submit" class="button" value ="',
    $langs->trans("Upload"), '">',
    '</fieldset>',
    '</form>',
    '<br>';
echo $langs->trans("Instructions4"),
    '</p>';

print_titre($langs->trans("ManualConfiguration"));

echo '<form method="POST" action="', $_SERVER['PHP_SELF'], '">',
    '<input type="hidden" name="token" value="', $_SESSION['newtoken'], '">',
    '<input type="hidden" name="action" value="update">',
    '<table class="noborder">',
    '<tr class="liste_titre">',
    '<td>', $langs->trans("ClientId"), '</td>',
    '<td>', $langs->trans("ClientSecret"), '</td>',
    '<td></td>',
    '</tr>',
    '<tr>',
    '<td>',
    '<input type="text" name="clientId" value="', $conf->global->ZF_OAUTH2_CLIENT_ID, '" required="required">',
    '</td>',
    '<td>',
    '<input type="password" name="clientSecret" value="', $conf->global->ZF_OAUTH2_CLIENT_SECRET, '" required="required">',
    '</td>',
    '<td>',
    '<input type="submit" class="button" value ="', $langs->trans("Save"), '">',
    '</td>',
    '</table>',
    '</form>';

llxFooter();
