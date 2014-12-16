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
 *  \file       core/boxes/OauthStatus.php
 *       Token status box
 *  \ingroup zenfusionoauth
 *  \authors Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 *  \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 */

require_once DOL_DOCUMENT_ROOT . '/core/boxes/modules_boxes.php';
dol_include_once('/zenfusionoauth/class/Oauth2Client.class.php');

use \zenfusion\oauth\Oauth2Client;

/**
 * \class OauthStatus
 * Display OAuth token status
 */
class OauthStatus extends ModeleBoxes
{

    public $boxcode = 'Tokenstatus'; ///< Box Codename
    public $boximg = 'oauth@zenfusionoauth'; ///< Box img
    public $boxlabel; ///< Box name
    public $depends = array(); /// Box dependencies
    public $db; ///< Database handler
    public $param; ///< optional Parameters
    public $info_box_head = array(); ///< form informations
    public $info_box_contents = array(); ///< form informations

    /**
     * Constuctor
     */

    public function __construct()
    {
        global $langs;
        $langs->load('zenfusionoauth@zenfusionoauth');

        $this->boxlabel = $langs->trans("TokenStatus");
    }

    /**
     * Load data of box into memory for a future usage
     *
     * @param int $max Maximum number of records to show
     *
     * @return void
     */
    public function loadBox($max = 0)
    {
        global $user, $langs, $db, $conf;
        require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

        $dolibarr_version = versiondolibarrarray();

        if ($user->rights->zenfusionoauth->use || $user->admin) {
            $langs->load('zenfusionoauth@zenfusionoauth');

            $this->max = $max;

            $this->info_box_head = array(
                'text' => $langs->trans("TokenStatus", $max)
            );
            // Dolibarr compat
            if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 4) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.4
                $name = 'u.lastname';
            } else {
                $name = 'u.name';
            }
            $sql = 'SELECT u.rowid AS userid, u.firstname, u.email,';
            $sql .= ' g.rowid, g.token';
            $sql .= ', ' . $name;
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'user as u';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'zenfusion_oauth as g';
            $sql .= ' ON g.rowid = u.rowid';
            $extra = array();
            if (!$user->admin) {
                // Shows only self
                $extra[] = 'u.rowid = ' . $user->id;
            }
            if ($user->entity > 0) {
                $extra[] = 'u.entity = ' . $conf->entity;
            }
            if ($extra) {
                $filter = implode(' AND ', $extra);
                $sql .= ' WHERE ' . $filter;
            }
            $result = $db->query($sql);

            if ($result) {
                $num = $db->num_rows($result);

                $i = 0;
                while ($i < $num) {
                    $objp = $db->fetch_object($result);

                    $this->info_box_contents[$i][0] = array(
                        'td' => 'align="left" width="20"',
                        'logo' => $this->boximg
                    );

                    if ($objp->name) {
                        $objname = $objp->name;
                    } else {
                        $objname = $objp->lastname;
                    }

                    // fiche.php is renamed card.php in 3.7
                    if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 7) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.7
                        $url = DOL_URL_ROOT . 'user/card.php?id=' . $objp->userid;
                    } else {
                        $url = DOL_URL_ROOT . 'user/fiche.php?id=' . $objp->userid;
                    }
                    $this->info_box_contents[$i][1] = array(
                        'td' => 'align="left" ',
                        'text' => $objp->firstname . " " . $objname,
                        'url' => $url
                    );

                    $token = $objp->token;
                    if ($objp->rowid) {
                        $client = new Oauth2Client();
                        try {
                            $client->setAccessToken($token);
                            $this->info_box_contents[$i][2] = array(
                                'td' => 'align="left"',
                                'text' => $langs->trans("StatusOk")
                            );
                        } catch (Google_Auth_Exception $e) {
                            $this->info_box_contents[$i][2] = array(
                                'td' => 'align="left"',
                                'text' => $langs->trans("Error") . ": " . $e->getMessage(),
                                'url' => dol_buildpath(
                                    '/zenfusionoauth/initoauth.php',
                                    1
                                ) . '?id=' . $objp->userid . '&action=delete_token'
                            );
                        }
                    } else {
                        // If token == NULL
                        $this->info_box_contents[$i][2] = array(
                            'td' => 'align="left"',
                            'text' => $langs->trans("NoToken"),
                            'url' => dol_buildpath(
                                '/zenfusionoauth/initoauth.php',
                                1
                            ) . '?id=' . $objp->userid

                        );
                    }
                    $this->info_box_contents[$i][3] = array(
                        'td' => 'align="right"',
                        'text' => $objp->email
                    );

                    $i++;
                }

                if ($num == 0) {
                    $this->info_box_contents[$i][0] = array(
                        'td' => 'align="center"',
                        'text' => $langs->trans("NoUserFound")
                    );
                }
            } else {
                $this->info_box_contents[0][0] = array(
                    'td' => 'align="left"',
                    'maxlength' => 500,
                    'text' => ($db->error() . ' sql=' . $sql)
                );
            }
        }
    }

    /**
     * Displays the box
     *
     * @return void
     */
    public function showBox()
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }
}
