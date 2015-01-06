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
 * \defgroup zenfusionoauth Module Zenfusion OAuth
 * Zenfusion Oauth module for Dolibarr
 *
 * Manages the OAuth 2 authentication process for Google APIs.
 *
 * Helps obtaining and managing user tokens through a panel on
 * each user's card.
 *
 * Allows using OAuth 2 for Google APIs accesses.
 *
 */

/**
 * \file core/modules/modZenFusionOAuth.class.php
 * Zenfusion OAuth module
 *
 * Declares and initializes the Google contacts OAuth module in Dolibarr
 *
 * \ingroup zenfusionoauth
 * \authors Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * \authors Cédric Salvador <csalvador@gpcsolutions.fr>
 */

require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';
dol_include_once('/zenfusionoauth/inc/oauth.inc.php');
dol_include_once('/zenfusionoauth/lib/scopes.lib.php');

/**
 * \class modZenFusionOAuth
 * Describes and activates Google contacts OAuth module
 */
class modZenFusionOAuth extends DolibarrModules
{

    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param string $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->numero = 105001;
        $this->rights_class = 'zenfusionoauth';
        $this->family = "other";
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "OAuth 2 authentication for Google APIs";
        $this->version = '3.0.2';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->special = 1;
        $this->picto = 'oauth@zenfusionoauth';
        $this->module_parts = array();
        $this->dirs = array();
        $this->config_page_url = array("conf.php@zenfusionoauth");
        $this->depends = array();
        $this->requiredby = array(
            "modZenFusionContacts",
            "modZenFusionSSO",
            "modZenFusionDrive"
        );
        $this->phpmin = array(5, 3);
        $this->need_dolibarr_version = array(3, 2);
        $this->langfiles = array("zenfusionoauth@zenfusionoauth");
        $this->const = array();
        $r = 0;
        $this->const[$r] = array(
            'ZF_SUPPORT',
            'string',
            '0',
            'Zenfusion support contract',
            0,
            'current',
            0
        );
        $r++;
        $this->const[$r] = array(
            'ZF_OAUTH2_CLIENT_ID',
            'string',
            '',
            'Oauth2 client ID',
            0,
            'current',
            0
        );
        $r++;
        $this->const[$r] = array(
            'ZF_OAUTH2_CLIENT_SECRET',
            'string',
            '',
            'Oauth2 client secret',
            0,
            'current',
            0
        );
        $r++;
        // JSON encoded array of scopes set by depending modules using
        // addScope() from scopes.lib.php
        $this->const[$r] = array(
            'ZF_OAUTH2_SCOPES',
            'string',
            '',
            'Oauth2 requested scopes',
            0,
            'current',
            0
        );

        $this->tabs = array(
            'user:+google:Google:@zenfusionoauth:$user->rights->zenfusionoauth->use'
            . ':/zenfusionoauth/initoauth.php?id=__ID__'
        );
        $this->boxes = array();
        $this->boxes[0][1] = "OauthStatus@zenfusionoauth";
        $this->rights = array();
        $this->rights[0][0] = 7345701;
        $this->rights[0][1] = 'Use ZenFusionOAuth';
        $this->rights[0][3] = 0;
        $this->rights[0][4] = 'use';
        $this->menus = array();
    }

    /**
     * Function called when module is enabled.
     * The init function add constants, boxes, permissions and menus
     * (defined in constructor) into Dolibarr database.
     * It also creates data directories.
     *
     * @return int 1 if OK, 0 if KO
     */
    public function init()
    {
        global $langs;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

        $msg = ""; // User message
        $dolibarr_version = versiondolibarrarray();

        $sql = array();
        $this->load_tables();
        if (function_exists('curl_init')) {
            addscope(GOOGLE_USERINFO_EMAIL_SCOPE);
            $this->_init($sql);
        } else {
            $langs->load('zenfusionoauth@zenfusionoauth');
            $mesg = $langs->trans("MissingCURL");
            if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 7) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.7
                setEventMessages($mesg, '', 'errors');
            } elseif ($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 3) { // DOL_VERSION >= 3.3
                /** @noinspection PhpDeprecationInspection */
                setEventMessage($mesg, 'errors');
            } else {
                $mesg = urlencode($mesg);
                $msg = '&mesg=' . $mesg;
            }
            header("Location: modules.php?mode=interfaces" . $msg);
        }
    }

    /**
     * Create tables, keys and data required by module
     * Files llx_table1.sql, llx_table1.key.sql llx_data.sql
     * with create table, create keys
     * and create data commands must be stored in directory /mymodule/sql/
     * This function is called by this->init.
     *
     * @return int <=0 if KO, >0 if OK
     */
    public function load_tables()
    {
        return $this->_load_tables('/zenfusionoauth/sql/');
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions
     * from Dolibarr database.
     * Data directories are not deleted.
     *
     * @return int 1 if OK, 0 if KO
     */
    public function remove()
    {
        $sql = array();

        return $this->_remove($sql);
    }
}
