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
$continue = true;

if ($liste_option->verifie_option_existe ( "fichier_sortie" ) === false) {
	$liste_option->setOption ( "fichier_sortie", "/tmp/liste_full_serveurs_sitescope.csv" );
}

$soapClient_preferences = sitescope_soap_preferences::creer_sitescope_soap_preferences ( $liste_option );

if ($continue && $soapClient_preferences) {
	abstract_log::onInfo_standard ( "Fichier de sortie : " . $liste_option->getOption ( "fichier_sortie" ) );
	$fichier_out = fichier::creer_fichier ( $liste_option, $liste_option->getOption ( "fichier_sortie" ), "oui" );
	$fichier_out->ouvrir ( "w" );
	$fichier_out->ecrit ( "client;Sis;Nom du CI;Id Sis;Host;Description;OS;Methode conn" );
	$fichier_out->ecrit ( ";Nom Credential;Login;Password" );
	$fichier_out->ecrit ( ";sshAuthMethod;Clé privée;Port SSH;Type client SSH;Version2 Only;Keep Alive;sshConnectionsLimit" );
	$fichier_out->ecrit ( ";sshCommand;loginPrompt;passwordPrompt;preLoginPrompt;Prompt;initShellEnvironment" );
	$fichier_out->ecrit ( ";agentBased;remoteEncoding;Status conn CI;InTest\n" );
	
	foreach ( $soapClient_preferences->getServeurDatas () as $serveur_data ) {
		abstract_log::onInfo_standard ( "Sitescope : " . $serveur_data ["nom"] );
		abstract_log::onDebug_standard ( $serveur_data, 2 );
		
		if ($soapClient_preferences->connect ( $serveur_data ["nom"] )) {
			
			$soapClient_preferences->setArbreMachines ( array () );
			$soapClient_preferences->retrouve_arbre_machines ();
			
			$datas_finales = $soapClient_preferences->getArbreMachines ();
			abstract_log::onDebug_standard ( $datas_finales, 1 );
			foreach ( $datas_finales as $machine => $datas ) {
				if (! isset ( $datas ["_name"] )) {
					continue;
				}
				$ligne = $datas ["_name"];
				$ligne .= ";" . $datas ["_id"];
				$ligne .= ";" . $datas ["_host"];
				$ligne .= ";" . $datas ["_description"];
				
				$ligne .= ";" . $datas ["_os"];
				$ligne .= ";" . $datas ["_method"];
				
				$ligne .= ";" . $datas ["_credentials"] . ";" . $datas ["_login"] . ";" . $datas ["_password"];
				// SSH
				$ligne .= ";" . $datas ["_sshAuthMethod"] . ";" . $datas ["_keyFile"] . ";" . $datas ["_sshPort"] . ";" . $datas ["_sshClient"];
				$ligne .= ";" . ($datas ["_version2"] == "on" ? "on" : "off") . ";" . ($datas ["_keepAlive"] == "on" ? "on" : "off") . ";" . $datas ["_sshConnectionsLimit"];
				if (isset ( $datas ["_sshCommand"] )) {
					$ligne .= ";" . '"' . $datas ["_sshCommand"] . '"';
					// Les suivant n'existent pas en 10.14
					$ligne .= ";" . (isset ( $datas ["_loginPrompt"] ) ? '"' . $datas ["_loginPrompt"] . '"' : '""');
					$ligne .= ";" . (isset ( $datas ["_passwordPrompt"] ) ? '"' . $datas ["_passwordPrompt"] . '"' : '""');
					$ligne .= ";" . (isset ( $datas ["_preLoginPrompt"] ) ? '"' . $datas ["_preLoginPrompt"] . '"' : '""');
				} else {
					$ligne .= ';"";"";"";""';
				}
				$ligne .= ";" . (isset ( $datas ["_prompt"] ) ? $datas ["_prompt"] : '""');
				
				$ligne .= ";" . (isset ( $datas ["_initShellEnvironment"] ) ? '"' . $datas ["_initShellEnvironment"] . '"' : '""');
				$ligne .= ";" . (isset ( $datas ["_agentBased"] ) ? $datas ["_agentBased"] : '""');
				$ligne .= ";" . $datas ["_remoteEncoding"] . ";" . $datas ["_status"] . ";" . (isset ( $datas ["inTest"] ) ? $datas ["inTest"] : "false");
				$fichier_out->ecrit ( $serveur_data ["client"] . ";" . $serveur_data ["nom"] . ";" . $ligne . "\n" );
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
