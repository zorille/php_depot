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

if ($liste_option->verifie_option_existe ( "client" ) === false) {
	$liste_option->setOption ( "fichier_sortie", "/tmp/liste_moniteurs_sitescope.csv" );
}

$soapClient_configuration = sitescope_soap_configuration::creer_sitescope_soap_configuration ( $liste_option );
$soapClient_preferences = sitescope_soap_preferences::creer_sitescope_soap_preferences ( $liste_option );
$compare_fonctions = sitescope_compare_tables::creer_sitescope_compare_tables ( $liste_option );

// On se connecte a la base SiteScope de Hob
$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
$db_sitescope = fonctions_standards_sgbd::recupere_db_sitescope ( $connexion_db );

$dry_run = $liste_option->verifie_option_existe ( "dry-run" );

if ($continue && $soapClient_configuration && $soapClient_preferences && $db_sitescope && $compare_fonctions) {
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
			
			abstract_log::onInfo_standard ( "Runtime des cis" );
			if ($soapClient_preferences->connect ( $serveur_data ["name"] ) === false) {
				abstract_log::onError_standard ( "Pas de connexion au sitescope" );
				continue;
			}
			// runtime des cis
			$liste_ci = $db_sitescope->requete_select_standard ( 'ci', array (
					"serveur_id" => $serveur_data ["id"] 
			), "id ASC" );
			if ($liste_ci === false) {
				abstract_log::onError_standard ( "Impossible de lire la table ci" );
				continue;
			}
			$liste_ci_ids = array ();
			foreach ( $liste_ci as $ci ) {
				$liste_ci_ids [$ci ["name"] . $serveur_data ["id"]] = $ci ["id"];
			}
			
			$valeurs_runtime = array ();
			$valeurs_props = array ();
			$pos = 0;
			$soapClient_preferences->retrouve_arbre_machines ();
			foreach ( $soapClient_preferences->getArbreMachines () as $nom => $machine ) {
				$ci_id = "";
				if (trim ( $nom ) == "machines") {
					continue;
				}
				foreach ( $machine as $key => $value ) {
					if ($key == "_name" && isset ( $liste_ci_ids [$value . $serveur_data ["id"]] )) {
						$ci_id = $liste_ci_ids [$value . $serveur_data ["id"]];
						break;
					}
				}
				if ($ci_id == "") {
					abstract_log::onError_standard ( "Le ci_id n'est pas trouve." );
					continue;
				}
				$valeurs_props [$pos] ['ci_id'] = $ci_id;
				$valeurs_props [$pos] ['_key'] = "_remoteID";
				$valeurs_props [$pos] ['_value'] = $nom;
				$valeurs_props [$pos] ['parent_table'] = 'ci';
				$pos ++;
				foreach ( $machine as $key => $value ) {
					if (is_string ( $value ) || is_numeric ( $value )) {
						$value = trim ( $value );
						if ($value == "") {
							continue;
						}
					} else {
						continue;
					}
					
					switch ($key) {
						case "inTest" :
						case "_status" :
						case "_trace" :
							$valeurs_runtime [$pos] ['parent_id'] = $ci_id;
							$valeurs_runtime [$pos] ['_key'] = $key;
							$valeurs_runtime [$pos] ['_value'] = $value;
							$valeurs_runtime [$pos] ['parent_table'] = 'ci';
							break;
						case "_password" :
							continue;
						default :
							$valeurs_props [$pos] ['parent_id'] = $ci_id;
							$valeurs_props [$pos] ['_key'] = $key;
							$valeurs_props [$pos] ['_value'] = $value;
							$valeurs_props [$pos] ['parent_table'] = 'ci';
					}
					$pos ++;
				}
			}
			
			$liste_differences = $compare_fonctions->compare_runtime ( $valeurs_runtime, $db_sitescope->requete_select_runtime_sans_id ( $serveur_data ["id"], 'ci' ), $db_sitescope->renvoi_table ( "runtime" ), $serveur_data ["id"] );
			sitescope_functions_locales::applique_sql ( $db_sitescope, $liste_differences, $dry_run );
			unset ( $liste_differences );
			unset ( $liste_ci_ids );
			unset ( $valeurs_runtime );
			abstract_log::onInfo_standard ( "On met a jour les proprietes des ci" );
			$liste_differences = $compare_fonctions->compare_props ( $valeurs_props, $db_sitescope->requete_select_props_sans_id ( $serveur_data ["id"], 'ci' ), $db_sitescope->renvoi_table ( "props" ), $serveur_data ["id"] );
			sitescope_functions_locales::applique_sql ( $db_sitescope, $liste_differences, $dry_run );
			unset ( $liste_differences );
			unset ( $valeurs_props );
			
			// Runtime des leafs
			abstract_log::onInfo_standard ( "Runtime des leafs" );
			if ($soapClient_configuration->connect ( $serveur_data ["name"] ) === false) {
				abstract_log::onError_standard ( "Pas de connexion au sitescope" );
				continue;
			}
			
			$liste_leaf = $db_sitescope->requete_select_leaf_fullpath ( $serveur_data ["id"] );
			if ($liste_leaf === false) {
				abstract_log::onError_standard ( "Impossible de lire la table leaf" );
				continue;
			}
			
			$valeurs_runtime = array ();
			$pos = 0;
			foreach ( $liste_leaf as $leaf ) {
				$fullpathMonitor = str_replace ( "!", $soapClient_configuration->getSeparateur (), $leaf ["fullpathname"] ) . $soapClient_configuration->getSeparateur () . $leaf ["name"];
				$moniteur_runtime = $soapClient_configuration->getMonitorSnapshots ( $fullpathMonitor, array (
						"name" => "FALSE",
						"full_path" => "FALSE",
						"type" => "TRUE",
						"target_ip" => "FALSE",
						"target_name" => "FALSE",
						"target_display_name" => "TRUE",
						"description" => "FALSE",
						"disable_description" => "TRUE",
						"associated_alerts_disable_description" => "TRUE",
						"acknowledgment_comment" => "TRUE",
						"updated_date" => "TRUE",
						"disable_start_time" => "TRUE",
						"disable_end_Time" => "TRUE",
						"associated_alerts_disable_start_time" => "TRUE",
						"associated_alerts_disable_end_time" => "TRUE",
						"is_disabled_permanently" => "TRUE",
						"is_associated_alerts_disabled" => "TRUE",
						"status" => "TRUE",
						"summary" => "TRUE",
						"availability" => "TRUE",
						"availability_description" => "TRUE" 
				) );
				
				if (is_array ( $moniteur_runtime )) {
					foreach ( $moniteur_runtime as $liste_runtime ) {
						foreach ( $liste_runtime as $runtime ) {
							if (! is_array ( $runtime )) {
								abstract_log::onError_standard ( "Runtime en erreur de : " . $fullpathMonitor );
								continue;
							}
							foreach ( $runtime as $key => $value ) {
								if (is_string ( $value ) || is_numeric ( $value )) {
									$value = trim ( $value );
									if ($value == "") {
										continue;
									}
								} else {
									continue;
								}
								switch ($key) {
									case 'updated_date' :
									case 'disable_start_time' :
									case 'disable_end_Time' :
									case 'associated_alerts_disable_start_time' :
									case 'associated_alerts_disable_end_time' :
										try {
											$value = $liste_dates->extraire_date_mysql_timestamp ( $value );
										} catch ( Exception $e ) {
											$value = false;
										}
								}
								$valeurs_runtime [$pos] ['parent_id'] = $leaf ['id'];
								$valeurs_runtime [$pos] ['_key'] = $key;
								$valeurs_runtime [$pos] ['_value'] = trim ( $value );
								$valeurs_runtime [$pos] ['parent_table'] = 'leaf';
								$pos ++;
							}
						}
					}
				}
			}
			
			$liste_differences = $compare_fonctions->compare_runtime ( $valeurs_runtime, $db_sitescope->requete_select_runtime_sans_id ( $serveur_data ["id"], 'leaf' ), $db_sitescope->renvoi_table ( "runtime" ), $serveur_data ["id"] );
			sitescope_functions_locales::applique_sql ( $db_sitescope, $liste_differences, $dry_run );
			unset ( $liste_differences );
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
