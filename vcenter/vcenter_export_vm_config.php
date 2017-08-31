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
	$help [$fichier] ["text"] [] .= "\t--vmware_utilise";
	$help [$fichier] ["text"] [] .= "\t--dossier /tmp Dossier de sortie pour les fichiers xml. /tmp par defaut";
	
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
		$liste_option->setOption ( 'vmware_utilise', "POC_VMWare2" );
	}
	if ($liste_option->verifie_option_existe ( "dossier", true ) === false) {
		$liste_option->setOption ( 'dossier', "/tmp" );
	}
	$vmware_webservice = vmwareWsclient::creer_vmwareWsclient ( $liste_option, vmwareDatas::creer_vmwareDatas ( $liste_option ) );
	if ($vmware_webservice) {
		try {
			$vmware_webservice->prepare_connexion ( $liste_option->getOption ( "vmware_utilise" ) );
		} catch ( Exception $e ) {
			return abstract_log::onError_standard ( $e->getMessage (), "", $e->getCode () );
		}
	} else {
		return abstract_log::onError_standard ( "Erreur dans les variables necessaires" );
	}
	
	$vim25 = Vim25::creer_Vim25 ( $liste_option, $vmware_webservice );
	$liste_VMs = $vim25->Get_VirtualMachine ();
	foreach ( $liste_VMs as $VM ) {
		try {
			$donnees_Vm = $vim25->getObjectVmwarePropertyCollector ()
				->retrouve_propset ( $VM, false, array (
					"config" 
			) );
			if (isset ( $donnees_Vm ['config'] ['name'] )) {
				$nom = str_replace ( " ", "_", $donnees_Vm ['config'] ['name'] );
				// On creer un objet SimpleXMLElement
				$xml_vm_info = new SimpleXMLElement ( "<?xml version=\"1.0\" encoding=\"UTF-8\"?><" . $nom . "/>" );
				
				//On converti le tableau de retour en XML
				$liste_option->array_to_xml ( $donnees_Vm ["config"], $xml_vm_info );
				
				//On enregistre le fichier de sortie
				$fichier = $liste_option->getOption ( "dossier" ) . "/" . $nom . ".xml";
				$texte = str_replace ( "><", ">\n<", $xml_vm_info->asXML () );
				$file = fichier::creer_fichier ( $liste_option, $fichier, "oui" );
				$file->ouvrir ( "w" );
				$file->ecrit ( $texte );
				$file->close ();
				abstract_log::onInfo_standard ( $fichier );
			}
		} catch ( Exception $e ) {
			$vmware_webservice->logout ();
			return abstract_log::onError_standard ( $e->getMessage (), "", $e->getCode () );
		}
	}
	
	$vmware_webservice->logout ();
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
