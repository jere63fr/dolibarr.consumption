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


class ActionsConsumption
{ 
	
	function completeTabsHead($parameters, &$object, &$action, $hookmanager)
	{
		if (in_array('fileslib', explode(':', $parameters['context']))){
			global $langs;
			global $db;
			global $user;
			dol_include_once("/custom/consumption/class/consumption.class.php");
			$conso = new Consumption($db);
			$test = $parameters['head'];
			$object=$parameters['object'];
			foreach ($test as $key => $val){
				if ($val[2]=='conso'){				
					$nbmvt=$conso->countconso($object);
					if($nbmvt > 0 && !strstr($test[$key][1], "badge")){
						$test[$key][1].=' <span class="badge">'. $nbmvt .'</span>';
						$this->results=$test;
						return 1;
					}
				}
			}
		}
	}

}
