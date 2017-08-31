#!/usr/bin/php
<?php
/**
 * Programme d'envoi de mail.
 * @author dvargas
 * @package Envoi_mail
*/
$rep_document = dirname ( $argv [0] ) . "/../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
*/
require_once $rep_document . "/php_framework/config.php";

/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/prepare_mail.class.php";

/**
 * @ignore
 * Affiche le help.<br>
 * Cette fonction fait un exit.
 * Arguments reconnus :<br>
 * --help
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
	$help [$fichier] ["text"] [] .= "Permet d'envoyer un mail par la ligne de commande";
	$help [$fichier] ["text"] [] .= "\t--liste_fichier Liste des fichiers a attacher";
	$help [$fichier] ["text"] [] .= "\t--email_corp_text text a ajouter dans le corp de l'email, prioritaire sur le fichier";
	$help [$fichier] ["text"] [] .= "\t--fichier_corp_text fichier contenant le texte du corp de l'email";
	$help [$fichier] ["text"] [] .= "\t--email_corp_html html  a ajouter dans le corp de l'email, prioritaire sur le fichier";
	$help [$fichier] ["text"] [] .= "\t--fichier_corp_html fichier contenant le html du corp de l'email";
	
	$class_utilisees = array ( 
			"message",
			"fichier" 
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

//Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();
	
	//Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

$liste_fichier = prepare_mail::prepare_liste_fichier ( $liste_option );
abstract_log::onDebug_standard ( "Liste des fichiers : ", 2 );
abstract_log::onDebug_standard ( $liste_fichier, 2 );

//Traitement du body
$body = array ();
$body ["text"] = prepare_mail::prepare_texte ( $liste_option );
$body ["html"] = prepare_mail::prepare_html ( $liste_option );

//Enfin on envoi le(s) mail(s)
fonctions_standards_mail::envoieMail_standard ( $liste_option, "no_sujet", $body, $liste_fichier );

abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?> 