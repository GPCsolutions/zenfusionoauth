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
 *  \file       htdocs/includes/boxes/box_oauthusers.php
 *  \brief      Status token display
 *  \ingroup oauthgooglecontacts
 *  \version development
 *  \authors Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 *  \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 */
include_once(DOL_DOCUMENT_ROOT . "/includes/boxes/modules_boxes.php");
require_once (DOL_DOCUMENT_ROOT . "/oauthgooglecontacts/classe/DoliOauth.php");

/**
 * \class box_oauthusers
 * \brief Display OAuth token status
 */
class box_oauthusers extends ModeleBoxes {

  var $boxcode = "Tokenstatus"; ///< Box Codename
  var $boximg = "object_user"; ///< Box img
  var $boxlabel; ///< Box name
  var $depends = array(); /// Box dependencies
  var $db; ///< Database handler
  var $param; ///< optional Parameters
  var $info_box_head = array(); ///< form informations
  var $info_box_contents = array(); ///< form informations

  /**
   *      \brief Constuctor
   */

  function box_oauthusers() {
    global $langs;
    $langs->load("oauthgooglecontacts@oauthgooglecontacts");

    $this->boxlabel = $langs->trans("Token_status");
  }

  /**
   *      Load data of box into memory for a future usage
   *      \param int $max Maximum number of records to show
   */
  function loadBox($max=0) {
    global $user, $langs, $db, $conf;
    $langs->load("oauthgooglecontacts@oauthgooglecontacts");

    $this->max = $max;

    $this->info_box_head = array('text' => $langs->trans("Token_status", $max));

    if ($user->rights->societe->lire) {
      $sql = "SELECT u.rowid, u.firstname, u.name, u.email,";
      $sql.= " g.rowid, g.access_token, g.secret_token";
      $sql.= " FROM " . MAIN_DB_PREFIX . "user as u";
      $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "oauth_google_contacts as g";
      $sql.= " ON g.rowid = u.rowid";
      if (!$user->admin) {
        $sql.= " WHERE u.rowid = $user->id";
      }
      $result = $db->query($sql);

      if ($result) {
        $num = $db->num_rows($result);

        $i = 0;
        while ($i < $num) {
          $objp = $db->fetch_object($result);

          $this->info_box_contents[$i][0] = array('td' => 'align="left" width="20"',
              'logo' => $this->boximg);

          $this->info_box_contents[$i][1] = array('td' => 'align="left" ',
              'text' => $objp->name . " " . $objp->firstname);
          //'url' => DOL_URL_ROOT."comm/fiche.php?id".$objp->rowid);

          $token = $objp->access_token;
          $secret = $objp->secret_token;

          if ($token != NULL) {
            $dolioauth = new DoliOauth(DoliOauth::OAUTH_CONSUMER_KEY, DoliOauth::OAUTH_CONSUMER_SECRET, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
            $dolioauth->setToken($token, $secret);

            try {
              $testtoken = $dolioauth->tokenInfo();

              $this->info_box_contents[$i][2] = array('td' => 'align="left"',
                  'text' => $langs->trans("status_ok"));
            }
            catch (OAuthException $e) {
              dol_syslog($e->getMessage);
              $this->info_box_contents[$i][2] = array('td' => 'align="left"',
                  'text' => $langs->trans("status_ko"),
                  'url' => DOL_URL_ROOT . "/oauthgooglecontacts/initoauth.php?id=" . $objp->rowid . "&action=delete");
            }
          }
          else { // If token = NULL
            $this->info_box_contents[$i][2] = array('td' => 'align="left"',
                'text' => $langs->trans("no_token"));
          }
          $this->info_box_contents[$i][3] = array('td' => 'align="right"',
              'text' => $objp->email);

          $i++;
        }

        if ($num == 0)
          $this->info_box_contents[$i][0] = array('td' => 'align="center"', 'text' => $langs->trans("NoRecordedUser"));
      }
      else {
        $this->info_box_contents[0][0] = array('td' => 'align="left"',
            'maxlength' => 500,
            'text' => ($db->error() . ' sql=' . $sql));
      }
    }
    else {
      $this->info_box_contents[0][0] = array('align' => 'left',
          'text' => $langs->trans("ReadPermissionNotAllowed"));
    }
  }

  function showBox() {
    parent::showBox($this->info_box_head, $this->info_box_contents);
  }

}

?>
