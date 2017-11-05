<?php
/**
 * Localized data
 *
 * @copyright   Copyright (C) 2013 XXXXX
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

Dict::Add('FR FR', 'French', 'Français', array(
	// Dictionary entries go here
	'Class:VSwitch' => '(D)VSwitch',
	'Class:VSwitch+' => 'VSwitch ou Distributed VSwitch',
	'Class:VSwitch/Attribute:farm_id' => 'vCluster/ESXi',
	'Class:VSwitch/Attribute:virtualmachine_id' => 'vCenter',
	'Class:VSwitch/Attribute:virtualmachine_id+' => 'Nécessaire en mode DVSwitch',
	'Class:VSwitch/Attribute:physicalinterfaces_list' => 'Interfaces Réseau',
	'Class:VSwitch/Attribute:physicalinterfaces_list+' => 'Liste des Interfaces Réseau connectées au (D)VSwitch',
	'Class:VSwitch/Attribute:vmportgroups_list' => 'Port-groups',
	'Class:VSwitch/Attribute:vmportgroups_list+' => 'Liste des Port-Groups connectés au (D)VSwitch',
	'Class:VCenter' => 'vCenter',
	'Class:VCenter/Attribute:vswitchs_list' => '(D)VSwitchs',
	'Class:VCenter/Attribute:vswitchs_list+' => 'Liste des (D)VSwitchs connectés au vCenter',
));
?>
