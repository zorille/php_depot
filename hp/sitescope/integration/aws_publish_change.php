#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage sitescope
 */
$INCLUDE_SITESCOPE = true;

$rep_document = dirname ( $argv [0] ) . "/../../../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

/**
 *
 * @ignore Affiche le help.<br>
 *         Cette fonction fait un exit.
 *         Arguments reconnus :<br>
 *         --help
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
	$help [$fichier] ["text"] [] .= "Execute un 'PublishChange' sur le template AWS";
	$help [$fichier] ["text"] [] .= "\t--sitescope_utilise Nom du sitescope a utiliser";
	$help [$fichier] ["text"] [] .= "\t--connect_to_server oui/non oui par defaut";
	$help [$fichier] ["text"] [] .= "\t--delete_on_update oui/non oui par defaut";
	$help [$fichier] ["text"] [] .= "\t--template_aws 'AWS/test_aws' par defaut";
	
	$class_utilisees = array (
			"fichier",
			"sitescope_fonctions_standards",
			"sitescope_datas",
			"sitescope_soap_configuration"
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();

abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

function principale(&$liste_option, &$fichier_log) {
	if ($liste_option->verifie_option_existe ( "sitescope_utilise" ) === false) {
		return abstract_log::onError_standard ( "Il faut un sitescope pour travailler." );
	}
	
	if ($liste_option->verifie_option_existe ( "connect_to_server" ) === false) {
		$liste_option->setOption("connect_to_server","oui");
	}
	
	if ($liste_option->verifie_option_existe ( "delete_on_update" ) === false) {
		$liste_option->setOption("delete_on_update","oui");
	}
	
	if ($liste_option->verifie_option_existe ( "template_aws" ) === false) {
		$liste_option->setOption("template_aws","AWS/test_aws");
	}
	
	$soapClient_configuration = sitescope_soap_configuration::creer_sitescope_soap_configuration ( $liste_option );
	if (! $soapClient_configuration) {
		return abstract_log::onError_standard ( "Erreur dans les classes necessaires" );
	}
	
	if ($soapClient_configuration->valide_presence_sitescope_data ( $liste_option->getOption("sitescope_utilise") ) === false) {
		return abstract_log::onError_standard ( "Pas de configuration pour le serveur : " . $liste_option->getOption("sitescope_utilise") );
	}
	
	if ($soapClient_configuration->connect ( $liste_option->getOption("sitescope_utilise") ) === false) {
		return abstract_log::onError_standard ( "Pas de connexion sur le serveur : " . $liste_option->getOption("sitescope_utilise") );
	}
	
	if($liste_option->getOption("connect_to_server")=="oui"){
		$connect_to_server=true;
	} else {
		$connect_to_server=false;
	}

	if($liste_option->getOption("delete_on_update")=="oui"){
		$delete_on_update=true;
	} else {
		$delete_on_update=false;
	}
	
	$resultat=$soapClient_configuration->publishTemplateChanges("AWS/test_aws", array(),$connect_to_server,$delete_on_update);
	abstract_log::onInfo_standard($resultat);
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
