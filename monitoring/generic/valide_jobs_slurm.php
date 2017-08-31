#!/usr/bin/php
<?php
/**
 * Verifie un rotate grace au logs PHP.
 * @author dvargas
 * @package Monitoring
 */
$rep_document = dirname ( $argv [0] ) . "/../../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/../lib/moniteur.inc.php";
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/../lib/fonctions_standards_monitoring.class.php";

//Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

$liste_dates = dates::creer_dates ( $liste_option );

if ($liste_option->verifie_option_existe ( "date_traitement", true ) === false) {
	$liste_option->setOption ( "date_traitement", $liste_dates->recupere_dernier_jour () );
}

//Gestion du moniteur
$moniteur = moniteur::creer_moniteur ( $liste_option, "", true );

//Gestion des horaires
$horaire = contraintesHoraire::creer_contraintesHoraire ( $liste_option, $liste_option->getOption ( "date_traitement" ) );

//fonction standard monitoring
$fs_monitoring = fonctions_standards_moniteur::creer_fonctions_standards_moniteur($liste_option, $moniteur, $horaire);

//On trouve les traitements correspondant au service/date a valider
/******* Gestion des BASES de DONNEES ********/
$connexion = array ();
$connexion = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
$mongo = fonctions_standards_sgbd::recupere_db_mongodbAbstract ( $connexion, true );
/******* FIN de la Gestion des BASES de DONNEES ********/

//gestion du type_traitement
if ($liste_option->verifie_option_existe ( "type_traitement" ) === false) {
	$liste_option->setOption ( "type_traitement", "" );
}
abstract_log::onDebug_standard ( "Type traitement : " . $liste_option->getOption ( "type_traitement" ), 1 );

if ($mongo) {
	try {
		$resultat_runtime_slurm = $mongo->retrouve_liste_jobs ( $liste_dates->recupere_premier_jour () . " 00:00:00", $liste_dates->recupere_dernier_jour () . " 23:59:59", $liste_option->getOption ( "type_traitement" ) );
		if ($resultat_runtime_slurm) {
			if ($resultat_runtime_slurm->count () === 0) {
				if ($horaire->activeAlarme ()) {
					$moniteur->ecrit ( "Pas de job dans slurm apr&egrave;s " . $horaire->getHoraireDebutMax () . " \n" );
					$moniteur->red ();
				} else {
					$moniteur->ecrit ( "Pas de job dans slurm.\n" );
				}
			} else {
				foreach ( $resultat_runtime_slurm as $slurmJob ) {
					$fs_monitoring->valide_job ( $mongo, $moniteur, $horaire, $slurmJob, $liste_option->getOption ( "type_traitement" ) );
				}
			}
		}
	} catch ( MongoCursorException $e ) {
		abstract_log::onError_standard ( $e->getMessage () );
		return array ();
	}
	
	//Si une alarme est activée, et que  
	//soit ce n'est pas encore l'heure de l'alarme
	//soit on a passe l'heure de fin d'alarme 
	if ($moniteur->renvoi_couleur () != "green" && ($horaire->valideHeureDebutAlarmeGlobal () === false || $horaire->valideHeureFinAlarmeGlobal ())) {
		//On met l'alarme en yellow
		$moniteur->yellow ();
	}
} else {
	$moniteur->ecrit ( "Il n'y a pas de connexion a la mongoDb.", "red" );
	$moniteur->yellow ();
}

if ($moniteur->renvoi_couleur () == "green") {
	$moniteur->ecrit ( "Pas d'erreur dans slurm." );
}

$moniteur->send ();

abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );
exit ( $fichier_log->renvoiExit () );
?>
