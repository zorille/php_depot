#!/usr/bin/php
<?php
/**
 * @ignore
 */
/**
 * @author dvargas
 * @package Steria
 * @subpackage Cacti
 */

// Specifiquement pour cacti, on a des INCLUDE qui permettent de charger les APIs de Cacti
$rep_document = dirname ( $argv [0] ) . "/../../../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->renvoie_option ( "rep_scripts" ) . "/lib/config_cacti.php";

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
	$help [$fichier] ["text"] [] .= "Permet d'extraire la liste des donnees d'un cacti";
	$help [$fichier] ["text"] [] .= "\t--cacti_env mut/tlt/dev/perso";
	$help [$fichier] ["text"] [] .= "\t--serveur nom du serveur recherche";
	
	$class_utilisees = array (
			"fichier",
			"wsclient"
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

/**
 * ******** VOTRE CODE A PARTIR D'ICI*********
 */
/**
 * Main programme
 *
 * @ignore
 *
 * @param options &$liste_option        	
 * @param logs &$fichier_log    
 * @param cacti_datas &$cacti_datas
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log, &$cacti_datas) {
	$liste_snmp_client = array ();
	$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
	$db_cacti = fonctions_standards_sgbd::recupere_db_gestion_cacti ( $connexion_db );
	
	$cacti_ws = cacti_wsclient::creer_cacti_wsclient ( $liste_option, $cacti_datas, false );
	// Ne pas gere les certificat https
	$cacti_ws->setValidSSL ( false );
	
	$fonctions_gestion_cacti = gestion_ci_cacti::creer_gestion_ci_cacti ( $liste_option, $db_cacti, $cacti_ws, false );
	
	if (! $cacti_ws || ! $db_cacti || ! $fonctions_gestion_cacti) {
		abstract_log::onError_standard ( "Il manque des variables necessaires", "", 2000 );
		return false;
	}
	
	$resultat = $db_cacti->requete_select_standard ( 'serveur', array (
			"actif" => 1 
	), "id ASC" );
	
	foreach ( $resultat as $serveur ) {
		abstract_log::onInfo_standard ( "Id du serveur = " . $serveur ["id"] );
		//On met la connexion sur le client demande
		if ($cacti_ws->prepare_connexion ( $serveur ["name"] ) === false) {
			//Pas de connexion pour le serveur, on passe au suivant
			continue;
		}
		
		// On prepare la connexion WS ( POC : ajout de _hobinv)
		$fonctions_gestion_cacti->getWSCacti ()
			->getGestionConnexionUrl ()
			->setPrependUrl ( $fonctions_gestion_cacti->getWSCacti ()
			->getGestionConnexionUrl ()
			->getPrependUrl () . "_hobinv" );
		
		// On recupere la liste a ajouter
		$fonctions_gestion_cacti->ajouter_liste_ci_dans_cacti ( $serveur );
		
		//On applique les modifications
		$fonctions_gestion_cacti->modifier_liste_ci_dans_cacti ( $serveur );
		
		// On recupere la liste a supprimer
		$fonctions_gestion_cacti->supprime_liste_ci_de_cacti ( $serveur ["id"], $serveur ["cacti_env"] );
	}
	
	return true;
}

principale ( $liste_option, $fichier_log, $cacti_datas );
/**
 * ********* FIN DE VOTRE CODE ***************
 */
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoie_exit () );
?>
