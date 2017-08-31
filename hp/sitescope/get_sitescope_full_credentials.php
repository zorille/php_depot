#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage sitescope
 */
$INCLUDE_SITESCOPE=true;
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
	$help [$fichier] ["text"] [] .= "Permet d'extraire la liste des credentials d'un ou plusieurs sitescope";
	
	$class_utilisees = array (
			"fichier",
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

$soapClient_preferences = sitescope_soap_preferences::creer_sitescope_soap_preferences ( $liste_option );
$compare_fonctions = sitescope_compare_tables::creer_sitescope_compare_tables ( $liste_option );

// On se connecte a la base SiteScope de Hob
$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
$db_sitescope = fonctions_standards_sgbd::recupere_db_sitescope ( $connexion_db );

$dry_run = $liste_option->verifie_option_existe ( "dry-run" );

if ($continue && $soapClient_preferences && $db_sitescope && $compare_fonctions) {
	// On recupere la liste des serveur
	$liste_servers_actif = $db_sitescope->requete_select_standard ( 'serveur', array (
			"actif" => "1" 
	), "id ASC" );
	// On se connecte au sitescope demande
	if ($liste_servers_actif !== false) {
		foreach ( $liste_servers_actif as $serveur_data ) {
			abstract_log::onInfo_standard ( "Sitescope : " . $serveur_data ["name"] );
			abstract_log::onDebug_standard ( $serveur_data, 2 );
						
			if ($soapClient_preferences->valide_presence_sitescope_data ( $serveur_data ["name"] ) === false) {
				abstract_log::onWarning_standard ( "Pas de configuration pour le serveur : " . $serveur_data ["name"] );
				continue;
			}
			
			if ($soapClient_preferences->connect ( $serveur_data ["name"] ) === false) {
				abstract_log::onError_standard ( "Pas de connexion au sitescope" );
				continue;
			}
			
			$soapClient_preferences->setArbreCredentials ( array () );
			$soapClient_preferences->retrouve_arbre_credentials ();
			
			$datas_finales = $soapClient_preferences->getArbreCredentials ();
			abstract_log::onDebug_standard ( $datas_finales, 1 );
			$liste_creds = array ();
			$pos = 0;
			foreach ( $datas_finales as $machine => $datas ) {
				$row = array ();
				$row ["serveur_id"] = $serveur_data ["id"];
				$row ["type"] = "profil";
				$row ["name"] = $datas ["_name"];
				foreach ( $datas as $key => $value ) {
					if (is_string ( $value )) {
						$value = trim ( $value );
						if ($value == "") {
							continue;
						}
					}
					switch ($key) {
						case "_name" :
						case "objcategory" :
							continue 2;
					}
					$row ["_key"] = $key;
					$row ["_value"] = $value;
					$liste_creds [$pos] = $row;
					$pos ++;
				}
			}
			
			$liste_differences = $compare_fonctions->compare_creds ( $liste_creds, $db_sitescope->requete_select_credentials_sans_id ( $serveur_data ["id"], "profil" ), $db_sitescope->renvoi_table ( "credentials" ), $serveur_data ["id"] );
			sitescope_functions_locales::applique_sql ( $db_sitescope, $liste_differences, $dry_run );
			unset ( $liste_differences );
			unset ( $liste_creds );
			
			$soapClient_preferences->setArbreMachines ( array () );
			$soapClient_preferences->retrouve_arbre_machines ();
			
			$datas_finales = $soapClient_preferences->getArbreMachines ();
			abstract_log::onDebug_standard ( $datas_finales, 1 );
			$liste_creds = array ();
			$pos = 0;
			foreach ( $datas_finales as $machine => $datas ) {
				if (! isset ( $datas ["_name"] )) {
					continue;
				}
				if(isset($datas ["_credentials"]) && $datas ["_credentials"]!=""){
					//il y a un profil defini
					$row ["serveur_id"] = $serveur_data ["id"];
					$row ["type"] = "serveur";
					$row ["name"] = $datas ["_name"];
					$row ["_key"] = "_credentials";
					$row ["_value"] = $datas ["_credentials"];
					$liste_creds [$pos] = $row;
					$pos ++;
					
					continue;
				}
				
				
				$row = array ();
				$row ["serveur_id"] = $serveur_data ["id"];
				$row ["type"] = "serveur";
				$row ["name"] = $datas ["_name"];
				foreach ( $datas as $key => $value ) {
					if (is_string ( $value )) {
						$value = trim ( $value );
						if ($value == "") {
							continue;
						}
					}
					switch ($key) {
						case "_name" :
						case "_id" :
						case "_host" :
						case "_description" :
						case "_os" :
						case "_remoteEncoding" :
						case "_status" :
						case "_initShellEnvironment" :
						case "_trace" :
						case "inTest" :
						case "_lastTest" :
						case "objcategory" :
							continue 2;
					}
					$row ["_key"] = $key;
					$row ["_value"] = $value;
					$liste_creds [$pos] = $row;
					$pos ++;
				}
			}
			$liste_differences = $compare_fonctions->compare_creds ( $liste_creds, $db_sitescope->requete_select_credentials_sans_id ( $serveur_data ["id"], "serveur" ), $db_sitescope->renvoi_table ( "credentials" ), $serveur_data ["id"] );
			sitescope_functions_locales::applique_sql ( $db_sitescope, $liste_differences, $dry_run );
			unset ( $liste_differences );
			unset ( $liste_creds );
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
