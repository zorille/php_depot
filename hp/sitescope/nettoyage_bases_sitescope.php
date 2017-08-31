#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage sitescope
 */
$rep_document = dirname ( $argv [0] ) . "/../../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/sitescope_tasks_functions.class.php";

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
	$help [$fichier] ["text"] [] .= "Nettoyage de la base sitescope";
	$help [$fichier] ["text"] [] .= "\t--nettoie_tasks_elements_sans_leaf force le nettoyage des tasks elements sans leaf/group";
	$help [$fichier] ["text"] [] .= "\t--nettoie_tasks_elements_sans_task force le nettoyage des tasks elements sans task";
	
	$class_utilisees = array (
			"dates",
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
ini_set ( "memory_limit", '500M' );

function principale(&$liste_option, &$fichier_log) {
	// On se connecte a la base SiteScope de Hob
	$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
	$db_sitescope = fonctions_standards_sgbd::recupere_db_sitescope ( $connexion_db );
	$db_sitescope->setRenvoiePDO ( true );
	
	$liste_dates = dates::creer_dates ( $liste_option );
	
	$tasks_sitescope = sitescope_tasks_functions::creer_sitescope_tasks_functions ( $liste_option, false );
	
	if (! $db_sitescope || ! $liste_dates || ! $tasks_sitescope) {
		abstract_log::onError_standard ( "Erreur dans les variables necessaires" );
		return false;
	}
	
	// Gestion de histo_planning
	abstract_log::onInfo_standard ( "Gestion de histo_planning" );
	foreach ( $liste_dates->getListeDates () as $date ) {
		abstract_log::onDebug_standard ( "Date en cours : " . $date, 1 );
		try {
			$resultat_db = $db_sitescope->requete_select_standard ( 'histo_planning', array (
					"when" => "< '" . $liste_dates->extraire_date_mysql_standard ( $date ) . "'" 
			), "histo_id ASC" );
			if ($resultat_db === false) {
				continue;
			}
		} catch ( Exception $e ) {
			continue;
		}
		
		foreach ( $resultat_db as $row ) {
			abstract_log::onInfo_standard ( "Id en cours : " . $row ["histo_id"] );
			$db_sitescope->requete_delete_standard ( 'histo_planning', array (
					"histo_id" => $row ["histo_id"] 
			) );
		}
	}
	
	// Gestion des tasks_elements sans moniteurs
	if ($liste_option->verifie_option_existe ( "nettoie_tasks_elements_sans_leaf" ) !== false) {
		abstract_log::onInfo_standard ( "Gestion des tasks_elements sans moniteurs" );
		$ts_1_mois = 2678400;
		
		// On nettoie les entrées superieur à 1 mois
		$liste_to_clean = $db_sitescope->requete_select_standard ( 'tasks_elements', array (
				"not_exist" => "1" 
		), "ele_id ASC" );
		if ($liste_to_clean === false) {
			$liste_to_clean = array ();
		}
		$liste_restant_a_traiter = array ();
		foreach ( $liste_to_clean as $ele_to_clean ) {
			$ts_not_exist = $liste_dates->timestamp_mysql_date ( $ele_to_clean ["not_exist_since"] );
			$diff = time () - $ts_not_exist;
			// Si le since est superieur a 1 mois
			if ($diff > $ts_1_mois) {
				// On supprime
				$db_sitescope->requete_delete_standard ( 'tasks_elements', array (
						"ele_id" => $ele_to_clean ["ele_id"] 
				) );
			} else {
				// Sinon il faut les traiter
				$liste_restant_a_traiter [$ele_to_clean ["ele_id"]] = $ele_to_clean;
			}
		}
		
		// On met a jour la liste des tasks_elements qui n'ont plus de reference en base
		$liste_id_tree = $db_sitescope->retrouve_tasks_elements_sans_leaf ( "1" );
		if ($liste_id_tree) {
			foreach ( $liste_id_tree as $ele_id ) {
				if (isset ( $ele_id ["ele_id"] ) && $ele_id ["ele_id"] != "") {
					if ($ele_id ["not_exist"] == "0") {
						$db_sitescope->requete_update_standard ( 'tasks_elements', array (
								"not_exist" => "1",
								"not_exist_since" => $liste_dates->extraire_date_mysql_timestamp ( time () ) 
						), array (
								"ele_id" => $ele_id ["ele_id"] 
						) );
					} else {
						if (isset ( $liste_restant_a_traiter [$ele_id ["ele_id"]] )) {
							unset ( $liste_restant_a_traiter [$ele_id ["ele_id"]] );
						}
					}
				}
			}
		}
		$liste_id_leaf = $db_sitescope->retrouve_tasks_elements_sans_leaf ( "0" );
		if ($liste_id_leaf) {
			foreach ( $liste_id_leaf as $ele_id ) {
				if (isset ( $ele_id ["ele_id"] ) && $ele_id ["ele_id"] != "") {
					if ($ele_id ["not_exist"] == "0") {
						$db_sitescope->requete_update_standard ( 'tasks_elements', array (
								"not_exist" => "1",
								"not_exist_since" => $liste_dates->extraire_date_mysql_timestamp ( time () ) 
						), array (
								"ele_id" => $ele_id ["ele_id"] 
						) );
					} else {
						if (isset ( $liste_restant_a_traiter [$ele_id ["ele_id"]] )) {
							unset ( $liste_restant_a_traiter [$ele_id ["ele_id"]] );
						}
					}
				}
			}
		}
		// La liste restante doit être remis à zéro !
		foreach ( $liste_restant_a_traiter as $ele_id => $data ) {
			$db_sitescope->requete_update_standard ( 'tasks_elements', array (
					"not_exist" => "0" 
			), array (
					"ele_id" => $ele_id ["ele_id"] 
			) );
		}
	}
	
	// Gestion des tasks_elements sans tasks
	if ($liste_option->verifie_option_existe ( "nettoie_tasks_elements_sans_task" ) !== false) {
		abstract_log::onInfo_standard ( "Gestion des tasks_elements sans tasks" );
		$liste_tasks_elements_sans_tasks = $db_sitescope->retrouve_tasks_elements_sans_tasks ();
		if ($liste_tasks_elements_sans_tasks === false) {
			continue;
		}
		foreach ( $liste_tasks_elements_sans_tasks as $tasks ) {
			abstract_log::onInfo_standard ( "ele_id en cours : " . $tasks ["ele_id"] );
			if ($tasks ["ele_id"] !== "") {
				$db_sitescope->requete_delete_standard ( 'tasks_elements', array (
						"ele_id" => $tasks ["ele_id"] 
				) );
			}
		}
	}
	
	//Gestion du fullpathname de la table tasks_elements
	$liste_to_check = $db_sitescope->requete_select_standard ( 'tasks_elements', array (
			"not_exist" => "0" 
	), "ele_id ASC" );
	if ($liste_to_check === false) {
		continue;
	}
	foreach ( $liste_to_check as $task_ele ) {
		$fullpathname = $tasks_sitescope->prepare_chemin_moniteur ( $db_sitescope, $task_ele ["source_id"], $task_ele ["isgroup"], false );
		if ($fullpathname !== "" && $fullpathname != $task_ele ["fullpathname"]) {
			$db_sitescope->requete_update_standard ( "tasks_elements", array (
					"fullpathname" => $fullpathname 
			), array (
					"ele_id" => $task_ele ["ele_id"] 
			) );
		}
	}
}

principale ( $liste_option, $fichier_log );
/**
 * ********* FIN DE VOTRE CODE ***************
 */
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
