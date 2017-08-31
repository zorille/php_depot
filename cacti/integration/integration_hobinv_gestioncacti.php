#!/usr/bin/php
<?php
/**
 * @ignore
 * @author dvargas
 * @package Steria
 * @subpackage Cacti 
 */

// Specifiquement pour cacti, on a des INCLUDE qui permettent de charger les APIs de Cacti
$rep_document = dirname ( $argv [0] ) . "/../../../..";
/** Permet d'inclure toutes les librairies communes necessaires */
require_once $rep_document . "/php_framework/config.php";
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->renvoie_option ( "rep_scripts" ) . "/lib/config_cacti.php";

/**
 * @ignore 
 * Affiche le help.<br> 
 * Cette fonction fait un exit. Arguments reconnus :<br> 
 * --help 
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
	$help [$fichier] ["text"] [] .= "\t--cacti_env mut/tlt/dev/perso";
	$help [$fichier] ["text"] [] .= "\t--serveur nom du serveur recherche";
	
	$class_utilisees = array (
			
			"fichier",
			"wsclient" 
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

/** ******** VOTRE CODE A PARTIR D'ICI********* */
/** Main programme
 * 
 * @ignore
 * @param options $liste_option
 * @param logs $fichier_log
 * @return boolean */
function principale(&$liste_option, &$fichier_log) {
	// alter table ci ADD COLUMN status int(2) DEFAULT 1;
	// alter table ci ADD COLUMN hobinv_id int(11);
	$liste_snmp_client = array ();
	$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
	$db_cacti = fonctions_standards_sgbd::recupere_db_gestion_cacti ( $connexion_db );
	$db_tools = fonctions_standards_sgbd::recupere_db_tools ( $connexion_db );
	
	$fonctions_cacti = gestion_ci_hobinv::creer_gestion_ci_hobinv ( $liste_option, $db_cacti, $db_tools );
	
	if (! $db_cacti || ! $db_tools || ! $fonctions_cacti) {
		abstract_log::onError_standard ( "Il manque des variables necessaires", "", 2000 );
		return false;
	}
	
	$resultat = $db_cacti->requete_select_standard ( 'serveur', array (
			"actif" => 1 
	), "id ASC" );
	
	foreach ( $resultat as $serveur ) {
		abstract_log::onInfo_standard ( "Client en cours = " . $serveur ["customer"] );
		
		// On charge la liste des ci existant
		$fonctions_cacti->retrouve_liste_ci ( $serveur ["id"] );
		
		// On recupere la liste des ci de HOBINV
		$fonctions_cacti->retrouve_liste_ci_hobinv ( $serveur );
		
		//En premier, on prepare les mises a jour de hobinv vers cacti
		$fonctions_cacti->modification_liste_ci ( $serveur ["customer"], $serveur ["id"] );
		
		// Pour chaque ci de HobINV qiu n'existe pas dans gestion_cacti
		$fonctions_cacti->ajoute_liste_ci ( $serveur ["customer"], $serveur ["id"] );
		
		//Enfin on supprime le reste des entrees de gestion_cacti qui n'apparaissent pas dans hobinv
		$fonctions_cacti->supprime_liste_ci ();
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
/** ********* FIN DE VOTRE CODE *************** */
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoie_exit () );
?>
