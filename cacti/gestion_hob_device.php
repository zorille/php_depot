#!/usr/bin/php
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
	$help [$fichier] ["text"] [] .= "Gere la liste des device dans les differents cacti";
	
	$class_utilisees = array (
			"fichier",
			"wsclient",
			"db" 
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
 * Main programme
 * @ignore
 * @param options $liste_option
 * @param logs $fichier_log
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	/**
	 * ******** VOTRE CODE A PARTIR D'ICI*********
	 */
	
	// On se connecte a la base SiteScope de Hob
	$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
	$db_tools = fonctions_standards_sgbd::recupere_db_tools ( $connexion_db );
	$db_gestion_cacti = fonctions_standards_sgbd::recupere_db_gestion_cacti ( $connexion_db );
	
	$liste_hash_serveur=array();
	$liste_serveur_cacti=$db_gestion_cacti->requete_select_standard ( 'serveur', array(), "id ASC" );
	foreach($liste_serveur_cacti as $serveur){
		abstract_log::onDebug_standard("Serveur en cours : ".$serveur["customer"],2);
		$liste_hash_serveur[$serveur["customer"]]=$serveur["cacti_env"];
	}
	
	$liste_hash_ci=array();
	$liste_ci=$db_tools->requete_select_standard ( 'get_cacti_info', array(), "ci_name ASC" );
	foreach($liste_ci as $ci){
		abstract_log::onDebug_standard("CI en cours : ".$ci["ci_name"],2);
		$liste_hash_ci[$ci["ci_name"]]=1;
		if(!isset($liste_hash_serveur[$ci["client"]])){
			continue;
		}
		$liste_hash_ci[$liste_hash_serveur[$ci["client"]]."-".$ci["ci_name"]]=1;
		$liste_hash_ci[$liste_hash_serveur[$ci["client"]]." - ".$ci["ci_name"]]=1;
	}
	
	$liste_ci_cacti=$db_gestion_cacti->requete_select_standard ( 'ci', array(), "id ASC" );
	foreach($liste_ci_cacti as $ci){
		if(!isset($liste_hash_ci[$ci["name"]])){
			echo $ci["name"]."\n";
		}
	}
	
	/**
	 * ********* FIN DE VOTRE CODE ***************
	 */
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoie_exit () );
?>

