#!/usr/bin/php
<?php
/**
 * @author dvargas
 * @package Monitoring
 * @subpackage Hobbit
 */
$rep_document = dirname ( $argv [0] ) . "/../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";
/**
 * Permet d'inclure la lib check hobbit
 */
require_once $liste_option->renvoie_option ( "rep_scripts" ) . "/lib/check_hobbit.class.php";

abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

if ($liste_option->verifie_option_existe ( "help" ) !== false) {
	$help = array (
			"titre" => "CHECK_HOBBIT.PHP" 
	);
	$help [$fichier] ["text"] = array ();
	
	fonctions_standards::affichage_standard_help ( $help );
	
	fonctions_standards::help_fonctions_standard ( "oui", true, false, false, false, false, false );
	$methodes = array (
			"check_hobbit",
			"logs" 
	);
	foreach ( $methodes as $methode ) {
		if (method_exists ( $methode, "help" )) {
			fonctions_standards::affichage_standard_help ( call_user_func ( $methode . "::help" ) );
		}
	}
	echo "[Exit]0\n";
	exit ( 0 );
}

//On prepare les variables
$hobbit = check_hobbit::creer_check_hobbit ( $liste_option );
$hobbit->charge_liste_fichiers ();
$hobbit->check_etat_hobbit ();

abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoie_exit () );
?>
