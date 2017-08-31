#!/usr/bin/php
<?php
/**
 * Permet de Nettoyer des Bases de donnees.
 * @author dvargas

 * @package Nettoyage
*/
# =================================
#  Etape 1 : Appel des librairies
# =================================


$rep_document=dirname($argv[0])."/../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
*/
require_once $rep_document."/php_framework/config.php";


# ==================================================
#  Etape 2 : Recuperation des arguments du scripts
# ==================================================
/**
 * Librairies specifiques au programme
*/
require_once $liste_option->getOption("rep_scripts")."/lib/nettoyage_base.inc.php";

//Cette fonction fait un exit 0
if($liste_option->verifie_option_existe("help")) help();

# ==============================================================
#  Etape 3 : Initialisation des variables et connexion a la BD
# ==============================================================

//Le fichier de log est cree
abstract_log::onInfo_standard("Heure de depart : ".date("d/m/Y H:i:s",time()));

//Preparation du mail
$mail=fonctions_standards_mail::creer_liste_mail($liste_option);
//Cette date ne sert qu'a l'affichage
$date_du_jour=date("d/m/Y");

//Les connexions aux bases de donnees
$connexion=fonctions_standards_sgbd::creer_connexion_liste_option($liste_option);
//On a fini d'initialiser les variables

if($connexion)
{
	abstract_log::onInfo_standard("Les variables sont initialisees.");
	$db=nettoie_table($fichier_log,$liste_option,$connexion);
//On traite les donnees
} else $flag_erreur=TRUE;

//Enfin on envoi le(s) mail(s)
fonctions_standards_mail::envoieMail_standard($liste_option,
    "Le nettoyage de la base ".$db." du ".date("d/m/Y",time())." OK",
    array("text"=>"La nettoyage de la base du ".date("d/m/Y",time())." est terminee."));

abstract_log::onInfo_standard("Le nettoyage de la base ".$db." du ".date("d/m/Y",time())." OK");
abstract_log::onInfo_standard("Heure de fin : ".date("d/m/Y H:i:s",time()));

exit($fichier_log->renvoiExit());
?>
