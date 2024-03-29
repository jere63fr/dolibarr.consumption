<?php
/*
 * Copyright (C) 2018-2021 Jeremie Ter-Heide  <jeremie@ter-heide.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */


/**
 *      \file       htdocs/custom/admin/setup.php
 *		\ingroup    facture
 *		\brief      Page to setup consumption module
 */

/**
 * @global $conf
 * @global $langs
 * @global $user
 * @global $db
 * @global $error
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../lib/consumption.lib.php';

$langs->loadLangs(
	array(
		'admin',
		'errors',
		'other',
		'consumption@consumption',
	)
);

if (!$user->admin) {
	accessforbidden();
}

/*
 * Actions
 */
$action = GETPOST('action','alpha');

if ($action == 'updateprefix') {
    $prefix=GETPOST('prefix','alpha');
    $res = dolibarr_set_const( $db, 'CONSUMPTION_INVCODEPREFIX', $prefix,'chaine',0, $langs->trans( 'CONSUMPTION_SEARCHMODE_DESC' ), $conf->entity);

	if (!$res > 0) $error++;

 	if (!$error) {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }

} elseif ($action == 'updatesearchmode') {
    $prefix = GETPOST('searchmode','alpha');
    $res    = dolibarr_set_const( $db, 'CONSUMPTION_SEARCHMODE', $prefix, 'chaine', 0, $langs->trans( 'CONSUMPTION_INVCODEPREFIX_DESC' ), $conf->entity);

	if (!$res > 0) $error++;

 	if (!$error) {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}
/*
 * View
 */
$form       = new Form($db);
$head       = ConsumptionAdminPrepareHead();
$dirmodels  = array_merge(array('/'),(array) $conf->modules_parts['models']);
$linkback   = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

llxHeader("",$langs->trans("ConsumptionManagement"),'');

print load_fiche_titre($langs->trans("ConsumptionManagement"), $linkback, 'title_setup');
print dol_get_fiche_head($head, 'settings', $langs->trans("Module9789Name"), 0, 'consumption@consumption');

print '<table class="noborder" width="100%">';
//print '<tr class="liste_titre">';
//print '<td width="40%">'.$langs->trans("PrefixInvcod").'</td>';
//print '<td width="40%"></td>';
//print '<td width="20%"></td>';
//print '</tr>'."\n";

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="action" value="updateprefix">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<tr class="oddeven"><td>'.$langs->trans("PrefixInvcod").'</td>';
print '<td><input type="text" name="prefix" value="'.$conf->global->CONSUMPTION_INVCODEPREFIX.'"></td>';
print '<td><input type="submit" class="button button-save" name="save" value="'.$langs->trans('Save').'"></td>';
print "</tr></form>";

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="action" value="updatesearchmode">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<tr class="oddeven"><td>'.$langs->trans("searchMode").'</td>';
print '<td><select name="searchmode">';
for ($i=1; $i<=4; $i++){
	$sel='';
	if($conf->global->CONSUMPTION_SEARCHMODE==$i){
		$sel='selected';
	}
	print '<option value="'.$i.'" '.$sel.'>'.$langs->trans("search".$i).'</option>';
}
print '</select></td>';
print '<td><input type="submit" class="button button-save" name="save" value="'.$langs->trans('Save').'"></td>';
print "</tr></form>";
print '</table>';

print dol_get_fiche_end();

llxFooter();

$db->close();
