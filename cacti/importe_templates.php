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
// Deplacement pour joindre le repertoire lib
$deplacement = "/../../..";

if (! isset ( $argv ) && isset ( $_SERVER ) && isset ( $_SERVER ["SCRIPT_FILENAME"] )) {
	$rep_document = dirname ( $_SERVER ["SCRIPT_FILENAME"] ) . $deplacement;
	if (isset ( $_REQUEST ["env"] )) {
		$env = $_REQUEST ["env"];
	} else {
		$env = "no_env";
	}
	
	$liste_variables_systeme = array (
			"conf" => array (
					$rep_document . "/conf_clients/database/" . $env . "_cacti.xml",
					$rep_document . "/conf_clients/cacti/" . $env . "_" . $_REQUEST ["cacti_env"] . "_cacti.xml" 
			) 
	);
} else {
	$rep_document = dirname ( $argv [0] ) . $deplacement;
}
// permet inclure les api de cacti
$INCLUDE_CACTI_IMPORTTEMPLATE = true;
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
	$help [$fichier] ["text"] [] .= "Permet d'ajouter les templates dans cacti a partir d'un dossier contenant les templates a jour";
	$help [$fichier] ["text"] [] .= "\t--env prod/preprod/test environnement de travail";
	$help [$fichier] ["text"] [] .= "\t--cacti_env 'mut' Code client, il permet aussi de mettre le nom machine en {code_client}/{description}";
	$help [$fichier] ["text"] [] .= "\t--template Nom du template";
	$help [$fichier] ["text"] [] .= "\t--with-template-rras ";
	$help [$fichier] ["text"] [] .= "\t--with-user-rras ";
	$help [$fichier] ["text"] [] .= "";
	$help [$fichier] ["text"] [] .= "curl -F\"fichier_template=@cacti_host_template_client_-_reseau.xml\" \"http://addresse_cacti/cacti_hobinv/php_depot/steria/cacti/importe_templates.php?env=test&cacti_env=TRI&template=cacti_host_template_client_-_reseau.xml&verbose=0\"";
	
	$class_utilisees = array (
			"fichier",
			"cacti_importTemplate"
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
 * curl -F"fichier_template=@cacti_host_template_client_-_reseau.xml" "http://addresse_cacti/cacti_hobinv/php_depot/steria/cacti/importe_templates.php?env=test&cacti_env=CPL&template=cacti_host_template_client_-_reseau.xml&verbose=0"
 */
/**
 * Main programme
 * @ignore
 * @param options $liste_option        	
 * @param logs $fichier_log        	
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	if ($liste_option->verifie_option_existe ( "template" ) === false) {
		abstract_log::onError_standard ( "Il faut un template --template en argument." );
		return false;
	}
	
	if (isset ( $_FILES )) {
		if (isset ( $_FILES ['fichier_template'] ) && $_FILES ['fichier_template'] ['tmp_name'] != "") {
			$fichier = $_FILES ['fichier_template'] ['tmp_name'];
		} else {
			abstract_log::onError_standard ( "Il faut un fichier template par POST http." );
			return false;
		}
	} elseif ($liste_option->verifie_option_existe ( "fichier_template" ) === false) {
		abstract_log::onError_standard ( "Il faut un fichier template --fichier_template en argument." );
		return false;
	} else {
		$fichier = $liste_option->renvoie_option ( "fichier_template" );
	}
	
	$importTemplate = cacti_importTemplate::creer_cacti_importTemplate ( $liste_option );
	
	if (! $importTemplate) {
		abstract_log::onError_standard ( "Pas d'objet cacti_importTemplate valide", "", 2000 );
		return false;
	}
	
	if ($liste_option->verifie_option_existe ( "with-template-rras" ) === false) {
		$importTemplate->setWith_template_rras ( false );
	} else {
		$importTemplate->setWith_template_rras ( true );
	}
	
	if ($liste_option->verifie_option_existe ( "with-user-rras" ) !== false) {
		$importTemplate->setWith_user_rras ( $liste_option->renvoie_option ( "with-user-rras" ) );
	}
	
	abstract_log::onDebug_standard ( "Fichier en cours : " . $fichier, 1 );
	abstract_log::onInfo_standard ( "On importe le fichier : " . $liste_option->renvoie_option ( "template" ) . " (" . $fichier . ")" );
	$importTemplate->setTemplate ( $fichier );
	
	if ($importTemplate->executecacti_importTemplate () === false) {
		abstract_log::onError_standard ( "L'importation est en erreur sur le fichier " . $fichier, 2001 );
		return false;
	}
	
	return true;
}

$retour = principale ( $liste_option, $fichier_log );
/**
 * ********* FIN DE VOTRE CODE ***************
 */
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoie_exit () );
?>
