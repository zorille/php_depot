#!/usr/bin/php
<?php
/**
 *
 * @ignore
 *
 *
 *
 */
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage Cacti
 */
$rep_document = dirname ( $argv [0] ) . "/../../..";
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
	$help [$fichier] ["text"] [] .= "\t--sitescope_host host sitescope";
	$help [$fichier] ["text"] [] .= "\t--sitescope_port port sitescope";
	$help [$fichier] ["text"] [] .= "\t--sitescope_wsdl lien sur le wsdl de sitescope";
	
	$class_utilisees = array (
			"fichier"
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();
	
	// Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );
/**
 * ******** VOTRE CODE A PARTIR D'ICI*********
 */
$continue = true;

if ($liste_option->verifie_variable_standard ( "sitescope_host" ) === false) {
	$continue = false;
	abstract_log::onError_standard ( "Il faut un host pour sitescope" );
} else {
	$host_sitescope = $liste_option->renvoi_variables_standard ( "sitescope_host" );
}

if ($liste_option->verifie_variable_standard ( "sitescope_port" ) === false) {
	$port_sitescope = "8080";
} else {
	$port_sitescope = $liste_option->renvoi_variables_standard ( "sitescope_port" );
}
if ($liste_option->verifie_variable_standard ( "sitescope_wsdl" ) === false) {
	$wsdl_sitescope = "APIConfigurationImpl";
} else {
	$wsdl_sitescope = $liste_option->renvoi_variables_standard ( "sitescope_wsdl" );
}

$soapClient = null;
if ($continue) {
	
	try {
		$soapClient = @new SoapClient ( "http://" . $host_sitescope . ":" . $port_sitescope . "/SiteScope/services/" . $wsdl_sitescope . "?wsdl" );
	} catch ( Exception $e ) {
		abstract_log::onError_standard ( $e->getMessage () );
	}
	if ($soapClient instanceof SoapClient) {
		foreach ( $soapClient->__getFunctions () as $function ) {
			abstract_log::onInfo_standard ( $function );
		}
		
		foreach ( $soapClient->__getTypes () as $types ) {
			abstract_log::onInfo_standard ( $types );
		}
	}
} else {
	abstract_log::onError_standard ( "Erreur dans les variables necessaires" );
}

/**
 * ********* FIN DE VOTRE CODE ***************
 */
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
