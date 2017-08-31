
<?php
/**
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package Steria
 * @subpackage Cacti
 */

// Specifiquement pour cacti, on a des INCLUDE qui permettent de charger les APIs de Cacti


//Deplacement pour joindre le repertoire lib
$deplacement = "/../../..";

if (! isset ( $argv ) && isset ( $_SERVER ) && isset ( $_SERVER ["SCRIPT_FILENAME"] )) {
	$rep_document = dirname ( $_SERVER ["SCRIPT_FILENAME"] ) . $deplacement;
	$liste_variables_systeme = array (
			"conf" => array (
					$rep_document . "/conf_clients/cacti/prod_" . $_REQUEST ["cacti_env"] . "_cacti.xml",
					$rep_document . "/conf_clients/database/prod_cacti.xml" 
			),
			"no_mail" => "" 
	);
} else {
	$rep_document = dirname ( $argv [0] ) . $deplacement;
}

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
	$help [$fichier] ["text"] [] .= "Permet d'extraire la liste des hosts d'un cacti";
	$help [$fichier] ["text"] [] .= "\t--env prod/preprod/test environnement de travail";
	$help [$fichier] ["text"] [] .= "\t--cacti_env 'mut' Code client, il permet aussi de mettre le nom machine en {code_client}/{description}";
	$help [$fichier] ["text"] [] .= "\t--fichier_sortie /tmp/fichier.out Chemin et nom du fichier d'extraction";
	$help [$fichier] ["text"] [] .= "\t--client mut Nom du client contenu dans le fichier de sortie";
	
	$class_utilisees = array (
			"fichier",
			"cacti_datas",
			"cacti_hosts"
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
/**
 * Main programme
 * @ignore
 * @param options $liste_option
 * @param logs $fichier_log
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	if ($liste_option->verifie_option_existe ( "fichier_sortie" ) === false) {
		$liste_option->set_option ( "fichier_sortie", "/tmp/" . date ( "Ymd_H:i:s" ) . "_liste_serveurs_cacti.csv" );
	}
	if ($liste_option->verifie_option_existe ( "client" ) === false) {
		$liste_option->set_option ( "client", "mut" );
	}
	
	$cacti = cacti_hosts::creer_cacti_hosts ( $liste_option );
	$html = html::creer_html ( $liste_option );
	
	if (! $cacti || ! $html) {
		abstract_log::onError_standard ( "Il manque des variables necessaires", "", 2000 );
		return false;
	}
	
	abstract_log::onInfo_standard ( "Fichier de sortie : " . $liste_option->renvoie_option ( "fichier_sortie" ) );
	$fichier_out = fichier::creer_fichier ( $liste_option, $liste_option->renvoie_option ( "fichier_sortie" ), "oui" );
	$fichier_out->ouvrir ( "w" );
	$fichier_out->ecrit ( "client;Nom du CI;IP/hostname;Disabled\n" );
	
	foreach ( $cacti->getHosts () as $host ) {
		abstract_log::onDebug_standard ( $host, 2 );
		$fichier_out->ecrit ( $liste_option->renvoie_option ( "client" ) . ";" . $host ["description"] . ";" . $host ["hostname"] . ";" . ($host ["disabled"] ? "true" : "false") . "\n" );
	}
	
	$fichier_out->close ();
	
	if ($fichier_log->getIsWeb () === true) {
		$html->envoyer_fichier ( $liste_option->renvoie_option ( "fichier_sortie" ), "application/vnd.ms-excel" );
	} else {
		fonctions_standards_mail::envoieMail_standard ( $liste_option, "Extraction cacti du " . date ( "M/Y" ), array (
				"text" => "Ci-joint votre extraction\nCordialement." 
		), array (
				$liste_option->renvoie_option ( "fichier_sortie" ) 
		) );
	}
	
	if ($liste_option->verifie_option_existe ( "no_clean" ) === false) {
		fichier::supprime_fichier ( $liste_option->renvoie_option ( "fichier_excel" ) );
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
