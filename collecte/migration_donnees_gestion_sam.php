#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package collecte
 */
$INCLUDE_PHPEXCEL = true;

$rep_document = dirname ( $argv [0] ) . "/../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

/**
 * Librairies specifiques au programme
 */
require_once $liste_option->renvoie_option ( "rep_scripts" ) . "/lib/cobra_application_retrieving.class.php";

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
			$fichier => array () 
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Permet d'extraire les donnees collectees en base gestion_sam";
	
	$class_utilisees = array (
			"fichier" 
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();

abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

function principale(
		&$liste_option, 
		&$fichier_log) {
	try {
		// On prepare la liste des tables
		$liste_tables = array (
				"crontabs",
				"logs",
				"nagios",
				"network",
				"os",
				"process",
				"rpm" 
		);
		
		// On se connecte a la base cmdb_vodafone
		$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
		$db_gestion_sam_sqlite = $connexion_db ["gestion_sam_sqlite"];
		$db_gestion_sam = fonctions_standards_sgbd::recupere_db_gestion_sam ( $connexion_db );
		
		$cobra_application_retrieving = cobra_application_retrieving::creer_cobra_application_retrieving ( $liste_option, $db_gestion_sam );
		
		// $liste_serveur = $db_cmdb_vodafone ->select_serveur ();
		$liste_serveur = $db_gestion_sam_sqlite->faire_requete ( "select distinct serveur from os;" );
		
		$row = 1;
		foreach ( $liste_serveur as $row_serveur ) {
			abstract_log::onInfo_standard ( "Serveur en cours : " . $row_serveur ['serveur'] );
			$ci_datas = $db_gestion_sam->requete_select_standard ( "ci", array (
					"name" => $row_serveur ['serveur'] 
			) );
			if (count ( $ci_datas ) == 0) {
				$db_gestion_sam->requete_insert_standard ( "ci", array (
						"id" => fonctions_standards::uuid_perso ( $row_serveur ['serveur'] ),
						"name" => $row_serveur ['serveur'] 
				) );
				$ci_datas = $db_gestion_sam->requete_select_standard ( "ci", array (
						"name" => $row_serveur ['serveur'] 
				) );
			}
			foreach ( $ci_datas as $row_ci ) {
				$Ci_Id = $row_ci ["id"];
			}
			
			foreach ( $liste_tables as $table ) {
				
				$donnees_machine = $db_gestion_sam_sqlite->faire_requete ( "select cle,valeur from " . $table . " where serveur='" . $row_serveur ['serveur'] . "' order by cle" );
				
				$db_gestion_sam->requete_delete_standard ( $table, array (
						"parent_id" => $Ci_Id,
						"table_parent" => "ci" 
				) );
				
				foreach ( $donnees_machine as $row ) {
					if (strtolower ( $table ) == 'process') {
						$cobra_application_retrieving->setDonneesSource ( $row ["valeur"] )
							->retrieving_ccore ( $Ci_Id )
							->retrieving_jcore ( $Ci_Id )
							->retrieving_kannel ( $Ci_Id )
							->retrieving_susan ( $Ci_Id )
							->retrieving_logmon ( $Ci_Id )
							->retrieving_Tower_Watson ( $Ci_Id );
					}
					$db_gestion_sam->requete_insert_standard ( strtolower ( $table ), array (
							"parent_id" => $Ci_Id,
							"key" => $row ["cle"],
							"value" => $row ["valeur"],
							"table_parent" => "ci" 
					) );
				}
			}
		}
		
		$cobra_application_retrieving->update_db ();
	} catch ( Exception $e ) {
		return abstract_log::onError_standard ( $e->getMessage () );
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoie_exit () );
?>
