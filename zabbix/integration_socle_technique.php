#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package Zabbix
 * @subpackage Zabbix
 */
$rep_document = dirname ( $argv [0] ) . "/../..";
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
					$fichier . " --help" ),
			$fichier => array () );
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Permet d'ajouter un CI avec interface(s)/groupe(s)/template(s) dans zabbix";
	$help [$fichier] ["text"] [] .= "\t--action ajout|supp Action a faire";
	
	$class_utilisees = array (
			"zabbix_host_administration",
			"zabbix_connexion" );
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option ->verifie_option_existe ( "help" ))
	help ();

abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

function principale(&$liste_option, &$fichier_log) {
	try {
		//On valide la liste des parametres
		if ($liste_option ->verifie_option_existe ( "action" ) === false) {
			return abstract_log::onError_standard ( "Il faut une action a effectuer : ajout|supp pour travailler." );
		}
		
		//On se connecte au zabbix
		$zabbix_connexion = zabbix_connexion::creer_zabbix_connexion ( $liste_option ) ->connect_zabbix ();
		$zabbix_host_admin = zabbix_host_administration::creer_zabbix_host_administration ( $liste_option, $zabbix_connexion ->getObjetZabbixWsclient () );
		switch (strtolower ( $liste_option ->getOption ( "action" ) )) {
			case 'ajout' :
				$zabbix_host_admin ->ajoute_host ();
				break;
			case 'supp' :
				$zabbix_host_admin ->supprime_host ();
				break;
			default :
				abstract_log::onError_standard ( "Action inconnue " . $liste_option ->getOption ( "action" ) );
		}
	} catch ( Exception $e ) {
		// Exception in ZabbixApi catched
		return abstract_log::onError_standard ( $e ->getMessage (), "", $e ->getCode () );
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log ->renvoiExit () );
?>
