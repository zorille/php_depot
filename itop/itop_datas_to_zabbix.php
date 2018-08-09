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
require_once $liste_option ->getOption ( "rep_scripts" ) . '/lib/itop_liste_ci.class.php';
require_once $liste_option ->getOption ( "rep_scripts" ) . '/lib/itop_to_zabbix.class.php';

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
		return abstract_log::onError_standard ( "Il faut un itop_serveur pour travailler." );
	}
	if ($liste_option ->verifie_option_existe ( "zabbix_serveur" ) === false) {
		return abstract_log::onError_standard ( "Il faut un zabbix_serveur pour travailler." );
	}
	
	//On se connecte au zabbix
	$zabbix_connexion = zabbix_connexion::creer_zabbix_connexion ( $liste_option ) ->connect_zabbix ();
	
	//On se connect a itop
	$itop_webservice = itop_wsclient_rest::creer_itop_wsclient_rest ( $liste_option, itop_datas::creer_itop_datas ( $liste_option ) );
	if ($itop_webservice && $zabbix_connexion) {
		try {
			$itop_webservice ->prepare_connexion ( $liste_option ->getOption ( "itop_serveur" ) );
			
			//On se connecte a iTop
			$liste_ci = itop_liste_ci::creer_itop_liste_ci ( $liste_option, $itop_webservice );
			$liste_ci ->recupere_VirtualMachine ( $itop_webservice ) 
				->recupere_Server ( $itop_webservice ) 
				->recupere_Middleware ( $itop_webservice ) 
				->recupere_MiddlewareInstance ( $itop_webservice ) 
				->recupere_PCSoftware ( $itop_webservice ) 
				->recupere_OtherSoftware ( $itop_webservice ) 
				->recupere_WebServer ( $itop_webservice );
			/*
			 * Format : expecting {AsyncTask, AsyncSendEmail, DBProperty, CMDBChange, CMDBChangeOp, CMDBChangeOpCreate, CMDBChangeOpDelete, 
			 * CMDBChangeOpSetAttribute, CMDBChangeOpSetAttributeScalar, CMDBChangeOpSetAttributeBlob, CMDBChangeOpSetAttributeOneWayPassword, 
			 * CMDBChangeOpSetAttributeEncrypted, CMDBChangeOpSetAttributeText, CMDBChangeOpSetAttributeLongText, CMDBChangeOpSetAttributeCaseLog, 
			 * CMDBChangeOpPlugin, CMDBChangeOpSetAttributeLinks, CMDBChangeOpSetAttributeLinksAddRemove, CMDBChangeOpSetAttributeLinksTune, 
			 * AuditCategory, AuditRule, Query, QueryOQL, ModuleInstallation, UserDashboard, Shortcut, ShortcutOQL, appUserPreferences, User, 
			 * UserInternal, Event, EventNotification, EventNotificationEmail, EventIssue, EventWebService, EventRestService, EventLoginUsage, 
			 * Action, ActionNotification, ActionEmail, Trigger, TriggerOnObject, TriggerOnPortalUpdate, TriggerOnStateChange, TriggerOnStateEnter, 
			 * TriggerOnStateLeave, TriggerOnObjectCreate, lnkTriggerAction, TriggerOnThresholdReached, BulkExportResult, iTopOwnershipToken, 
			 * SynchroDataSource, SynchroAttribute, SynchroAttExtKey, SynchroAttLinkSet, SynchroLog, SynchroReplica, BackgroundTask, UserExternal, 
			 * UserLDAP, UserLocal, Attachment, CMDBChangeOpAttachmentAdded, CMDBChangeOpAttachmentRemoved, Organization, Location, Contact, 
			 * Person, Team, Document, DocumentFile, DocumentNote, DocumentWeb, FunctionalCI, PhysicalDevice, ConnectableCI, DatacenterDevice, 
			 * NetworkDevice, Server, ApplicationSolution, BusinessProcess, SoftwareInstance, Middleware, DBServer, WebServer, PCSoftware, 
			 * OtherSoftware, MiddlewareInstance, DatabaseSchema, WebApplication, Software, Patch, OSPatch, SoftwarePatch, Licence, OSLicence, 
			 * SoftwareLicence, lnkDocumentToLicence, Typology, OSVersion, OSFamily, DocumentType, ContactType, Brand, Model, NetworkDeviceType, 
			 * IOSVersion, lnkDocumentToPatch, lnkSoftwareInstanceToSoftwarePatch, lnkFunctionalCIToOSPatch, lnkDocumentToSoftware, lnkContactToFunctionalCI, 
			 * lnkDocumentToFunctionalCI, Subnet, VLAN, lnkSubnetToVLAN, NetworkInterface, IPInterface, PhysicalInterface, lnkPhysicalInterfaceToVLAN, 
			 * lnkConnectableCIToNetworkDevice, lnkApplicationSolutionToFunctionalCI, lnkApplicationSolutionToBusinessProcess, lnkPersonToTeam, Group, 
			 * lnkGroupToCI, Rack, Enclosure, PowerConnection, PowerSource, PDU, PC, Printer, TelephonyCI, Phone, MobilePhone, IPPhone, Tablet, Peripheral, 
			 * StorageSystem, SANSwitch, TapeLibrary, NAS, FiberChannelInterface, Tape, NASFileSystem, LogicalVolume, lnkServerToVolume, lnkSanToDatacenterDevice, 
			 * Ticket, lnkContactToTicket, lnkFunctionalCIToTicket, WorkOrder, VirtualDevice, VirtualHost, Hypervisor, Farm, VirtualMachine, LogicalInterface, 
			 * lnkVirtualDeviceToVolume, Change, RoutineChange, ApprovedChange, NormalChange, EmergencyChange, Incident, KnownError, lnkErrorToFunctionalCI, 
			 * lnkDocumentToError, FAQ, FAQCategory, Problem, UserRequest, ContractType, Contract, CustomerContract, ProviderContract, lnkContactToContract, 
			 * lnkContractToDocument, lnkFunctionalCIToProviderContract, ServiceFamily, Service, lnkDocumentToService, lnkContactToService, ServiceSubcategory, 
			 * SLA, SLT, lnkSLAToSLT, lnkCustomerContractToService, lnkCustomerContractToProviderContract, lnkCustomerContractToFunctionalCI, DeliveryModel, 
			 * lnkDeliveryModelToContact, URP_Profiles, URP_UserProfile, URP_UserOrg}
			 */
			
			foreach ( $liste_ci ->getListeCi() as $machine => $liste_cis ) {
				$itop_to_zabbix = itop_to_zabbix::creer_itop_to_zabbix ( $liste_option, $zabbix_connexion );
				$itop_to_zabbix ->setMachineName ( $machine );
				
				foreach ( $liste_cis as $pos => $ci ) {
					//--zabbix_interface_ip 122.122.122.122 --zabbix_interfaces 'agent|oui|10050' --zabbix_host_host test_host
					// --zabbix_host_name test_host --zabbix_host_status monitored --zabbix_host_groups 'VirtualMachine' --zabbix_templates 'Template OS Linux'
					switch ($ci ['class']) {
						case 'VirtualMachine' :
						case 'Server' :
							if($ci ['managementip']==""){
								abstract_log::onError_standard("Il faut une ip pour fonctionner",$ci);
								continue 3;
							}
							
							//Exemple de Gestion des proxys
							/*if (preg_match ( "/^10\.20.*$/", $ci ['managementip'] ) === 1) {
								$liste_cis [$pos] ['proxy'] = "zabbix_proxy_1020";
							} elseif (preg_match ( "/^10\.25.*$/", $ci ['managementip'] ) === 1) {
								$liste_cis [$pos] ['proxy'] = "zabbix_proxy_1025";
							}*/
							break;
					}
				}
				$itop_to_zabbix ->creer_liste_cis ( $liste_cis );
				
				unset ( $itop_to_zabbix );
			}
		} catch ( Exception $e ) {
			return abstract_log::onError_standard ( $e ->getMessage (), "", $e ->getCode () );
		}
	} else {
		return abstract_log::onError_standard ( "Erreur dans les variables necessaires" );
	}
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log ->renvoiExit () );
?>
