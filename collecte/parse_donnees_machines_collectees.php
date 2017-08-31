#!/usr/bin/php
<?php
/**
 *
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package collecte
 */

// Deplacement pour joindre le repertoire lib
$deplacement = "/../..";
$rep_document = dirname ( $argv [0] ) . $deplacement;

/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

/**
 *
 * @ignore Affiche le help.<br> Cette fonction fait un exit. Arguments reconnus :<br> --help
 */
function help() {
	$fichier = basename ( __FILE__ );
	$help = array (
			"usage" => array (
					$fichier . " --conf [fichiers de conf] [OPTIONS]",
					$fichier . " --help" 
			),
			"exemple" => array (
					"./" . $fichier . " --conf {Chemin vers conf_clients}/database/prod_cmdb_vodafone.xml --repertoire_fichiers ./liste_datas/ --verbose" 
			),
			$fichier => array () 
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Permet de charge les donnees recoltees par le script shell dans la base cmdb_vodafone";
	
	$class_utilisees = array (
			"requete_complexe_cmdb_vodafone" 
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();
function retrouve_donnees($liste_donnees, &$pos) {
	$data = "";
	for($i = ++ $pos; $i < count ( $liste_donnees ); $i ++) {
		if (strpos ( $liste_donnees [$i], "##### " ) !== false) {
			break;
		}
		$data .= $liste_donnees [$i];
	}
	
	$pos = -- $i;
	return $data;
}

// Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );
/**
 * Main programme Code retour en 2xxx en cas d'erreur
 *
 * @ignore
 *
 *
 *
 *
 *
 * @param options $liste_option
 * @param logs $fichier_log
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	/**
	 * ******** VOTRE CODE A PARTIR D'ICI*********
	 */
	if ($liste_option->verifie_option_existe ( "repertoire_fichiers" ) === false) {
		return abstract_log::onError_standard ( "Il faut un --repertoire_fichiers pour travailler." );
	}
	// On se connecte a la base cmdb_vodafone
	$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
	$db_cmdb_vodafone = fonctions_standards_sgbd::recupere_db_cmdb_vodafone ( $connexion_db );
	
	$liste_fichiers = repertoire::lire_repertoire ( $liste_option->getOption ( "repertoire_fichiers" ) );
	
	foreach ( $liste_fichiers as $fichier ) {
		abstract_log::onInfo_standard ( "Fichier en cours : " . $fichier );
		$donnees_fichier = fichier::Lit_integralite_fichier_en_tableau ( $liste_option->getOption ( "repertoire_fichiers" ) . "/" . $fichier );
		
		abstract_log::onDebug_standard ( $donnees_fichier, 1 );
		$donnees = array ();
		for($i = 0; $i < count ( $donnees_fichier ); $i ++) {
			if (strpos ( $donnees_fichier [$i], "##### hostname" ) !== false) {
				$hostname = trim(strtok ( $donnees_fichier [$i + 1], "." ));
				$db_cmdb_vodafone->requete_delete_standard ( "collected_datas", array (
						"serveur" => $hostname 
				) );
			}
			if (strpos ( $donnees_fichier [$i], "##### " ) !== false) {
				$commande = str_replace ( "##### ", "", $donnees_fichier [$i] );
				$donnees = retrouve_donnees ( $donnees_fichier, $i );
				$db_cmdb_vodafone->requete_insert_standard ( "collected_datas", array (
						"serveur" => $hostname,
						"commande" => $commande,
						"resultat" => str_replace ( "\"", "''", str_replace ( "'", "''", $donnees ) ) 
				) );
			}
		}
	}
	
	/**
	 * ********* FIN DE VOTRE CODE ***************
	 */
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoie_exit () );
?>
