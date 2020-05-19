#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package iTop
 * @subpackage extract
 */

$rep_document = dirname ( $argv [0] ) . "/../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";
use Zorille\framework as Core;
use Zorille\itop as itop;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use \Exception as Exception;

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
	$help = array_merge ( $help, Core\fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	Core\fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option ->verifie_option_existe ( "help" ))
	help ();
	
	// Le fichier de log est cree
Core\abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );
require_once $liste_option ->getOption ( "rep_scripts" ) . '/lib/itop_liste_ci.class.php';

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
	if ($liste_option ->verifie_option_existe ( "fichier_sortie", true ) === false) {
		return Core\abstract_log::onError_standard ( "Il faut un --fichier_sortie" );
	}
	
	Core\abstract_log::onInfo_standard ( "fichier de sortie : " . $liste_option ->getOption ( "fichier_sortie" ) );
	
	if ($liste_option ->verifie_option_existe ( "itop_serveur", true ) === false) {
		return Core\abstract_log::onError_standard ( "Il faut un itop_serveur pour travailler." );
	}
	$itop_webservice = itop\wsclient_rest::creer_wsclient_rest ( $liste_option, itop\datas::creer_datas ( $liste_option ) );
	
	// Create new PHPExcel object
	Core\abstract_log::onInfo_standard ( "Create new PHPExcel object" );
	$objPHPExcel = new Spreadsheet ();
	
	if ($itop_webservice && $objPHPExcel) {
		// Set document properties
		Core\abstract_log::onInfo_standard ( "Set Excel document properties" );
		$objPHPExcel ->getProperties () 
			->setCreator ( "Damien Vargas" ) 
			->setLastModifiedBy ( "Damien Vargas" ) 
			->setTitle ( "Production Export" );
		$objPHPExcel ->removeSheetByIndex ( 0 );
		
		try {
			$itop_webservice ->prepare_connexion ( $liste_option ->getOption ( "itop_serveur" ) );
			
			/*
			 * Format : expecting {AsyncTask, AsyncSendEmail, DBProperty, CMDBChange, CMDBChangeOp, CMDBChangeOpCreate, CMDBChangeOpDelete, 
			 * CMDBChangeOpSetAttribute, CMDBChangeOpSetAttributeScalar, CMDBChangeOpSetAttributeBlob, CMDBChangeOpSetAttributeOneWayPassword, 
			 * CMDBChangeOpSetAttributeEncrypted, CMDBChangeOpSetAttributeText, CMDBChangeOpSetAttributeLongText, CMDBChangeOpSetAttributeCaseLog, 
			 * CMDBChangeOpPlugin, CMDBChangeOpSetAttributeLinks, CMDBChangeOpSetAttributeLinksAddRemove, CMDBChangeOpSetAttributeLinksTune, 
			 * AuditCategory, AuditRule, Query, QueryOQL, ModuleInstallation, UserDashboard, Shortcut, ShortcutOQL, appUserPreferences, User, 
			 * UserInternal, Event, EventNotification, EventNotificationEmail, EventIssue, EventWebService, EventRestService, EventLoginUsage, 
			 * Action, ActionNotification, ActionEmail, Trigger, TriggerOnObject, TriggerOnPortalUpdate, TriggerOnStateChange, TriggerOnStateEnter, 
			 * TriggerOnStateLeave, TriggerOnObjectCreate, lnkTriggerAction, TriggerOnThresholdReached, BulkExportResult, iTopOwnershipToken, SynchroDataSource, 
			 * SynchroAttribute, SynchroAttExtKey, SynchroAttLinkSet, SynchroLog, SynchroReplica, BackgroundTask, UserExternal, UserLDAP, UserLocal, 
			 * Attachment, CMDBChangeOpAttachmentAdded, CMDBChangeOpAttachmentRemoved, Organization, Location, Contact, Person, Team, Document, 
			 * DocumentFile, DocumentNote, DocumentWeb, FunctionalCI, PhysicalDevice, ConnectableCI, DatacenterDevice, NetworkDevice, Server, 
			 * ApplicationSolution, BusinessProcess, SoftwareInstance, Middleware, DBServer, WebServer, PCSoftware, OtherSoftware, MiddlewareInstance, 
			 * DatabaseSchema, WebApplication, Software, Patch, OSPatch, SoftwarePatch, Licence, OSLicence, SoftwareLicence, lnkDocumentToLicence, 
			 * Typology, OSVersion, OSFamily, DocumentType, ContactType, Brand, Model, NetworkDeviceType, IOSVersion, lnkDocumentToPatch, 
			 * lnkSoftwareInstanceToSoftwarePatch, lnkFunctionalCIToOSPatch, lnkDocumentToSoftware, lnkContactToFunctionalCI, lnkDocumentToFunctionalCI, 
			 * Subnet, VLAN, lnkSubnetToVLAN, NetworkInterface, IPInterface, PhysicalInterface, lnkPhysicalInterfaceToVLAN, lnkConnectableCIToNetworkDevice, 
			 * lnkApplicationSolutionToFunctionalCI, lnkApplicationSolutionToBusinessProcess, lnkPersonToTeam, Group, lnkGroupToCI, Rack, Enclosure, 
			 * PowerConnection, PowerSource, PDU, PC, Printer, TelephonyCI, Phone, MobilePhone, IPPhone, Tablet, Peripheral, StorageSystem, SANSwitch, 
			 * TapeLibrary, NAS, FiberChannelInterface, Tape, NASFileSystem, LogicalVolume, lnkServerToVolume, lnkSanToDatacenterDevice, Ticket, lnkContactToTicket, 
			 * lnkFunctionalCIToTicket, WorkOrder, VirtualDevice, VirtualHost, Hypervisor, Farm, VirtualMachine, LogicalInterface, lnkVirtualDeviceToVolume, 
			 * Change, RoutineChange, ApprovedChange, NormalChange, EmergencyChange, Incident, KnownError, lnkErrorToFunctionalCI, lnkDocumentToError, FAQ, 
			 * FAQCategory, Problem, UserRequest, ContractType, Contract, CustomerContract, ProviderContract, lnkContactToContract, lnkContractToDocument, 
			 * lnkFunctionalCIToProviderContract, ServiceFamily, Service, lnkDocumentToService, lnkContactToService, ServiceSubcategory, SLA, SLT, lnkSLAToSLT, 
			 * lnkCustomerContractToService, lnkCustomerContractToProviderContract, lnkCustomerContractToFunctionalCI, DeliveryModel, lnkDeliveryModelToContact, 
			 * URP_Profiles, URP_UserProfile, URP_UserOrg}
			 */
			
			//On se connecte a iTop
			$liste_ci = itop\liste_ci::creer_liste_ci ( $liste_option, $itop_webservice );
			$liste_ci ->recupere_VirtualMachine ( $itop_webservice ) 
				->recupere_Server ( $itop_webservice ) 
				->recupere_Middleware ( $itop_webservice ) 
				->recupere_MiddlewareInstance ( $itop_webservice ) 
				->recupere_PCSoftware ( $itop_webservice ) 
				->recupere_OtherSoftware ( $itop_webservice ) 
				->recupere_WebServer ( $itop_webservice ) 
				->recupere_WebApplication ( $itop_webservice ) 
				->recupere_IPInterface ( $itop_webservice );
			
			$count=0;
			foreach ( $liste_ci ->getListeCi () as $machine => $liste_cis ) {
				$row = 1;
				$colonne = 0;
				$Sheet = $objPHPExcel ->createSheet () 
					->setTitle ( "n".$count++ );
					#->setTitle ( substr ( $machine, 0, 31 ) );
				foreach ( $liste_cis as $ci ) {
					switch ($ci ['class']) {
						case 'IPInterface' :
						case 'LogicalInterface' :
						case 'PhysicalInterface' :
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'Card' );
							$Sheet ->setCellValueByColumnAndRow ( 1, $row, $ci ['name'] );
							$Sheet ->setCellValueByColumnAndRow ( 2, $row, $ci ['ipaddress'] );
							$row ++;
							break;
						case 'VirtualMachine' :
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'Name' );
							$Sheet ->setCellValueByColumnAndRow ( 1, $row, $ci ['name'] );
							$row ++;
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'Ip Management' );
							$Sheet ->setCellValueByColumnAndRow ( 1, $row, $ci ['managementip'] );
							$row ++;
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'Business Criticity' );
							$Sheet ->setCellValueByColumnAndRow ( 1, $row, $ci ['business_criticity'] );
							$row ++;
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'Production Date' );
							if (isset ( $ci ['move2production'] )) {
								$Sheet ->setCellValueByColumnAndRow ( 1, $row, $ci ['move2production'] );
							}
							$row ++;
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'OS' );
							$Sheet ->setCellValueByColumnAndRow ( 1, $row, $ci ['osfamily_name'] . " " . $ci ['osversion_name'] );
							$row ++;
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'CPU' );
							$Sheet ->setCellValueByColumnAndRow ( 1, $row, $ci ['cpu'] . " CPUs" );
							$row ++;
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'Ram' );
							$Sheet ->setCellValueByColumnAndRow ( 1, $row, $ci ['ram'] );
							$row ++;
							
							break;
						case 'Server' :
							if (strpos ( $ci ['name'], "ibm-oracle" ) !== false) {
								break;
							}
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'Name' );
							$Sheet ->setCellValueByColumnAndRow ( 1, $row, $ci ['name'] );
							$row ++;
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'Ip Management' );
							$Sheet ->setCellValueByColumnAndRow ( 1, $row, $ci ['managementip'] );
							$row ++;
							
							break;
						case 'Middleware' :
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'Middleware' );
							$Sheet ->setCellValueByColumnAndRow ( 1, $row, $ci ['software_id_friendlyname'] );
							$Sheet ->setCellValueByColumnAndRow ( 2, $row, $ci ['name'] );
							$row ++;
							
							break;
						case 'MiddlewareInstance' :
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'Middleware Instance' );
							$Sheet ->setCellValueByColumnAndRow ( 1, $row, $ci ['middleware_id_friendlyname'] );
							$Sheet ->setCellValueByColumnAndRow ( 2, $row, $ci ['name'] );
							$row ++;
							
							break;
						case 'PCSoftware' :
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'Software Instance' );
							$Sheet ->setCellValueByColumnAndRow ( 1, $row, $ci ['software_id_friendlyname'] );
							$Sheet ->setCellValueByColumnAndRow ( 2, $row, $ci ['name'] );
							$row ++;
							
							break;
						case 'OtherSoftware' :
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'Software Instance' );
							$Sheet ->setCellValueByColumnAndRow ( 1, $row, $ci ['software_id_friendlyname'] );
							$Sheet ->setCellValueByColumnAndRow ( 2, $row, $ci ['name'] );
							$row ++;
							
							break;
						case 'WebServer' :
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'WebServer' );
							$Sheet ->setCellValueByColumnAndRow ( 1, $row, $ci ['software_id_friendlyname'] );
							$Sheet ->setCellValueByColumnAndRow ( 2, $row, $ci ['name'] );
							$row ++;
							
							break;
						case 'WebApplication' :
							$Sheet ->setCellValueByColumnAndRow ( 0, $row, 'WebApplication' );
							$Sheet ->setCellValueByColumnAndRow ( 2, $row, $ci ['name'] );
							$row ++;
							
							break;
					}
				}
				$Sheet ->getColumnDimensionByColumn ( 0 ) 
					->setAutoSize ( true );
				$Sheet ->getColumnDimensionByColumn ( 1 ) 
					->setAutoSize ( true );
			}
			
			// Save Excel 2007 file
			Core\abstract_log::onInfo_standard ( "Write to Excel format" );
			#$objWriter = PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel2007' );
			$objWriter = new Xlsx($objPHPExcel);
			$objWriter ->save ( $liste_option ->getOption ( "fichier_sortie" ) );
			Core\abstract_log::onInfo_standard ( "File written to " . $liste_option ->getOption ( "fichier_sortie" ) );
		} catch ( Exception $e ) {
			return Core\abstract_log::onError_standard ( $e ->getMessage (), "", $e ->getCode () );
		}
	} else {
		return Core\abstract_log::onError_standard ( "Erreur dans les variables necessaires" );
	}
}

principale ( $liste_option, $fichier_log );
Core\abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log ->renvoiExit () );
?>
