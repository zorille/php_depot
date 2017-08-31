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
					"./" . $fichier . " --conf {Chemin vers conf_clients}/prod_client_zabbix_serveurs.xml --zabbix_serveur zabbix.dev.client.fr.ghc.local --action ajout --zabbix_mediatype_nom '{Nom du MediaType}' --zabbix_mediatype_type script --zabbix_mediatype_status enable --zabbix_mediatype_exec_path /path/to/script --verbose",
					"./" . $fichier . " --conf {Chemin vers conf_clients}/prod_client_zabbix_serveurs.xml --zabbix_serveur zabbix.dev.client.fr.ghc.local --action supp --zabbix_mediatype_nom '{Nom du MediaType}' --verbose"
			),
			$fichier => array () 
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Permet de gerer les mediatypes dans zabbix";
	$help [$fichier] ["text"] [] .= "\t--zabbix_serveur Nom du zabbix a utiliser";
	$help [$fichier] ["text"] [] .= "\t--action ajout|supp Action a faire";
	
	$class_utilisees = array (
			"zabbix_datas",
			"zabbix_wsclient",
			"zabbix_mediatype"
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
	try {
		$zabbix_ws = zabbix_wsclient::creer_zabbix_wsclient ( $liste_option, zabbix_datas::creer_zabbix_datas ( $liste_option ) );
		$zabbix_mediatype = zabbix_mediatype::creer_zabbix_mediatype ( $liste_option, $zabbix_ws );
		
		if (! $zabbix_ws && $zabbix_mediatype) {
			return abstract_log::onError_standard ( "Erreur dans les classes necessaires" );
		}
		if ($liste_option->verifie_option_existe ( "zabbix_serveur" ) === false) {
			return abstract_log::onError_standard ( "Il faut un zabbix pour travailler." );
		}
		
		//On valide la liste des parametres
		if ($liste_option->verifie_option_existe ( "action" ) === false) {
			return abstract_log::onError_standard ( "Il faut une action a effectuer : ajout|supp pour travailler." );
		}
		
		//On se connecte au zabbix
		if ($zabbix_ws->prepare_connexion ( $liste_option->getOption ( "zabbix_serveur" ) ) === false) {
			return false;
		}
		
		switch (strtolower ( $liste_option->getOption ( "action" ) )) {
			case 'ajout' :
				$zabbix_mediatype->retrouve_zabbix_param ( false );
				$zabbix_mediatype->creer_mediatype ();
				break;
			case 'supp' :
				$zabbix_mediatype->retrouve_zabbix_param ( true );
				$zabbix_mediatype->supprime_mediatype ();
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
