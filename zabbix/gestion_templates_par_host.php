#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package Zabbix
 * @subpackage Zabbix
 */
$rep_document = dirname ( $argv [0] ) . "/../..";
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
			"exemples" => array (
					"./".$fichier." --conf {Chemin vers conf_clients}/prod_client_zabbix_serveurs.xml --zabbix_serveur zabbix.dev.client.fr.ghc.local --action ajout --zabbix_templates '{Nom du nouveau template a ajouter}' --verbose",
					"./".$fichier." --conf {Chemin vers conf_clients}/prod_client_zabbix_serveurs.xml --zabbix_serveur zabbix.dev.client.fr.ghc.local --action supp --zabbix_templates '{Nom du template a supprimer}' --verbose",
			),
			$fichier => array () 
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Permet de gerer la liste des templates d'un host dans zabbix";
	$help [$fichier] ["text"] [] .= "\t--zabbix_serveur Nom du zabbix a utiliser";
	$help [$fichier] ["text"] [] .= "\t--action ajout|supp Action a faire";
	
	$class_utilisees = array (
			"zabbix_host",
			"zabbix_templates"
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();

abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

function principale(&$liste_option, &$fichier_log) {
	//On se connecte au zabbix
	try {
		$zabbix_ws = zabbix_wsclient::creer_zabbix_wsclient ( $liste_option, zabbix_datas::creer_zabbix_datas ( $liste_option ) );
		$zabbix_templates = zabbix_templates::creer_zabbix_templates ( $liste_option, $zabbix_ws );
		$zabbix_templates->retrouve_zabbix_param ();
		$zabbix_host = zabbix_host::creer_zabbix_host ( $liste_option, $zabbix_ws );
		$zabbix_host->retrouve_zabbix_param ()
			->setObjetTemplates ( $zabbix_templates );
		
		if (! $zabbix_ws && $zabbix_host && $zabbix_templates) {
			return abstract_log::onError_standard ( "Erreur dans les classes necessaires" );
		}
		if ($liste_option->verifie_option_existe ( "zabbix_serveur" ) === false) {
			return abstract_log::onError_standard ( "Il faut un zabbix pour travailler." );
		}
		
		//On valide la liste des parametres
		if ($liste_option->verifie_option_existe ( "action" ) === false) {
			return abstract_log::onError_standard ( "Il faut une action a effectuer : ajout|supp pour travailler." );
		}
		
		if ($zabbix_ws->prepare_connexion ( $liste_option->getOption ( "zabbix_serveur" ) ) === false) {
			return false;
		}
		
		abstract_log::onInfo_standard ( "On trouve l'id du host : " . $zabbix_host->getHost () );
		//on trouve l'id du host
		$zabbix_host->recherche_hostid_by_Name ();
		
		switch (strtolower ( $liste_option->getOption ( "action" ) )) {
			case 'ajout' :
				abstract_log::onInfo_standard ( "ajout de template a " . $zabbix_host->getHost () );
				//Retrouve la liste des Templates dans zabbix
				$zabbix_host->getObjetTemplates()->recherche_liste_templates ();
				$zabbix_host->ajouter_templates_au_host ();
				break;
			case 'supp' :
				abstract_log::onInfo_standard ( "Supp de " . $zabbix_host->getHost () );
				//Retrouve la liste des Templates dans zabbix
				$zabbix_host->supprimer_templates_au_host ();
				break;
			default :
				abstract_log::onError_standard ( "Action inconnue " . $liste_option->getOption ( "action" ) );
		}
	} catch ( Exception $e ) {
		// Exception in ZabbixApi catched
		return abstract_log::onError_standard ( $e->getMessage (), "", $e->getCode () );
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
