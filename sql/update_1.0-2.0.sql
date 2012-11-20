ALTER TABLE llx_oauth_google_contacts CHANGE COLUMN rowid rowid INT(11) NOT NULL;
ALTER TABLE llx_oauth_google_contacts DROP COLUMN access_token;
ALTER TABLE llx_oauth_google_contacts DROP COLUMN secret_token;
ALTER TABLE llx_oauth_google_contacts ADD COLUMN token VARCHAR(255) NULL AFTER rowid;
ALTER TABLE llx_oauth_google_contacts ADD COLUMN scopes VARCHAR(255) NULL AFTER token;

