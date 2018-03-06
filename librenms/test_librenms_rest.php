#!/usr/bin/php
<?php
/**
 *
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package Oneshoot
 * @subpackage Test
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
					$fichier . " --help"
			),
			$fichier => array ()
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "./test_librenms_rest.php --conf ../../conf_clients/librenms/dev_client_librenms_serveurs.xml --librenms_serveur librenms-test --librenms_token XXXXxxxXXXX --verbose";
	$help [$fichier] ["text"] [] .= "\t--librenms_serveurr Nom du server dans le fichier de configuration";
	$help [$fichier] ["text"] [] .= "\t--librenms_token Token fournit par LibreNMS";
	
	
	$class_utilisees = array (
			"fichier","librenms_wsclient","librenms_datas"
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();
	

// ==================================================
// Etape 2 : Recuperation des arguments du scripts
// ==================================================
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

/**
 * ******** VOTRE CODE A PARTIR D'ICI*********
 */
/**
 * Fonction principale
 * @param options $liste_option
 * @param logs $fichier_log
 * @return true
 */
function principale(
		&$liste_option,
		&$fichier_log) {
	if ($liste_option->verifie_option_existe ( "librenms_serveur" ) === false) {
		return abstract_log::onError_standard ( "Il faut un parametre --librenms_serveur pour travailler." );
	}
	if ($liste_option->verifie_option_existe ( "librenms_token" ) === false) {
		return abstract_log::onError_standard ( "Il faut un parametre --librenms_token pour travailler." );
	}
	$librenms_webservice = librenms_wsclient::creer_librenms_wsclient ( $liste_option, librenms_datas::creer_librenms_datas ( $liste_option ) );
	try {
		$librenms_webservice->setAuth ( $liste_option->getOption ( "librenms_token" ) );
		$librenms_webservice->prepare_connexion ( $liste_option->getOption ( "librenms_serveur" ) );
		$resultat = $librenms_webservice->getMethod ( "" );
		abstract_log::onInfo_standard ( $resultat );
	} catch ( Exception $e ) {
		// Exception in librenmsApi catched
		abstract_log::onError_standard ( $e->getMessage (), "", $e->getCode () );
	}
	/**
	 * ******** FIN DE VOTRE CODE*********
	 */
}
principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );
exit ( $fichier_log->renvoiExit () );
?>
