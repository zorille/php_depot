#!/usr/bin/php
<?php
/**
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package Zabbix
 * @package itop
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
	//On remplace les parametres
	$argv [1] = '--zabbix_action_nom';
	$argv [2] = $nom;
	$argv [3] = '--zabbix_action_sujet';
	$argv [] .= $sujet;
	$argv [] .= '--zabbix_action_message';
	$argv [] .= $message;
	$argv [] .= '--conf';
	$argv [] .= $rep_document . '/conf_clients/itop/prod_client_itop_serveurs.xml';
	$argv [] .= $rep_document . '/conf_clients/itop/itop_users.xml';
	$argv [] .= '--itop_serveur';
	$argv [] .= 'itop-soap';
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
	$argv [] .= "--help";
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
					$fichier . " --help" ), 
			"exemples" => array ( 
					"./genere_alert_zabbix_over_itop.php 'MONITOR NAME' 'FQDN.DOMAIN' 'trig_eti:ETItrig_node:NODEtrig_stat:STATtrig_sev:SEVtrig_key:KEYtrig_ip:IPtrig_name:NAMEtrig_value:VALUEtrig_expr:EXPR'" ), 
			$fichier => array () );
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Permet d'ouvrir un ticket dans iTop a partir d'une alerte Zabbix";
	$help [$fichier] ["text"] [] .= "\targument 1 : action_nom";
	$help [$fichier] ["text"] [] .= "\targument 2 : action_sujet";
	$help [$fichier] ["text"] [] .= "\targument 3 : action_message : /trig_eti:(.*)trig_node:(.*)trig_stat:(.*)trig_sev:(.*)trig_key:(.*)trig_ip:(.*)trig_name:(.*)trig_value:(.*)trig_expr:(.*)trig_app:(.*)/";
	
	$class_utilisees = array ( 
			"hpom_client" );
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\r\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option ->verifie_option_existe ( "help" ))
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
	if ($liste_option ->verifie_option_existe ( "zabbix_action_nom" ) === false) {
		abstract_log::onError_standard ( "il manque le parametre zabbix_action_nom dans la ligne de commande." );
		return false;
	}
	if ($liste_option ->verifie_option_existe ( "zabbix_action_sujet" ) === false) {
		abstract_log::onError_standard ( "il manque le parametre zabbix_action_sujet dans la ligne de commande." );
		return false;
	}
	if ($liste_option ->verifie_option_existe ( "zabbix_action_message" ) === false) {
		abstract_log::onError_standard ( "il manque le parametre zabbix_action_message dans la ligne de commande." );
		return false;
	}
	
	//trig_node:(.*)trig_stat:(.*)trig_sev:(.*)trig_key:(.*)trig_ip:(.*)trig_name:(.*)trig_value:(.*)trig_expr:(.*)
	if (preg_match ( "/trig_eti:(?<eti>.*)trig_node:(?<node>.*)trig_stat:(?<stat>.*)trig_sev:(?<sev>.*)trig_key:(?<key>.*)trig_ip:(?<ip>.*)trig_name:(?<name>.*)trig_value:(?<value>.*)trig_expr:(?<expr>.*)trig_app:(?<app>.*)/", $liste_option ->getOption ( "zabbix_action_message" ), $donnees )) {
		if (! isset ( $donnees ["eti"] ) || $donnees ["eti"] == "") {
			return abstract_log::onError_standard ( "Pas de trig_eti dans la ligne." );
		}
		if (! isset ( $donnees ["node"] ) || $donnees ["node"] == "") {
			return abstract_log::onError_standard ( "Pas de trig_node dans la ligne." );
		}
		if (! isset ( $donnees ["stat"] ) || $donnees ["stat"] == "") {
			return abstract_log::onError_standard ( "Pas de trig_stat dans la ligne." );
		}
		if (! isset ( $donnees ["sev"] ) || $donnees ["sev"] == "") {
			return abstract_log::onError_standard ( "Pas de trig_sev dans la ligne." );
		}
		if (! isset ( $donnees ["key"] ) || $donnees ["key"] == "") {
			return abstract_log::onError_standard ( "Pas de trig_key dans la ligne." );
		}
		if (! isset ( $donnees ["ip"] ) || $donnees ["ip"] == "") {
			return abstract_log::onError_standard ( "Pas de trig_ip dans la ligne." );
		}
		if (! isset ( $donnees ["name"] ) || $donnees ["name"] == "") {
			return abstract_log::onError_standard ( "Pas de trig_name dans la ligne." );
		}
		if (! isset ( $donnees ["value"] ) || $donnees ["value"] == "") {
			return abstract_log::onError_standard ( "Pas de trig_value dans la ligne." );
		}
		if (! isset ( $donnees ["expr"] ) || $donnees ["expr"] == "") {
			return abstract_log::onError_standard ( "Pas de trig_expr dans la ligne." );
		}
		if (! isset ( $donnees ["app"] ) || $donnees ["app"] == "") {
			abstract_log::onError_standard ( "Pas de trig_app dans la ligne." );
			$donnees ["app"] = $liste_option ->getOption ( "zabbix_action_sujet" );
		}
	}
	
	//On transmet l'alerte dans iTop
	$aSOAPMapping = SOAPMapping::GetMapping ();
	
	$itop_webservice = itop_wsclient_soap::creer_itop_wsclient_soap ( $liste_option, itop_datas::creer_itop_datas ( $liste_option ) );
	if ($itop_webservice) {
		try {
			$itop_webservice ->getObjetSoap () 
				->setSoapAddedParams ( array ( 
					'classmap' => $aSOAPMapping ) );
			$itop_webservice ->prepare_connexion ( $liste_option ->getOption ( "itop_serveur" ) );
			
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
			
			//$titre, $description, $customer, $ci, $impact, $urgency, $caller = '', $workgroup = '', $service = '', $service_subcategory = '', $product = ''
			$id = $itop_webservice ->CreateIncidentTicket ( $liste_option ->getOption ( "zabbix_action_sujet" ), $msg_text, 'client', array ( 
					"VirtualMachine" => $donnees ["node"] ), 1, 2 );
			abstract_log::onInfo_standard ( "Ticket numero : " . $id );
		} catch ( Exception $e ) {
			//Erreur deja affichee
			return false;
		}
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log ->renvoiExit () );
?>
