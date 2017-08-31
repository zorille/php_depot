#!/usr/bin/php
<?php

/**
 * Permet de Nettoyer des fichiers et/ou des dossiers en local ou distant.
 * @author dvargas
 * @package Nettoyage
 */

/**
 * Affiche le help.<br>
 * Cette fonction fait un exit.
 * Arguments reconnus :<br>
 * --help
 */
function help() {
	echo "

	--machines=\"machine1 machine2 ....\"  //optionne Par defaut localhost
	--repertoires=\"rep1 rep2 ....\"			 //optionnel 
	--fichiers=\"fichier1 fichier2 ....\"  //optionnel

		\n";
	fonctions_standards::help_fonctions_standard ( "oui",false,false,false,false,false,false );
	echo "[Exit]0\n";
	exit ( 0 );
}

$rep_document = dirname ( $argv [0] ) . "/../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

//Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();
	
	//Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

$class_flux = fonctions_standards_flux::creer_fonctions_standards_flux ( $liste_option );
$ssh = $class_flux->creer_connexion_ssh ();

$liste_machines = $liste_option->getOption ( "machines", true );
if ($liste_machines) {
	if (! is_array ( $liste_machines )) {
		$liste_machines = explode ( " ", $liste_machines );
	}
} else {
	$liste_machines [0] = "localhost";
}

$liste_repertoires = $liste_option->getOption ( "repertoires", true );
if ($liste_repertoires) {
	if (! is_array ( $liste_repertoires )) {
		$liste_repertoires = explode ( " ", $liste_repertoires );
	}
	
	foreach ( $liste_machines as $machine ) {
		foreach ( $liste_repertoires as $dossier ) {
			$CMD = "rm -Rf " . $dossier;
			abstract_log::onInfo_standard ( "Nettoyage de " . $machine . " pour le repertoire " . $dossier . "." );
			if ($machine != "localhost") {
				if ($ssh && $ssh->setMachineDistante ( $machine )
					->ssh_connect () !== false) {
					$retour = $ssh->ssh_shell_commande ( $CMD, true );
				} else {
					abstract_log::onError_standard ( "Connexion Impossible a " . $machine );
				}
			} else {
				$retour = fonctions_standards::applique_commande_systeme ( $CMD, false );
			}
			abstract_log::onInfo_standard ( "Retour du nettoyage de " . $machine . " pour le repertoire " . $dossier . " : " . $retour [0] );
		}
	}
}

$liste_fichier = $liste_option->getOption ( "fichiers", true );
if ($liste_fichier) {
	if (! is_array ( $liste_fichiers )) {
		$liste_fichiers = explode ( " ", $liste_fichiers );
	}
	
	foreach ( $liste_machines as $machine ) {
		foreach ( $liste_fichiers as $fichier ) {
			$CMD = "rm -f " . $fichier;
			abstract_log::onInfo_standard ( "Nettoyage de " . $machine . " pour le repertoire " . $dossier . "." );
			if ($machine != "localhost") {
				if ($ssh && $ssh->ssh_connect ( $machine ) !== false) {
					$retour = $ssh->ssh_shell_commande ( $CMD, true );
				} else {
					abstract_log::onError_standard ( "Connexion Impossible a " . $machine );
				}
			} else {
				$retour = fonctions_standards::applique_commande_systeme ( $CMD, false );
			}
			abstract_log::onInfo_standard ( "Retour du nettoyage de " . $machine . " pour le fichier " . $fichier . " : " . $retour [0] );
		}
	}
}

abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
