<?php
/*
 * ZenFusion OAuth - A Google Oauth authorization module for Dolibarr
 * Copyright (C) 2011 Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * Copyright (C) 2011-2012 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013 Cédric Salvador <csalvador@gpcsolutions.fr>
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
 * \file inc/oauth.inc.php
 * \brief Oauth constants
 *
 * \ingroup zenfusionoauth
 * \authors Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * \authors Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * \authors Cédric Salvador <csalvador@gpcsolutions.fr>
 */
/**
 * Gdata version is mandatory
 */
 //TODO Put each scope in its own module
define('GDATA_VERSION', '3.0');
/**
 * Can be one of full, base or thin
 */
define('GOOGLE_PROJECTION', 'full');
/**
 * Google Contacts and Contacts Groups scope
 */
define('GOOGLE_CONTACTS_SCOPE', 'https://www.google.com/m8/feeds');
/**
 * Google single contact feed URI
 */
define('GOOGLE_CONTACTS_URI', GOOGLE_CONTACTS_SCOPE . '/contacts');
/**
 * Google contacts group feed URI
 */
define('GOOGLE_CONTACTS_GROUPS_URI', GOOGLE_CONTACTS_SCOPE . '/groups');
/**
 * Google contacts batch feed URI
 */
define(
    'GOOGLE_CONTACTS_BATCH_URI',
    GOOGLE_CONTACTS_URI . '/default/' . GOOGLE_PROJECTION . '/batch'
);
define('GOOGLE_AUTH_SCOPE', 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile');
/**
 * Token info URI
 */
define(
    'GOOGLE_TOKEN_INFO',
    'https://www.googleapis.com/oauth2/v1/tokeninfo?access_token='
);
