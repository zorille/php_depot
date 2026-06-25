#!/usr/bin/php
<?php
/**
 * @author dvargas
 * @package iTop
 * @subpackage extract
 */
$rep_document = dirname ( $argv [0] ) . "/../../../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";
use Zorille\framework as Core;
use Exception as Exception;

/**
 * @ignore Affiche le help.<br> Cette fonction fait un exit. Arguments reconnus :<br> --help
 */
function help() {
	$fichier = basename ( __FILE__ );
	$help = array (
			"usage" => array (
					$fichier . " --conf [fichiers de conf] [OPTIONS]",
					$fichier . " --help"
			),
			$fichier => array ()
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "--itop_serveur";
	$help [$fichier] ["text"] [] .= "--o365_serveur_mail serveur o365 pour la messagerie";
	$help [$fichier] ["text"] [] .= "--o365_user_message 'Damien Vargas'";
	$class_utilisees = array (
			"Zorille\o365\Message"
	);
	$help = array_merge ( $help, Core\fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	Core\fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}
// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();
// Le fichier de log est cree
Core\abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

/**
 * ******** VOTRE CODE A PARTIR D'ICI*********
 */
/**
 * @param Core\options $liste_option
 * @param Core\logs $fichier_log
 * @return boolean
 */
function principale(
		&$liste_option,
		&$fichier_log) {
	// Gestion de fichier
	if ($liste_option->verifie_option_existe ( "fichier", true ) === false) {
		return Core\abstract_log::onError_standard ( "Il faut un fichier pour travailler." );
	}
	$doublon=array();
	$count=0;
	$nbVM=0;
	$fichier=Core\fichier::Lit_integralite_fichier_en_tableau ( $liste_option->getOption ( "fichier" ) );
	foreach($fichier as $ligne){
		$vm_data=explode(";", $ligne);
		$nbVM++;
		if(isset($doublon[$vm_data[0]])){
			$count++;
			Core\abstract_log::onWarning_standard("Doublon detecte :");
			Core\abstract_log::onWarning_standard($doublon[$vm_data[0]][1]."/".$doublon[$vm_data[0]][3]."/".$doublon[$vm_data[0]][9]);
			Core\abstract_log::onWarning_standard($vm_data[1]."/".$vm_data[3]."/".$vm_data[9]);
		}
		$doublon[$vm_data[0]]=$vm_data;
		
	}
	Core\abstract_log::onInfo_standard("Doublon=".$count);
	Core\abstract_log::onInfo_standard("NBVM=".$nbVM);
	//print_r($doublon);
}
principale ( $liste_option, $fichier_log );
Core\abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );
exit ( $fichier_log->renvoiExit () );
?>
