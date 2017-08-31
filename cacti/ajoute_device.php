<?php
/**
 *
 * @author dvargas
 * @package Steria
 * @subpackage Cacti
 */
//Deplacement pour joindre le repertoire lib
$deplacement = "/../../..";

if (! isset ( $argv ) && isset ( $_SERVER ) && isset ( $_SERVER ["SCRIPT_FILENAME"] )) {
	$rep_document = dirname ( $_SERVER ["SCRIPT_FILENAME"] ) . $deplacement;
	if (isset ( $_REQUEST ["env"] )) {
		$env = $_REQUEST ["env"];
	} else {
		$env = "no_env";
	}
	
	$liste_variables_systeme = array (
			"conf" => array (
					$rep_document . "/conf_clients/database/" . $env . "_cacti.xml",
					$rep_document . "/conf_clients/cacti/" . $env . "_" . $_REQUEST ["cacti_env"] . "_cacti.xml" 
			),
			"availability_method" => "snmp",
			"update_ref" => "ip" 
	);
} else {
	$rep_document = dirname ( $argv [0] ) . $deplacement;
}
// Specifiquement pour cacti, on a des INCLUDE qui permettent de charger les APIs de Cacti
$INCLUDE_CACTI_DEVICE = true;
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
	$help [$fichier] ["text"] [] .= "Permet d'ajouter un host dans cacti a partir d'une URL";
	$help [$fichier] ["text"] [] .= "\t--env prod/preprod/test environnement de travail";
	$help [$fichier] ["text"] [] .= "\t--cacti_env 'mut' Code client, il permet aussi de mettre le nom machine en {code_client}/{description}";
	$help [$fichier] ["text"] [] .= "\t--oid_a_checker 'sysDescr.0' OID de test pour valider le snmp du host";
	$help [$fichier] ["text"] [] .= "\t--description '' Description (nom du ci en general)";
	$help [$fichier] ["text"] [] .= "\t--ip '' IP ou nom resolvable du CI";
	$help [$fichier] ["text"] [] .= "\t--notes '' notes d'information";
	$help [$fichier] ["text"] [] .= "\t--snmp_version {1/2/2c/3} version du snmp";
	$help [$fichier] ["text"] [] .= "\t--snmp_community '' communite du snmp";
	$help [$fichier] ["text"] [] .= "\t--snmp_username '' Username du SNMPV3";
	$help [$fichier] ["text"] [] .= "\t--snmp_password '' Password du SNMPV3";
	$help [$fichier] ["text"] [] .= "\t--snmp_auth_protocol '' Protocole d'authentification du SNMPV3";
	$help [$fichier] ["text"] [] .= "\t--snmp_priv_passphrase '' PassPhrase prive du SNMPV3";
	$help [$fichier] ["text"] [] .= "\t--snmp_priv_protocol '' Protocole prive du SNMPV3";
	$help [$fichier] ["text"] [] .= "\t--snmp_context '' Context du SNMPV3";
	$help [$fichier] ["text"] [] .= "\t--availability_method {none/ping/snmp/pingsnmp} snmp par defaut";
	$help [$fichier] ["text"] [] .= "\t--template 'client-FR-Linux' Template a utiliser";
	$help [$fichier] ["text"] [] .= "\t--update Active le mode update";
	$help [$fichier] ["text"] [] .= "\t--update_ref ip/description Reference a utiliser en cas d'update";
	$help [$fichier] ["text"] [] .= "";
	$help [$fichier] ["text"] [] .= "curl \"http://addresse_cacti/cacti_hobinv/php_depot/steria/cacti/ajoute_device.php?env=test&cacti_env=TRI&verbose=1&description=testZ&ip=nom_machine_resolvable_ou_ip&snmp_version=2c&community=Public&template=Linux\"";
	$help [$fichier] ["text"] [] .= "&update en cas de mise a jour";
	
	$class_utilisees = array (
			"fichier",
			"cacti_datas",
			"cacti_addDevice" 
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
 * curl "http://addresse_cacti/cacti_hobinv/php_depot/steria/cacti/ajoute_device.php?env=test&cacti_env=CPL&verbose=1&description=testZ&ip=nom_machine_resolvable&snmp_version=2c&community=Public&template=Linux"
 * &update en cas de mise a jour
 */

require_once $liste_option->renvoie_option ( "rep_scripts" ) . "/lib/correspondances_cacti.class.php";

/**
 * Main programme
 * @ignore
 * @param options $liste_option        	
 * @param logs $fichier_log        	
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	if ($liste_option->verifie_option_existe ( "oid_a_checker" ) === false) {
		$liste_option->set_option ( "oid_a_checker", "sysDescr.0" );
	}
	
	$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
	$db_cacti = fonctions_standards_sgbd::recupere_db_cacti ( $connexion_db );
	$correspondances_cacti = correspondances_cacti::creer_correspondances_cacti ( $liste_option, false );
	$cacti = cacti_addDevice::creer_cacti_addDevice ( $liste_option, false );
	
	if (! $db_cacti || ! $correspondances_cacti || ! $cacti) {
		abstract_log::onError_standard ( "Il manque une variable", "", 2000 );
		return false;
	}
	
	$cacti->setHostTemplatesData ( cacti_hostsTemplates::creer_cacti_hostsTemplates ( $liste_option ) );
	
	// On prepare la ligne de command
	abstract_log::onInfo_standard ( "Cacti_env utilise : " . $liste_option->renvoie_option ( "cacti_env" ) );
	$retour = $cacti->setDescription ( trim ( $liste_option->renvoie_option ( 'description' ) ) );
	
	if (! $retour) {
		// erreur
		abstract_log::onError_standard ( "Il manque la description.", "", 5003 );
		return false;
	}
	if (! $cacti->setIp ( trim ( $liste_option->renvoie_option ( 'ip' ) ) )) {
		abstract_log::onError_standard ( "Il manque l'IP.", "", 5004 );
		return false;
	}
	if (! $cacti->setSnmpVersion ( trim ( $liste_option->renvoie_option ( 'snmp_version' ) ) )) {
		abstract_log::onError_standard ( "Il manque la version SNMP.", "", 5010 );
		return false;
	}
	if (! $cacti->setCommunity ( trim ( $liste_option->renvoie_option ( 'snmp_community' ) ) )) {
		abstract_log::onError_standard ( "Il manque le community.", "", 5011 );
		return false;
	}
	
	if ($cacti->getSnmpVersion () == "3") {
		if (! $cacti->setUsername ( trim ( $liste_option->renvoie_option ( 'snmp_username' ) ) )) {
			abstract_log::onError_standard ( "Il manque le username.", "", 5009 );
			return false;
		}
		if (! $cacti->setPassword ( trim ( $liste_option->renvoie_option ( 'snmp_password' ) ) )) {
			abstract_log::onError_standard ( "Il manque le password.", "", 5009 );
			return false;
		}
		if (! $cacti->setAuthproto ( trim ( $liste_option->renvoie_option ( 'snmp_auth_protocol' ) ) )) {
			abstract_log::onError_standard ( "Il manque le Authproto.", "", 5012 );
			return false;
		}
		if (! $cacti->setPrivpass ( trim ( $liste_option->renvoie_option ( 'snmp_priv_passphrase' ) ) )) {
			abstract_log::onError_standard ( "Il manque le privpass.", "", 5013 );
			return false;
		}
		if (! $cacti->setPrivproto ( trim ( $liste_option->renvoie_option ( 'snmp_priv_protocol' ) ) )) {
			abstract_log::onError_standard ( "Il manque le privproto.", "", 5014 );
			return false;
		}
		if (! $cacti->setContext ( trim ( $liste_option->renvoie_option ( 'snmp_context' ) ) )) {
			abstract_log::onError_standard ( "Il manque le context.", "", 5015 );
			return false;
		}
	}
	if (! $cacti->setAvailability ( trim ( $liste_option->renvoie_option ( 'availability_method' ) ) )) {
		abstract_log::onError_standard ( "Il manque l'availability.", "", 5016 );
		return false;
	}
	if ($liste_option->verifie_option_existe ( 'notes' ) !== false) {
		$cacti->setNote ( trim ( $liste_option->renvoie_option ( 'notes' ) ) );
	}
	if ($liste_option->verifie_option_existe ( 'template' ) === false) {
		abstract_log::onError_standard ( "Il manque le template pour " . $cacti->getDescription (), "", 5005 );
		return false;
	}
	$template = trim ( $liste_option->renvoie_option ( 'template' ) );
	
	if ($liste_option->verifie_option_existe ( "update" ) === false) {
		$update = false;
	} else {
		$update = true;
	}
	
	if ($correspondances_cacti->valideIP ( $cacti->getIp () ) === false) {
		abstract_log::onError_standard ( "IP non utilisable " . $cacti->getIp (), "", 2001 );
		return false;
	}
	
	abstract_log::onInfo_standard ( "On valide le SNMP." );
	// On valide le SNMP
	if ($correspondances_cacti->valideSnmp ( $liste_option->renvoie_option ( "oid_a_checker" ), $cacti->getIp (), $cacti->getCommunity (), $cacti->getSnmpVersion (), 1000000, 1, $cacti->getSnmpUsername (), $cacti->getSnmpPassword (), $cacti->getAuthproto (), $cacti->getPrivproto (), $cacti->getPrivpass () ) === false) {
		abstract_log::onError_standard ( "Erreur durant le check SNMP pour " . $cacti->getDescription (), "", 5022 );
		return false;
	}
	
	abstract_log::onInfo_standard ( "On retrouve le template." );
	// On retrouve le numero du template
	$template_id = $cacti->getHostTemplatesData ()
		->retrouve_templateid_par_nom ( $db_cacti, $template, "/^client - /", false );
	if ($template_id === false) {
		$type_OS = $correspondances_cacti->retrouveTypeOSParSnmp ( $cacti->getIp (), $cacti->getCommunity (), $cacti->getSnmpVersion (), 1000000, 1, $cacti->getSnmpUsername (), $cacti->getSnmpPassword (), $cacti->getAuthproto (), $cacti->getPrivproto (), $cacti->getPrivpass () );
		if ($type_OS !== false) {
			$template_id = $cacti->getHostTemplatesData ()
				->retrouve_templateid_par_nom ( $db_cacti, $type_OS, "/^client - /", true );
		}
	}
	if ($template_id === false) {
		abstract_log::onError_standard ( "Template introuvable pour " . $cacti->getDescription (), "", 2002 );
		return false;
	}
	$cacti->setTemplate_id ( $template_id );
	
	// On ajoute le host
	abstract_log::onInfo_standard ( "On ajoute le host : " . $cacti->getDescription () );
	if ($cacti->executeCacti_AddDevice ( $update, $liste_option->renvoie_option ( "update_ref" ) ) !== false) {
		abstract_log::onInfo_standard ( "Machine ajoutee : " . $cacti->getDescription () . " Device-id : " . $cacti->getHostId () );
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
/**
 * ********* FIN DE VOTRE CODE ***************
 */
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoie_exit () );
?>
