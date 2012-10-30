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
 * \defgroup oauthgooglecontacts Module OAuth Google contacts
 * \brief Google contacts Oauth module for Dolibarr
 *
 * Manages the Oauth authentication process for Google contact API.
 *
 * Helps obtaining and managing user tokens through a panel on
 * each user's card.
 *
 * Allows using Oauth for Google contacts API accesses.
 *
 */
/**
 * \file core/modules/modOAuthGoogleContacts.class.php
 * \brief Google contacts OAuth module
 *
 * Declares and initializes the Google contacts OAuth module in Dolibarr
 *
 * \ingroup oauthgooglecontacts
 * \version development
 * \authors Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * \authors Cédric Salvador <csalvador@gpcsolutions.fr>
 * \todo Implement English (default) and French translations
 */
include_once (DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php");

/**
 * \class modOAuthGoogleContacts
 * \brief Describes and activates Google contacts OAuth module
 */
class modOAuthGoogleContacts extends DolibarrModules
{

	/**
	 * \brief Constructor. Define names, constants, directories, boxes, permissions
	 * \param string $db Database handler
	 */
	function modOAuthGoogleContacts($db)
	{
		$this->db = $db;
		$this->numero = 150;
		$this->rights_class = 'oauthgooglecontacts';
		$this->family = "crm";
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Oauth authentification for Google Apps";
		$this->version = '1.0';
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name); // vérifier MAIN_MODULE_
		$this->special = 3;
		$this->picto = 'oauth@oauthgooglecontacts';
		$this->triggers = 0;
		$this->dirs = array();
		$this->config_page_url = array("config.php@oauthgooglecontacts");
		$this->depends = array();
		$this->requiredby = array("modGoogleContacts");
		$this->phpmin = array(4, 3);
		$this->need_dolibarr_version = array(3, 0);
		$this->langfiles = array("@oauthgooglecontacts");
		$this->const = array(0 => array('OAUTHGOOGLECONTACTS_VERSION', 'chaine', '1.0', 'Version du module', 0),
			1 => array('ZF_CONTRAT_ASSISTANCE', 'chaine', '0', 'Type de contrat', 0));
		$this->tabs = array('user:Google Apps:@oauthgooglecontacts:/oauthgooglecontacts/initoauth.php?id=__ID__');
		$this->boxes = array(); // List of boxes
		$this->boxes[0][1] = "box_oauthusers@oauthgooglecontacts";
		$this->rights = array();
		$this->menus = array();
	}

	/**
	 * \brief Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 * It also creates data directories.
	 * \return int 1 if OK, 0 if KO
	 */
	function init()
	{
		$sql = array();
		$result = $this->load_tables();
		return $this->_init($sql);
	}

	/**
	 * \brief Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted.
	 * \return int 1 if OK, 0 if KO
	 */
	function remove()
	{
		$sql = array();
		return $this->_remove($sql);
	}

	/**
	 * \brief Create tables, keys and data required by module
	 * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * and create data commands must be stored in directory /mymodule/sql/
	 * This function is called by this->init.
	 *  \return int <=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables('/oauthgooglecontacts/sql/');
	}

}

?>
