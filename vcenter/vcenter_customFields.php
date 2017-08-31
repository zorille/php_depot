#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package Vcenter
 * @subpackage VMWare
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
	$help [$fichier] ["text"] [] .= "\t--vmware_utilise {nom du vcenter} Nom du vcenter defini dans le fichier conf";
	$help [$fichier] ["text"] [] .= "\t--affiche_liste_customFields Affiche la liste des customFields";
	$help [$fichier] ["text"] [] .= "\t--ajoute_customFields Ajoute un customField";
	$help [$fichier] ["text"] [] .= "\t--renomme_liste_customFields Renomme un customField";
	$help [$fichier] ["text"] [] .= "\t--supprime_liste_customFields Supprime un customField";
	$help [$fichier] ["text"] [] .= "\t--customField_nom Nom du customField";
	$help [$fichier] ["text"] [] .= "\t--customField_key Cle du customField";
	$help [$fichier] ["text"] [] .= "\t--customField_omtype VirtualMachine/HostSystem omType pour l'ajout du customField (Ne pas mettre ce parametre pour un type Global)";
	$help [$fichier] ["text"] [] .= "";
	$help [$fichier] ["text"] [] .= "\t--modifie_customField VM/Host Modifie la valeur d'un customFiel pour une VM ou un Host";
	$help [$fichier] ["text"] [] .= "\t--VM_nom Nom de la VM a modifier";
	$help [$fichier] ["text"] [] .= "\t--customField_valeur valeur a mettre dans le customField";
	//
	

	$class_utilisees = array ();
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
 */
function principale(&$liste_option, &$fichier_log) {
	if ($liste_option->verifie_option_existe ( "vmware_utilise", true ) === false) {
		return abstract_log::onError_standard ( "il manque le parametre --vmware_utilise " );
	}
	$vmware_webservice = vmwareWsclient::creer_vmwareWsclient ( $liste_option, vmwareDatas::creer_vmwareDatas ( $liste_option ) );
	if ($vmware_webservice) {
		try {
			$vmware_webservice->prepare_connexion ( $liste_option->getOption ( "vmware_utilise" ) );
			$vmware_customFields = vmwareCustomFieldsManager::creer_vmwareCustomFieldsManager ( $liste_option, $vmware_webservice, $vmware_webservice->getObjectServiceInstance () );
			$vim25 = Vim25::creer_Vim25 ( $liste_option, $vmware_webservice );
			
			$vmwareExtensibleManagedObject = vmwareExtensibleManagedObject::creer_vmwareExtensibleManagedObject ( $liste_option, $vmware_webservice );
		} catch ( Exception $e ) {
			return abstract_log::onError_standard ( $e->getMessage (), "", $e->getCode () );
		}
	} else {
		return abstract_log::onError_standard ( "Erreur dans les variables necessaires" );
	}
	
	try {
		
		if ($liste_option->verifie_option_existe ( "ajoute_customFields" ) !== false) {
			if ($liste_option->verifie_option_existe ( "customField_nom", true ) === false) {
				return abstract_log::onError_standard ( "il manque le parametre --customField_nom " );
			}
			if ($liste_option->verifie_option_existe ( "customField_omtype", true ) === false) {
				$liste_option->setOption ( "customField_omtype", "" );
			}
			$add_customFields = $vmware_customFields->AddCustomFieldDef ( $liste_option->getOption ( "customField_nom" ), $liste_option->getOption ( "customField_omtype" ) );
			abstract_log::onInfo_standard ( $add_customFields );
		} elseif ($liste_option->verifie_option_existe ( "renomme_liste_customFields" ) !== false) {
			if ($liste_option->verifie_option_existe ( "customField_nom", true ) === false) {
				return abstract_log::onError_standard ( "il manque le parametre --customField_nom " );
			}
			if ($liste_option->verifie_option_existe ( "customField_key", true ) === false) {
				return abstract_log::onError_standard ( "il manque le parametre --customField_key " );
			}
			$renomme_customFields = $vmware_customFields->RenameCustomFieldDef ( $liste_option->getOption ( "customField_key" ), $liste_option->getOption ( "customField_nom" ) );
			abstract_log::onInfo_standard ( $renomme_customFields );
		} elseif ($liste_option->verifie_option_existe ( "supprime_liste_customFields" ) !== false) {
			if ($liste_option->verifie_option_existe ( "customField_key", true ) === false) {
				return abstract_log::onError_standard ( "il manque le parametre --customField_key " );
			}
			$vmware_customFields->RemoveCustomFieldDef ( $liste_option->getOption ( "customField_key" ) );
			abstract_log::onInfo_standard ( "Suppression termine" );
		}
		
		if ($liste_option->verifie_option_existe ( "affiche_liste_customFields" ) !== false) {
			$liste_customFields = $vim25->getObjectVmwarePropertyCollector ()
				->retrouve_propset ( (array) $vmware_customFields->getCustomFieldsManager (), true, array () );
			abstract_log::onInfo_standard ( $liste_customFields );
		}
		
		if ($liste_option->verifie_option_existe ( "modifie_customField" ) !== false) {
			if ($liste_option->verifie_option_existe ( "customField_nom", true ) === false) {
				return abstract_log::onError_standard ( "il manque le parametre --customField_nom " );
			}
			if ($liste_option->verifie_option_existe ( "customField_valeur" ) === false) {
				return abstract_log::onError_standard ( "il manque le parametre --customField_valeur " );
			}
			switch ($liste_option->getOption ( "modifie_customField" )) {
				case "VM" :
					if ($liste_option->verifie_option_existe ( "VM_nom", true ) === false) {
						return abstract_log::onError_standard ( "il manque le parametre --VM_nom " );
					}
					if ($liste_option->getOption ( "VM_nom" ) == "all") {
						$liste_VMs = $vim25->Get_VirtualMachine ();
						foreach ( $liste_VMs as $moid_VM ) {
							$vim25->getObjectVmwareVirtualMachine ()
								->setMoIDVirtualMachine ( $moid_VM )
								->setCustomValue ( $liste_option->getOption ( "customField_nom" ), $liste_option->getOption ( "customField_valeur" ) );
						}
					} else {
						$vim25->Get_VirtualMachine_Name ( $liste_option->getOption ( "VM_nom" ) )
							->setCustomValue ( $liste_option->getOption ( "customField_nom" ), $liste_option->getOption ( "customField_valeur" ) );
					}
					break;
				case "Host" :
					if ($liste_option->verifie_option_existe ( "Host_nom", true ) === false) {
						return abstract_log::onError_standard ( "il manque le parametre --Host_nom " );
					}
					if ($liste_option->getOption ( "Host_nom" ) == "all") {
						$liste_Hosts = $vim25->Get_HostSystem ();
						foreach ( $liste_Hosts as $moid_Host ) {
							$vim25->getObjectVmwareHostSystem ()
								->setHostSystem ( $moid_Host )
								->setCustomValue ( $liste_option->getOption ( "customField_nom" ), $liste_option->getOption ( "customField_valeur" ) );
						}
					} else {
						$vim25->Get_HostSystem_Name ( $liste_option->getOption ( "Host_nom" ) )
							->setCustomValue ( $liste_option->getOption ( "customField_nom" ), $liste_option->getOption ( "customField_valeur" ) );
					}
					break;
				default :
					abstract_log::onError_standard ( "Type de modification inconnu : " . $liste_option->getOption ( "modifie_customField" ) );
			}
		}
		
		$vmware_webservice->logout ();
	} catch ( Exception $e ) {
		printf ( "%s\n", $e->getMessage () );
		$vmware_webservice->logout ();
		return false;
	}
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
