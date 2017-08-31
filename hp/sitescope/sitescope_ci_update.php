#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage sitescope
 */
$INCLUDE_SITESCOPE=true;
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
	$help [$fichier] ["text"] [] .= "\t--dry-run Ne fait pas les mises a jour de la base";
	
	$class_utilisees = array (
			"fichier",
			"sitescope_datas",
			"sitescope_soap_configuration",
			"sitescope_soap_preferences"
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();
	
	


// Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );
ini_set("memory_limit",'1500M');
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/sitescope_functions_locales.class.php";

$continue = true;

// On se connecte a la base SiteScope de Hob
$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
$db_sitescope = fonctions_standards_sgbd::recupere_db_sitescope ( $connexion_db );

$outils_comparaison= new comparaison_resultat_sql ();
$dry_run=$liste_option->verifie_option_existe ( "dry-run" );


if ($continue && $db_sitescope && $outils_comparaison) {
	
	$liste_ci_actif = $db_sitescope->requete_select_sitescope_ci_from_ci ( );
	$liste_sitescope_ci=$db_sitescope->requete_select_sitescope_ci_synchro();
	// On se connecte au sitescope demande
	if ($liste_ci_actif !== false && $liste_sitescope_ci!==false) {
		$liste_champs = array (
				"customer",
				"ci_name",
				"id"
		);
		$outils_comparaison->synchro_table($liste_ci_actif, $liste_sitescope_ci, "sitescope_ci",$liste_champs);
		
		$liste_modifs = array ();
		$liste_modifs ["supprime"] = $outils_comparaison->getTableauSupprime ();
		$liste_modifs ["ajoute"] = $outils_comparaison->getTableauAjoute ();
		
		sitescope_functions_locales::applique_sql($db_sitescope, $liste_modifs,$dry_run);
	}
} else {
	abstract_log::onError_standard ( "Erreur dans les variables necessaires" );
}

/**
 * ********* FIN DE VOTRE CODE ***************
 */
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
