ALTER TABLE llx_oauth_google_contacts RENAME TO llx_zenfusion_oauth;
UPDATE llx_const SET name="MAIN_MODULE_ZENFUSIONOAUTH" WHERE name="MAIN_MODULE_OAUTHGOOGLECONTACTS";
UPDATE llx_const SET name="MAIN_MODULE_ZENFUSIONOAUTH_TABS_0", value="user:Google:@zenfusionoauth:/zenfusionoauth/initoauth.php?id=__ID__" WHERE name="MAIN_MODULE_OAUTHGOOGLECONTACTS_TABS_0";


