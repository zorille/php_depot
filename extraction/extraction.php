#!/usr/bin/php
<?php
/**
 * Permet de faire des extractions de donnees clientes.
 * @author dvargas
 * @package Extraction
 */
$rep_document = dirname ( $argv [0] ) . "/../..";
/**
 * Permet de charger les librairies necessaire.
 */
require_once $rep_document . "/php_framework/config.php";

/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/algorythme.class.php";
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/extraction.inc.php";

if ($liste_option->verifie_option_existe ( "use_storage_engine" ) !== false) {
	/**
	 * Librairies specifiques au programme
	 */
	require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/extraction_storage_engine.inc.php";
} else {
	/**
	 * Librairies specifiques au programme
	 */
	require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/extraction_sqlite.inc.php";
}

//Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ) !== false)
	help ();

//$commonTools = commonTools::creer_commonTools ( $liste_option );
$flag = true;

//Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

if ($liste_option->verifie_option_existe ( "fonctions_client", true ) !== false) {
	require_once $liste_option->getOption ( "rep_scripts" ) . "/" . $liste_option->getOption ( "fonctions_client" );
} else {
	require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/fonctions_client_standard.php";
}

$workspace = gestion_workspace::creer_gestion_workspace ( $liste_option );

//On creer la liste des dates a creer
$liste_dates = dates::creer_dates ( $liste_option );
abstract_log::onDebug_standard ( "Liste des dates : ", 2 );
abstract_log::onDebug_standard ( $liste_dates, 2 );
if (count ( $liste_dates ) == 0) {
	abstract_log::onError_standard ( "Pas de dates !", "" );
	$flag = false;
}

if ($flag) {
	
	$donnees_resultat = array ();
	
	//on charge les donnees clientes
	//au format :
	//array (
	//[serial] (
	//	[date] (
	//		[champ](
	//			valeur1
	//			valeur2
	//			.
	//			.
	//			valeurn
	//			)
	//		)
	//		[champ2] ....
	//	)
	//	[date2] ...
	//[serial2] ...
	//)
	//conditions :
	//	-le nombre de valeurs par champ doit etre identique
	//	-le nombre de champs par date doit etre identique
	

	$donnees = extraire_donnees_sqlite ( $liste_option, $liste_serial, $liste_dates ); //fonction proprietaire
	

	//On creer l'algorythme
	$algorythme = algorythme::creer_algorythme ( $liste_option, $donnees, $liste_option->getOption ( "algorythme" ), $liste_dates );
	//donnees_resultat est au format :
	//array(
	//[serial ou algo_serial]
	//	[date ou algo_date]
	//		[nom_algo]
	//  .
	//  .
	//)
	$donnees_resultat = $algorythme->calcul ();
	abstract_log::onDebug_standard ( "Resultat des algorithmes : ", 1 );
	abstract_log::onDebug_standard ( $donnees_resultat, 1 );
	
	//donnees_a_enregistrer est au format :
	//array (
	//[nom_du_fichier](
	//	0 => entete separer par des ;
	//	1 => valeur separer par des ;
	//	. => valeur separer par des ;
	//	. => valeur separer par des ;
	//	n => valeur separer par des ;
	//	)
	//[nom_du_fichier2] ...
	//)
	$donnees_a_enregistrer = prepare_donnees ( $liste_option, $donnees_resultat ); //fonction proprietaire
	abstract_log::onDebug_standard ( "donnees preparees : ", 1 );
	abstract_log::onDebug_standard ( $donnees_a_enregistrer, 1 );
	
	$liste_fichier = enregistre_donnees ( $liste_option, $liste_dates, $donnees_a_enregistrer );
	
	if ($liste_option->verifie_option_existe ( "fonctions_client_supplementaire" ) !== false) {
		$retour = fonction_client_supplementaire ( $liste_option, $liste_dates, $liste_fichier );
		if ($retour === false) {
			$fichier_log->setExit ( 1 );
		}
	}
	
	//Gestion du mail
	fonctions_standards_mail::envoieMail_standard ( $liste_option, "no_sujet", array (
			"text" => "Bonjour, \n\n Ci-joint votre extraction." 
	), $liste_fichier );
	
	if ($liste_option->verifie_option_existe ( "fichier[@nettoyage='oui']", true ) !== false) {
		foreach ( $liste_fichier as $fichier ) {
			fichier::supprime_fichier ( $fichier );
		}
	}
	if ($liste_option->verifie_option_existe ( "nettoie_workspace" ) !== false) {
		$workspace->supprime_workspace ();
	}
} else {
	$fichier_log->exit = 1;
}
//pas d'extraction si le flag est a false


abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );
exit ( $fichier_log->renvoiExit () );
?>
