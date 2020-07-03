<?php
/* Copyright (C) 2018-2020 Jeremie Ter-Heide  <jeremie@ter-heide.fr>
 *
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
 *   	\file       consumption/propalconsumption.php
 *		\ingroup    Consumption 
 *		\brief      This file manages consumption on orders
 *		\version    $Id: orderconsumption.php,v 1.0 2011/04/28 eldy Exp $
 *		\author		Jérémie TER-HEIDE
 *		\remarks	
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

dol_include_once("/comm/propal/class/propal.class.php");
dol_include_once("/core/lib/propal.lib.php");
dol_include_once("/fichinter/class/fichinter.class.php");
dol_include_once("/core/lib/fichinter.lib.php");
dol_include_once("/projet/class/project.class.php");
dol_include_once("/core/lib/project.lib.php");
dol_include_once("/commande/class/commande.class.php");
dol_include_once("/core/lib/order.lib.php");
dol_include_once("/product/stock/class/entrepot.class.php");
dol_include_once("/product/class/product.class.php");
dol_include_once("/custom/consumption/class/consumption.class.php");
dol_include_once("/core/lib/stock.lib.php");
dol_include_once("/core/lib/product.lib.php");
dol_include_once("/core/lib/date.lib.php");
dol_include_once("/product/class/html.formproduct.class.php");
dol_include_once("/core/class/html.formcompany.class.php");
dol_include_once("/core/class/html.formother.class.php");
$langs->load("consumption@consumption");
$langs->load("bills");
$langs->load("propal");
$langs->load("orders");
$langs->load("interventions");
$langs->load("sendings");
$langs->load("companies");
$langs->load("products");
$langs->load("stocks");
$langs->load("productbatch");
$id='';
$ref='';
if (isset($_GET["type"]))  { $type=$_GET["type"];}
if (isset($_GET["id"]))  { $id=$_GET["id"]; }
if (isset($_GET["ref"])) { $ref=$_GET["ref"]; }
$module=$type;
$headtit=$type;
$headpic=$type;
if($type=='commande'){
	$headtit='CustomerOrder';
	$headpic='order';
}
if($type=='propal'){
	$headtit='Proposal';
	$headpic='propal';
}
if($type=='projet'){
	$type='project';
	$headtit='Project';
	$headpic='project';
}
elseif($type=='ficheinter'){
	$type='fichinter';
	$headtit='InterventionCard';
	$headpic='intervention';
}
if ($id == '' && $ref == '')
{
        dol_print_error('','Bad parameter');
        exit;
}

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
//$result = restrictedArea($user, $module, $id);



/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/
if ($_POST["action"] == "conso" && ! $_POST["cancel"])
 {
         if (is_numeric($_POST["nbpiece"]))
         {
                 $conso = new Consumption($db);
				 $product = new Product($db);
                 $result=$product->fetch($_POST["product"]);
                 $result=$conso->correct_stock($product->id, //produit
				 $user,									//user
                 $_POST["id_entrepot"],					//entrepot
                 $_POST["nbpiece"],						//nb piece
                 1,										//Direction of movement:0=input (stock increase by a stock transfer), 1=output (stock decrease after by a stock transfer),2=output (stock decrease), 3=input (stock increase)
                 $_POST["label"],						//label
                 0,										//price
				 '',									//inventorycode
				 $type,									//origintype & id
				 $_GET["id"],							//
				 $_POST["eatby"],						//eat-by date. Will be used if lot does not exists yet and will be created.
				 $_POST["sellby"],					//sell-by date. Will be used if lot does not exists yet and will be created.
				 $_POST["batch_number"]);				//batch number

                 if ($result > 0)
                 {
                         header("Location: card.php?id=".$id."&type=".$module);
                         exit;
                 }
         }
 }

/***************************************************
* PAGE
*
****************************************************/

llxHeader('',$langs->trans("StockConsumption"),'');
	$form = new Form($db);
	$userstatic=new User($db);
	$formproduct=new FormProduct($db);
	$classname = ucfirst($type);
	$object = new $classname($db);
	$object->fetch($_GET["id"],$_GET["ref"]);
	$conso = new Consumption($db);
	$html=new Form($db);
	$soc = new Societe($db, $object->socid);
	$soc->fetch($object->socid);
	$fonction = $type.'_prepare_head';
	$head = $fonction($object);
	$nbmvt=$conso->countconso($object);
	if($nbmvt > 0){
		foreach($head as $key=>$tab){
			if($tab[2]=='conso'){
				$head[$key][1].=' <span class="badge">'. $nbmvt .'</span>';
			}
		}
	}
	dol_fiche_head($head, 'conso', $langs->trans($headtit), 0, $headpic);
	print '<table class="border" width="100%">';
	// Ref
	print '<tr><td width="18%">'.$langs->trans("Ref").'</td><td colspan="3">';
	if($type=='project'){
		$projectsListId = $object->getProjectsAuthorizedForUser($user,$mine,1);
		$object->next_prev_filter=" rowid in (".$projectsListId.")";
		print $form->showrefnav($object,'ref','',1,'ref','ref','','&type=project');
		print '</td></tr>';
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->title.'</td></tr>';
		print '<tr><td>'.$langs->trans("Company").'</td><td>';
		if (! empty($object->societe->id)) print $object->societe->getNomUrl(1);
		else print '&nbsp;';
		print '</td></tr>';
		// Visibility
		print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
		if ($object->public) print $langs->trans('SharedProject');
		else print $langs->trans('PrivateProject');
		print '</td></tr>';
		// Statut
		print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';
		print '</table>';
		print '</div>';
		
	}
	else{
		print $form->showrefnav($object,'ref','',1,'ref','ref','','&type='.$type);
		print "</td></tr>";
		// Ref commande client
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
		print $langs->trans('RefCustomer').'</td><td align="left">';
		print '</td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		print $object->ref_client;
		print '</td>';
		print '</tr>';

		// Customer
		if ( is_null($object->thirdparty) )
			 $object->fetch_thirdparty();

		print "<tr><td>".$langs->trans("Company")."</td>";
		print '<td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';
		print "</table>";

		print '</div>';
	}
	$conso->showformwrite($user,$module,$object,$formproduct,$html,$conf);
	$conso->showformview($user,$module,$object,$formproduct,$html,$conf);
