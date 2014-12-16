<?php
/*
 * ZenFusion OAuth - A Google OAuth authentication module for Dolibarr
 * Copyright (C) 2012 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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
 * \file lib/scopes.lib.php
 * \ingroup zenfusionoauth
 * Oauth scopes functions library
 */

/**
 * \function addScope
 * Allows a depending module to set the scope it needs
 *
 * @param string $scope The scope to add
 *
 * @return boolean Operation status
 */
function addScope($scope)
{
    include_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

    global $conf, $db;

    $error = 0; // Error counter

    $scopes = json_decode($conf->global->ZF_OAUTH2_SCOPES);
    // This can fail, let's initialize it
    if ($scopes === null) {
        $scopes = array();
    }
    if (!in_array($scope, $scopes)) {
        array_push($scopes, $scope);
    }
    $json = json_encode($scopes);
    $res = dolibarr_set_const(
        $db,
        'ZF_OAUTH2_SCOPES',
        $json,
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

        return true;
    } else {
        $db->rollback();

        return false;
    }
}

/**
 * \function removeScope
 * Allows a depending module to delete its scope
 *
 * @param string $scope The scope to delete
 *
 * @return boolean Operation status
 */

function removeScope($scope)
{
    include_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

    global $conf, $db;

    $error = 0; // Error counter

    $scopes = json_decode($conf->global->ZF_OAUTH2_SCOPES);
    // This can fail, let's initialize it
    if ($scopes === null) {
        $scopes = array();
    }
    if (in_array($scope, $scopes)) {
        unset($scopes[array_search($scope, $scopes)]);
        $scopes = array_values($scopes);
    }
    $json = json_encode($scopes);
    $res = dolibarr_set_const(
        $db,
        'ZF_OAUTH2_SCOPES',
        $json,
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

        return true;
    } else {
        $db->rollback();

        return false;
    }
}

/**
 * Reads a scopes array and returns a human readable array
 *
 * @param array $scopes Scopes array
 *
 * @return array
 */
function readScopes($scopes)
{
    global $conf;
    $hr_scopes = array();

    // Check if we got something
    if (!$scopes) {
        array_push($hr_scopes, 'None');

        return $hr_scopes;
    }

    if (in_array(GOOGLE_CONTACTS_SCOPE, $scopes)
        && $conf->global->MAIN_MODULE_ZENFUSIONCONTACTS
    ) {
        array_push($hr_scopes, 'Contacts');
    }

    if (in_array(GOOGLE_DRIVE_SCOPE, $scopes)
        && $conf->global->MAIN_MODULE_ZENFUSIONDRIVE
    ) {
        array_push($hr_scopes, 'Drive');
    }

    if (in_array(GOOGLE_USERINFO_PROFILE_SCOPE, $scopes)
        && in_array(GOOGLE_USERINFO_EMAIL_SCOPE, $scopes)
        && $conf->global->MAIN_MODULE_ZENFUSIONSSO
    ) {
        array_push($hr_scopes, 'SSO');
    }

    return $hr_scopes;
}
