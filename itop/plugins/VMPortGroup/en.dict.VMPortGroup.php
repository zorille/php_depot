<?php
/**
 * Localized data
 *
 * @copyright   Copyright (C) 2013 XXXXX
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

Dict::Add('EN US', 'English', 'English', array(
	// Dictionary entries go here
	'Class:VMPortGroup' => 'Vswitch Port-Group',
	'Class:VMPortGroup+' => '(D)Vswitch\' Port-Group',
	'Class:VMPortGroup/Attribute:virtualswitch_id' => '(D)VSwitch',
	'Class:VMPortGroup/Attribute:vlan_id' => 'VLAN',
	'Class:VMPortGroup/Attribute:logicalinterfaces_list' => 'Virtual Network Interface',
	'Class:VMPortGroup/Attribute:logicalinterfaces_list+' => 'Network Interface connected to Port-Group',
	'Class:VLAN/Attribute:vmportgroups_list' => 'Port-groups',
	'Class:VLAN/Attribute:vmportgroups_list+' => 'Port-Groups using VLAN',
	'Class:VirtualSwitch/Attribute:vmportgroups_list' => 'Liste des Port-groups',
	'Class:VirtualSwitch/Attribute:vmportgroups_list+' => 'Liste des Port-Groups utilisant le (D)VSwitch',
	'Class:LogicalInterface/Attribute:vmportgroup_id' => 'Port-Group',
));
?>
