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
			$fichier => array () 
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Permet de gerer les items dans zabbix";
	$help [$fichier] ["text"] [] .= "\t--zabbix_serveur Nom du zabbix a utiliser";
	$help [$fichier] ["text"] [] .= "\t--action ajout|supp Action a faire";
	
	$class_utilisees = array (
			"zabbix_datas",
			"zabbix_wsclient",
			"zabbix_host",
			"zabbix_templates",
			"zabbix_host_interfaces"
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
		if ($liste_option->verifie_option_existe ( "zabbix_serveur" ) === false) {
			return abstract_log::onError_standard ( "Il faut un zabbix pour travailler." );
		}
		if ($liste_option->verifie_option_existe ( "hpomi_liste_ci" ) === false) {
			return abstract_log::onError_standard ( "Il faut un fichier dans le parametre --hpomi_liste_ci pour travailler." );
		}
		if ($liste_option->verifie_option_existe ( "fichier_sortie" ) === false) {
			return abstract_log::onError_standard ( "Il faut un fichier dans le parametre --fichier_sortie pour travailler." );
		}
		
		$donnees_entree = fichier::Lit_integralite_fichier_en_tableau ( $liste_option->getOption ( "hpomi_liste_ci" ) );
		$fichier_sortie = fichier::creer_fichier ( $liste_option, $liste_option->getOption ( "fichier_sortie" ), "oui" );
		
		$zabbix_ws = zabbix_wsclient::creer_zabbix_wsclient ( $liste_option, zabbix_datas::creer_zabbix_datas ( $liste_option ) );
		$zabbix_hosts = zabbix_hosts::creer_zabbix_hosts ( $liste_option, $zabbix_ws );
		$zabbix_items = zabbix_items::creer_zabbix_items ( $liste_option, $zabbix_ws );
		
		if (! $zabbix_ws && $zabbix_hosts && $zabbix_items && $donnees_entree && $fichier_sortie) {
			return abstract_log::onError_standard ( "Erreur dans les classes necessaires" );
		}
		
		if ($zabbix_ws->prepare_connexion ( $liste_option->getOption ( "zabbix_serveur" ) ) === false) {
			return false;
		}
		
		$fichier_sortie->ouvrir ( "w" );
		
		abstract_log::onInfo_standard ( "On trouve les hosts : " );
		//on trouve l'id du host
		$zabbix_hosts->recherche_liste_hosts ();
		
		foreach ( $donnees_entree as $ligne_entree ) {
			$ligne_entree = trim ( $ligne_entree );
			abstract_log::onDebug_standard ( $ligne_entree, 2 );
			if (strpos ( $ligne_entree, "#" ) === 0) {
				//Si la ligne est commentee
				continue;
			}
			
			//<flag> ;<FQDN> ;/application1|application2|…/
			$donnees = explode ( ";", $ligne_entree );
			//Si la decoupe est en erreur, ou si le nombre de champs est different de 3
			if (! $donnees || count ( $donnees ) != 3) {
				//on ne peux pas utiliser la ligne
				abstract_log::onError_standard ( "Ligne non utilisable : " . $ligne_entree );
				continue;
			}
			
			//si le flag est a 0
			if ($donnees [0] == 0) {
				//on ne peux pas utiliser la ligne
				abstract_log::onWarning_standard ( "Flag a 0 : " . $ligne_entree );
				continue;
			}
			
			$liste_hosts = $zabbix_hosts->getListeHost ();
			if (isset ( $liste_hosts [$donnees [1]] )) {
				$zabbix_items->recherche_liste_items_par_filtre ( "", "", $liste_hosts [$donnees [1]]->getHostId () );
				foreach ( $zabbix_items->getListeItem () as $item ) {
					if ($item->getStatus () != "1") {
						abstract_log::onDebug_standard ( $donnees [2], 2 );
						$applications = $item->getApplications ();
						abstract_log::onDebug_standard ( $item->getApplications (), 2 );
						foreach ( $applications as $application ) {
							if (isset ( $application ["name"] ) && preg_match ( $donnees [2], $application ["name"] ) != 0 && $item->getLastclock () != 0) {
								abstract_log::onDebug_standard ( "Item " . $item->getName () . " Serveur : " . $liste_hosts [$donnees [1]]->getHost (), 1 );
								$ligne_sortie = $item->getLastclock () . ";" . $liste_hosts [$donnees [1]]->getHost () . ";" . $item->getKey_ () . ";" .$application ["name"].";". $item->getLastvalue ();
								$fichier_sortie->ecrit ( $ligne_sortie . "\n" );
							}
						}
					}
				}
			}
		}
		
		$fichier_sortie->close ();
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
