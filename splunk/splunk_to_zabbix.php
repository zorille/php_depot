#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package Zabbix
 * @subpackage Zabbix
 */
$rep_document = dirname ( $argv [0] ) . "/../..";
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
					$fichier . " --help" ), 
			"exemples" => array (), 
			$fichier => array () );
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "--splunk_serveur";
	$help [$fichier] ["text"] [] .= "--splunk_saved_searches";
	$help [$fichier] ["text"] [] .= "--splunk_nom_champ";
	$help [$fichier] ["text"] [] .= "--splunk_seuil_champ";
	$help [$fichier] ["text"] [] .= "--splunk_earliest_time";
	$help [$fichier] ["text"] [] .= "--splunk_latest_time";
	
	
	$class_utilisees = array ( 
			"splunk_datas", 
			"splunk_wsclient", 
			"splunk_datamodel_model", 
			"splunk_search_jobs" );
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option ->verifie_option_existe ( "help" ))
	help ();

abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

function dispatch_saved_searches(&$liste_option, &$splunk_ws) {
	$search = NULL;
	
	$saved_searches_name = splunk_saved_searches_name::creer_splunk_saved_searches_name ( $liste_option, $splunk_ws, $liste_option ->getOption ( 'splunk_saved_searches' ) );
	try {
		$sid = $saved_searches_name ->getSavedSearchDispatch ( array ( 
				'dispatch.earliest_time' => $liste_option->getOption('splunk_earliest_time'),
				'dispatch.latest_time' => $liste_option->getOption('splunk_latest_time'),
#				'dispatch.now' => true, 
				'trigger_actions' => false ) );
	} catch ( Exception $e ) {
		abstract_log::onError_standard ( $e ->getMessage (), "", $e ->getCode () );
		return NULL;
	}
	
	return $sid->sid;
}

function attend_fin_job(&$search_job_id) {
	$job_res = false;
	while ( $job_res===false || $search_job_id ->getContent () ['isDone'] != 1 ) {
		sleep ( 1 );
		$job_res = $search_job_id ->getJobInformations ();
		$search_job_id ->setContent ( $search_job_id ->parseValueInside ( $job_res->content ) );
	}
	
	return true;
}

/**
 * Fonction principale
 * @param options $liste_option
 * @param logs $fichier_log
 * @return true
 */
function principale(&$liste_option, &$fichier_log) {
	if ($liste_option ->verifie_option_existe ( "splunk_serveur" ) === false) {
		return abstract_log::onError_standard ( "Il faut un parametre --splunk_serveur pour travailler." );
	}
	
	if ($liste_option ->verifie_option_existe ( "splunk_saved_searches" ) === false) {
		return abstract_log::onError_standard ( "Il faut un parametre --splunk_saved_searches pour travailler." );
	}
	if ($liste_option ->verifie_option_existe ( "splunk_nom_champ" ) === false) {
		return abstract_log::onError_standard ( "Il faut un parametre --splunk_nom_champ pour travailler." );
	}
	if ($liste_option ->verifie_option_existe ( "splunk_seuil_champ" ) === false) {
		return abstract_log::onError_standard ( "Il faut un parametre --splunk_seuil_champ pour travailler." );
	}
	if ($liste_option ->verifie_option_existe ( "splunk_earliest_time" ) === false) {
		$liste_option->setOption('splunk_earliest_time', '-24h');
	}
	if ($liste_option ->verifie_option_existe ( "splunk_latest_time" ) === false) {
		$liste_option->setOption('splunk_latest_time', 'now');
	}
	
	try {
		$splunk_ws = splunk_wsclient::creer_splunk_wsclient ( $liste_option, splunk_datas::creer_splunk_datas ( $liste_option ) );
		$splunk_ws ->prepare_connexion ( $liste_option ->getOption ( "splunk_serveur" ) ) 
			->setDefaultParams ( array () );
		
		$search_id = dispatch_saved_searches ( $liste_option, $splunk_ws );
		abstract_log::onDebug_standard("Sid : ".$search_id,1);
		
		if ($search_id == NULL) {
			return abstract_log::onError_standard ( "Saved Searches non trouve : " . $liste_option ->getOption ( 'splunk_saved_searches' ) );
		}
		
		//On gere le job en cours
		$search_job_id = splunk_search_jobs_id::creer_splunk_search_jobs_id ( $liste_option, $splunk_ws, $search_id );
		attend_fin_job ( $search_job_id );
		
		//On recupere les resultats
		$job_res = $search_job_id ->recupereListResult ( $search_job_id ->getJobResults ( array ( 
				'count' => 50 ) ) );
		foreach ( $job_res as $resultat ) {
			abstract_log::onDebug_standard($resultat,1);
			if (isset ( $resultat [$liste_option ->getOption ( 'splunk_nom_champ' )] ) && $resultat [$liste_option ->getOption ( 'splunk_nom_champ' )] > $liste_option ->getOption ( 'splunk_seuil_champ' )) {
				echo $liste_option ->getOption ( 'splunk_nom_champ' ) . '=' . $resultat [$liste_option ->getOption ( 'splunk_nom_champ' )] . "\n";
			}
		}
	} catch ( Exception $e ) {
		
		// Exception in splunkApi catched
		abstract_log::onError_standard ( $e ->getMessage (), "", $e ->getCode () );
	}
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log ->renvoiExit () );
?>

