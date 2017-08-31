#!/usr/bin/php
<?php
/**
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage sitescope
 */

//Deplacement pour joindre le repertoire lib
$deplacement = "/../../../..";

if (isset ( $_SERVER ) && isset ( $_SERVER ["SCRIPT_FILENAME"] )) {
	$rep_document = dirname ( $_SERVER ["SCRIPT_FILENAME"] ) . $deplacement;
	$liste_variables_systeme = array (
			"conf" => array (
					$rep_document . "/conf_clients/cacti/prod_MUT_cacti.xml",
					$rep_document . "/conf_clients/database/prod_cacti.xml" 
			),
			"no_mail" => "" 
	);
} else {
	$rep_document = dirname ( $argv [0] ) . $deplacement;
}

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
	$help [$fichier] ["text"] [] .= "Permet de recupere le nombre de CI par client";
	$help [$fichier] ["text"] [] .= "\t--client liste des clients a selectionner";
	$help [$fichier] ["text"] [] .= "\t--nom 'PTLP%' liste de nom de machine a filtrer (c'est un like sql, le % est possible)";
	
	$class_utilisees = array ();
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

/**
 * Main programme
 * Code retour en 2xxx en cas d'erreur
 * @ignore
 *
 * @param options $liste_option Reference sur les options 	
 * @param logs $fichier_log Reference sur les logs
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	// On se connecte a la base SiteScope de Hob
	$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
	$db_sitescope = fonctions_standards_sgbd::recupere_db_sitescope ( $connexion_db );
	
	if (! $db_sitescope) {
		abstract_log::onError_standard ( "Erreur dans les variables necessaires", "", 2000 );
		return false;
	}
	
	if ($liste_option->verifie_option_existe ( "nom" ) === false) {
		$liste_nom = array (
				0 => "" 
		);
	} else {
		$liste_nom = $liste_option->getOption ( "nom" );
		if (! is_array ( $liste_nom )) {
			$liste_nom = array (
					$liste_nom 
			);
		}
	}
	
	$liste_serveur_sitescope = array ();
	$liste_serveur = $db_sitescope->requete_select_standard ( "serveur", array (
			"customer" => $liste_option->getOption ( "client" ),
			"actif" => 1 
	) );
	if ($liste_serveur === false) {
		abstract_log::onError_standard ( "Probleme durant la requete", "", 2002 );
		return false;
	}
	foreach ( $liste_serveur as $serveur ) {
		$liste_serveur_sitescope [$serveur ["customer"]] [$serveur ["name"]] = $serveur ["id"];
	}
	
	foreach ( $liste_serveur_sitescope as $customer => $liste_sitescope ) {
		foreach ( $liste_nom as $nom ) {
			//if ($liste_option->verifie_option_existe ( "par_serveur" ) === false) {
			$liste_CI_globale = $db_sitescope->compte_ci_par_client ( $customer, $liste_sitescope, $nom );
			
			if ($liste_CI_globale === false) {
				abstract_log::onError_standard ( "Probleme durant la requete", "", 2002 );
				return false;
			}
			foreach ( $liste_CI_globale as $compte_serveurs ) {
				abstract_log::onInfo_standard ( $customer . " " . $nom . " : " . $compte_serveurs ["compteur"] );
			}
			
			if ($liste_option->verifie_option_existe ( "par_os" ) !== false) {
				$liste_CI_par_os = $db_sitescope->compte_ci_par_client_et_os ( $liste_sitescope, $nom, "NT" );
				if ($liste_CI_par_os === false) {
					abstract_log::onError_standard ( "Probleme durant la requete", "", 2002 );
					return false;
				}
				foreach ( $liste_CI_par_os as $compte_serveurs ) {
					abstract_log::onInfo_standard ( $customer . " " . $nom . " os=WINDOWS : " . $compte_serveurs ["compteur"] );
				}
				
				$liste_CI_par_os = $db_sitescope->compte_ci_par_client_et_os ( $liste_sitescope, $nom, "!NT" );
				if ($liste_CI_par_os === false) {
					abstract_log::onError_standard ( "Probleme durant la requete", "", 2002 );
					return false;
				}
				foreach ( $liste_CI_par_os as $compte_serveurs ) {
					abstract_log::onInfo_standard ( $customer . " " . $nom . " os=LINUX/UNIX : " . $compte_serveurs ["compteur"] );
				}
			}
		}
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
