<?php
/*
 * ZenFusion OAuth - A Google Oauth authorization module for Dolibarr
 * Copyright (C) 2011 Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * Copyright (C) 2011-2012 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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
 *  \brief      Token status box
 *  \ingroup oauthgooglecontacts
 *  \authors Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 *  \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 */
include_once DOL_DOCUMENT_ROOT . '/core/boxes/modules_boxes.php';
dol_include_once('/oauthgooglecontacts/class/Zenfusion_Oauth2Client.class.php');

/**
 * \class OauthStatus
 * \brief Display OAuth token status
 */
class OauthStatus extends ModeleBoxes
{

	public $boxcode = 'Tokenstatus'; ///< Box Codename
	public $boximg = 'object_user'; ///< Box img
	public $boxlabel; ///< Box name
	public $depends = array(); /// Box dependencies
	public $db; ///< Database handler
	public $param; ///< optional Parameters
	public $info_box_head = array(); ///< form informations
	public $info_box_contents = array(); ///< form informations

	/**
	 *      \brief Constuctor
	 */

	public function __construct()
	{
		global $langs;
		$langs->load('oauthgooglecontacts@oauthgooglecontacts');

		$this->boxlabel = $langs->trans("TokenStatus");
	}

	/**
	 *      Load data of box into memory for a future usage
	 *      \param int $max Maximum number of records to show
	 */
	public function loadBox($max = 0)
	{
		global $user, $langs, $db, $conf;
		$langs->load('oauthgooglecontacts@oauthgooglecontacts');

		$this->max = $max;

		$this->info_box_head = array(
			'text' => $langs->trans("TokenStatus", $max)
		);

		if ($user->rights->societe->lire) {
			$sql = 'SELECT u.rowid AS userid, u.firstname, u.name, u.email,';
			$sql.= ' g.rowid, g.token';
			$sql.= ' FROM ' . MAIN_DB_PREFIX . 'user as u';
			$sql.= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'oauth_google_contacts as g';
			$sql.= ' ON g.rowid = u.rowid';
			if (! $user->admin) {
				// Shows only self
				$sql.= ' WHERE u.rowid = ' . $user->id;
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

					$this->info_box_contents[$i][1] = array(
						'td' => 'align="left" ',
						'text' => $objp->name . " " . $objp->firstname,
						'url' => DOL_URL_ROOT . 'user/fiche.php?id=' . $objp->userid
					);

					$token = $objp->token;

					if ($token) {
						try {
							$client = new Oauth2Client();
						} catch (Oauth2Exception $e) {
							$this->info_box_contents[$i][2] = array(
								'td' => 'align="left"',
								'text' => $langs->trans("NotConfigured")
							);
							return;
						}
						$client->setAccessToken($token);
						if ($client->validateToken()) {
							$this->info_box_contents[$i][2] = array(
								'td' => 'align="left"',
								'text' => $langs->trans("StatusOk")
							);
						} else {
							$this->info_box_contents[$i][2] = array(
								'td' => 'align="left"',
								'text' => $langs->trans("StatusKo"),
								'url' => dol_buildpath(
									'/oauthgooglecontacts/initoauth.php',
									1
								) . '?id=' . $objp->rowid . '&action=delete_token'
							);
						}
					} else {
						// If token == NULL
						$this->info_box_contents[$i][2] = array(
							'td' => 'align="left"',
							'text' => $langs->trans("NoToken")
						);
					}
					$this->info_box_contents[$i][3] = array(
						'td' => 'align="right"',
						'text' => $objp->email
					);

					$i ++;
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
		} else {
			$this->info_box_contents[0][0] = array(
				'align' => 'left',
				'text' => $langs->trans("ReadPermissionNotAllowed")
			);
		}
	}

	public function showBox()
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}
}
