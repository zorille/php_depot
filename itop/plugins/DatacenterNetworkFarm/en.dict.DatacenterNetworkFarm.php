<?php
/**
 * Localized data
 *
 * @copyright   Copyright (C) 2013 XXXXX
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

Dict::Add('EN US', 'English', 'English', array(
	// Dictionary entries go here
		'Server:networkfarm'=>'Network Device Bonding',
		'Class:DatacenterDevice/Attribute:networkA_id' => 'Network Card A',
		'Class:DatacenterDevice/Attribute:networkA_id+' => 'Primary Network Card',
		'Class:DatacenterDevice/Attribute:networkB_id' => 'Network Card B',
		'Class:DatacenterDevice/Attribute:networkB_id+' => 'Secondary Network Card',
		'Class:DatacenterDevice/Attribute:redundancy_network' => 'Redundancy',
		'Class:DatacenterDevice/Attribute:redundancy_network/count' => 'The device is up if at least one network card connection (A or B) is up'
		
));
?>
