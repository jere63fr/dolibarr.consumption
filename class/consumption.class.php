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
 *   		\file       consumption/class/consumption.class.php
 *		\ingroup    Consumption
 *		\brief      This file manages consumption
 *		\author		Jeremie TER-HEIDE
 *		\remarks
 */
 require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';
 require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
 //require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
 /**
 *	Classe des gestion des consommations
 */
class Consumption extends CommonObject {

	public $consotype = 'consotype';

	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
 	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->consotype = '';

	}

	public function correct_stock( $productid, $user, $id_entrepot, $nbpiece, $movement, $label = '', $price = 0, $inventorycode = '', $origin_element = '', $origin_id = null, $eatby = '', $sellby = '', $batch = '', $datem = '' ) {

		if ($id_entrepot)
		{
			$this->db->begin();
			global $conf;
			require_once DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php';
			require_once DOL_DOCUMENT_ROOT .'/product/class/product.class.php';

			$product = new Product($this->db);
			$product->fetch($productid);
			if($product->pmp>0){
				$price=$product->pmp;
			}
			else{
				$price=$product->cost_price;
			}
			$op[0] = "+".trim($nbpiece);
			$op[1] = "-".trim($nbpiece);
			$movementstock=new MouvementStock($this->db);
			$classname = ucfirst($origin_element);
			$origin = new $classname($this->db);
			$res=$origin->fetch($origin_id);
			$movementstock->origin = $origin;
			$movementstock->origin->id = $origin_id;
			$inventorycode=$conf->global->CONSUMPTION_INVCODEPREFIX.$movementstock->origin->ref.dol_print_date(dol_now(),'%y%m%d%H%M%S');
			$date = strtotime($datem);
			$result = $movementstock->_create($user,$productid,$id_entrepot,$op[$movement],$movement,$price,$label,$inventorycode,$date,$eatby,$sellby,$batch);

			if ($result >= 0)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
			    $this->error  = $movementstock->error;
			    $this->errors = $movementstock->errors;
				$this->db->rollback();
				return -1;
			}
		}
	}

	public function showformwrite( $user, $consotype, $object, $formproduct, $html ) {

		global $langs, $db, $conf;

		$productstatic   = new Product( $db );
		$warehousestatic = new Entrepot( $db );
		$userstatic      = new User( $db );
		$form            = new Form ( $db );

		$page    = "card.php?type=" . $consotype . "&id=";
		$right   = false;
		$libelle = '';

		switch ( $consotype ) {
			case 'project':
				$right   = $object->statut > 0 && $user->rights->consumption->writeproject;
				$libelle = $langs->trans( "ProjectConsumption" );
				break;
			case 'user':
				$right   = $object->statut > 0 && $user->rights->consumption->writeuser;
				$libelle = $langs->trans( "UserConsumption" ) . ' \'' . $object->login . '\' ';
				break;
			case 'commande':
				$right   = $object->statut > 0 && $user->rights->consumption->writeorder;
				$libelle = $langs->trans( "OrderConsumption" );
				break;
			case 'ficheinter':
				$right   = $object->statut > 0 && $user->rights->consumption->writeintervention;
				$libelle = $langs->trans( "InterConsumption" );
				break;
			case 'propal':
				$right   = $object->statut > 0 && $user->rights->consumption->writepropal;
				$libelle = $langs->trans( "PropalConsumption" );
				break;
		}

		if ( $right ) {
			//form for consumption
			print load_fiche_titre($langs->trans("Consumption"), '', 'generic');

			print "<form action=\"".$page.$_GET["id"]."\" method=\"post\">";
			print dol_get_fiche_head();
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="conso">';

			print '<table class="border centpercent">';
			print '<tbody>';

			print '<tr>'; // Row1

			// Product
			print '<td class="fieldrequired">'.$langs->trans("Product").'</td><td>';
			$html->select_produits('','product',0,$conf->product->limit_size,0,-1,2);
			print '</td>';

			// Amount
			print '<td class="fieldrequired">'.$langs->trans("NumberOfUnit").'</td><td>';
			print '<input class="flat" name="nbpiece" size="10" value="">';
			print '</td>';

			// Warehouse
			print '<td class="fieldrequired">'.$langs->trans("Warehouse").'</td><td>';
			print $formproduct->selectWarehouses(($_GET["dwid"]?$_GET["dwid"]:GETPOST('id_entrepot')),'id_entrepot','',1);
			print '</td>';

			print '</tr>'; // END Row2
			print '<tr>'; // Row2

			// Label
			print '<td>'.$langs->trans("MovementLabel").'</td><td>';
			print '<input type="text" name="label" size="65" value="'.$libelle.' ('.$object->ref.')">';
			print '</td>';

			// Batch
			if ( !empty( $conf->productbatch->enabled ) ) {
				print '<td>'.$langs->trans("batch_number").'</td><td>';
				print '<input type="text" name="batch_number" size="40" value="'.GETPOST("batch_number").'">';
				print '</td>';
			}

			// Date
			print '<td>' . $langs->trans( "ConsumptionDate" ) . '</td><td>';
			print $form->selectDate( '', 'datem', '', '', 0, "" );
			print '</td>';

			print '</tr>'; // END Row2

			if (empty($conf->global->PRODUCT_DISABLE_EATBY) || empty($conf->global->PRODUCT_DISABLE_SELLBY)) {
				print '<tr>';
				if ( empty( $conf->global->PRODUCT_DISABLE_EATBY ) ) {
					print '<td>' . $langs->trans( "EatByDate" ) . '</td><td>';
					$eatbyselected = dol_mktime( 0, 0, 0, GETPOST( 'eatbymonth' ), GETPOST( 'eatbyday' ), GETPOST( 'eatbyyear' ) );
					print $form->selectDate( $eatbyselected, 'eatby', '', '', 1, "" );
					print '</td>';
				}
				if ( empty( $conf->global->PRODUCT_DISABLE_SELLBY ) ) {
					print '<td>' . $langs->trans( "SellByDate" ) . '</td><td>';
					$sellbyselected = dol_mktime( 0, 0, 0, GETPOST( 'sellbymonth' ), GETPOST( 'sellbyday' ), GETPOST( 'sellbyyear' ) );
					print $form->selectDate( $sellbyselected, 'sellby', '', '', 1, "" );
					print '</td>';
				}
				print '</tr>';
			}

			print '</tbody>';

			print '</table>';

			print '</div>';

			print '<div class="center">';

			print '<input type="submit" class="button butActionNew" value="'.$langs->trans('ConsumptionBtnRegister').'">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';
				print '</form>';
			print '<br /><br />';


		}
	}

	public function showformview( $user, $consotype, $object, $formproduct, $html ) {

		global $langs, $db, $conf, $hookmanager;

		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		if (! empty($conf->projet->enabled))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
			require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
		}

		$langs->loadLangs(
			array(
				'products',
				'stocks',
			)
		);

		if (! empty($conf->productbatch->enabled)) $langs->load("productbatch");

		// Security check
		$result=restrictedArea($user,'stock');

		$id=GETPOST('id','int');
		$type=GETPOST('type','alpha');
		$ref = GETPOST('ref','alpha');
		$msid=GETPOST('msid','int');
		$product_id=GETPOST("product_id");
		$action=GETPOST('action','aZ09');
		$cancel=GETPOST('cancel','alpha');
		$idproduct = GETPOST('idproduct','int');
		$year = GETPOST("year");
		$month = GETPOST("month");
		$search_ref = GETPOST('search_ref', 'alpha');
		$search_movement = GETPOST("search_movement");
		$search_product_ref = trim(GETPOST("search_product_ref"));
		$search_product = trim(GETPOST("search_product"));
		$search_warehouse = trim(GETPOST("search_warehouse"));
		$search_inventorycode = trim(GETPOST("search_inventorycode"));
		$search_user = trim(GETPOST("search_user"));
		$search_batch = trim(GETPOST("search_batch"));
		$search_qty = trim(GETPOST("search_qty"));

		$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
		$page = GETPOST("page",'int');
		if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
		$sortfield = GETPOST("sortfield",'alpha');
		$sortorder = GETPOST("sortorder",'alpha');
		if ($page < 0) $page = 0;
		$offset = $limit * $page;
		if (! $sortfield) $sortfield="m.datem";
		if (! $sortorder) $sortorder="DESC";

		$pdluoid=GETPOST('pdluoid','int');

		// Initialize context for list
		$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'movementlist';

		$extrafields = new ExtraFields($db);

		// fetch optionals attributes and labels
		$extralabels = $extrafields->fetch_name_optionals_label('movement');
		$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

		$arrayfields=array(
			'm.rowid'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
			'm.datem'=>array('label'=>$langs->trans("Date"), 'checked'=>1),
			'p.ref'=>array('label'=>$langs->trans("ProductRef"), 'checked'=>1),
			'p.label'=>array('label'=>$langs->trans("ProductLabel"), 'checked'=>1),
			'm.batch'=>array('label'=>$langs->trans("BatchNumberShort"), 'checked'=>1, 'enabled'=>(! empty($conf->productbatch->enabled))),
			'pl.eatby'=>array('label'=>$langs->trans("EatByDate"), 'checked'=>0, 'enabled'=>(! empty($conf->productbatch->enabled))),
			'pl.sellby'=>array('label'=>$langs->trans("SellByDate"), 'checked'=>0, 'position'=>10, 'enabled'=>(! empty($conf->productbatch->enabled))),
			'e.ref'=>array('label'=>$langs->trans("Warehouse"), 'checked'=>1, 'enabled'=>(! $id > 0)),	// If we are on specific warehouse, we hide it
			'm.fk_user_author'=>array('label'=>$langs->trans("Author"), 'checked'=>0),
			'm.inventorycode'=>array('label'=>$langs->trans("InventoryCodeShort"), 'checked'=>1),
			'm.label'=>array('label'=>$langs->trans("MovementLabel"), 'checked'=>1),
			'origin'=>array('label'=>$langs->trans("Origin"), 'checked'=>1),
			'm.value'=>array('label'=>$langs->trans("Qty"), 'checked'=>1),
		);

		$object = new MouvementStock($db);	// To be passed as parameter of executeHooks that need

		include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

		// Do we click on purge search criteria ?
		if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // Both test are required to be compatible with all browsers
		{
			$year='';
			$month='';
			$search_ref='';
			$search_movement="";
			$search_product_ref="";
			$search_product="";
			$search_warehouse="";
			$search_user="";
			$search_batch="";
			$search_qty='';
			$sall="";
			$toselect='';
			$search_array_options=array();
		}


		/*
		 * View
		 */

		$productlot=new ProductLot($db);
		$productstatic=new Product($db);
		$warehousestatic=new Entrepot($db);
		$movement=new MouvementStock($db);
		$userstatic=new User($db);
		$form=new Form($db);
		$formother=new FormOther($db);
		$formproduct=new FormProduct($db);
		if (!empty($conf->projet->enabled)) $formproject=new FormProjets($db);

		$sql = "SELECT p.rowid, p.ref as product_ref, p.label as produit, p.fk_product_type as type, p.entity,";
		$sql.= " e.ref as stock, e.rowid as entrepot_id, e.lieu,";
		$sql.= " m.rowid as mid, m.value as qty, m.datem, m.fk_user_author, m.label, m.inventorycode, m.fk_origin, m.origintype,";
		$sql.= " m.batch,";
		$sql.= " pl.rowid as lotid, pl.eatby, pl.sellby,";
		$sql.= " u.login, u.photo, u.lastname, u.firstname";
		// Add fields from extrafields
		foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
		$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
		$sql.= " ".MAIN_DB_PREFIX."product as p,";
		$sql.= " ".MAIN_DB_PREFIX."stock_mouvement as m";
		if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."movement_extrafields as ef on (m.rowid = ef.fk_object)";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON m.fk_user_author = u.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_lot as pl ON m.batch = pl.batch AND m.fk_product = pl.fk_product";
		$sql.= " WHERE m.fk_product = p.rowid";
		if ($msid > 0) $sql .= " AND m.rowid = ".$msid;
		$sql.= " AND m.fk_entrepot = e.rowid";
		$sql.= " AND e.entity IN (".getEntity('stock').")";
		switch ($conf->global->CONSUMPTION_SEARCHMODE)
		{
			case 1:
				$sql.= " AND m.label LIKE '%".addslashes($object->ref)."%'";
				break;
			case 2:
				$sql.= " AND m.inventorycode LIKE '".addslashes($conf->global->CONSUMPTION_INVCODEPREFIX.$object->ref)."%'";
				break;
			case 3:
				$sql.= " AND  (m.inventorycode LIKE '".addslashes($conf->global->CONSUMPTION_INVCODEPREFIX.$object->ref)."%' OR m.label LIKE '%".addslashes($object->ref)."%')";
				break;
			case 4:
				$sql.= " AND  (m.origintype = '".$object->element."' AND m.fk_origin = '".$object->id."')";
				break;
		}
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) $sql.= " AND p.fk_product_type = 0";
		if ($month > 0)
		{
			if ($year > 0)
			$sql.= " AND m.datem BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
			else
			$sql.= " AND date_format(m.datem, '%m') = '$month'";
		}
		else if ($year > 0)
		{
			$sql.= " AND m.datem BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
		}
		if (! empty($search_ref))			$sql.= natural_search('m.rowid', $search_ref, 1);
		if (! empty($search_movement))      $sql.= natural_search('m.label', $search_movement);
		if (! empty($search_inventorycode)) $sql.= natural_search('m.inventorycode', $search_inventorycode);
		if (! empty($search_product_ref))   $sql.= natural_search('p.ref', $search_product_ref);
		if (! empty($search_product))       $sql.= natural_search('p.label', $search_product);
		if ($search_warehouse > 0)          $sql.= " AND e.rowid = '".$db->escape($search_warehouse)."'";
		if (! empty($search_user))          $sql.= natural_search('u.login', $search_user);
		if (! empty($search_batch))         $sql.= natural_search('m.batch', $search_batch);
		if ($search_qty != '')				$sql.= natural_search('m.value', $search_qty, 1);
		// Add where from extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

		$sql.= $db->order($sortfield,$sortorder);
		//echo $conf->global->CONSUMPTION_SEARCHMODE." ".$sql;exit;
		$nbtotalofrecords = '';
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
		{
			$result = $db->query($sql);
			$nbtotalofrecords = $db->num_rows($result);
		}

		$sql.= $db->plimit($limit+1, $offset);

		//print $sql;

		$resql = $db->query($sql);
		if ($resql)
		{
			$product = new Product($db);
			$object = new Entrepot($db);

			if ($idproduct > 0)
			{
				$product->fetch($idproduct);
			}
			if ($id > 0 || $ref)
			{
				$result = $object->fetch($id, $ref);
				if ($result < 0)
				{
					dol_print_error($db);
				}
			}

			$num = $db->num_rows($resql);

			$arrayofselected=is_array($toselect)?$toselect:array();


			$i = 0;
			$help_url='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
			if ($msid) $texte = $langs->trans('StockMovementForId', $msid);
			else
			{
				$texte = $langs->trans("ConsumptionListOfConsumption");
				if ($id) $texte.=' ('.$langs->trans("ForThis".$type).')';
			}

			$param='';
			if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
			if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
			if ($id > 0)                 $param.='&id='.$id;
			if ($search_movement)        $param.='&search_movement='.urlencode($search_movement);
			if ($search_inventorycode)   $param.='&search_inventorycode='.urlencode($search_inventorycode);
			if ($search_product_ref)     $param.='&search_product_ref='.urlencode($search_product_ref);
			if ($search_product)         $param.='&search_product='.urlencode($search_product);
			if ($search_batch)           $param.='&search_batch='.urlencode($search_batch);
			if ($search_warehouse > 0)   $param.='&search_warehouse='.urlencode($search_warehouse);
			if (!empty($sref))           $param.='&sref='.urlencode($sref); // FIXME $sref is not defined
			if (!empty($snom))           $param.='&snom='.urlencode($snom); // FIXME $snom is not defined
			if ($search_user)            $param.='&search_user='.urlencode($search_user);
			if ($idproduct > 0)          $param.='&idproduct='.$idproduct;
			// Add $param from extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';


			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'&type='.$type.'">';
			if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
			print '<input type="hidden" name="action" value="list">';
			print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
			print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
			print '<input type="hidden" name="page" value="'.$page.'">';
			print '<input type="hidden" name="type" value="'.$type.'">';
			print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
			//if ($id > 0) print '<input type="hidden" name="id" value="'.$id.'">';

			//if ($id > 0) print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,$massactionbutton,$num, $nbtotalofrecords, '', 0, '', '', $limit);
			print_barre_liste($texte, $page, $_SERVER["PHP_SELF"].'?id='.$id.'&type=projet', $param, $sortfield, $sortorder,$massactionbutton,$num, $nbtotalofrecords, 'title_generic', 0, '', '', $limit);

			if ($sall)
			{
				foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
				print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
			}

			$moreforfilter='';


			if (! empty($moreforfilter))
			{
				print '<div class="liste_titre liste_titre_bydiv centpercent">';
				print $moreforfilter;
				print '</div>';
			}

			$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
			$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields

			print '<div class="div-table-responsive">';
			print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

			// Lignes des champs de filtre
			print '<tr class="liste_titre_filter">';
			if (! empty($arrayfields['m.rowid']['checked']))
			{
				// Ref
				print '<td class="liste_titre" align="left">';
				print '<input class="flat maxwidth25" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
				print '</td>';
			}
			if (! empty($arrayfields['m.datem']['checked']))
			{
				print '<td class="liste_titre" valign="right">';
				print '<input class="flat" type="text" size="2" maxlength="2" placeholder="'.dol_escape_htmltag($langs->trans("Month")).'" name="month" value="'.$month.'">';
				if (empty($conf->productbatch->enabled)) print '&nbsp;';
				//else print '<br>';
				$syear = $year?$year:-1;
				print '<input class="flat" type="text" size="3" maxlength="4" placeholder="'.dol_escape_htmltag($langs->trans("Year")).'" name="year" value="'.($syear > 0 ? $syear : '').'">';
				//print $formother->selectyear($syear,'year',1, 20, 5);
				print '</td>';
			}
			if (! empty($arrayfields['p.ref']['checked']))
			{
				// Product Ref
				print '<td class="liste_titre" align="left">';
				print '<input class="flat maxwidth100" type="text" name="search_product_ref" value="'.dol_escape_htmltag($idproduct?$product->ref:$search_product_ref).'">';
				print '</td>';
			}
			if (! empty($arrayfields['p.label']['checked']))
			{
				// Product label
				print '<td class="liste_titre" align="left">';
				print '<input class="flat maxwidth100" type="text" name="search_product" value="'.dol_escape_htmltag($idproduct?$product->label:$search_product).'">';
				print '</td>';
			}
			// Batch
			if (! empty($arrayfields['m.batch']['checked']))
			{
				print '<td class="liste_titre" align="center"><input class="flat maxwidth100" type="text" name="search_batch" value="'.dol_escape_htmltag($search_batch).'"></td>';
			}
			if (! empty($arrayfields['pl.eatby']['checked']))
			{
				print '<td class="liste_titre" align="left">';
				print '</td>';
			}
			if (! empty($arrayfields['pl.sellby']['checked']))
			{
				print '<td class="liste_titre" align="left">';
				print '</td>';
			}
			// Warehouse
			if (! empty($arrayfields['e.ref']['checked']))
			{
				print '<td class="liste_titre maxwidthonsmartphone" align="left">';
				//print '<input class="flat" type="text" size="8" name="search_warehouse" value="'.($search_warehouse).'">';
				print $formproduct->selectWarehouses($search_warehouse, 'search_warehouse', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, null, 'maxwidth200');
				print '</td>';
			}
			if (! empty($arrayfields['m.fk_user_author']['checked']))
			{
				// Author
				print '<td class="liste_titre" align="left">';
				print '<input class="flat" type="text" size="6" name="search_user" value="'.dol_escape_htmltag($search_user).'">';
				print '</td>';
			}
			if (! empty($arrayfields['m.inventorycode']['checked']))
			{
				// Inventory code
				print '<td class="liste_titre" align="left">';
				print '<input class="flat" type="text" size="4" name="search_inventorycode" value="'.dol_escape_htmltag($search_inventorycode).'">';
				print '</td>';
			}
			if (! empty($arrayfields['m.label']['checked']))
			{
				// Label of movement
				print '<td class="liste_titre" align="left">';
				print '<input class="flat" type="text" size="8" name="search_movement" value="'.dol_escape_htmltag($search_movement).'">';
				print '</td>';
			}
			if (! empty($arrayfields['origin']['checked']))
			{
				// Origin of movement
				print '<td class="liste_titre" align="left">';
				print '&nbsp; ';
				print '</td>';
			}
			if (! empty($arrayfields['m.value']['checked']))
			{
				// Qty
				print '<td class="liste_titre" align="right">';
				print '<input class="flat" type="text" size="4" name="search_qty" value="'.dol_escape_htmltag($search_qty).'">';
				print '</td>';
			}
			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
			// Fields from hook
			//$parameters=array('arrayfields'=>$arrayfields);
			//$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
			//print $hookmanager->resPrint;
			// Date creation
			if (! empty($arrayfields['m.datec']['checked']))
			{
				print '<td class="liste_titre">';
				print '</td>';
			}
			// Date modification
			if (! empty($arrayfields['m.tms']['checked']))
			{
				print '<td class="liste_titre">';
				print '</td>';
			}
			// Actions
			print '<td class="liste_titre" align="right">';
			$searchpicto=$form->showFilterAndCheckAddButtons(0);
			print $searchpicto;
			print '</td>';
			print "</tr>\n";

			print '<tr class="liste_titre">';
			if (! empty($arrayfields['m.rowid']['checked']))            print_liste_field_titre($arrayfields['m.rowid']['label'],$_SERVER["PHP_SELF"],'m.rowid','',$param,'',$sortfield,$sortorder);
			if (! empty($arrayfields['m.datem']['checked']))            print_liste_field_titre($arrayfields['m.datem']['label'],$_SERVER["PHP_SELF"],'m.datem','',$param,'',$sortfield,$sortorder);
			if (! empty($arrayfields['p.ref']['checked']))              print_liste_field_titre($arrayfields['p.ref']['label'],$_SERVER["PHP_SELF"],'p.ref','',$param,'',$sortfield,$sortorder);
			if (! empty($arrayfields['p.label']['checked']))            print_liste_field_titre($arrayfields['p.label']['label'],$_SERVER["PHP_SELF"],'p.label','',$param,'',$sortfield,$sortorder);
			if (! empty($arrayfields['m.batch']['checked']))            print_liste_field_titre($arrayfields['m.batch']['label'],$_SERVER["PHP_SELF"],'m.batch','',$param,'align="center"',$sortfield,$sortorder);
			if (! empty($arrayfields['pl.eatby']['checked']))           print_liste_field_titre($arrayfields['pl.eatby']['label'],$_SERVER["PHP_SELF"],'pl.eatby','',$param,'align="center"',$sortfield,$sortorder);
			if (! empty($arrayfields['pl.sellby']['checked']))          print_liste_field_titre($arrayfields['pl.sellby']['label'],$_SERVER["PHP_SELF"],'pl.sellby','',$param,'align="center"',$sortfield,$sortorder);
			if (! empty($arrayfields['e.ref']['checked']))  	      	print_liste_field_titre($arrayfields['e.ref']['label'],$_SERVER["PHP_SELF"], "e.ref","",$param,"",$sortfield,$sortorder);	// We are on a specific warehouse card, no filter on other should be possible
			if (! empty($arrayfields['m.fk_user_author']['checked']))   print_liste_field_titre($arrayfields['m.fk_user_author']['label'],$_SERVER["PHP_SELF"], "m.fk_user_author","",$param,"",$sortfield,$sortorder);
			if (! empty($arrayfields['m.inventorycode']['checked']))    print_liste_field_titre($arrayfields['m.inventorycode']['label'],$_SERVER["PHP_SELF"], "m.inventorycode","",$param,"",$sortfield,$sortorder);
			if (! empty($arrayfields['m.label']['checked']))            print_liste_field_titre($arrayfields['m.label']['label'],$_SERVER["PHP_SELF"], "m.label","",$param,"",$sortfield,$sortorder);
			if (! empty($arrayfields['origin']['checked']))             print_liste_field_titre($arrayfields['origin']['label'],$_SERVER["PHP_SELF"], "","",$param,"",$sortfield,$sortorder);
			if (! empty($arrayfields['m.value']['checked']))            print_liste_field_titre($arrayfields['m.value']['label'],$_SERVER["PHP_SELF"], "m.value","",$param,'align="right"',$sortfield,$sortorder);
			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
			if (! empty($arrayfields['m.datec']['checked']))     print_liste_field_titre($arrayfields['p.datec']['label'],$_SERVER["PHP_SELF"],"p.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
			if (! empty($arrayfields['m.tms']['checked']))       print_liste_field_titre($arrayfields['p.tms']['label'],$_SERVER["PHP_SELF"],"p.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
			print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"].'?id='.$id.'&type=projet',"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
			print "</tr>\n";


			$arrayofuniqueproduct=array();

			while ($i < min($num,$limit))
			{
				$objp = $db->fetch_object($resql);

				$userstatic->id=$objp->fk_user_author;
				$userstatic->login=$objp->login;
				$userstatic->lastname=$objp->lastname;
				$userstatic->firstname=$objp->firstname;
				$userstatic->photo=$objp->photo;

				$productstatic->id=$objp->rowid;
				$productstatic->ref=$objp->product_ref;
				$productstatic->label=$objp->produit;
				$productstatic->type=$objp->type;
				$productstatic->entity=$objp->entity;

				$productlot->id = $objp->lotid;
				$productlot->batch= $objp->batch;
				$productlot->eatby= $objp->eatby;
				$productlot->sellby= $objp->sellby;

				$warehousestatic->id=$objp->entrepot_id;
				$warehousestatic->libelle=$objp->stock;
				$warehousestatic->lieu=$objp->lieu;

				$arrayofuniqueproduct[$objp->rowid]=$objp->produit;
				if(!empty($objp->fk_origin)) {
					$origin = $movement->get_origin($objp->fk_origin, $objp->origintype);
				} else {
					$origin = '';
				}

				print "<tr>";
				// Id movement
				if (! empty($arrayfields['m.rowid']['checked']))
				{
					print '<td>'.$objp->mid.'</td>';	// This is primary not movement id
				}
				if (! empty($arrayfields['m.datem']['checked']))
				{
					// Date
					print '<td>'.dol_print_date($db->jdate($objp->datem),'dayhour').'</td>';
				}
				if (! empty($arrayfields['p.ref']['checked']))
				{
					// Product ref
					print '<td>';
					print $productstatic->getNomUrl(1,'stock',16);
					print "</td>\n";
				}
				if (! empty($arrayfields['p.label']['checked']))
				{
					// Product label
					print '<td>';
					print $productstatic->label;
					print "</td>\n";
				}
				if (! empty($arrayfields['m.batch']['checked']))
				{
					print '<td align="center">';
					if ($productlot->id > 0) print $productlot->getNomUrl(1);
					else print $productlot->batch;		// the id may not be defined if movement was entered when lot was not saved or if lot was removed after movement.
					print '</td>';
				}
				if (! empty($arrayfields['pl.eatby']['checked']))
				{
					print '<td align="center">'. dol_print_date($objp->eatby,'day') .'</td>';
				}
				if (! empty($arrayfields['pl.sellby']['checked']))
				{
					print '<td align="center">'. dol_print_date($objp->sellby,'day') .'</td>';
				}
				// Warehouse
				if (! empty($arrayfields['e.ref']['checked']))
				{
					print '<td>';
					print $warehousestatic->getNomUrl(1);
					print "</td>\n";
				}
				// Author
				if (! empty($arrayfields['m.fk_user_author']['checked']))
				{
					print '<td class="tdoverflowmax100">';
					print $userstatic->getNomUrl(-1);
					print "</td>\n";
				}
				if (! empty($arrayfields['m.inventorycode']['checked']))
				{
					// Inventory code
					print '<td>'.$objp->inventorycode.'</td>';
				}
				if (! empty($arrayfields['m.label']['checked']))
				{
					// Label of movement
					print '<td class="tdoverflowmax100aaa">'.$objp->label.'</td>';
				}
				if (! empty($arrayfields['origin']['checked']))
				{
					// Origin of movement
					print '<td>'.$origin.'</td>';
				}
				if (! empty($arrayfields['m.value']['checked']))
				{
					// Qty
					print '<td align="right">';
					if ($objp->qt > 0) print '+';
					print $objp->qty;
					print '</td>';
				}
				// Action column
				print '<td class="nowrap" align="center">';
				print '</td>';
				if (! $i) $totalarray['nbfield']++;

				print "</tr>\n";
				$i++;
			}
			$db->free($resql);

			print "</table>";
			print '</div>';
			print "</form>";

			// Add number of product when there is a filter on period
			if (count($arrayofuniqueproduct) == 1 && is_numeric($year))
			{
				print "<br>";

				$productidselected=0;
				foreach ($arrayofuniqueproduct as $key => $val)
				{
					$productidselected=$key;
					$productlabelselected=$val;
				}
				$datebefore=dol_get_first_day($year?$year:strftime("%Y",time()), $month?$month:1, true);
				$dateafter=dol_get_last_day($year?$year:strftime("%Y",time()), $month?$month:12, true);
				$balancebefore=$movement->calculateBalanceForProductBefore($productidselected, $datebefore);
				$balanceafter=$movement->calculateBalanceForProductBefore($productidselected, $dateafter);

				//print '<tr class="total"><td class="liste_total">';
				print $langs->trans("NbOfProductBeforePeriod", $productlabelselected, dol_print_date($datebefore,'day','gmt'));
				//print '</td>';
				//print '<td class="liste_total" colspan="6" align="right">';
				print ': '.$balancebefore;
				print "<br>\n";
				//print '</td></tr>';
				//print '<tr class="total"><td class="liste_total">';
				print $langs->trans("NbOfProductAfterPeriod", $productlabelselected, dol_print_date($dateafter,'day','gmt'));
				//print '</td>';
				//print '<td class="liste_total" colspan="6" align="right">';
				print ': '.$balanceafter;
				print "<br>\n";
				//print '</td></tr>';
			}
		}
		else
		{
			dol_print_error($db);
		}

		llxFooter();

		$db->close();


	}

	/**
	 * @param $object
	 *
	 * @return int
	 */
	public function countconso( $object ) {

		global $db, $conf;

		$sql = "SELECT * FROM";
		$sql.= " ".MAIN_DB_PREFIX."stock_mouvement as m WHERE";
		switch ( $conf->global->CONSUMPTION_SEARCHMODE ) {
			case 1:
				$sql .= " m.label LIKE '%" . addslashes( $object->ref ) . "%'";
				break;
			case 2:
				$sql .= " m.inventorycode LIKE '" . addslashes( $conf->global->CONSUMPTION_INVCODEPREFIX . $object->ref ) . "%'";
				break;
			case 3:
				$sql .= " (m.inventorycode LIKE '" . addslashes( $conf->global->CONSUMPTION_INVCODEPREFIX . $object->ref ) . "%' OR m.label LIKE '%" . addslashes( $object->ref ) . "%')";
				break;
			case 4:
				$sql .= " (m.origintype = '" . $object->element . "' AND m.fk_origin = '" . $object->id . "')";
				break;
		}
		$nbtotalofrecords = 0;
		$result = $db->query($sql);
		$nbtotalofrecords = $db->num_rows($result);

		return $nbtotalofrecords;
	}

	public function fetchconso( $object ) {

		global $conf,$user,$db;

		$sql = "SELECT p.rowid, p.ref as product_ref, p.label as produit, p.fk_product_type as type, p.entity,";
		$sql.= " e.ref as stock, e.rowid as entrepot_id, e.lieu,";
		$sql.= " m.rowid as mid, m.value as qty, m.datem, m.fk_user_author, m.label, m.inventorycode, m.fk_origin, m.origintype,";
		$sql.= " m.batch,";
		$sql.= " pl.rowid as lotid, pl.eatby, pl.sellby,";
		$sql.= " u.login, u.photo, u.lastname, u.firstname";
		$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
		$sql.= " ".MAIN_DB_PREFIX."product as p,";
		$sql.= " ".MAIN_DB_PREFIX."stock_mouvement as m";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON m.fk_user_author = u.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_lot as pl ON m.batch = pl.batch AND m.fk_product = pl.fk_product";
		$sql.= " WHERE m.fk_product = p.rowid";
		$sql.= " AND m.fk_entrepot = e.rowid";
		$sql.= " AND e.entity IN (".getEntity('stock').")";
		switch ( $conf->global->CONSUMPTION_SEARCHMODE ) {
			case 1:
				$sql .= " AND m.label LIKE '%" . addslashes( $object->ref ) . "%'";
				break;
			case 2:
				$sql .= " AND m.inventorycode LIKE '" . addslashes( $conf->global->CONSUMPTION_INVCODEPREFIX . $object->ref ) . "%'";
				break;
			case 3:
				$sql .= " AND  (m.inventorycode LIKE '" . addslashes( $conf->global->CONSUMPTION_INVCODEPREFIX . $object->ref ) . "%' OR m.label LIKE '%" . addslashes( $object->ref ) . "%')";
				break;
			case 4:
				$sql .= " AND  (m.origintype = '" . $object->element . "' AND m.fk_origin = '" . $object->id . "')";
				break;
		}

		if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) $sql.= " AND p.fk_product_type = 0";
		if ($month > 0)
		{
			if ($year > 0)
			$sql.= " AND m.datem BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
			else
			$sql.= " AND date_format(m.datem, '%m') = '$month'";
		}
		else if ($year > 0)
		{
			$sql.= " AND m.datem BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
		}
		$result = $db->query($sql);
		$nbtotalofrecords = $db->num_rows($result);
		$this->lines=array();
		$resql = $db->query($sql);
		if ($resql)
		{
			$i=0;
			while ($i < $db->num_rows($resql))
			{
				$obj=$db->fetch_object($resql);
				$this->lines[]=$obj;
				$i++;
			}
			return $this->lines;
		}
		else{
			return -1;
		}
	}
}
