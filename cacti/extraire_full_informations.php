
<?php
/**
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package Steria
 * @subpackage Cacti
 */
// Deplacement pour joindre le repertoire lib
$deplacement = "/../../..";

if (! isset ( $argv ) && isset ( $_SERVER ) && isset ( $_SERVER ["SCRIPT_FILENAME"] )) {
	$rep_document = dirname ( $_SERVER ["SCRIPT_FILENAME"] ) . $deplacement;
	if (isset ( $_REQUEST ["env"] )) {
		$env = $_REQUEST ["env"];
	} else {
		$env = "no_env";
	}
	
	$liste_variables_systeme = array (
			"conf" => array (
					$rep_document . "/conf_clients/cacti/" . $env . "_" . $_REQUEST ["cacti_env"] . "_cacti.xml" 
			) 
	);
} else {
	$rep_document = dirname ( $argv [0] ) . $deplacement;
}
$INCLUDE_CACTI_ADDTREE = true;

// Specifiquement pour cacti, on a des INCLUDE qui permettent de charger les APIs de Cacti
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
	$help [$fichier] ["text"] [] .= "Permet d'extraire la liste des donnees d'un cacti";
	$help [$fichier] ["text"] [] .= "\t--env prod/preprod/test environnement de travail";
	$help [$fichier] ["text"] [] .= "\t--cacti_env 'mut' Code client, il permet aussi de mettre le nom machine en {code_client}/{description}";
	$help [$fichier] ["text"] [] .= "\t--serveur nom du serveur recherche";
	
	$class_utilisees = array (
			"fichier",
			"cacti_datas",
			"cacti_hosts"
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

require_once $liste_option->renvoie_option ( "rep_scripts" ) . "/lib/correspondances_cacti.class.php";

/**
 * Main programme
 *
 * @ignore
 *
 *
 * @param options $liste_option        	
 * @param logs $fichier_log        	
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	$cacti = cacti_hosts::creer_cacti_hosts ( $liste_option );
	$correspondances_cacti = correspondances_cacti::creer_correspondances_cacti ( $liste_option, false );
	$html = html::creer_html ( $liste_option );
	
	if (! $cacti || ! $html || ! $correspondances_cacti) {
		abstract_log::onError_standard ( "Il manque des variables necessaires", "", 2000 );
		return false;
	}
	
	if ($liste_option->verifie_option_existe ( "serveur", true ) === false) {
		$liste_hosts = $cacti->getHosts ();
	} else {
		$liste_hosts = array (
				$cacti->getOneHosts ( $liste_option->renvoie_option ( "serveur" ) ) 
		);
	}
	
	foreach ( $liste_hosts as $id => $host ) {
		$liste_hosts [$id] ["availability_method"] = $correspondances_cacti->retrouveAvailabilityMethod ( $host ["availability_method"] );
		$liste_hosts [$id] ["ping_method"] = $correspondances_cacti->retrouvePingMethod ( $host ["ping_method"] );
		$liste_hosts [$id] ["status"] = $correspondances_cacti->retrouveStatus ( $host ["status"] );
	}
	
	if ($fichier_log->getIsWeb () === true) {
		$html->setBody ( $liste_hosts );
		$html->afficher_json ();
	} else {
		abstract_log::onDebug_standard ( json_encode ( $liste_hosts ), 1 );
		fonctions_standards_mail::envoieMail_standard ( $liste_option, "Extraction cacti du " . date ( "M/Y" ), array (
				"text" => "Ci-joint votre extraction\nCordialement." . json_encode ( $liste_hosts ) 
		), array () );
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
/**
 * ********* FIN DE VOTRE CODE ***************
 */
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoie_exit () );
?>
