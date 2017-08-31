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
	$help [$fichier] ["text"] [] .= "Permet de mettre a jour la liste des ci de sitescope";
	$help [$fichier] ["text"] [] .= "\t--processus process\t\t\t\tNom du processus a retrouver en memoire";
	$help [$fichier] ["text"] [] .= "\t--fichier_log /var/log/{DATE}_fichier.log\tNom du fichier de log a parser";
	$help [$fichier] ["text"] [] .= "\t--dossier_log   /var/log \t\t\tDossier contenant une liste de fichiers de log a parser (linux)";
	$help [$fichier] ["text"] [] .= "\t--message \"message\"\t\t\t\tmessage a afficher";
	$help [$fichier] ["text"] [] .= "\t--type_os linux/win\t\t\t\tType d'os";
	
	$class_utilisees = array ( 
			"fichier",
			"moniteur",
			"contraintesHoraire",
			"fonctions_standards_moniteur",
			"dates" 
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();
	
	//Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

/**
 * Main programme
 * Code retour en 2xxx en cas d'erreur
 * @ignore
 *
 * @param options $liste_option
 * @param logs $fichier_log
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	if ($liste_option->verifie_option_existe ( "processus" ) === false)
		$liste_option->setOption ( "processus", "no_process" );
	if ($liste_option->verifie_option_existe ( "fichier_log" ) === false)
		$liste_option->setOption ( "fichier_log", "tempo.log" );
	if ($liste_option->verifie_option_existe ( "moniteur_titre" ) === false)
		$liste_option->setOption ( "moniteur_titre", "Check du fichier " . $liste_option->getOption ( "fichier_log" ) );
	if ($liste_option->verifie_option_existe ( "message" ) === false)
		$liste_option->setOption ( "message", "Le process" );
	
	$liste_dates = dates::creer_dates ( $liste_option );
	$date = $liste_dates->recupere_date ( 0, "day" );
	
	if ($liste_option->verifie_option_existe ( "type_os", true ) === false) {
		$liste_option->setOption ( "type_os", "linux" );
	}
	
	//Gestion du moniteur
	$moniteur = moniteur::creer_moniteur ( $liste_option );
	
	//Gestion des horaires
	$horaire = contraintesHoraire::creer_contraintesHoraire ( $liste_option, $date );
	
	//fonction standard monitoring
	$fs_monitoring = fonctions_standards_moniteur::creer_fonctions_standards_moniteur ( $liste_option, $moniteur, $horaire );
	
	//d'abord on test la presence d'un processus en memoire
	$liste_ps = $fs_monitoring->check_processus ( $liste_option->getOption ( "processus" ), $liste_option->getOption ( "type_os" ) );
	
	//On prepare le nom du fichier
	if ($liste_option->verifie_option_existe ( "dossier_log" ) !== false) {
		$dossier_log = $liste_option->getOption ( "dossier_log" );
		$CMD = "ls -tr " . $dossier_log . " | grep " . $nom_fichier . " | grep -v err";
		abstract_log::onDebug_standard ( "Recherche en cours : " . $CMD, 2 );
		$liste_fichiers = fonctions_standards::applique_commande_systeme ( $CMD, "non" );
		if ($liste_fichiers) {
			$liste_fichiers [count ( $liste_fichiers ) - 1] = $dossier_log . "/" . $liste_fichiers [count ( $liste_fichiers ) - 1];
		}
		$dossier = true;
		abstract_log::onDebug_standard ( "Liste des fichiers trouves : ", 2 );
		abstract_log::onDebug_standard ( $liste_fichiers, 2 );
	} else {
		//on trouve le log du rotate le plus recent
		$liste_fichiers = array (
				0,
				str_replace ( "{DATE}", $date, $liste_option->getOption ( "fichier_log" ) ) 
		);
		abstract_log::onDebug_standard ( "Nom du fichier recherche : " . $liste_fichiers [1], 1 );
		$dossier = false;
	}
	
	//Si les process sont termine (car il faut eviter les acces concurrents sur le fichier de log)
	//et qu'il y a des fichiers dans la liste
	if (count ( $liste_ps ) == 1 && (is_array ( $liste_fichiers ) && count ( $liste_fichiers ) > 1)) {
		$erreur = array ();
		
		//On grep les Warning et Error dans le fichier
		$flag_exit = $fs_monitoring->parse_fichier_log_with_mail ( $liste_fichiers [count ( $liste_fichiers ) - 1], false, "Code Exit : 0\n", $liste_option->getOption ( "message" ) . " s'est termin&eacute; en erreur Exit!=0\n", false );
		
		//Si le process n'est pas en cours et il n'y a pas de code Exit
		if (! $flag_exit) {
			$moniteur->ecrit ( "Il n'y a pas de Exit et plus de process en cours sur le serveur\n", "red" );
			$moniteur->red ();
		}
	} elseif (count ( $liste_ps ) == 1 && (is_array ( $liste_fichiers ) && count ( $liste_fichiers ) == 1)) {
		//si il n'y a pas de process et pas de log du jour dans un dossier
		if ($horaire->valideHeureDebutGlobal ()) {
			$moniteur->ecrit ( "Pas de log apr&eacute;s l'heure de d&eacute;but : " . $horaire->getHoraireDebutMax () . " dans le dossier " . $dossier_log . ".\n", "red" );
			$moniteur->red ();
		}
	} elseif (count ( $liste_ps ) >= 1 && $horaire->valideHeureFinGlobal ()) {
		//Si il a toujour un process apres l'heure de fin de traitement
		$moniteur->ecrit ( "Il reste au moins un processus en m&eacute;moire apr&eacute; " . $horaire->getHoraireFinMax () . ".\n", "red" );
		$moniteur->red ();
	}
	
	//Si une alarme est activée, et que
	//soit ce n'est pas encore l'heure de l'alarme
	//soit on a passe l'heure de fin d'alarme
	if ($moniteur->renvoi_couleur () != "green" && ($horaire->valideHeureDebutAlarmeGlobal () === false || $horaire->valideHeureFinAlarmeGlobal ())) {
		//On met l'alarme en yellow
		$moniteur->yellow ();
	} elseif ($moniteur->renvoi_couleur () == "green") {
		$moniteur->ecrit ( "\nPas d'erreur detect&eacute;.\n" );
	}
	$moniteur->send ();
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
