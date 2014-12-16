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
 * \file class/TokenStorage.class.php CRUD for zenfusion_oauth
 *
 * Creates/Reads/Updates/Deletes Oauth tokens informations
 * from llx_zenfusion_oauth table.
 *
 * \remarks Mostly automatically generated
 *
 * \ingroup zenfusionoauth
 * \authors Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * \authors Cédric Salvador <csalvador@gpcsolutions.fr>
 */

namespace zenfusion\oauth;

use \DoliDB;
use \User;

require_once 'Token.class.php';

/**
 * Class TokenStorage
 * @package zenfusion\oauth
 *
 * Manages Access and Secret tokens for each user
 */
class TokenStorage
{
    /**
     * @var DoliDB Database handler
     */
    protected $db;

    /**
     * @var string Error code (or message)
     */
    public $error;

    /**
     * @var string[] Several error codes (or messages)
     */
    public $errors = array();

    /**
     * @var int Object ID
     */
    public $id;

    /**
     * @var Token The token
     */
    public $token;

    /**
     * @param string $token A token bundle
     */
    public function setTokenFromBundle($token)
    {
        $this->token = new Token($token);
    }

    /**
     * @var string[] Registered scopes for this token
     */
    public $scopes;

    /**
     * @var string Registered email for this token
     */
    public $email;

    /**
     * @var string registered ID for SSO
     */
    public $oauth_id;

