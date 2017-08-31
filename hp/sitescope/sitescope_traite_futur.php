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
	$help [$fichier] ["text"] [] .= "\t--dry-run Ne fait pas les mises a jour de la base et les desactivations";
	
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

/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/sitescope_tasks_functions.class.php";
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/sitescope_functions_locales.class.php";

function principale(&$liste_option, &$fichier_log) {
	$sitescope_tasks = sitescope_tasks_functions::creer_sitescope_tasks_functions ( $liste_option, false );
	$sitescope_functions_locales = sitescope_functions_locales::creer_sitescope_functions_locales ( $liste_option );
	$sitescope_fs = sitescope_fonctions_standards::creer_sitescope_fonctions_standards ( $liste_option );
	
	// On se connecte a la base SiteScope de Hob
	$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
	$db_sitescope = fonctions_standards_sgbd::recupere_db_sitescope ( $connexion_db );
	
	if (! $db_sitescope || ! $sitescope_tasks || ! $sitescope_functions_locales || ! $sitescope_fs) {
		abstract_log::onError_standard ( "Erreur dans les variables necessaires" );
		return false;
	}
	//On prend la date du jour
	$now = $sitescope_tasks->getCurrentDate ();
	
	//On recupere la liste des serveur actif
	$liste_servers_actif = $db_sitescope->requete_select_standard ( 'serveur', array (
			"actif" => "1" 
	), "id ASC" );
	
	// On valide la liste
	if ($liste_servers_actif === false) {
		abstract_log::onError_standard ( "Pas de serveur actif" );
		return false;
	}
	
	// En premier lieu, on connecte le maximun de sitescope
	$liste_noms_sis = array ();
	foreach ( $liste_servers_actif as $serveur_data ) {
		abstract_log::onInfo_standard ( "Sitescope : " . $serveur_data ["name"] );
		abstract_log::onDebug_standard ( $serveur_data, 2 );
		
		$liste_noms_sis [$serveur_data ["id"]] = $serveur_data ["name"];
	}
	
	// On recupere la liste des plannings du serveur
	$liste_planning_serveur = $sitescope_functions_locales->recupere_liste_planning ( $db_sitescope );
	// Si on a un planning invalide
	if ($liste_planning_serveur === false || count ( $liste_planning_serveur ) === 0) {
		//Il n'y a pas d'entree dans le planning
		return false;
		// sinon on tente les connexions
	} elseif ($sitescope_fs->connexion_soap_configuration_de_tous_les_sitescopes ( $liste_noms_sis ) === false) {
		//Les connexions ont echoues
		return abstract_log::onError_standard ( "Pas de connexion aux sitescopes" );
	}
	
	//Pour toutes les entrees du planning, on les traites
	foreach ( $liste_planning_serveur as $planning_data ) {
		abstract_log::onInfo_standard ( "On verifie l'entree du planning : " . $planning_data ["reason"] );
		
		//On ne traite que la gestion des moniteurs sitescope
		switch (strtoupper ( $planning_data ["operation"] )) {
			case "ENABLE" :
			case "DISABLE" :
				break;
			default :
				continue 2;
		}
		
		//Si le serveur n'est pas connecte, on passe a au moniteur suivant
		if (! isset ( $liste_noms_sis [$planning_data ["serveur_id"]] )) {
			continue;
		}
		
		$temps_desactivation = $sitescope_functions_locales->gere_entree_planning ( $sitescope_tasks, $planning_data );
		if ($temps_desactivation === false) {
			continue;
		}
		
		// On supprime l'entree dans planning car l'horaire correspond
		$db_sitescope->requete_delete_standard ( 'planning', array (
				"id" => $planning_data ["id"] 
		) );
		//On transmet la demande 
		$sitescope_functions_locales->gere_appel_ws ( $db_sitescope, $sitescope_tasks, $sitescope_fs, $planning_data, $liste_noms_sis, $temps_desactivation, $now );
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
