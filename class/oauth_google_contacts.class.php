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
 * \file class/oauth_google_contacts.class.php
 * \brief llx_oauth_google_contacts database table manipulation
 *
 * Creates/Reads/Updates/Deletes access token and secret token
 * from llx_oauth_google_contacts table.
 *
 * \remarks Mostly automatically generated
 *
 *
 * \ingroup oauthgooglecontacts
 * \version development
 * \authors Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * \authors Cédric Salvador <csalvador@gpcsolutions.fr>
 */
// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

/**
 * \class Oauth_google_contacts
 * \brief Manages Access and Secret tokens for each user
 */
class Oauth_google_contacts extends CommonObject
{

	var $db; //!< To store db handler
	var $error; //!< To return error code (or message)
	var $errors = array(); //!< To return several error codes (or messages)
	//var $element='oauth_google_contacts';			//!< Id that identify managed objects
	//var $table_element='oauth_google_contacts';	//!< Name of table without prefix where object is stored
	var $id; ///< object id
	var $access_token; ///< Access token
	var $secret_token; ///< Secret token
	var $email;

	/**
	 * \brief Instanciates a new database object
	 * \param string $db Database handler
	 */
	function Oauth_google_contacts($db)
	{
		$this->db = $db;
		return 1;
	}

	/**
	 * \brief Create in database
	 * \param string $user User that create
	 * \param int $notrigger 0=launch triggers after, 1=disable triggers
	 * \return int <0 if KO, Id of created object if OK
	 */
	function create($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;
		// Clean parameters
		if (isset($this->access_token))
				$this->access_token = trim($this->access_token);
		if (isset($this->secret_token))
				$this->secret_token = trim($this->secret_token);
		if (isset($this->email)) $this->email = trim($this->email);
		// Check parameters
		// Put here code to add control on parameters values
		// Insert request
		// For PGSQL, we must first found the max rowid and use it as rowid in insert because postgresql
		// may use an already used value because its internal cursor does not increase when we do
		// an insert with a forced id.
		if (in_array($this->db->type, array('pgsql'))) {
			$sql = "SELECT MAX(rowid) as maxrowid FROM " . MAIN_DB_PREFIX . "oauth_google_contacts";
			$resqlrowid = $this->db->query($sql);
			if ($resqlrowid) {
				$obj = $this->db->fetch_object($resqlrowid);
				$maxrowid = $obj->maxrowid;
				// Max rowid can be empty if there is no record yet
				if (empty($maxrowid)) $maxrowid = 1;

				$sql = "SELECT setval('" . MAIN_DB_PREFIX . "oauth_google_contacts_rowid_seq', " . ($maxrowid) . ")";
				//print $sql; exit;
				$resqlrowidset = $this->db->query($sql);
				if ( ! $resqlrowidset) dol_print_error($this->db);
			}
			else dol_print_error($this->db);
		}

		if ( ! in_array($this->db->type, array('pgsql'))) {
			$token = addslashes($this->access_token);
		} else {
			$token = $this->access_token;
		}
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "oauth_google_contacts(";
		$sql.= "rowid,";
		$sql.= "access_token,";
		$sql.= "secret_token";
		$sql.= ", email";
		$sql.= ") VALUES (";
		$sql.= " " . ( ! isset($this->rowid) ? 'NULL' : "'" . $this->rowid . "'") . ",";
		$sql.= " " . ( ! isset($this->access_token) ? 'NULL' : "'" . $token . "'") . ",";
		$sql.= " " . ( ! isset($this->secret_token) ? 'NULL' : "'" . addslashes($this->secret_token) . "'") . "";
		$sql.= ", " . ( ! isset($this->email) ? 'NULL' : "'" . $this->db->escape($this->email) . "'") . "";
		$sql.= ")";
		$this->db->begin();
		dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ( ! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}
		if ( ! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "oauth_google_contacts");
			if ( ! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.
				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}
		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
				$this->error.= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * \brief Load Access token and Secret token in memory from database
	 * \param int $id id object
	 * \return int <0 if KO, >0 if OK
	 */
	function fetch($id)
	{
		global $langs;
		$sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.access_token,";
		$sql.= " t.secret_token";
		$sql.= ", t.email";
		$sql.= " FROM " . MAIN_DB_PREFIX . "oauth_google_contacts as t";
		$sql.= " WHERE t.rowid = " . $id;
		dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->access_token = $obj->access_token;
				$this->secret_token = $obj->secret_token;
				$this->email = $obj->email;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * \brief Update Access token and Secret token database
	 * \param string $user User that modify
	 * \param int $notrigger 0=launch triggers after, 1=disable triggers
	 * \return int <0 if KO, >0 if OK
	 */
	function update($user = 0, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;
		// Clean parameters
		if (isset($this->access_token))
				$this->access_token = trim($this->access_token);
		if (isset($this->secret_token))
				$this->secret_token = trim($this->secret_token);
		if (isset($this->email)) $this->email = trim($this->email);
		// Check parameters
		// Put here code to add control on parameters values
		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "oauth_google_contacts SET";
		$sql.= " access_token=" . (isset($this->access_token) ? "'" . addslashes($this->access_token) . "'"
					: "null") . ",";
		$sql.= " secret_token=" . (isset($this->secret_token) ? "'" . addslashes($this->secret_token) . "'"
					: "null") . "";
		$sql.= ", email=" . (isset($this->email) ? "'" . $this->db->escape($this->email) . "'"
					: "null") . "";
		$sql.= " WHERE rowid=" . $this->id;
		$this->db->begin();
		dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ( ! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}
		if ( ! $error) {
			if ( ! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.
				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}
		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
				$this->error.= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 * \brief Delete Access token and Secret token in database
	 * \param int $id id object
	 * \return int <0 if KO, >0 if OK
	 */
	function delete($id)
	{
		global $conf, $langs;
		$error = 0;
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "oauth_google_contacts";
		$sql.= " WHERE rowid=" . $id;
		$this->db->begin();
		dol_syslog(get_class($this) . "::delete sql=" . $sql);
		$resql = $this->db->query($sql);
		if ( ! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}
		if ( ! $error) {
			if ( ! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.
				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}
		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error.= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 * \brief Load an object from its id and create a new one in database
	 * \param int $fromid Id of object to clone
	 * \return int New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user, $langs;
		$error = 0;
		$object = new Oauth_google_contacts($this->db);
		$this->db->begin();
		// Load source object
		$object->fetch($fromid);
		$object->id = 0;
		$object->statut = 0;
		// Clear fields
		// ...
		// Create clone
		$result = $object->create($user);
		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$error ++;
		}
		// End
		if ( ! $error) {
			$this->db->commit();
			return $object->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * \brief Initialise object with example values
	 * \remarks id must be 0 if object instance is a specimen.
	 */
	function initAsSpecimen()
	{
		$this->id = 0;
		$this->access_token = '';
		$this->secret_token = '';
	}

}

?>
