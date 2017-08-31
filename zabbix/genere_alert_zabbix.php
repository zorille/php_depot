#!/usr/bin/php
<?php
/**
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package Steria
 * @subpackage sitescope
 */
if (! isset ( $argv ) && ! isset ( $argc )) {
	fwrite ( STDOUT, "Il n'y a pas de parametres en argument.\r\n" );
	exit ( 0 );
}

$deplacement = "/../..";
$rep_document = dirname ( $argv [0] ) . $deplacement;

//On reconstruit la liste des arguments au format "Framework PHP"
if ($argc == 4) {
	$nom = $argv [1];
	$sujet = $argv [2];
	$message = $argv [3];
	$argv [1] = '--zabbix_action_nom';
	$argv [2] = $nom;
	$argv [3] = '--zabbix_action_sujet';
	$argv [] .= $sujet;
	$argv [] .= '--zabbix_action_message';
	$argv [] .= $message;
	$argv [] .= '--conf';
	$argv [] .= $rep_document . '/conf_clients/hpom/prod_hpom_linux.xml';
	$argv [] .= '--create_log_file';
	$argv [] .= 'oui';
	$argv [] .= '--dossier_log';
	$argv [] .= '/tmp';
	$argv [] .= '--fichier_log';
	$argv [] .= 'genere_alert_zabbix.log';
	$argv [] .= '--fichier_log_unique';
	$argv [] .= 'non';
	$argv [] .= '--fichier_log_append';
	$argv [] .= 'oui';
	$argv [] .= '--verbose';
} else {
	$argv [] .= "\t--help";
	$argv [] .= '--verbose';
}

$argc = count ( $argv );
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
					"./genere_alert_zabbix.php 'MONITOR NAME' 'FQDN.DOMAIN' 'trig_eti:ETItrig_node:NODEtrig_stat:STATtrig_sev:SEVtrig_key:KEYtrig_ip:IPtrig_name:NAMEtrig_value:VALUEtrig_expr:EXPR'" 
			),
			$fichier => array () 
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Permet d'ouvrir un ticket dans HPOM a partir d'une alerte Zabbix";
	$help [$fichier] ["text"] [] .= "\targument 1 : action_nom";
	$help [$fichier] ["text"] [] .= "\targument 2 : action_sujet";
	$help [$fichier] ["text"] [] .= "\targument 3 : action_message : /trig_eti:(.*)trig_node:(.*)trig_stat:(.*)trig_sev:(.*)trig_key:(.*)trig_ip:(.*)trig_name:(.*)trig_value:(.*)trig_expr:(.*)trig_app:(.*)/";
	
	$class_utilisees = array (
			"hpom_client" 
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\r\n";
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
 * @param options $liste_option        	
 * @param logs $fichier_log        	
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	if ($liste_option->verifie_option_existe ( "zabbix_action_nom" ) === false) {
		abstract_log::onError_standard ( "il manque le parametre zabbix_action_nom dans la ligne de commande." );
		return false;
	}
	if ($liste_option->verifie_option_existe ( "zabbix_action_sujet" ) === false) {
		abstract_log::onError_standard ( "il manque le parametre zabbix_action_sujet dans la ligne de commande." );
		return false;
	}
	if ($liste_option->verifie_option_existe ( "zabbix_action_message" ) === false) {
		abstract_log::onError_standard ( "il manque le parametre zabbix_action_message dans la ligne de commande." );
		return false;
	}
	
	//trig_node:(.*)trig_stat:(.*)trig_sev:(.*)trig_key:(.*)trig_ip:(.*)trig_name:(.*)trig_value:(.*)trig_expr:(.*)
	if (preg_match ( "/trig_eti:(?<eti>.*)trig_node:(?<node>.*)trig_stat:(?<stat>.*)trig_sev:(?<sev>.*)trig_key:(?<key>.*)trig_ip:(?<ip>.*)trig_name:(?<name>.*)trig_value:(?<value>.*)trig_expr:(?<expr>.*)trig_app:(?<app>.*)/", $liste_option->getOption ( "zabbix_action_message" ), $donnees )) {
		if (! isset ( $donnees ["eti"] ) || $donnees ["eti"] == "") {
			abstract_log::onError_standard ( "Pas de trig_eti dans la ligne." );
		}
		if (! isset ( $donnees ["node"] ) || $donnees ["node"] == "") {
			abstract_log::onError_standard ( "Pas de trig_node dans la ligne." );
		}
		if (! isset ( $donnees ["stat"] ) || $donnees ["stat"] == "") {
			abstract_log::onError_standard ( "Pas de trig_stat dans la ligne." );
		}
		if (! isset ( $donnees ["sev"] ) || $donnees ["sev"] == "") {
			abstract_log::onError_standard ( "Pas de trig_sev dans la ligne." );
		}
		if (! isset ( $donnees ["key"] ) || $donnees ["key"] == "") {
			abstract_log::onError_standard ( "Pas de trig_key dans la ligne." );
		}
		if (! isset ( $donnees ["ip"] ) || $donnees ["ip"] == "") {
			abstract_log::onError_standard ( "Pas de trig_ip dans la ligne." );
		}
		if (! isset ( $donnees ["name"] ) || $donnees ["name"] == "") {
			abstract_log::onError_standard ( "Pas de trig_name dans la ligne." );
		}
		if (! isset ( $donnees ["value"] ) || $donnees ["value"] == "") {
			abstract_log::onError_standard ( "Pas de trig_value dans la ligne." );
		}
		if (! isset ( $donnees ["expr"] ) || $donnees ["expr"] == "") {
			abstract_log::onError_standard ( "Pas de trig_expr dans la ligne." );
		}
		if (! isset ( $donnees ["app"] ) || $donnees ["app"] == "") {
			abstract_log::onError_standard ( "Pas de trig_app dans la ligne." );
			$donnees ["app"] = $liste_option->getOption ( "zabbix_action_sujet" );
		}
		var_dump ( $liste_option->getOption ( "zabbix_action_message" ) );
		var_dump ( $donnees ["app"] );
	}
	
	try {
		$hpom_client = hpom_client::creer_hpom_client ( $liste_option, false );
		$pos = strpos ( $donnees ["node"], "." );
		$domain = substr ( $donnees ["node"], $pos + 1 );
		$nom = $donnees ["name"];
		$expr = $donnees ["expr"];
		$value = $donnees ["value"];
		
		$msg_text = <<< ENDTXT
$nom
-----------------------------------

trigger expression:$expr
trigger value:$value

Zabbix: http://zabbix.$domain/zabbix
ENDTXT;
		
		$hpom_client->setMsgGrp ( $liste_option->getOption ( "zabbix_action_sujet" ) )
			->setNode ( $donnees ["node"] )
			->gestion_severite ( $donnees ["sev"] )
			->setApplication ( $donnees ["app"] )
			->setObjet ( $donnees ["key"] )
			->setMsgText ( $msg_text )
			->AjouteOption ( "eti", $donnees ["eti"] );
		
		$hpom_client->envoie_hpom_datas ();
	} catch ( Exception $e ) {
		//Erreur deja affichee
		return false;
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoie_exit () );
?>
