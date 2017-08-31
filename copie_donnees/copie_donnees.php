#!/usr/bin/php
<?php
/**
 * Permet de copier des donnees entre serveur.
 * @author dvargas

 * @package Lib
 * @subpackage Copie_Donnees
 */
$rep_document = dirname ( $argv [0] ) . "/../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

//Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" )) {
	copie_donnees::help ();
	exit ( $fichier_log->renvoiExit () );
}

//Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

$fichier_a_copier = copie_donnees::creer_copie_donnees ( $liste_option );
$structure_fichier = $fichier_a_copier->copie_donnees ();

if ($structure_fichier === false || (isset ( $structure_fichier ["telecharger"] ) && ($structure_fichier ["telecharger"] === false && $structure_fichier ["mandatory"] === true))) {
	abstract_log::onError_standard ( "COPIE : le fichier n'est pas copie.", $structure_fichier );
	$fichier_log->exit = 1;
}

abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );
$fichier_log->ferme_fichier_log ();

exit ( $fichier_log->renvoiExit () );
?>
