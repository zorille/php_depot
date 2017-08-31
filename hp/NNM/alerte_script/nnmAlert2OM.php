<?php
/**
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage NNM
 */
if (! isset ( $argv ) && ! isset ( $argc )) {
	fwrite ( STDOUT, "Il n'y a pas de parametres en argument.\r\n" );
	exit ( 0 );
}

//Deplacement pour joindre le repertoire lib
$deplacement = "/../../../..";
$rep_document = dirname ( $argv [0] ) . $deplacement;

//On reconstruit la liste des arguments au format "Framework PHP"
//my $object=$ARGV[0]; #Value of the incident Name (corresponds to stars problem type)
//my $sev=$ARGV[1]; #severity
//my $cust_node=$ARGV[2]; #node name with customer as prefix
//my $NODEcustomer=$ARGV[3]; #customer for node incident
//my $NODEdomain=$ARGV[4]; #domain for node incident
//my $IFcustomer=$ARGV[5];#customer for interface incident
//my $IFdomain=$ARGV[6];#domain for interface incident
//my $msg_text=$ARGV[7];#incident message text
//my $ifdescr=$ARGV[8]; #interface description
//my $ifname=$ARGV[9]; #interface name
//my $ifalias=$ARGV[10]; #interface alias


$new_argv = array (
		$argv [0] 
);
if ($argc >= 9) {
	$new_argv [] .= '--ressource';
	$new_argv [] .= $argv [1];
	$new_argv [] .= '--severite';
	$new_argv [] .= $argv [2];
	$new_argv [] .= '--noeud_nom';
	$new_argv [] .= $argv [3];
	$new_argv [] .= '--noeud_client';
	$new_argv [] .= $argv [4];
	$new_argv [] .= '--noeud_domaine';
	$new_argv [] .= $argv [5];
	$new_argv [] .= '--interface_client';
	$new_argv [] .= $argv [6];
	$new_argv [] .= '--interface_domaine';
	$new_argv [] .= $argv [7];
	$new_argv [] .= '--msg_text';
	$new_argv [] .= $argv [8];
	
	if ($argc == 12) {
		$new_argv [] .= '--interface_description';
		$new_argv [] .= $argv [9];
		$new_argv [] .= '--interface_nom';
		$new_argv [] .= $argv [10];
		$new_argv [] .= '--interface_alias';
		$new_argv [] .= $argv [11];
	}
}

$new_argv [] .= '--conf';
$new_argv [] .= $rep_document . '/conf_clients/hpom/prod_hpom_windows.xml';
$new_argv [] .= '--verbose';

$argv = $new_argv;
$argc = count ( $argv );
$INCLUDE_HPOM = true;
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
	$help [$fichier] ["text"] [] .= "Permet d'ouvrir un ticket alerte dans HPOM via NNM";
	$help [$fichier] ["text"] [] .= "argument 1 : ressource";
	$help [$fichier] ["text"] [] .= "argument 2 : severite";
	$help [$fichier] ["text"] [] .= "argument 3 : noeud_nom";
	$help [$fichier] ["text"] [] .= "argument 4 : noeud_client";
	$help [$fichier] ["text"] [] .= "argument 5 : noeud_domaine";
	$help [$fichier] ["text"] [] .= "argument 6 : interface_client";
	$help [$fichier] ["text"] [] .= "argument 7 : interface_domaine";
	$help [$fichier] ["text"] [] .= "argument 8 : msg_text";
	$help [$fichier] ["text"] [] .= "argument 9 : interface_description";
	$help [$fichier] ["text"] [] .= "argument 10 : interface_nom";
	$help [$fichier] ["text"] [] .= "argument 11 : interface_alias";
	
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
	$fichier_log->setIsErrorStdout ( true );
	
	if ($liste_option->verifie_option_existe ( "ressource", true ) === false) {
		$liste_option->setOption ( "ressource", "NONE" );
	}
	
	//Dans tous les cas on decoupe le ci du noeud_nom
	if (preg_match ( '/^.*__(?P<ci>.*)$/', $liste_option->getOption ( "noeud_nom" ), $matches ) != 0) {
		$ci = $matches ["ci"];
	} else {
		$ci = $liste_option->getOption ( "noeud_nom" );
	}
	
	//Gestion du client
	if ($liste_option->verifie_option_existe ( "interface_client", true ) !== false && ! preg_match ( "/unknown cia/i", $liste_option->getOption ( "interface_client" ) )) {
		$client = $liste_option->getOption ( "interface_client" );
	} elseif ($liste_option->verifie_option_existe ( "noeud_client", true ) !== false && ! preg_match ( "/unknown cia/i", $liste_option->getOption ( "noeud_client" ) )) {
		$client = $liste_option->getOption ( "noeud_client" );
	} else {
		$client = "CUSTOMER_UNKNOWN";
	}
	
	//Gestion de l'application
	if ($liste_option->verifie_option_existe ( "interface_domaine", true ) !== false && ! preg_match ( "/unknown cia/i", $liste_option->getOption ( "interface_domaine" ) )) {
		$application = $liste_option->getOption ( "interface_domaine" );
	} elseif ($liste_option->verifie_option_existe ( "noeud_domaine", true ) !== false && ! preg_match ( "/unknown cia/i", $liste_option->getOption ( "noeud_domaine" ) )) {
		$application = $liste_option->getOption ( "noeud_domaine" );
	} else {
		$application = "NETWORK";
	}
	
	if ($liste_option->verifie_option_existe ( "interface_nom", true ) !== false) {
		$instance = $liste_option->getOption ( "interface_nom" );
	} elseif ($liste_option->verifie_option_existe ( "interface_description", true ) !== false) {
		$instance = $liste_option->getOption ( "interface_description" );
	} elseif ($liste_option->verifie_option_existe ( "interface_alias", true ) !== false) {
		$instance = $liste_option->getOption ( "interface_alias" );
	} else {
		$instance = ""; //pas d'instance definissable
	}
	
	//message description
	$message = "This alert was generated by NNM\nAlert from " . $liste_option->getOption ( "netname" );
	$message .= "\nNode Name = " . $liste_option->getOption ( "noeud_nom" );
	$message .= "\nNode Customer = " . $liste_option->getOption ( "noeud_client" );
	$message .= "\nNode Domain = " . $liste_option->getOption ( "noeud_domaine" );
	$message .= "\nIF Name = " . $liste_option->getOption ( "interface_nom" );
	$message .= "\nIF Customer = " . $liste_option->getOption ( "interface_client" );
	$message .= "\nIF Domain = " . $liste_option->getOption ( "interface_domaine" );
	$message .= "\nIF Desc = " . $liste_option->getOption ( "interface_description" );
	$message .= "\nIF Alias = " . $liste_option->getOption ( "interface_alias" );
	
	try {
		$hpom_client = hpom_client::creer_hpom_client ( $liste_option, false );
		
		$hpom_client->setMsgGrp ( $client )
			->setNode ( $ci )
			->gestion_severite ( $liste_option->getOption ( "severite" ) )
			->setApplication ( $application )
			->setObjet ( $liste_option->getOption ( "ressource" ) )
			->setInstances ( $instance )
			->AjouteOption ( "incident_descr", $message )
			->setAppendMsgText ( $liste_option->getOption ( "msg_text" ) );
		
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
