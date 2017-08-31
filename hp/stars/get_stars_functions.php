#!/usr/bin/php
<?php
/**
 *
 * @ignore
 *
 *
 *
 */
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage Stars
 */
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
	$help [$fichier] ["text"] [] .= "\t--stars_host host stars";
	$help [$fichier] ["text"] [] .= "\t--stars_port port stars";
	$help [$fichier] ["text"] [] .= "\t--stars_username username stars";
	$help [$fichier] ["text"] [] .= "\t--stars_password password stars";
	$help [$fichier] ["text"] [] .= "\t--stars_wsdl lien sur le wsdl de stars";
	
	$class_utilisees = array (
			"fichier"
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
 */

/*
 * Const stars3_user = "CONNECT_IT_HPOM" 'user to access STARS 3 web services
Const stars3_pwd  = "YyD!74R$" 'password to access STARS 3 web services
Const stars3_host = "prdllb01" 'stars 3 host
Const stars3_port = 15090
Const OpenBy = "SCITO-HPOM-FR"
Const OM_server = "pmon1bddhpov01.fr.sterianet"
Const WMIMsg  = "WinMgmts:{impersonationLevel=impersonate}!root/HewlettPackard/OpenView/Data:OV_Message.Id="
Const WMINode = "WinMgmts:{impersonationLevel=impersonate}!root/HewlettPackard/OpenView/Data:OV_ManagedNode.Name="
'stars ticket default value in case ticket not created due to wrong value
Const def_object = "NONE"
Const def_appl = "APPLICATION"
Const def_group = "client_FR_MONITORING"
Const STARS3_area = "HPOM"
Const STARS3_category = "incident"
strUrl = "http://" & stars3_user & ":" & stars3_pwd & "@" & stars3_host & ":" & stars3_port & "/SM/7/ws"

 * @param options $liste_option
 */
function principale(&$liste_option, &$fichier_log) {
	$continue = true;
	
	if ($liste_option->verifie_variable_standard ( array (
			"stars",
			"host" 
	) ) === false) {
		abstract_log::onError_standard ( "Il faut un host pour stars" );
		return false;
	} else {
		$host_stars = $liste_option->renvoi_variables_standard ( array (
				"stars",
				"host" 
		) );
	}
	
	if ($liste_option->verifie_variable_standard ( array (
			"stars",
			"username" 
	) ) === false) {
		abstract_log::onError_standard ( "Il faut un username pour stars" );
		return false;
	} else {
		$username_stars = $liste_option->renvoi_variables_standard ( array (
				"stars",
				"username" 
		) );
	}
	
	if ($liste_option->verifie_variable_standard ( array (
			"stars",
			"password" 
	) ) === false) {
		abstract_log::onError_standard ( "Il faut un password pour stars" );
		return false;
	} else {
		$password_stars = $liste_option->renvoi_variables_standard ( array (
				"stars",
				"password" 
		) );
	}
	
	if ($liste_option->verifie_variable_standard ( array (
			"stars",
			"port" 
	) ) === false) {
		abstract_log::onError_standard ( "Il faut un port pour stars" );
		return false;
	} else {
		$port_stars = $liste_option->renvoi_variables_standard ( array (
				"stars",
				"port" 
		) );
	}
	
	if ($liste_option->verifie_variable_standard ( array (
			"stars",
			"wsdl" 
	) ) === false) {
		abstract_log::onError_standard ( "Il faut un wsdl pour stars" );
		return false;
	} else {
		$wsdl_stars = $liste_option->renvoi_variables_standard ( array (
				"stars",
				"wsdl" 
		) );
	}
	
	$soapClient = null;
	
	try {
		//"http://" & stars3_user & ":" & stars3_pwd & "@" & stars3_host & ":" & stars3_port & "/SM/7/ws"
		//$soapClient = @new SoapClient ( "http://" . $username_stars . ":" . $password_stars ."@"
		//		.$host_stars . ":" . $port_stars . "/SM/7/" . $wsdl_stars
		$soapClient = @new SoapClient ( "http://" . $host_stars . ":" . $port_stars . "/SM/7/" . $wsdl_stars . "?wsdl", array () );
		// Stuff for development.
		//'trace' => 1,
		//'exceptions' => true,
		//'cache_wsdl' => WSDL_CACHE_NONE,
		//'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
		

		// Auth credentials for the SOAP request.
		//'login' => $username_stars,
		//'password' => $password_stars,
		

		// Proxy url.
		//'proxy_host'     => "10.200.1.60",
		//'proxy_port'     => intval(8080),
	} catch ( Exception $e ) {
		abstract_log::onError_standard ( $e->getMessage () );
	}
	if ($soapClient instanceof SoapClient) {
		foreach ( $soapClient->__getFunctions () as $function ) {
			echo $function . "\n";
			//abstract_log::onInfo_standard ( $function );
		}
		
		foreach ( $soapClient->__getTypes () as $types ) {
			print_r ( $types );
			echo "\n";
			//abstract_log::onInfo_standard ( $types );
		}
	}
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
