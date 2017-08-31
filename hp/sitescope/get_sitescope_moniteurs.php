#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage sitescope
 */
$INCLUDE_SITESCOPE = true;
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
	$help [$fichier] ["text"] [] .= "Permet d'extraire la liste des devices d'un ou plusieurs sitescope";
	$help [$fichier] ["text"] [] .= "\t--fichier_sortie /tmp/fichier.out Chemin et nom du fichier d'extraction";
	
	$class_utilisees = array (
			"fichier",
			"sitescope_fonctions_standards",
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

function retrouve_moniteurs_par_machine(&$liste_moniteurs, &$liste_machines) {
	$liste = array ();
	// Pour assurer les dependances
	

	$dependance = array ();
	foreach ( $liste_moniteurs as $groupe => $liste_moniteurs_machine ) {
		foreach ( $liste_moniteurs_machine as $moniteur ) {
			if (isset ( $moniteur ["entitySnapshot_properties"] ["_ownerID"] ))
				$dependance [$moniteur ["entitySnapshot_properties"] ["_ownerID"] . " " . $moniteur ["entitySnapshot_properties"] ["_id"]] = $moniteur ["entitySnapshot_properties"] ["_name"];
		}
	}
	
	// puis on creer les entres du fichier
	foreach ( $liste_moniteurs as $groupe => $liste_moniteurs_machine ) {
		foreach ( $liste_moniteurs_machine as $moniteur ) {
			creer_ligne_texte ( $liste, $groupe, $dependance, $moniteur, $liste_machines );
		}
	}
	return $liste;
}

function creer_ligne_texte(&$liste, $groupe, &$dependance, $moniteur, &$liste_machines) {
	abstract_log::onDebug_standard ( $moniteur, 2 );
	
	if (! isset ( $moniteur ["monitor_snapshot_hostName"] )) {
		if (isset ( $moniteur ["entitySnapshot_properties"] ["_hostname"] )) {
			$moniteur ["monitor_snapshot_hostName"] = $moniteur ["entitySnapshot_properties"] ["_hostname"];
		} elseif (isset ( $moniteur ["entitySnapshot_properties"] ["_machine"] )) {
			$moniteur ["monitor_snapshot_hostName"] = $moniteur ["entitySnapshot_properties"] ["_machine"];
		} else {
			$moniteur ["monitor_snapshot_hostName"] = $groupe;
		}
	}
	
	switch ($moniteur ["entitySnapshot_properties"] ["_class"]) {
		case "PingMonitor" :
		case "PortMonitor" :
			$position = $moniteur ["monitor_snapshot_hostName"];
			if (isset ( $liste_machines ["machines"] [$moniteur ["entitySnapshot_properties"] ["_hostname"]] )) {
				$nom_CI = $liste_machines ["machines"] [$moniteur ["entitySnapshot_properties"] ["_hostname"]];
			} else {
				$nom_CI = $moniteur ["entitySnapshot_properties"] ["_hostname"];
			}
			
			// Nom du CI;Nom du moniteur;Frequence (en s);Disabled;Dependance
			

			break;
		case "URLMonitor" :
		case "URLContentMonitor" :
		case "URLSequenceMonitor" :
		case "Exchange2007MsgTrafficMonitor" :
		case "PDHMonitor" :
		case "Exchange2007Monitor" :
			$position = $groupe;
			if (isset ( $moniteur ["entitySnapshot_properties"] ["_url"] )) {
				$nom_CI = $moniteur ["entitySnapshot_properties"] ["_url"];
			} else {
				$nom_CI = $moniteur ["monitor_snapshot_hostName"];
			}
			
			break;
		case "WebServiceMonitor" :
			$position = $groupe;
			$nom_CI = $moniteur ["entitySnapshot_properties"] ["_pwebserviceserverurl"];
			
			break;
		case "DatabaseMonitor" :
			// jdbc:postgresql://AFRHAPSIHA04:5432/afpa_bs
			$pos = strpos ( $moniteur ["entitySnapshot_properties"] ["_database"], "//" );
			if ($pos === false) {
				$pos = strpos ( $moniteur ["entitySnapshot_properties"] ["_database"], "@" ) + 1;
			}
			$substr = substr ( $moniteur ["entitySnapshot_properties"] ["_database"], $pos );
			$machine = explode ( ":", $substr );
			
			$position = $machine [0];
			$nom_CI = $moniteur ["entitySnapshot_properties"] ["_database"];
			
			break;
		case "JMXMonitor" :
			// [_jmxUrl] => service:jmx:rmi:///jndi/rmi://172.18.32.71:9090/jmxrmi
			$pos = strpos ( $moniteur ["entitySnapshot_properties"] ["_jmxUrl"], "rmi://" );
			$substr = substr ( $moniteur ["entitySnapshot_properties"] ["_jmxUrl"], $pos + 6 );
			$machine = explode ( ":", $substr );
			
			$position = $machine [0];
			$nom_CI = $moniteur ["entitySnapshot_properties"] ["_jmxUrl"];
			break;
		case "SNMPMonitor" :
		case "VMwareHostMemoryMonitor" :
		case "VMwareHostCPUMonitor" :
			$position = $moniteur ["monitor_snapshot_hostName"];
			if (isset ( $liste_machines ["machines"] [$moniteur ["entitySnapshot_properties"] ["_host"]] )) {
				$nom_CI = $liste_machines ["machines"] [$moniteur ["entitySnapshot_properties"] ["_host"]];
			} else {
				$nom_CI = $moniteur ["entitySnapshot_properties"] ["_host"];
			}
			
			break;
		case "CPUMonitor" :
		case "DiskSpaceMonitor" :
		case "AutoServicesMonitor" :
		case "ServiceMonitor" :
		case "MemoryMonitor" :
		case "ScriptMonitor" :
		case "LogMonitor" :
		case "FileMonitor" :
		case "UnixSystemMonitor" :
		case "SQLServerMonitor" :
		case "NTEventLogMonitor" :
		case "NTCounterMonitor" :
		case "DirectoryMonitor" :
		case "CitrixMonitor" :
		case "SAPCCMSMonitor" :
		case "SapAlertMonitor" :
		case "SNMPTrapMonitor" :
			$position = $moniteur ["monitor_snapshot_hostName"];
			
			if (isset ( $moniteur ["entitySnapshot_properties"] ["_remoteID"] )) {
				$nom_CI = $moniteur ["entitySnapshot_properties"] ["_remoteID"];
			} elseif (isset ( $liste_machines ["machines"] [$moniteur ["monitor_snapshot_hostName"]] )) {
				$nom_CI = $liste_machines ["machines"] [$moniteur ["monitor_snapshot_hostName"]];
			} else {
				$nom_CI = $moniteur ["monitor_snapshot_hostName"];
			}
			;
			
			break;
		default :
			abstract_log::onError_standard ( "type inconnu : " );
			abstract_log::onError_standard ( $moniteur );
			$position = $moniteur ["monitor_snapshot_hostName"];
			$nom_CI = $moniteur ["monitor_snapshot_hostName"];
	}
	
	$type = $moniteur ["entitySnapshot_properties"] ["_class"];
	$nom_moniteur = $moniteur ["entitySnapshot_properties"] ["_name"];
	$frequence = $moniteur ["entitySnapshot_properties"] ["_frequency"];
	$disabled = (isset ( $moniteur ["entitySnapshot_properties"] ["_disabled"] ) ? "Yes" : "No");
	$dependance_name = gestion_dependance ( $moniteur, $dependance );
	
	if ($position === "" || ! is_string ( $position )) {
		abstract_log::onWarning_standard ( "class : " . $type );
		abstract_log::onWarning_standard ( $moniteur ["monitor_snapshot_hostName"] );
		abstract_log::onWarning_standard ( "Position de type inconnu : " );
		
		abstract_log::onWarning_standard ( $position );
		return;
	}
	if (isset ( $liste_machines ["machines"] [$position] )) {
		$position = $liste_machines [$liste_machines ["machines"] [$position]] ["_name"];
	} else {
		$position = str_replace ( "\\\\", "", $position );
	}
	
	// Gestion de la liste
	if (! isset ( $liste [$position] )) {
		$liste [$position] = array ();
	}
	if (isset ( $liste_machines [$nom_CI] )) {
		$nom_CI = $liste_machines [$nom_CI] ["_name"] . ";" . $liste_machines [$nom_CI] ["_os"] . ";" . $liste_machines [$nom_CI] ["_method"] . ";" . $liste_machines [$nom_CI] ["_status"];
	} else {
		$nom_CI = str_replace ( "\\\\", "", str_replace ( "//", "", $nom_CI ) ) . ";" . ";" . ";";
	}
	$liste [$position] [] .= $nom_moniteur . ";" . $type . ";" . $frequence . ";" . $disabled . ";" . $dependance_name . ";" . $nom_CI;
}

function gestion_dependance($moniteur, &$dependance) {
	// [groupId] => AFW2AP01.1
	

	// Dependance : [_dependsOn] => AFW2AP01.1 5 [hasDependencies] => true
	if (isset ( $moniteur ["entitySnapshot_properties"] ) && isset ( $moniteur ["entitySnapshot_properties"] ["_dependsOn"] ) && isset ( $dependance [$moniteur ["entitySnapshot_properties"] ["_dependsOn"]] )) {
		return $dependance [$moniteur ["entitySnapshot_properties"] ["_dependsOn"]];
	} else {
		return "";
	}
}

// Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

/**
 * ******** VOTRE CODE A PARTIR D'ICI*********
 */
$continue = true;

$sitescope_fonctions = sitescope_fonctions_standards::creer_sitescope_fonctions_standards ( $liste_option );

if ($liste_option->verifie_option_existe ( "fichier_sortie" ) === false) {
	$liste_option->setOption ( "fichier_sortie", "/tmp/liste_moniteurs_sitescope.csv" );
}

$soapClient_configuration = sitescope_soap_configuration::creer_sitescope_soap_configuration ( $liste_option );
$soapClient_preferences = sitescope_soap_preferences::creer_sitescope_soap_preferences ( $liste_option );

if ($continue && $sitescope_fonctions && $soapClient_configuration && $soapClient_preferences) {
	abstract_log::onInfo_standard ( "Fichier de sortie : " . $liste_option->getOption ( "fichier_sortie" ) );
	$fichier_out = fichier::creer_fichier ( $liste_option, $liste_option->getOption ( "fichier_sortie" ), "oui" );
	$fichier_out->ouvrir ( "w" );
	$fichier_out->ecrit ( "client;nom;Nom du moniteur;type;Frequence (en s);Disabled;Dependance;Nom du CI;Type du CI;Methode conn CI;Status conn CI\n" );
	
	foreach ( $soapClient_configuration->getServeurDatas () as $serveur_data ) {
		abstract_log::onInfo_standard ( "Sitescope : " . $serveur_data ["nom"] );
		abstract_log::onDebug_standard ( $serveur_data, 2 );
				
		$liste_confs = null;
		if ($soapClient_configuration->connect ( $serveur_data ["nom"] ) && $soapClient_preferences->connect ( $serveur_data ["nom"] )) {
			if ($liste_option->verifie_option_existe ( "fichier_serialize" ) === false) {
				$liste_confs = $soapClient_configuration->retrouve_FullConfiguration_sitescope ();
				
				if ($liste_option->verifie_option_existe ( "fichier_serialize_out" ) !== false) {
					$serialized = serialize ( $liste_confs );
					$fichier_serialized = fichier::creer_fichier($liste_option, $liste_option->getOption ( "fichier_serialize_out" ), "oui" );
					$fichier_serialized->ouvrir ( "w" );
					$fichier_serialized->ecrit ( $serialized );
					$fichier_serialized->close ();
				}
			} else {
				$serialized = fichier::Lit_integralite_fichier ( $liste_option->getOption ( "fichier_serialize" ) );
				$liste_confs = unserialize ( $serialized );
			}
			abstract_log::onDebug_standard ( $liste_confs, 2 );
			
			if (is_array ( $liste_confs )) {
				$soapClient_preferences->setArbreMachines ( array () );
				$soapClient_preferences->retrouve_arbre_machines ();
				
				$sitescope_fonctions->setArbreMoniteurs ( array () );
				$sitescope_fonctions->retrouve_arbre_moniteurs_from_FullConf ( $liste_confs );
				
				$datas_finales = retrouve_moniteurs_par_machine ( $sitescope_fonctions->getArbreMoniteurs (), $soapClient_preferences->getArbreMachines () );
				abstract_log::onDebug_standard ( $datas_finales, 1 );
				foreach ( $datas_finales as $machine => $datas ) {
					foreach ( $datas as $ligne ) {
						$fichier_out->ecrit ( $serveur_data ["client"] . ";" . $serveur_data ["nom"] . ";" . $ligne . "\n" );
					}
				}
			}
		}
	}
	
	$fichier_out->close ();
} else {
	abstract_log::onError_standard ( "Erreur dans les variables necessaires" );
}

/**
 * ********* FIN DE VOTRE CODE ***************
 */
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
