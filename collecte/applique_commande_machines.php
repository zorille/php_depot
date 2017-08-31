#!/usr/bin/php
<?php
/**
 *
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package collecte
 */
// Deplacement pour joindre le repertoire lib
$deplacement = "/../..";
$rep_document = dirname ( $argv [0] ) . $deplacement;
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

/**
 *
 * @ignore Affiche le help.<br> Cette fonction fait un exit. Arguments reconnus :<br> --help
 */
function help() {
	$fichier = basename ( __FILE__ );
	$help = array (
			"usage" => array (
					$fichier . " --conf [fichiers de conf] [OPTIONS]",
					$fichier . " --help" 
			),
			"exemple" => array (
					"./" . $fichier . " --conf {Chemin vers conf_clients}/Flux/prod_CLIENT_ssh_serveur.xml --repertoire_fichiers ./liste_datas/ --verbose" 
			),
			$fichier => array () 
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Execute une commande SSH";
	$class_utilisees = array (
			"fonctions_standards_flux" 
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
 * Main programme Code retour en 2xxx en cas d'erreur
 *
 * @ignore
 * @param options $liste_option
 * @param logs $fichier_log
 * @return boolean
 */
function principale(
		&$liste_option, 
		&$fichier_log) {
	if ($liste_option->verifie_option_existe ( "machines" ) === false) {
		return abstract_log::onError_standard ( "Il faut une liste de machine --machines" );
	} elseif (! is_array ( $liste_option->getOption ( "machines" ) )) {
		$liste_machines = array (
				$liste_option->getOption ( "machines" ) 
		);
	} else {
		$liste_machines = $liste_option->getOption ( "machines" );
	}
	
	if ($liste_option->verifie_option_existe ( "commande" ) === false) {
		return abstract_log::onError_standard ( "Il faut une commande a appliquer --commande" );
	}
	
	foreach ( $liste_machines as $serveur ) {
		// On prepare une class flux par serveur
		$class_flux = fonctions_standards_flux::creer_fonctions_standards_flux ( $liste_option );
		if (! is_object ( $class_flux )) {
			return abstract_log::onError_standard ( "La class fonctions_standards_flux est introuvable." );
		}
		try {
			abstract_log::onInfo_standard ( "Connexion ssh sur " . $serveur );
			$connexion = $class_flux->creer_connexion_ssh ( $serveur );
			$datas = $class_flux->getConnexion ()
				->ssh_commande ( $liste_option->getOption ( "commande" ) );
			$class_flux->getConnexion ()
				->ssh_close ();
		} catch ( Exception $e ) {
		}
	}
	return true;
}
principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );
exit ( $fichier_log->renvoiExit () );
?>
