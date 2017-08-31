#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage sitescope
 */
$INCLUDE_SITESCOPE = true;
$INCLUDE_PHPEXCEL = true;

$rep_document = dirname ( $argv [0] ) . "/../../../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/lecture_fvs.class.php";
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/deploie_socle_technique.class.php";

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
	$help [$fichier] ["text"] [] .= "Permet d'integrer des machines (uniquement le socle technique) sur un sitescope";
	$help [$fichier] ["text"] [] .= "\t--sitescope_utilise Nom du sitescope a utiliser";
	$help [$fichier] ["text"] [] .= "\t--fichier_fvs chemin complet du fichier FVS a integrer ou DES fichiers séparés par un espace";
	
	$class_utilisees = array (
			"fichier",
			"sitescope_fonctions_standards",
			"sitescope_datas",
			"sitescope_soap_configuration",
			"lecture_fvs",
			"deploie_socle_technique"
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();

abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

function principale(&$liste_option, &$fichier_log) {
	//gestion des données template
	$sis_template_datas = sitescope_template_datas::creer_sitescope_template_datas ( $liste_option );
	$sitescope_functions_standards = sitescope_fonctions_standards::creer_sitescope_fonctions_standards ( $liste_option );
	
	if (! $sis_template_datas || ! $sitescope_functions_standards) {
		return abstract_log::onError_standard ( "Erreur dans les classes necessaires" );
	}
	
	$deploie_socle_technique = deploie_socle_technique::creer_deploie_socle_technique ( $liste_option, $sis_template_datas, $sitescope_functions_standards );
	if ($deploie_socle_technique->getSisSoapConfiguration ()
		->valide_presence_sitescope_data ( $liste_option->getOption ( "sitescope_utilise" ) ) === false) {
		return abstract_log::onError_standard ( "Pas de configuration pour le serveur : " . $liste_option->getOption ( "sitescope_utilise" ) );
	}
	if ($deploie_socle_technique->getSisSoapConfiguration ()
		->connect ( $liste_option->getOption ( "sitescope_utilise" ) ) === false) {
		return abstract_log::onError_standard ( "Pas de connexion au sitescope" );
	}
	
	if ($liste_option->verifie_option_existe ( "fichier_fvs" ) === false) {
		return abstract_log::onError_standard ( "Il faut une FVS pour travailler." );
	}
	
	$liste_fvs = $liste_option->getOption ( "fichier_fvs" );
	if (! is_array ( $liste_fvs )) {
		$liste_fvs = array (
				$liste_fvs 
		);
	}
	
	foreach ( $liste_fvs as $fvs ) {
		if (fichier::tester_fichier_existe ( $fvs ) === true) {
			try {
				$objPHPExcelReader = PHPExcel_IOFactory::load ( $fvs );
			} catch ( PHPExcel_Reader_Exception $e ) {
				abstract_log::onError_standard ( $e->getMessage (), "", $e->getCode () );
				return false;
			}
			$data_fvs = lecture_fvs::creer_lecture_fvs ( $liste_option, $sis_template_datas, $objPHPExcelReader );
			$data_fvs->parse_fvs ();
			
			abstract_log::onDebug_standard ( $sis_template_datas->getCI (), 1 );
			abstract_log::onDebug_standard ( $sis_template_datas->getOS (), 1 );
			abstract_log::onDebug_standard ( $sis_template_datas->getIPs (), 1 );
			abstract_log::onDebug_standard ( $sis_template_datas->getSchedule (), 1 );
			abstract_log::onDebug_standard ( $sis_template_datas->getDisks (), 1 );
			abstract_log::onDebug_standard ( $sis_template_datas->getDNS (), 1 );
			abstract_log::onDebug_standard ( $sis_template_datas->getFQDN (), 1 );
			abstract_log::onDebug_standard ( $sis_template_datas->getServices (), 1 );
			abstract_log::onDebug_standard ( $sis_template_datas->getScripts (), 1 );
			
			$deploie_socle_technique->integre_serveur ();
			
			//On nettoie pour la prochaine boucle
			$sis_template_datas->reset_datas ();
			unset ( $data_fvs );
		}
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
