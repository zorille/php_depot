<?php
/**
 * Localized data
 *
 * @copyright   Copyright (C) 2013 XXXXX
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

Dict::Add('EN US', 'English', 'English', array(
	// Dictionary entries go here
	'Class:VSwitch' => '(D)VSwitch',
	'Class:VSwitch+' => 'VSwitch or Distributed VSwitch',
	'Class:VSwitch/Attribute:farm_id' => 'vCluster/ESXi',
	'Class:VSwitch/Attribute:virtualmachine_id' => 'vCenter',
	'Class:VSwitch/Attribute:virtualmachine_id+' => 'Mandatory in DVSwitch mode',
	'Class:VSwitch/Attribute:physicalinterfaces_list' => 'Network Interface',
	'Class:VSwitch/Attribute:physicalinterfaces_list+' => 'Network Interface connected to (D)VSwitch',
	'Class:VSwitch/Attribute:vmportgroups_list' => 'Port-groups',
	'Class:VSwitch/Attribute:vmportgroups_list+' => 'Liste des Port-Groups connectés au (D)VSwitch',
	'Class:VCenter' => 'vCenter',
	'Class:VCenter/Attribute:vswitchs_list' => '(D)VSwitchs',
	'Class:VCenter/Attribute:vswitchs_list+' => '(D)VSwitchs connected to vCenter',
));
?>
