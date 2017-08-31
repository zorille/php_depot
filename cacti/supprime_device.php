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
	$help [$fichier] ["text"] [] .= "\t--ip '' IP ou nom resolvable du CI";
	
	$class_utilisees = array (
			"fichier",
			"cacti_datas",
			"cacti_addDevice",
			"cacti_removeDevice"
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
 * curl "https://addresse_cacti/cacti_hobinv/php_depot/steria/cacti/supprime_device.php?env=test&cacti_env=CPL&ip=nom_machine_resolvable&verbose=0"
 */
/**
 * Main programme
 *
 * @ignore
 *
 *
 *
 *
 * @param options $liste_option        	
 * @param logs $fichier_log        	
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	$cacti = cacti_removeDevice::creer_cacti_removeDevice ( $liste_option, false );
	
	if (! $cacti->setIp ( trim ( $liste_option->renvoie_option ( 'ip' ) ) )) {
		abstract_log::onError_standard ( "Il manque l'IP.", "", 5004 );
		return false;
	}
	
	// On ajoute le host
	abstract_log::onInfo_standard ( "On supprime le host IP : " . $cacti->getIp () );
	if ($cacti->executeCacti_removeDevice () !== false) {
		abstract_log::onInfo_standard ( "Machine supprimee : " . $cacti->getIp () );
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
