-- ZenFusion OAuth - A Google Oauth authorization module for Dolibarr
-- Copyright (C) 2011 Sebastien Bodrero <sbodrero@gpcsolutions.fr>
-- Copyright (C) 2011 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
-- Copyright (C) 2012 Cédric Salvador <csalvador@gpcsolutions.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.
CREATE TABLE IF NOT EXISTS llx_oauth_google_contacts (
  rowid integer NOT NULL auto_increment primary key,
  access_token VARCHAR(255) NULL,
  secret_token VARCHAR(255) NULL,
  email        VARCHAR(255) NULL
) ENGINE = InnoDB;
