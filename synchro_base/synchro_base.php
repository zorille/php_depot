#!/usr/bin/php
<?php
/**
 * Permet de synchroniser une base avec une autre. 
 * @author dvargas
 * @package SynchroBase
*/
# =================================
#  Description
# =================================
#
# SCRIPT de synchronisation des bases dest et source (chaine unique)


# =================================
#  Etape 1 : Appel des librairies
# =================================
$rep_document = dirname ( $argv [0] ) . "/../..";

ini_set ( "memory_limit", '1500M' );

/**
 * Permet d'inclure toutes les librairies communes necessaires
*/
require_once $rep_document . "/php_framework/config.php";

# ==================================================
#  Etape 2 : Recuperation des arguments du scripts
# ==================================================
/**
 * Librairies specifiques au programme
*/
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/synchro_base.inc.php";

//Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" )) {
	fonctions_standards::help_fonctions_standard ( "oui", false, false, false, false, false, false );
	echo "[Exit]0\n";
	exit ( 0 );
}

# ==============================================================
#  Etape 3 : Initialisation des variables et connexion a la BD
# ==============================================================


//Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

//Preparation du mail
$mail = fonctions_standards_mail::creer_liste_mail ( $liste_option );
//Cette date ne sert qu'a l'affichage
$date_du_jour = date ( "d/m/Y" );

//Les connexions aux bases de donnees
$connexion = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
$bd_source = $connexion ["sql_entree"];
$bd_dest = $connexion ["sql_sortie"];
//On a fini d'initialiser les variables


$synchro = synchro_base::creer_synchro_base ( $liste_option );

if ($bd_source && $bd_dest && $synchro) {
	abstract_log::onInfo_standard ( "Les variables sont initialisees." );
	$synchro->synchro_table ( $liste_option, $bd_source, $bd_dest, $connexion );
	//On traite les donnees
} else {
	$flag_erreur = TRUE;
}

//Enfin on envoi le(s) mail(s)
if ($mail) {
	abstract_log::onInfo_standard ( "Envoi du mail de confirmation." );
	$mail->setSujet ( "Synchro des bases " . $bd_dest->getServeurDatabase () . "." . $bd_dest->getDatabase () . " et " . $bd_source->getServeurDatabase () . "." . $bd_source->getDatabase () . " du " . date ( "d/m/Y", time () ) . " OK" );
	$mail->ecrit ( "La synchronisation des bases du " . date ( "d/m/Y", time () ) . " est terminee." );
	$mail->envoi ();
} else
	abstract_log::onInfo_standard ( "Envoi du mail de confirmation Desactive." );

abstract_log::onInfo_standard ( "Synchro des bases " . $bd_dest->getServeurDatabase () . "." . $bd_dest->getDatabase () . " et " . $bd_source->getServeurDatabase () . "." . $bd_source->getDatabase () . " du " . date ( "d/m/Y", time () ) . " OK" );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
