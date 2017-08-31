<?php
/**
 *
 * @author dvargas
 * @package Steria
 * @subpackage Cacti
 */
//Deplacement pour joindre le repertoire lib
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
					$rep_document . "/conf_clients/database/" . $env . "_cacti.xml",
					$rep_document . "/conf_clients/cacti/" . $env . "_" . $_REQUEST ["cacti_env"] . "_cacti.xml" 
			) 
	);
} else {
	$rep_document = dirname ( $argv [0] ) . $deplacement;
}
// Specifiquement pour cacti, on a des INCLUDE qui permettent de charger les APIs de Cacti
$INCLUDE_CACTI_DEVICE = true;
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
	$help [$fichier] ["text"] [] .= "Permet d'ajouter un host dans cacti a partir d'une URL";
	$help [$fichier] ["text"] [] .= "\t--env prod/preprod/test environnement de travail";
	$help [$fichier] ["text"] [] .= "\t--cacti_env 'mut' Code client, il permet aussi de mettre le nom machine en {code_client}/{description}";
	$help [$fichier] ["text"] [] .= "\t--oid_a_checker 'sysDescr.0' OID de test pour valider le snmp du host";
	$help [$fichier] ["text"] [] .= "\t--serveur 'xxx' nom du ci en general";
	
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
 * curl "http://addresse_cacti/cacti_hobinv/php_depot/steria/cacti/recupere_oids.php?env=test&cacti_env=CPL&serveur=NomDuCI&oid_a_checker=sysDescr.0"
 * &update en cas de mise a jour
 */

require_once $liste_option->renvoie_option ( "rep_scripts" ) . "/lib/correspondances_cacti.class.php";

/**
 * Main programme
 * @ignore
 * @param options $liste_option        	
 * @param logs $fichier_log        	
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	if ($liste_option->verifie_option_existe ( "oid_a_checker" ) === false) {
		if ($liste_option->verifie_option_existe ( "liste_oid_a_checker" ) === false) {
			$liste_option->set_option ( "oid_a_checker", "sysDescr.0" );
		} else {
			$liste = $liste_option->renvoie_option ( "liste_oid_a_checker" );
			if (strpos ( $liste, "[" ) === 0) {
				$liste_oids = json_decode ( $liste );
			} else {
				$liste_oids = explode ( ",", $liste );
			}
		}
	} else {
		$liste_oids = array (
				$liste_option->renvoie_option ( "oid_a_checker" ) 
		);
	}
	abstract_log::onDebug_standard ( $liste_oids, 1 );
	
	if ($liste_option->verifie_option_existe ( "serveur" ) === false) {
		abstract_log::onError_standard ( "Il manque le nom du serveur.", "", 5003 );
		return false;
	}
	
	if ($liste_option->verifie_option_existe ( "snmp_timeout" ) === false) {
		$liste_option->set_option ( "snmp_timeout", 1000000 );
	}
	
	$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
	$db_cacti = fonctions_standards_sgbd::recupere_db_cacti ( $connexion_db );
	$correspondances_cacti = correspondances_cacti::creer_correspondances_cacti ( $liste_option, false );
	$cacti = cacti_hosts::creer_cacti_hosts($liste_option);
	
	if (! $db_cacti || ! $correspondances_cacti || ! $cacti) {
		abstract_log::onError_standard ( "Il manque une variable", "", 2000 );
		return false;
	}
	
	// On prepare la ligne de command
	abstract_log::onInfo_standard ( "Cacti_env utilise : " . $liste_option->renvoie_option ( "cacti_env" ) );
	$CI = $cacti->getOneHosts ( $liste_option->renvoie_option ( "serveur" ) );
	
	if (! $CI) {
		// erreur
		abstract_log::onError_standard ( "Serveur " . $liste_option->renvoie_option ( "serveur" ) . " introuvable.", "", 5003 );
		return false;
	}
	
	$status = $correspondances_cacti->retrouveStatus ( $CI ["status"] );
	$fichier_log->AjouteMessageResultat ( $status, 'CI_status' );
	if ($status != "HOST_UP") {
		abstract_log::onError_standard ( "le CI n'est pas joignable : " . $status, "", 5022 );
		return false;
	}
	
	abstract_log::onInfo_standard ( "On recupere les donnees via SNMP." );
	$resultat_oids = array ();
	foreach ( $liste_oids as $oid ) {
		$resultat_oids [$oid] = $correspondances_cacti->retrouveSnmp ( $oid, $CI ["hostname"], $CI ["snmp_community"], $CI ["snmp_version"], $liste_option->renvoie_option ( "snmp_timeout" ), 1, $CI ["snmp_username"], $CI ["snmp_password"], $CI ["snmp_auth_protocol"], $CI ["snmp_priv_protocol"], $CI ["snmp_priv_passphrase"] );
	}
	
	abstract_log::onDebug_standard ( $resultat_oids, 1 );
	$fichier_log->AjouteMessageResultat ( $resultat_oids, 'resultat_oids' );
	
	return true;
}

principale ( $liste_option, $fichier_log );
/**
 * ********* FIN DE VOTRE CODE ***************
 */
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoie_exit () );
?>
