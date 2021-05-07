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


class ActionsConsumption
{

	public function completeTabsHead($parameters, &$object, &$action, $hookmanager)
	{
		if (in_array('fileslib', explode(':', $parameters['context']))){
			global $langs;
			global $db;
			global $user;
			dol_include_once("/custom/consumption/class/consumption.class.php");
			$conso  = new Consumption($db);
			$test   = $parameters['head'];
			$object = $parameters['object'];
			foreach ($test as $key => $val) {
				if ($val[2]=='conso') {
					$nbmvt=$conso->countconso($object);
					if ($nbmvt > 0 && !strstr($test[$key][1], "badge")) {
						$test[$key][1].=' <span class="badge">'. $nbmvt .'</span>';
						$this->results = $test;
						return 1;
					}
				}
			}
		}
	}

	public function completeListOfReferent( $parameters, &$object, &$action, $hookmanager ) {

		global $user;
		global $langs;

		$this->results = array(
			'consumption' => array(
				'name'          => $langs->trans("Verbrauch"),
				'title'         => $langs->trans("Liste der Verbräuche des Projektes"),
				'class'         => 'MouvementStock',
				'table'         => 'stock_mouvement',
				'datefieldname' => 'datev',
				'margin'        => 'minus',
				'disableamount' => 0,
				'urlnew'        => DOL_URL_ROOT . '/custom/consumption/card.php?id=' . $object->id . '&type=project',
				'lang'          => 'consumption',
				'buttonnew'     => $langs->trans("Neuen Verbrauch erfassen"),
				'testnew'       => $user->rights->consumption->writeproject,
				'test'          => $user->rights->consumption->writeproject,
			),
		);

		return 1;
	}

	public function getElementList( $parameters, &$object, &$action, $hookmanager ) {

		if ( $parameters['tablename'] == 'stock_mouvement' ) {
			$this->resprints = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . "stock_mouvement WHERE origintype IN ('project') AND fk_origin IN (" . $parameters['ids'] . ")";

			return 1;
		}

		return 0;
	}

	public function XXprintOverviewDetail( $parameters, &$object, &$action, $hookmanager ) {

		global $user;

		if ( $parameters['key'] == 'consumption' ) {
			// skip
		}

		return 0;

	}

}
