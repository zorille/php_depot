#!/usr/bin/php
<?php
/**
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package Package
 * @subpackage SubPackage
 */

//Deplacement pour joindre le repertoire home contenant php_framework
$deplacement = "/..";

if (isset ( $_SERVER ) && isset ( $_SERVER ["SCRIPT_FILENAME"] )) {
	$rep_document = dirname ( $_SERVER ["SCRIPT_FILENAME"] ) . $deplacement;
	$liste_variables_systeme = array ( 
			"conf" => array ( 
					$rep_document . "/conf_clients/cacti/prod_CLIENT_cacti.xml", 
					$rep_document . "/conf_clients/database/prod_cacti.xml" ), 
			"no_mail" => "" );
} else {
	$rep_document = dirname ( $argv [0] ) . $deplacement;
}

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
			"exemples" => array ( 
					"./" . $fichier . " --conf {Chemin vers conf_clients}/conf.xml --verbose" ), 
			$fichier => array () );
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Mettre le texte specifique ici";
	
	$class_utilisees = array ( 
			"class1", 
			"class2", 
			"etc" );
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option ->verifie_option_existe ( "help" ))
	help ();
	
	// Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

/**
 * Main programme
 * Code retour en 2xxx en cas d'erreur
 * @ignore
 *
 * @param options $liste_option        	
 * @param logs $fichier_log        	
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	/**
 * ******** VOTRE CODE A PARTIR D'ICI*********
 */
	try {
	} catch ( Exception $e ) {
	}
	/**
 * ********* FIN DE VOTRE CODE ***************
 */
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log ->renvoiExit () );
?>
