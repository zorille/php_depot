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
	$help [$fichier] ["text"] [] .= "Permet de gerer la desactivation des alertes des moniteurs de sitescope";
	$help [$fichier] ["text"] [] .= "\t--do_from Nom de la machine qui execute le script";
	$help [$fichier] ["text"] [] .= "\t--type_desactivation moniteur/alerte force le type de desactivation";
	$help [$fichier] ["text"] [] .= "\t--dry-run Ne fait pas les mises a jour de la base et les desactivations";
	
	$class_utilisees = array (
			"fichier",
			"sitescope_fonctions_standards",
			"sitescope_datas",
			"sitescope_soap_configuration",
			"sitescope_soap_preferences",
			"options" 
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
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/sitescope_tasks_functions.class.php";
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/sitescope_functions_locales.class.php";

function principale(&$liste_option, &$fichier_log) {
	if ($liste_option->verifie_option_existe ( "type_desactivation" ) === false) {
		$liste_option->setOption ( "type_desactivation", "no_force" );
	}
	
	// On prepare les variables SiteScope WebService
	$sitescope_tasks = sitescope_tasks_functions::creer_sitescope_tasks_functions ( $liste_option, false );
	$sitescope_fs = sitescope_fonctions_standards::creer_sitescope_fonctions_standards ( $liste_option );
	$sitescope_functions_locales = sitescope_functions_locales::creer_sitescope_functions_locales($liste_option);
	
	// On se connecte a la base SiteScope de Hob
	$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
	$db_sitescope = fonctions_standards_sgbd::recupere_db_sitescope ( $connexion_db );
	
	if (! $db_sitescope || ! $sitescope_tasks || ! $sitescope_functions_locales || ! $sitescope_fs) {
		return abstract_log::onError_standard ( "Erreur dans les variables necessaires" );
	}
	//On prend la date du jour
	$now = $sitescope_tasks->getCurrentDate ();
	
	// On recupere la liste des serveur
	$liste_servers_actif = $db_sitescope->requete_select_standard ( 'serveur', array (
			"actif" => "1" 
	), "id ASC" );
	
	// On valide la liste
	if ($liste_servers_actif === false) {
		return abstract_log::onError_standard ( "Pas de serveur actif" );
	}
	
	// En premier lieu, on connecte le maximun de sitescope
	$liste_noms_sis = array ();
	foreach ( $liste_servers_actif as $serveur_data ) {
		abstract_log::onInfo_standard ( "Sitescope : " . $serveur_data ["name"] );
		abstract_log::onDebug_standard ( $serveur_data, 2 );
		
		$liste_noms_sis [$serveur_data ["id"]] = $serveur_data ["name"];
	}
	
	if ($sitescope_fs->connexion_soap_configuration_de_tous_les_sitescopes ( $liste_noms_sis ) === false) {
		return abstract_log::onError_standard ( "Pas de connexion aux sitescopes" );
	}
	
	// On recupere la liste des tasks du serveur
	$liste_tasks_serveur = $sitescope_functions_locales->recupere_liste_tasks ( $db_sitescope );
	
	// Si on a un planning invalide
	if ($liste_tasks_serveur === false || count ( $liste_tasks_serveur ) === 0) {
		// on stoppe tout
		return false;
		// sinon on tente les connexions
	}
	
	//Pour toutes les entrees dans tasks, on les traites
	foreach ( $liste_tasks_serveur as $task_data ) {
		abstract_log::onInfo_standard ( "On verifie la desactivation de la task : " . $task_data ["reason"] );
		
		// mise a jour du last_check
		$db_sitescope->requete_update_standard ( 'tasks', array (
				"last_check" => $now ["date_mysql"] 
		), array (
				"id" => $task_data ["id"] 
		) );
		
		// On surcharge le type de desactivation
		switch ($liste_option->getOption ( "type_desactivation" )) {
			case "moniteur" :
				$task_data ["type"] = "MONITOR";
				break;
			case "alerte" :
				$task_data ["type"] = "ALERT";
				break;
			case "no_force" :
			default :
			// nothing todo
		}
		
		$temps_desactivation = $sitescope_functions_locales->gere_entree_task ( $sitescope_tasks, $task_data );
		if ($temps_desactivation === false) {
			// On passe a la task suivante
			continue;
		}
		
		// On selectionne la liste des moniteurs a desactiver
		$liste_moniteurs = $db_sitescope->requete_select_standard ( 'tasks_elements', array (
				"task_id" => $task_data ["id"],
				"not_exist" => "0" 
		), "ele_id ASC" );
		if ($liste_moniteurs === false) {
			abstract_log::onError_standard ( "Erreur durant la requete des tasks_elements" );
			// On passe a la task suivante
			continue;
		}
		
		//Pour chaque element de la task
		foreach ( $liste_moniteurs as $task_moniteur ) {
			
			//Si le serveur n'est pas connecte, on passe a au moniteur suivant
			if (! isset ( $liste_noms_sis [$task_moniteur ["serveur_id"]] )) {
				continue;
			}
			
			$planned_data = array (
					"id" => $task_moniteur ["ele_id"],
					"task_id" => $task_data ["id"],
					"source_id" => $task_moniteur ["source_id"],
					"serveur_id" => $task_moniteur ["serveur_id"],
					"user" => $task_data ["user"],
					"reason" => $task_data ["reason"],
					"fixed" => $task_data ["fixed"],
					"duration" => $task_data ["duration"],
					"unit" => $task_data ["unit"],
					"until" => $task_data ["until"],
					"operation" => $task_data ["operation"],
					"type" => $task_data ["type"],
					"isgroup" => $task_moniteur ["isgroup"],
					"immediate" => $task_data ["immediate"],
					"customer" => $task_data ["customer"] 
			);
			
			//On transmet la demande
			if ($sitescope_functions_locales->gere_appel_ws ( $db_sitescope, $sitescope_tasks, $sitescope_fs, $planned_data, $liste_noms_sis, $temps_desactivation, $now )) {
				$db_sitescope->requete_update_standard ( 'tasks', array (
						"last_done" => $now ["date_mysql"] 
				), array (
						"id" => $task_data ["id"] 
				) );
			}
		}
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
