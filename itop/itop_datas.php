#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package iTop
 * @subpackage itop
 */
$rep_document = dirname ( $argv [0] ) . "/../..";
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
					$fichier . " --help" ),
			$fichier => array () );
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "--itop_serveur";
	
	$class_utilisees = array ();
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option ->verifie_option_existe ( "help" ))
	help ();
	
	// Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );
require_once $liste_option ->renvoie_option ( "rep_scripts" ) . '/lib/itop_liste_ci.class.php';
require_once $liste_option ->renvoie_option ( "rep_scripts" ) . '/lib/itop_ci.class.php';

/**
 * ******** VOTRE CODE A PARTIR D'ICI*********
 */

/**
 *
 * @param options $liste_option
 * @param logs $fichier_log
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	if ($liste_option ->verifie_option_existe ( "itop_serveur", true ) === false) {
		$liste_option ->setOption ( 'itop_serveur', "POC_itop2" );
	}
	if ($liste_option ->verifie_option_existe ( "zabbix_serveur" ) === false) {
		return abstract_log::onError_standard ( "Il faut un zabbix pour travailler." );
	}
	
	//On se connecte au zabbix
	$zabbix_connexion=zabbix_connexion::creer_zabbix_connexion($liste_option)->connect_zabbix();
	
	$itop_webservice = itop_wsclient_soap::creer_itop_wsclient_soap ( $liste_option, itop_datas::creer_itop_datas ( $liste_option ) );
	if ($itop_webservice && $zabbix_connexion) {
		try {
			$itop_webservice ->get_Objet_Soap () 
				->set_Soap_Added_Params ( array (
					'classmap' => SOAPMapping::GetMapping () ) );
			$itop_webservice ->prepare_connexion ( $liste_option ->getOption ( "itop_serveur" ) );
			
			//On se connecte a iTop
			$liste_ci = itop_liste_ci::creer_itop_liste_ci ( $liste_option, $itop_webservice );
			
			/*
			 * Format : expecting {AsyncTask, AsyncSendEmail, DBProperty, CMDBChange, CMDBChangeOp, CMDBChangeOpCreate, CMDBChangeOpDelete, CMDBChangeOpSetAttribute, CMDBChangeOpSetAttributeScalar, CMDBChangeOpSetAttributeBlob, CMDBChangeOpSetAttributeOneWayPassword, CMDBChangeOpSetAttributeEncrypted, CMDBChangeOpSetAttributeText, CMDBChangeOpSetAttributeLongText, CMDBChangeOpSetAttributeCaseLog, CMDBChangeOpPlugin, CMDBChangeOpSetAttributeLinks, CMDBChangeOpSetAttributeLinksAddRemove, CMDBChangeOpSetAttributeLinksTune, AuditCategory, AuditRule, Query, QueryOQL, ModuleInstallation, UserDashboard, Shortcut, ShortcutOQL, appUserPreferences, User, UserInternal, Event, EventNotification, EventNotificationEmail, EventIssue, EventWebService, EventRestService, EventLoginUsage, Action, ActionNotification, ActionEmail, Trigger, TriggerOnObject, TriggerOnPortalUpdate, TriggerOnStateChange, TriggerOnStateEnter, TriggerOnStateLeave, TriggerOnObjectCreate, lnkTriggerAction, TriggerOnThresholdReached, BulkExportResult, iTopOwnershipToken, SynchroDataSource, SynchroAttribute, SynchroAttExtKey, SynchroAttLinkSet, SynchroLog, SynchroReplica, BackgroundTask, UserExternal, UserLDAP, UserLocal, Attachment, CMDBChangeOpAttachmentAdded, CMDBChangeOpAttachmentRemoved, Organization, Location, Contact, Person, Team, Document, DocumentFile, DocumentNote, DocumentWeb, FunctionalCI, PhysicalDevice, ConnectableCI, DatacenterDevice, NetworkDevice, Server, ApplicationSolution, BusinessProcess, SoftwareInstance, Middleware, DBServer, WebServer, PCSoftware, OtherSoftware, MiddlewareInstance, DatabaseSchema, WebApplication, Software, Patch, OSPatch, SoftwarePatch, Licence, OSLicence, SoftwareLicence, lnkDocumentToLicence, Typology, OSVersion, OSFamily, DocumentType, ContactType, Brand, Model, NetworkDeviceType, IOSVersion, lnkDocumentToPatch, lnkSoftwareInstanceToSoftwarePatch, lnkFunctionalCIToOSPatch, lnkDocumentToSoftware, lnkContactToFunctionalCI, lnkDocumentToFunctionalCI, Subnet, VLAN, lnkSubnetToVLAN, NetworkInterface, IPInterface, PhysicalInterface, lnkPhysicalInterfaceToVLAN, lnkConnectableCIToNetworkDevice, lnkApplicationSolutionToFunctionalCI, lnkApplicationSolutionToBusinessProcess, lnkPersonToTeam, Group, lnkGroupToCI, Rack, Enclosure, PowerConnection, PowerSource, PDU, PC, Printer, TelephonyCI, Phone, MobilePhone, IPPhone, Tablet, Peripheral, StorageSystem, SANSwitch, TapeLibrary, NAS, FiberChannelInterface, Tape, NASFileSystem, LogicalVolume, lnkServerToVolume, lnkSanToDatacenterDevice, Ticket, lnkContactToTicket, lnkFunctionalCIToTicket, WorkOrder, VirtualDevice, VirtualHost, Hypervisor, Farm, VirtualMachine, LogicalInterface, lnkVirtualDeviceToVolume, Change, RoutineChange, ApprovedChange, NormalChange, EmergencyChange, Incident, KnownError, lnkErrorToFunctionalCI, lnkDocumentToError, FAQ, FAQCategory, Problem, UserRequest, ContractType, Contract, CustomerContract, ProviderContract, lnkContactToContract, lnkContractToDocument, lnkFunctionalCIToProviderContract, ServiceFamily, Service, lnkDocumentToService, lnkContactToService, ServiceSubcategory, SLA, SLT, lnkSLAToSLT, lnkCustomerContractToService, lnkCustomerContractToProviderContract, lnkCustomerContractToFunctionalCI, DeliveryModel, lnkDeliveryModelToContact, URP_Profiles, URP_UserProfile, URP_UserOrg}
			 */
			abstract_log::onInfo_standard ( "VirtualMachine" );
			$liste_ci ->retrouve_cis ( 'VirtualMachine', 'name' );
			abstract_log::onInfo_standard ( "Server" );
			$liste_ci ->retrouve_cis ( 'Server', 'name' );
			abstract_log::onInfo_standard ( "Middleware" );
			$liste_ci ->retrouve_cis ( 'Middleware', 'system_name' );
			abstract_log::onInfo_standard ( "PCSoftware" );
			$liste_ci ->retrouve_cis ( 'PCSoftware', 'system_name' );
			abstract_log::onInfo_standard ( "OtherSoftware" );
			$liste_ci ->retrouve_cis ( 'OtherSoftware', 'system_name' );
			abstract_log::onInfo_standard ( "WebServer" );
			$liste_ci ->retrouve_cis ( 'WebServer', 'system_name' );
			
			$datas = $liste_ci ->get_liste_ci ();
			$nb_cis = 0;
			foreach ( $datas as $machine => $liste_cis ) {
				$local_liste_option = clone $liste_option;
				$local_liste_option ->set_option ( "zabbix_host_status", 'monitored' );
				foreach ( $liste_cis as $type_ci => $cis ) {
					$local_liste_option ->set_option ( "zabbix_interfaces", '' );
					$local_liste_option ->set_option ( "zabbix_host_groups", array () );
					$local_liste_option ->set_option ( "zabbix_templates", array () );
					foreach ( $cis as $nom_ci => $ci ) {
						//--zabbix_interface_ip 122.122.122.122 --zabbix_interfaces 'agent|oui|10050' --zabbix_host_host test_host --zabbix_host_name test_host --zabbix_host_status monitored --zabbix_host_groups 'VirtualMachine' --zabbix_templates 'Template OS Linux'
						$nb_cis += count ( $ci );
						switch ($type_ci) {
							case 'VirtualMachine' :
								//On enregistre l'ip qui sera valable pour tous les CIs de type VM
								$local_liste_option ->set_option ( "zabbix_interface_ip", $ci ->get_donnees ()['VirtualMachine.managementip'] );
								if(preg_match ( "/^10\.20.*$/",$ci ->get_donnees ()['VirtualMachine.managementip'] ) === 1){
									$local_liste_option ->set_option ( "zabbix_proxy_name", "zabbix_proxy_1020" );
								} elseif(preg_match ( "/^10\.25.*$/",$ci ->get_donnees ()['VirtualMachine.managementip'] ) === 1){
									$local_liste_option ->set_option ( "zabbix_proxy_name", "zabbix_proxy_1025" );
								}
								$local_liste_option ->set_option ( "zabbix_interfaces", 'agent|oui|10050' );
								$local_liste_option ->set_option ( "zabbix_host_host", $ci ->get_donnees ()['VirtualMachine.name'] );
								$local_liste_option ->set_option ( "zabbix_host_name", $ci ->get_donnees ()['VirtualMachine.name'] );
								$local_liste_option ->set_option ( "zabbix_host_groups", array (
										$machine,
										'Virtual Machines',
										'System' ) );
								$local_liste_option ->set_option ( "zabbix_templates", array (
										'Template OS ' . $ci ->get_donnees ()['VirtualMachine.osfamily_name'] ) );
								zabbix_host_administration::creer_zabbix_host_administration($local_liste_option, $zabbix_connexion->getObjetZabbixWsclient())->ajoute_host();
								break;
							case 'Server' :
								if (strpos ( $ci ->get_donnees ()['Server.name'], "ibm-oracle" ) !== false) {
									break;
								}
								//On enregistre l'ip qui sera valable pour tous les CIs de type Server
								$local_liste_option ->set_option ( "zabbix_interface_ip", $ci ->get_donnees ()['Server.managementip'] );
								if(preg_match ( "/^10\.20.*$/",$ci ->get_donnees ()['Server.managementip'] ) === 1){
									$local_liste_option ->set_option ( "zabbix_proxy_name", "zabbix_proxy_1020" );
								} elseif(preg_match ( "/^10\.25.*$/",$ci ->get_donnees ()['Server.managementip'] ) === 1){
									$local_liste_option ->set_option ( "zabbix_proxy_name", "zabbix_proxy_1025" );
								}
								$local_liste_option ->set_option ( "zabbix_interfaces", 'agent|oui|10050' );
								$local_liste_option ->set_option ( "zabbix_host_host", $ci ->get_donnees ()['Server.name'] );
								$local_liste_option ->set_option ( "zabbix_host_name", $ci ->get_donnees ()['Server.name'] );
								$local_liste_option ->set_option ( "zabbix_host_groups", array (
										$machine,
										'System' ) );
								$local_liste_option ->set_option ( "zabbix_templates", array (
										'Template OS ' . $ci ->get_donnees ()['Server.osfamily_name'] ) );
								zabbix_host_administration::creer_zabbix_host_administration($local_liste_option, $zabbix_connexion->getObjetZabbixWsclient())->ajoute_host();
								break;
							case 'Middleware' :
								$local_liste_option ->set_option ( "zabbix_host_host", $ci ->get_donnees ()['Middleware.friendlyname'] );
								$local_liste_option ->set_option ( "zabbix_host_name", $ci ->get_donnees ()['Middleware.friendlyname'] );
								if (preg_match ( "/^(JBoss |Java |JCore )(?P<Apps>.*)$/", $ci ->get_donnees ()['Middleware.name'], $valeur ) === 1) {
									$local_liste_option ->set_option ( "zabbix_interfaces", 'JMX|oui|8888' );
									$local_liste_option ->set_option ( "zabbix_host_groups", array (
											$machine,
											'Java',
											$valeur ['Apps'] ) );
									$local_liste_option ->set_option ( "zabbix_templates", array (
											'Template JMX Generic' ) );
								} elseif (preg_match ( "/^CCore (?P<Apps>\w+)$/", $ci ->get_donnees ()['Middleware.name'], $valeur ) === 1) {
									$local_liste_option ->set_option ( "zabbix_interfaces", 'agent|oui|10050' );
									$local_liste_option ->set_option ( "zabbix_host_groups", array (
											$machine,
											'CCore' ) );
									$local_liste_option ->set_option ( "zabbix_templates", array (
											'Template CCore' ) );
								} else {
									$local_liste_option ->set_option ( "zabbix_interfaces", 'agent|oui|10050' );
									$local_liste_option ->set_option ( "zabbix_host_groups", array (
											$machine,
											$ci ->get_donnees ()['Middleware.name'] ) );
									$local_liste_option ->set_option ( "zabbix_templates", array (
											'' ) );
								}
								zabbix_host_administration::creer_zabbix_host_administration($local_liste_option, $zabbix_connexion->getObjetZabbixWsclient())->ajoute_host();
								break;
							case 'PCSoftware' :
								$local_liste_option ->set_option ( "zabbix_host_host", $ci ->get_donnees ()['PCSoftware.friendlyname'] );
								$local_liste_option ->set_option ( "zabbix_host_name", $ci ->get_donnees ()['PCSoftware.friendlyname'] );
								$local_liste_option ->set_option ( "zabbix_interfaces", 'agent|oui|10050' );
								$local_liste_option ->set_option ( "zabbix_host_groups", array (
										$machine,
										$ci ->get_donnees ()['PCSoftware.name'] ) );
								$local_liste_option ->set_option ( "zabbix_templates", array (
										'Template ' . $ci ->get_donnees ()['PCSoftware.name'] ) );
								zabbix_host_administration::creer_zabbix_host_administration($local_liste_option, $zabbix_connexion->getObjetZabbixWsclient())->ajoute_host();
								break;
							case 'OtherSoftware' :
								/**
								 * [OtherSoftware.id] => 549 [OtherSoftware.name] => SolarWinds [OtherSoftware.description] => [OtherSoftware.org_id] => 1 [OtherSoftware.organization_name] => Vodafone Télématics [OtherSoftware.business_criticity] => medium [key] => OtherSoftware.move2production [value] => [OtherSoftware.system_id] => 547 [OtherSoftware.system_name] => SolarWinds [OtherSoftware.software_id] => 79 [OtherSoftware.software_name] => SolarWinds [OtherSoftware.softwarelicence_id] => 0 [OtherSoftware.softwarelicence_name] => [OtherSoftware.path] => [OtherSoftware.status] => inactive [OtherSoftware.finalclass] => OtherSoftware [OtherSoftware.friendlyname] => SolarWinds SolarWinds [OtherSoftware.org_id_friendlyname] => Vodafone Télématics [OtherSoftware.system_id_friendlyname] => SolarWinds [OtherSoftware.system_id_finalclass_recall] => Server [OtherSoftware.software_id_friendlyname] => SolarWinds 1 [OtherSoftware.softwarelicence_id_friendlyname] =>
								 */
								$local_liste_option ->set_option ( "zabbix_host_host", $ci ->get_donnees ()['OtherSoftware.friendlyname'] );
								$local_liste_option ->set_option ( "zabbix_host_name", $ci ->get_donnees ()['OtherSoftware.friendlyname'] );
								$local_liste_option ->set_option ( "zabbix_interfaces", 'agent|oui|10050' );
								$local_liste_option ->set_option ( "zabbix_host_groups", array (
										$machine,
										$ci ->get_donnees ()['OtherSoftware.name'] ) );
								$local_liste_option ->set_option ( "zabbix_templates", array (
										'Template ' . $ci ->get_donnees ()['OtherSoftware.name'] ) );
								zabbix_host_administration::creer_zabbix_host_administration($local_liste_option, $zabbix_connexion->getObjetZabbixWsclient())->ajoute_host();
								break;
							case 'WebServer' :
								$local_liste_option ->set_option ( "zabbix_host_host", $ci ->get_donnees ()['WebServer.friendlyname'] );
								$local_liste_option ->set_option ( "zabbix_host_name", $ci ->get_donnees ()['WebServer.friendlyname'] );
								$local_liste_option ->set_option ( "zabbix_host_groups", array (
										$machine,
										$ci ->get_donnees ()['WebServer.name'] ) );
								if (preg_match ( "/^Tomcat/", $ci ->get_donnees ()['WebServer.name'], $valeur ) === 1) {
									$local_liste_option ->set_option ( "zabbix_interfaces", 'JMX|oui|8888' );
									$local_liste_option ->set_option ( "zabbix_templates", array (
											'Template JMX Tomcat',
											'Template JMX Generic' ) );
								} else {
									$local_liste_option ->set_option ( "zabbix_interfaces", 'agent|oui|10050' );
									$local_liste_option ->set_option ( "zabbix_templates", array (
											'Template ' . $ci ->get_donnees ()['WebServer.name'] ) );
								}
								zabbix_host_administration::creer_zabbix_host_administration($local_liste_option, $zabbix_connexion->getObjetZabbixWsclient())->ajoute_host();
								break;
						}
					}
				}
				unset ( $local_liste_option );
			}
			
			abstract_log::onInfo_standard ( "Machines : " . count ( $datas ) . " cis : " . $nb_cis );
		} catch ( Exception $e ) {
			return abstract_log::onError_standard ( $e ->getMessage (), "", $e ->getCode () );
		}
	} else {
		return abstract_log::onError_standard ( "Erreur dans les variables necessaires" );
	}
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log ->renvoie_exit () );
?>
