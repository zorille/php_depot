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

$soapClient_preferences = sitescope_soap_preferences::creer_sitescope_soap_preferences ( $liste_option, false );
$soapClient_sitescope = sitescope_soap_sitescope::creer_sitescope_soap_sitescope ( $liste_option, false );
$compare_fonctions = sitescope_compare_tables::creer_sitescope_compare_tables ( $liste_option );

// On se connecte a la base SiteScope de Hob
$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
$db_sitescope = fonctions_standards_sgbd::recupere_db_sitescope ( $connexion_db );

$dry_run = $liste_option->verifie_option_existe ( "dry-run" );

if ($continue && $soapClient_preferences && $soapClient_sitescope && $db_sitescope && $compare_fonctions) {
	// On recupere la liste des serveur
	$liste_servers_actif = $db_sitescope->requete_select_standard ( 'serveur', array (
			"actif" => "1" 
	), "id ASC" );
	// On se connecte au sitescope demande
	if ($liste_servers_actif !== false) {
		foreach ( $liste_servers_actif as $serveur_data ) {
			abstract_log::onInfo_standard ( "Sitescope : " . $serveur_data ["name"] );
			abstract_log::onDebug_standard ( $serveur_data, 2 );
			
			if ($serveur_data === false) {
				abstract_log::onWarning_standard ( "Pas de configuration pour le serveur : " . $serveur_data ["name"] );
				continue;
			}
			
			if ($soapClient_preferences->connect ( $serveur_data ["name"] ) === false) {
				abstract_log::onError_standard ( "Pas de connexion au sitescope" );
				continue;
			}
			
			$liste_preferences = $soapClient_preferences->retrouve_toutes_les_preferences ();
			if ($liste_preferences === false) {
				continue;
			}
			$valeurs_prefs = array ();
			$pos = 0;
			foreach ( $liste_preferences as $pref_type => $data ) {
				foreach ( $data as $key => $value ) {
					if (is_array ( $value )) {
						foreach ( $value as $position => $value ) {
							$valeurs_prefs [$pos] ["serveur_id"] = $serveur_data ["id"];
							$valeurs_prefs [$pos] ["type"] = $pref_type;
							$valeurs_prefs [$pos] ["_key"] = $key . "_" . $position;
							$valeurs_prefs [$pos] ["_value"] = $value;
							$pos ++;
						}
					} else {
						$valeurs_prefs [$pos] ["serveur_id"] = $serveur_data ["id"];
						$valeurs_prefs [$pos] ["type"] = $pref_type;
						$valeurs_prefs [$pos] ["_key"] = $key;
						$valeurs_prefs [$pos] ["_value"] = $value;
						$pos ++;
					}
				}
			}
			
			if ($soapClient_sitescope->valide_presence_sitescope_data ( $serveur_data ["name"] ) === false) {
				abstract_log::onWarning_standard ( "Pas de configuration pour le serveur : " . $serveur_data ["name"] );
				continue;
			}
			
			if ($soapClient_sitescope->connect ( $serveur_data ["name"] ) === false) {
				abstract_log::onError_standard ( "Pas de connexion au sitescope" );
				continue;
			}
			
			$soapClient_sitescope->retrouve_toutes_les_preferences ();
			foreach ( $soapClient_sitescope->getListePrefs () as $key => $value ) {
				if (is_array ( $value )) {
					foreach ( $value as $position => $value ) {
						$valeurs_prefs [$pos] ["serveur_id"] = $serveur_data ["id"];
						$valeurs_prefs [$pos] ["type"] = "GlobalPrefs";
						$valeurs_prefs [$pos] ["_key"] = $key . "_" . $position;
						$valeurs_prefs [$pos] ["_value"] = $value;
						$pos ++;
					}
				} else {
					$valeurs_prefs [$pos] ["serveur_id"] = $serveur_data ["id"];
					$valeurs_prefs [$pos] ["type"] = "GlobalPrefs";
					$valeurs_prefs [$pos] ["_key"] = $key;
					$valeurs_prefs [$pos] ["_value"] = $value;
					$pos ++;
				}
			}
			
			$liste_differences = $compare_fonctions->compare_prefs ( $valeurs_prefs, $db_sitescope->requete_select_preferences_sans_id ( $serveur_data ["id"] ), $db_sitescope->renvoi_table ( "preferences" ), $serveur_data ["id"] );
			sitescope_functions_locales::applique_sql ( $db_sitescope, $liste_differences, $dry_run );
			unset ( $liste_differences );
			unset ( $valeurs_prefs );
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
