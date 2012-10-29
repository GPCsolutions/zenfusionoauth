<?php
/*
 * ZenFusion OAuth - A Google Oauth authorization module for Dolibarr
 * Copyright (C) 2011 Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * Copyright (C) 2011 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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
 * \file htdocs/oauthgooglecontacts/admin/statistiques.php
 * \ingroup oauthgooglecontacts
 * \brief Google contacts module statistiques page
 * \version development
 */

require("../../main.inc.php");

require_once(DOL_DOCUMENT_ROOT . "/oauthgooglecontacts/lib/zf_oauth.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");

$langs->load("oauthgooglecontacts@oauthgooglecontacts");
$langs->load("admin");
$langs->load("help");

if(isset($_POST["clientId"])){
  dolibarr_set_const($db, "OAUTH2_CLIENT_ID", $_POST["clientId"],'chaine',0,'',$conf->entity);
  unset($_POST["clientId"]);
}
  
if(isset($_POST["clientSecret"])){
  dolibarr_set_const($db, "OAUTH2_CLIENT_SECRET", $_POST["clientSecret"],'chaine',0,'',$conf->entity);
  unset($_POST["clientSecret"]);
}

if(isset($_POST["domainName"])){
  dolibarr_set_const($db, "DOMAIN_NAME", $_POST["domainName"],'chaine',0,'',$conf->entity);
  unset($_POST["domainName"]);
}

if(isset($_POST["domainAdmin"])){
  dolibarr_set_const($db, "DOMAIN_ADMIN", $_POST["domainAdmin"],'chaine',0,'',$conf->entity);
  unset($_POST["domainAdmin"]);
}

if($_POST["apps"]=="yes"){
  if(!$conf->global->DOMAIN_NAME || !$conf->global->DOMAIN_ADMIN){
    $msg = '<div class="error">'.$langs->trans("NeedDomainAndAdmin").'</div>';
  }
  else{
    dolibarr_set_const($db, "USE_APPS_MODE", $_POST["apps"],'chaine',0,'',$conf->entity);
  }
  unset($_POST["apps"]);
}

else if($_POST["apps"]=="no"){
  dolibarr_del_const($db, "USE_APPS_MODE");
  unset($_POST["apps"]);
}
/**
 *view 
 */
llxHeader();
dol_htmloutput_mesg($msg);
$form = new Form($db);
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
// Folder icon title
print_fiche_titre("ZenFusion", $linkback, 'setup');

$head = zf_prepare_head();
dol_fiche_head($head, 'configuration', $langs->trans("Config"), 0);

print_titre($langs->trans("ZenfusionConfig"));
print '<form method="post" action="'.$_SERVER[PHP_SELF].'">';
print '<table class="noborder" width="40%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ClientId").'</td>';
print '<td>'.$langs->trans("ClientSecret").'</td>';
print '<td>'.$langs->trans("domainName").'</td>';
print '<td>'.$langs->trans("Admin").'</td>';
print '<td>'.$langs->trans("AppsMode").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';
print '<tr>';
print '<td><input type="text" name ="clientId" ';
print 'value="'.$conf->global->OAUTH2_CLIENT_ID.'"';
print '/></td>';
print '<td><input type="text" name ="clientSecret" ';
print 'value="'.$conf->global->OAUTH2_CLIENT_SECRET.'"';
print '/></td>';
print '<td><input type="text" name="domainName" value ="'.$conf->global->DOMAIN_NAME.'" /></td>';
print '<td>'.$form->select_dolusers($conf->global->DOMAIN_ADMIN, "domainAdmin").'</td>';
print '<td>'.$form->selectyesno("apps", $conf->global->USE_APPS_MODE).'</td>';
print '<td><input type="submit" value ="'.$langs->trans("Save").'"/></td>';
print '</table>';
print '</form>';
llxFooter();
?>
