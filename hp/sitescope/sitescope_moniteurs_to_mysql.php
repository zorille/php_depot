#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage sitescope
 */
$INCLUDE_SITESCOPE = true;
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
	$help [$fichier] ["text"] [] .= "Permet d'extraire la liste des devices d'un ou plusieurs sitescope";
	$help [$fichier] ["text"] [] .= "\t--dry-run Ne fait pas les mises a jour de la base";
	
	$class_utilisees = array (
			"fichier",
			"sitescope_fonctions_standards",
			"sitescope_datas",
			"sitescope_soap_configuration",
			"sitescope_soap_preferences"
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
ini_set ( "memory_limit", '1500M' );
/**
 * ******** VOTRE CODE A PARTIR D'ICI*********
 */
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/sitescope_compare_tables.class.php";
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/sitescope_functions_locales.class.php";

$continue = true;
$liste_dates = dates::creer_dates ( $liste_option );

try {
	$date = $liste_dates->recupere_date ( 0, "day" );
	$date_now_mysql = $liste_dates->extraire_date_mysql_standard ( $date, date ( "H:i:s" ) );
} catch ( Exception $e ) {
	$continue=false;
}

if ($liste_option->verifie_option_existe ( "client" ) === false) {
	$liste_option->setOption ( "fichier_sortie", "/tmp/liste_moniteurs_sitescope.csv" );
}

$soapClient_configuration = sitescope_soap_configuration::creer_sitescope_soap_configuration ( $liste_option );
$compare_fonctions = sitescope_compare_tables::creer_sitescope_compare_tables ( $liste_option );

// On se connecte a la base SiteScope de Hob
$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
$db_sitescope = fonctions_standards_sgbd::recupere_db_sitescope ( $connexion_db );

$dry_run = $liste_option->verifie_option_existe ( "dry-run" );

if ($continue && $soapClient_configuration && $db_sitescope && $compare_fonctions) {
	// On recupere la liste des serveur
	$liste_servers_actif = $db_sitescope->requete_select_standard ( 'serveur', array (
			"actif" => "1" 
	), "id ASC" );
	// On se connecte au sitescope demande
	if ($liste_servers_actif !== false) {
		foreach ( $liste_servers_actif as $serveur_data ) {
			abstract_log::onInfo_standard ( "Sitescope : " . $serveur_data ["name"] );
			abstract_log::onDebug_standard ( $serveur_data, 2 );
			
			if ($soapClient_configuration->valide_presence_sitescope_data ( $serveur_data ["name"] ) === false) {
				abstract_log::onWarning_standard ( "Pas de configuration pour le serveur : " . $serveur_data ["name"] );
				continue;
			}
			
			$liste_confs = null;
			if ($soapClient_configuration->connect ( $serveur_data ["name"] ) === false) {
				abstract_log::onError_standard ( "Pas de connexion au sitescope" );
				continue;
			}
			
			$db_sitescope->requete_update_standard ( 'serveur', array (
					"last_check" => $date_now_mysql,
					"doing" => 1 
			), array (
					"id" => $serveur_data ["id"] 
			) );
			
			abstract_log::onInfo_standard ( "getFullConfigurationSnapshot sur " . $serveur_data ["name"] );
			$liste_confs = $soapClient_configuration->retrouve_FullConfiguration_sitescope ();
			
			abstract_log::onDebug_standard ( $liste_confs, 2 );
			
			if (is_array ( $liste_confs )) {
				$sitescope_fonctions = sitescope_fonctions_standards::creer_sitescope_fonctions_standards ( $liste_option, $serveur_data ["id"] );
				
				// On gere les machines
				$sitescope_fonctions->retrouve_arbre_machines_from_FullConf ( $liste_confs );
				
				abstract_log::onInfo_standard ( "On met a jour les ci" );
				$liste_differences = $compare_fonctions->compare_ci ( $sitescope_fonctions->getArbreMachines (), $db_sitescope->requete_select_ci_sis ( $serveur_data ["id"] ), $db_sitescope->renvoi_table ( "ci" ), $serveur_data ["id"] );
				sitescope_functions_locales::applique_sql ( $db_sitescope, $liste_differences, $dry_run );
				unset ( $liste_differences );
				
				$sitescope_fonctions->retrouve_arbre_moniteurs_from_FullConf ( $liste_confs );
				
				abstract_log::onInfo_standard ( "On met a jour les groupes" );
				$liste_differences = $compare_fonctions->compare_tree ( $sitescope_fonctions->getArbreGroupes (), $db_sitescope->requete_select_tree_sis ( $serveur_data ["id"] ), $db_sitescope->renvoi_table ( "tree" ), $serveur_data ["id"] );
				sitescope_functions_locales::applique_sql ( $db_sitescope, $liste_differences, $dry_run );
				unset ( $liste_differences );
				
				abstract_log::onInfo_standard ( "On met a jour les proprietes des groupes" );
				$liste_differences = $compare_fonctions->compare_tree_props ( $sitescope_fonctions->getArbreGroupes (), $db_sitescope->requete_select_props_sans_id ( $serveur_data ["id"], 'tree' ), $db_sitescope->renvoi_table ( "props" ), $serveur_data ["id"] );
				sitescope_functions_locales::applique_sql ( $db_sitescope, $liste_differences, $dry_run );
				unset ( $liste_differences );
				
				// leaf
				abstract_log::onInfo_standard ( "On met a jour les moniteurs" );
				$liste_differences = $compare_fonctions->compare_leaf ( $sitescope_fonctions->getArbreMoniteurs (), $db_sitescope->requete_select_leaf_sis ( $serveur_data ["id"] ), $db_sitescope->renvoi_table ( "leaf" ), $serveur_data ["id"] );
				sitescope_functions_locales::applique_sql ( $db_sitescope, $liste_differences, $dry_run );
				unset ( $liste_differences );
				
				// props
				abstract_log::onInfo_standard ( "On met a jour les proprietes des moniteurs" );
				$liste_differences = $compare_fonctions->compare_moniteurs_props ( $sitescope_fonctions->getArbreMoniteurs (), $db_sitescope->requete_select_props_sans_id ( $serveur_data ["id"], 'leaf' ), $db_sitescope->renvoi_table ( "props" ), $serveur_data ["id"] );
				sitescope_functions_locales::applique_sql ( $db_sitescope, $liste_differences, $dry_run );
				unset ( $liste_differences );
				
				// alerts
				abstract_log::onInfo_standard ( "On met a jour les alertes" );
				$liste_differences = $compare_fonctions->compare_alert ( $sitescope_fonctions->getArbreMoniteurs (), $db_sitescope->requete_select_alert_sans_id ( $serveur_data ["id"] ), $db_sitescope->renvoi_table ( "alert" ), $serveur_data ["id"] );
				sitescope_functions_locales::applique_sql ( $db_sitescope, $liste_differences, $dry_run );
				unset ( $liste_differences );
				
				unset ( $sitescope_fonctions );
			}
			
			// On finalise l'information de mise a jour du serveur
			$date_now_mysql = $liste_dates->extraire_date_mysql_standard ( $date, date ( "H:i:s" ) );
			$db_sitescope->requete_update_standard ( 'serveur', array (
					"last_check" => $date_now_mysql,
					"doing" => 0 
			), array (
					"id" => $serveur_data ["id"] 
			) );
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
