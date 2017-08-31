#!/usr/bin/php
<?php
/**
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage HPOM
 */
$INCLUDE_HPOM = true;

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
	$help [$fichier] ["text"] [] .= "Parametres du script ".$fichier;
	$help [$fichier] ["text"] [] .= "\t--client 		Code client";
	$help [$fichier] ["text"] [] .= "\t--ci 			CI impacte";
	$help [$fichier] ["text"] [] .= "\t--severite 	severite de l'alerte";
	$help [$fichier] ["text"] [] .= "\t--application Type de CI";
	$help [$fichier] ["text"] [] .= "\t--objet 		Type du moniteur";
	$help [$fichier] ["text"] [] .= "\t--msgtext (facultatif) Force le message texte de HPOM (titre du ticket)";
	$help [$fichier] ["text"] [] .= "\t--instances   (facultatif) Liste des instances en erreur";
	$help [$fichier] ["text"] [] .= "\t--description (facultatif) Description de l'erreur";
	$help [$fichier] ["text"] [] .= "\t--eti (facultatif) ETI de l'erreur";
	$help [$fichier] ["text"] [] .= "\t--ajout_msgtext (facultatif) Ajoute une information au message texte de HPOM (titre du ticket)";
	
	$class_utilisees = array (
			"hpom_client"
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
 * Main programme
 * Code retour en 2xxx en cas d'erreur
 * @ignore
 *
 * @param options $liste_option        	
 * @param logs $fichier_log        	
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	if ($liste_option->verifie_option_existe ( "client" ) === false) {
		abstract_log::onError_standard ( "Il faut un client (--client)", "", 2001 );
		return false;
	}
	if ($liste_option->verifie_option_existe ( "ci" ) === false) {
		abstract_log::onError_standard ( "Il faut un ci (--ci)", "", 2001 );
		return false;
	}
	if ($liste_option->verifie_option_existe ( "severite" ) === false) {
		abstract_log::onError_standard ( "Il faut une severite (--severite)", "", 2001 );
		return false;
	}
	if ($liste_option->verifie_option_existe ( "application" ) === false) {
		abstract_log::onError_standard ( "Il faut une application (--application)", "", 2001 );
		return false;
	}
	if ($liste_option->verifie_option_existe ( "objet" ) === false) {
		abstract_log::onError_standard ( "Il faut une objet (--objet)", "", 2001 );
		return false;
	}
	if ($liste_option->verifie_option_existe ( "msgtext" ) === false) {
		$liste_option->setOption ( "msgtext", "" );
	}
	if ($liste_option->verifie_option_existe ( "description" ) === false) {
		$liste_option->setOption ( "description", "" );
	}
	if ($liste_option->verifie_option_existe ( "instances" ) === false) {
		$liste_option->setOption ( "instances", array () );
	}
	if ($liste_option->verifie_option_existe ( "eti" ) === false) {
		$liste_option->setOption ( "eti", "" );
	}
	
	try {
		$hpom_client = hpom_client::creer_hpom_client ( $liste_option );
		
		$hpom_client->setMsgGrp ( $liste_option->getOption ( "client" ) )
			->setNode ( $liste_option->getOption ( "ci" ) )
			->gestion_severite ( $liste_option->getOption ( "severite" ) )
			->setApplication ( $liste_option->getOption ( "application" ) )
			->setObjet ( $liste_option->getOption ( "objet" ) )
			->setInstances ( $liste_option->getOption ( "instances" ) )
			->setMsgText ( $liste_option->getOption ( "msgtext" ) );
		
		if ($liste_option->getOption ( "msgtext" ) == "") {
			//S'il n'y a pas de msgtext, on ajoute un CMA incident_descr
			$hpom_client->AjouteOption ( "incident_descr", $liste_option->getOption ( "description" ) );
		} else {
			//S'il y a un msgtext et une description, on ajoute un CMA incident_descr
			if ($liste_option->getOption ( "description" ) != "") {
				$hpom_client->AjouteOption ( "incident_descr", $liste_option->getOption ( "description" ) );
			}
		}
		
		if ($liste_option->verifie_option_existe ( "ajout_msgtext" ) !== false) {
			$hpom_client->setAppendMsgText ( $liste_option->getOption ( "ajout_msgtext" ) );
		}
		
		if ($liste_option->verifie_option_existe ( "eti" ) !== false) {
			$hpom_client->AjouteOption ( "eti", $liste_option->getOption ( "eti" ) );
		}
		
		$hpom_client->envoi_hpom_datas ();
	} catch ( Exception $e ) {
		//Erreur deja affichee
		return false;
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
