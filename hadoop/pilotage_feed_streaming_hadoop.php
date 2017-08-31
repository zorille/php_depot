#!/usr/bin/php
<?php
/**
 * Permet de faire un streamingrtdumper.
 *
 * @author dvargas
 * @package Pilotage
 */
$rep_document = dirname ( $argv [0] ) . "/../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

/**
 * Help
 */
function help_feed_streaming_hadoop() {
	$help = array (
			"titre" => "PILOTEFEEDSTREAMINGHADOOP.PHP" 
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "\t--nb_cpu  nombre de cpu pour les services";
	
	fonctions_standards::affichage_standard_help ( $help );
	
	fonctions_standards::help_fonctions_standard ( "oui", true, false, false, false, false, false );
	$methodes = array (
			"streamingrtdumper",
			"commonTools_pilotes",
			"dates",
			"uuid",
			"slurm",
			"database",
			"db",
			"fonctions_standards_sgbd",
			"logs" 
	);
	foreach ( $methodes as $methode ) {
		if (method_exists ( $methode, "help" )) {
			fonctions_standards::affichage_standard_help ( call_user_func ( $methode . "::help" ) );
		}
	}
	echo "[Exit]0\n";
	exit ( 0 );
}

//Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" )) {
	help_feed_streaming_hadoop ();
}

//Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

$continue = true;

//On creer la liste des dates
$liste_dates = dates::creer_dates ( $liste_option );

//fichier_uuid pour la distribution
if ($liste_option->verifie_option_existe ( "dossier_sortie", true ) === false) {
	$liste_option->setOption ( "dossier_sortie", "/tmp" );
}

//fichier_uuid pour la distribution
if ($liste_option->verifie_option_existe ( "fichier_uuid", true ) === false) {
	$liste_option->setOption ( "fichier_uuid", "uuid" );
}

/******* Gestion des BASES de DONNEES ********/
$connexion = array ();
$connexion = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
$mongo = fonctions_standards_sgbd::recupere_db_mongodbAbstract ( $connexion, true );
/******* FIN de la Gestion des BASES de DONNEES ********/

$type_traitement = "feedstreaminghadoop";

if ($continue && $mongo && $liste_dates) {
	
	foreach ( $liste_dates->getListeDates () as $date ) {
		$nbjobs = 0;
		$liste_jobid = array ();
		
		$pilotesid = $mongo->requete_insere_dans_pilotes ( $type_traitement, $liste_option->getOption ( "chaine_prod" ), 0, "en cours", $date, date ( "Ymd H:i:s" ), 0 );
		
		//On insère un job
		$jobid = $mongo->requete_insere_dans_jobs ( $type_traitement, 0, "distribution", "hadoop", $date, date ( "Ymd H:i:s" ), 0, 0 );
		$liste_jobid [$nbjobs] = $jobid;
		$nbjobs ++;
		
		$nom_fichier = $liste_option->getOption ( "fichier_uuid" ) . "_" . $date;
		$fichier_sortie = $liste_option->getOption ( "dossier_sortie" ) . "/" . $nom_fichier;
		//nettoyage avant de demarrer
		fichier::supprime_fichier ( $fichier_sortie );
		
		$fichier_uuid = fichier::creer_fichier ( $liste_option, $fichier_sortie, "oui" );
		$fichier_uuid->ouvrir ( "w" );
		//Pour chaque uuid trouve, on verifie la liste des fichiers a traiter
		try {
			//On retrouve les streamings que contient la mongo
			//$resultat_streamings=$mongo->requete_select_streaminglines('','',$date);
			$resultat_streamings = $mongo->requete_select_reports ( '', '', $date, '', 'streaming_day' );
			if ($resultat_streamings === false) {
				abstract_log::onWarning_standard ( "Aucun streaming a dumper." );
				continue;
			}
			foreach ( $resultat_streamings as $fichier ) {
				$fichier_uuid->ecrit ( $fichier ["uuid"] . "\n" );
			}
		} catch ( MongoCursorException $e ) {
			abstract_log::onError_standard ( $e->getMessage () );
			$mongo->requete_update_dans_jobs ( $jobid, "__no_update", "__no_update", "erreur", 1, "__no_update", "__no_update", date ( "Ymd H:i:s" ) );
			break;
		}
		
		$fichier_uuid->close ();
		$mongo->requete_update_dans_jobs ( $jobid, "__no_update", "__no_update", "ok", 0, "__no_update", "__no_update", date ( "Ymd H:i:s" ) );
		
		$jobid = $mongo->requete_insere_dans_jobs ( $type_traitement, 0, "distribution", "hadoop", $date, date ( "Ymd H:i:s" ), 0, 0 );
		$liste_jobid [$nbjobs] = $jobid;
		$nbjobs ++;
		
		$transport = new TSocket ( $liste_option->getOption ( array (
				"thrift_hadoop_serveur" 
		) ), $liste_option->getOption ( array (
				"thrift_hadoop_port" 
		) ) );
		$protocol = new TBinaryProtocol ( $transport );
		$client = new ThriftHiveClient ( $protocol );
		
		try {
			$transport->open ();
			$client->execute ( 'USE streaming' );
			$client->execute ( "ALTER TABLE uuid ADD IF NOT EXISTS  PARTITION (dt=" . $date . ")" );
			$transport->close ();
		} catch ( TException $e ) {
			echo "ERREUR : " . $e->getMessage () . "\n";
		}
		
		$flux = fonctions_standards_flux::creer_fonctions_standards_flux ( $liste_option );
		
		$flux->verifie_variables_ftp ( $liste_option, "hadoop" );
		
		$hit = hit_sender::creer_hit_sender ();
		$hit->connectService ( "ftp://" . $liste_option->getOption ( array (
				"ftp",
				"hadoop",
				"serveur" 
		) ) . ":" . $liste_option->getOption ( array (
				"ftp",
				"hadoop",
				"port" 
		) ) . "/user/hive/warehouse/streaming.db/uuid/dt=" . $date . "/uuid" );
		$hit->setUserPasswd ( $liste_option->getOption ( array (
				"ftp",
				"hadoop",
				"user" 
		) ), $liste_option->getOption ( array (
				"ftp",
				"hadoop",
				"passwd" 
		) ) );
		$hit->setTimeout ( 100 );
		$hit->setVerbose ();
		$hit->setEpsv ( false );
		$hit->ftp_curl_put ( $fichier_sortie, false );
		$liste = $hit->ftp_curl_list ();
		//$liste_dossier=preg_split('/[\r\n]+/', $liste, -1, PREG_SPLIT_NO_EMPTY);
		$hit->close ();
		
		$mongo->requete_update_dans_jobs ( $jobid, "__no_update", "__no_update", "ok", 0, "__no_update", "__no_update", date ( "Ymd H:i:s" ) );
		
		//fichier::supprime_fichier($fichier_sortie);
		$mongo->requete_update_dans_pilotes ( $pilotesid, "__no_update", "__no_update", $nbjobs, "ok", "__no_update", "__no_update", date ( "Ymd H:i:s" ) );
	}
} else {
	abstract_log::onError_standard ( "Il manque au moins un parametre." );
}

abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