    /**
     * Instanciates a new database object
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;

        return 1;
    }

    /**
     * Create in database
     *
     * @param User $user User that create
     * @param int $notrigger 0=launch triggers after, 1=disable triggers
     *
     * @return int <0 if KO, Id of created object if OK
     */
    public function create(User $user, $notrigger = 0)
    {
        $error = 0;
        // Clean parameters
        if (isset($this->scopes)) {
            $this->scopes = trim($this->scopes);
        }
        if (isset($this->email)) {
            $this->email = trim($this->email);
        }
        if (isset($this->oauth_id)) {
            $this->oauth_id = trim($this->oauth_id);
        }
        // Check parameters
        // Put here code to add control on parameters values
        // Insert request
        // For PGSQL, we must first found the max rowid and use it as rowid in insert because postgresql
        // may use an already used value because its internal cursor does not increase when we do
        // an insert with a forced id.
        if (in_array($this->db->type, array('pgsql'))) {
            $sql = "SELECT MAX(rowid) as maxrowid FROM " . MAIN_DB_PREFIX . "zenfusion_oauth";
            $resqlrowid = $this->db->query($sql);
            if ($resqlrowid) {
                $obj = $this->db->fetch_object($resqlrowid);
                $maxrowid = $obj->maxrowid;
                // Max rowid can be empty if there is no record yet
                if (empty($maxrowid)) {
                    $maxrowid = 1;
                }

                $sql = "SELECT setval('" . MAIN_DB_PREFIX . "zenfusion_oauth_rowid_seq', " . ($maxrowid) . ")";
                $resqlrowidset = $this->db->query($sql);
                if (!$resqlrowidset) {
                    dol_print_error($this->db);
                }
            } else {
                dol_print_error($this->db);
            }
        }

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "zenfusion_oauth(";
        $sql .= "rowid";
        $sql .= ", token";
        $sql .= ", scopes";
        $sql .= ", email";
        $sql .= ", oauth_id";
        $sql .= ") VALUES (";
        $sql .= " " . (!isset($this->id) ? 'NULL' : "'" . $this->id . "'") . ",";
        $sql .= " " . (!isset($this->token) ? 'NULL' : "'" . $this->token->getTokenBundle() . "'") . ",";
        $sql .= " " . (!isset($this->scopes) ? 'NULL' : "'" . $this->scopes . "'") . "";
        $sql .= ", " . (!isset($this->email) ? 'NULL' : "'" . $this->db->escape($this->email) . "'") . "";
        $sql .= ", " . (!isset($this->oauth_id) || $this->oauth_id == '' ? 'NULL' : "'" . $this->oauth_id . "'") . "";
        $sql .= ")";
        $this->db->begin();
        dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }
        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "zenfusion_oauth");
            if (!$notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action call a trigger.
                //// Call triggers
                // include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                // $interface=new Interfaces($this->db);
                // $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
                // if ($result < 0) { $error++; $this->errors=$interface->errors; }
                //// End call triggers
            }
        }
        return $this->commitOrRollback($error, __METHOD__);
    }

    /**
     * Commit or rollback
     *
     * @param string $error The error count
     * @param string $method_name The calling method name
     *
     * @return int <0 if KO, Id of created object if OK
     */

    private function commitOrRollback($error, $method_name)
    {
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog($method_name . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();

            return -1 * $error;
        } else {
            $this->db->commit();

            return $this->id;
        }
    }

    /**
     * Load Access token and Secret token in memory from database
     *
     * @param int $id id object
     *
     * @return int <0 if KO, 0 if no data,  >0 if OK
     */
    public function fetch($id)
    {
        $sql = "SELECT";
        $sql .= " t.rowid,";
        $sql .= " t.token,";
        $sql .= " t.scopes";
        $sql .= ", t.email";
        $sql .= ", t.oauth_id";
        $sql .= " FROM " . MAIN_DB_PREFIX . "zenfusion_oauth as t";
        $sql .= " WHERE t.rowid = " . $id;
        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
                $this->id = $obj->rowid;
                $this->setTokenFromBundle($obj->token);
                $this->scopes = $obj->scopes;
                $this->email = $obj->email;
                $this->oauth_id = $obj->oauth_id;
            } else {
                $this->db->free($resql);
                return 0;
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
     * Update Access token and Secret token database
     *
     * @param User $user User that modify
     * @param int $notrigger 0=launch triggers after, 1=disable triggers
     *
     * @return int <0 if KO, >0 if OK
     */
    public function update($user = null, $notrigger = 0)
    {
        $error = 0;
        // Clean parameters
        if (isset($this->scopes)) {
            $this->scopes = trim($this->scopes);
        }
        if (isset($this->email)) {
            $this->email = trim($this->email);
        }
        if (isset($this->oauth_id)) {
            $this->oauth_id = trim($this->oauth_id);
        }
        // Check parameters
        // Put here code to add control on parameters values
        // Update request
        $sql = "UPDATE " . MAIN_DB_PREFIX . "zenfusion_oauth SET";
        $sql .= " token=" . (isset($this->token) ? "'" . $this->token->getTokenBundle() . "'"
                : "null") . ",";
        $sql .= " scopes=" . (isset($this->scopes) ? "'" . $this->scopes . "'"
                : "null") . "";
        $sql .= ", email=" . (isset($this->email) ? "'" . $this->db->escape($this->email) . "'"
                : "null") . "";
        $sql .= ", oauth_id=" . (isset($this->oauth_id) ? "'" . $this->oauth_id . "'"
                : "null") . "";
        $sql .= " WHERE rowid=" . $this->id;
        $this->db->begin();
        dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }
        if (!$error) {
            if (!$notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action call a trigger.
                //// Call triggers
                // include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                // $interface=new Interfaces($this->db);
                // $result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
                // if ($result < 0) { $error++; $this->errors=$interface->errors; }
                //// End call triggers
            }
        }
        return $this->commitOrRollback($error, __METHOD__);
    }

    /**
     * Delete Access token and Secret token in database
     *
     * @param int $id id object
     *
     * @return int <0 if KO, >0 if OK
     */
    public function delete($id)
    {
        $error = 0;
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "zenfusion_oauth";
        $sql .= " WHERE rowid=" . $id;
        $this->db->begin();
        dol_syslog(get_class($this) . "::delete sql=" . $sql);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }
        return $this->commitOrRollback($error, __METHOD__);
    }

    /**
     * Provides the user ID associated with the provided email address and oauth ID
     *
     * @param string $email The user's email address
     * @param int $oauth_id The user's Oauth ID
     *
     * @return int      The user's ID or -1 on error
     */
    public function search($email, $oauth_id)
    {
        $sql = 'select rowid from ' . MAIN_DB_PREFIX . 'zenfusion_oauth ';
        $sql .= 'where email="' . $this->db->escape($email) . '" and oauth_id="' . $this->db->escape($oauth_id) . '"';
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql) > 0) {
            $obj = $this->db->fetch_object($resql);
            $this->db->free($resql);

            return $obj->rowid;
        } else {
            return -1;
        }
    }

    /**
     * Return all tokens eventually with the corresponding scope.
     *
     * @param DoliDB $db database
     * @param null|string $scope Scope filter
     * @param null|string $filter SQL filter
     *
     * @return TokenStorage[] Tokens
     */
    public static function getAllTokens(DoliDB $db, $scope = null, $filter = null)
    {
        $db_tokens = array();

        $sql = 'SELECT rowid, token, email, scopes ';
        $sql .= 'FROM ' . MAIN_DB_PREFIX . 'zenfusion_oauth';
        if ($filter) {
            $sql .= ' WHERE ' . $db->escape($filter);
        }
        $resql = $db->query($sql);
        if ($resql) {
            if ($db->num_rows($resql)) {
                $num = $db->num_rows($resql);
                for ($i = 0; $i < $num; $i++) {
                    $obj = $db->fetch_object($resql);
                    if (json_decode($obj->token)) {
                        $tokenstorage = new TokenStorage($db);
                        $tokenstorage->id = $obj->rowid;
                        $tokenstorage->setTokenFromBundle($obj->token);
                        $tokenstorage->email = $obj->email;
                        $tokenstorage->scopes = $obj->scopes;
                        array_push($db_tokens, $tokenstorage);
                    }
                }
            }
        }

        return self::filterTokensByScope($scope, $db_tokens);
    }

    /**
     * Returns the token associated with the user
     *
     * @param DoliDB $db Database
     * @param User $user The user
     * @param bool $fresh Request a fresh token (For client side usage, not needed if you use the API client)
     * @param string $scope Scope to be filtered against
     * @return TokenStorage|false
     */
    public static function getUserToken(DoliDB $db, User $user, $fresh = false, $scope = null)
    {
        $sql = 'SELECT rowid, token, email, scopes ';
        $sql .= 'FROM ' . MAIN_DB_PREFIX . 'zenfusion_oauth ';
        $sql .= 'WHERE rowid=' . $user->id;
        $resql = $db->query($sql);
        if ($resql) {
            if ($db->num_rows($resql)) {
                $num = $db->num_rows($resql);
                if ($num == 1) {
                    $token_infos = $db->fetch_object($resql);
                    $tokenstorage = new TokenStorage($db);
                    $tokenstorage->id = $token_infos->rowid;
                    $tokenstorage->setTokenFromBundle($token_infos->token);
                    $tokenstorage->email = $token_infos->email;
                    $tokenstorage->scopes = $token_infos->scopes;
                    if ($fresh === true) {
                        $tokenstorage->refreshTokenIfExpired();
                    }
                    // Keep separated on two lines for PHP 5.3 compat
                    $tokenstorage = self::filterTokensByScope($scope, $tokenstorage);
                    return $tokenstorage[0];
                }
                // We didn't get the expected number of results, bail out
                return false;
            }
        }
        return false;
    }

    /**
     * @param string $scope
     * @param TokenStorage[]|TokenStorage $tokens
     * @return TokenStorage[] Filtered tokens
     */
    protected static function filterTokensByScope($scope, $tokens)
    {
        $filtered_tokens =  array();

        // Allow single object
        if (! is_array($tokens)) {
            $tokens = array($tokens);
        }

        if ($scope === null) {
            $filtered_tokens = $tokens;
        } else {
            foreach ($tokens as $token) {
                $token_scopes = json_decode($token->scopes);
                if (in_array($scope, $token_scopes)) {
                    array_push($filtered_tokens, $token);
                }
            }
        }
        return $filtered_tokens;
    }

    /**
     * Refresh the token if it is expired
     */
    protected function refreshTokenIfExpired()
    {
        if ($this->token->refreshIfExpired()) {
            // Store the new refresh token in database
            $this->update();
        }
    }
}
