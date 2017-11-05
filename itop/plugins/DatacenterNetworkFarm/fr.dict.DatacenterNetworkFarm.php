<?php
/**
 * Localized data
 *
 * @copyright   Copyright (C) 2013 XXXXX
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

Dict::Add('FR FR', 'French', 'Français', array(
	// Dictionary entries go here
	'Server:networkfarm'=>'Agrégat de cartes réseaux',
	'Class:DatacenterDevice/Attribute:networkA_id' => 'Carte réseau A',
	'Class:DatacenterDevice/Attribute:networkA_id+' => 'Carte réseau primaire',
	'Class:DatacenterDevice/Attribute:networkB_id' => 'Carte réseau B',
	'Class:DatacenterDevice/Attribute:networkB_id+' => 'Carte réseau secondaire',
	'Class:DatacenterDevice/Attribute:redundancy_network' => 'Redondance',
	'Class:DatacenterDevice/Attribute:redundancy_network/count' => 'Le %2$s est connecté si au moins une carte réseau (A ou B) est connectée',
));
?>
