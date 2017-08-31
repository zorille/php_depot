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
	$help [$fichier] ["text"] [] .= "Permet de supprimer des machines (uniquement le socle technique) sur un sitescope";
	$help [$fichier] ["text"] [] .= "\t--sitescope_utilise Nom du sitescope a utiliser";
	$help [$fichier] ["text"] [] .= "\t--fichier_csv Chemin complet du fichier CSV a utiliser";
	$help [$fichier] ["text"] [] .= "               Contenu du fichier CSV :";
	$help [$fichier] ["text"] [] .= "               Champ 1 : Nom du CI dans sitescope";
	$help [$fichier] ["text"] [] .= "               Champ 2 : OS du CI (WINDOWS/LINUX/UNIX)";
	$help [$fichier] ["text"] [] .= "               Champ 3 : Schedule du CI (NORMAL/ETENDU/PERMANENT)";
	
	$class_utilisees = array (
			"fichier",
			"sitescope_fonctions_standards",
			"sitescope_datas",
			"sitescope_soap_configuration",
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
		return abstract_log::onWarning_standard ( "Pas de configuration pour le serveur : " . $liste_option->getOption ( "sitescope_utilise" ) );
	}
	if ($deploie_socle_technique->getSisSoapConfiguration ()
		->connect ( $liste_option->getOption ( "sitescope_utilise" ) ) === false) {
		return abstract_log::onError_standard ( "Pas de connexion au sitescope" );
	}
	
	if ($liste_option->verifie_option_existe ( "fichier_csv" ) === false) {
		return abstract_log::onError_standard ( "Il faut un fichier csv pour travailler." );
	}
	
	if (fichier::tester_fichier_existe ( $liste_option->getOption ( "fichier_csv" ) ) === false) {
		return abstract_log::onError_standard ( "Le fichier csv est introuvable." );
	}
	$liste_entree_csv = fichier::Lit_integralite_fichier_en_tableau ( $liste_option->getOption ( "fichier_csv" ) );
	
	foreach ( $liste_entree_csv as $ligne ) {
		abstract_log::onDebug_standard ( $ligne, 1 );
		//On gere les lignes vide ou commentees
		if (empty ( $ligne ) || strpos ( $ligne, "#" ) === 0) {
			continue;
		}
		
		$ligne_param = explode ( ";", trim ( $ligne ) );
		if ($ligne_param === false || count ( $ligne_param ) != 3) {
			abstract_log::onError_standard ( "Ligne inutilisable : " . $ligne );
			continue;
		}
		abstract_log::onDebug_standard ( $ligne_param, 0 );
		/**
		 * On prepare les donnees minimale pour creer le moniteur
		 * $ligne_param[0]=Nom du CI
		 * $ligne_param[1]=OS du CI (WINDOWS/LINUX/UNIX)
		 * $ligne_param[2]=Schedule du CI
		*/
		$sis_template_datas->setCI ( $ligne_param [0] );
		$sis_template_datas->setOS ( $ligne_param [1] );
		$sis_template_datas->setSchedule ( $ligne_param [2] );
		abstract_log::onDebug_standard ( $sis_template_datas->getCI (), 1 );
		abstract_log::onDebug_standard ( $sis_template_datas->getOS (), 1 );
		abstract_log::onDebug_standard ( $sis_template_datas->getSchedule (), 1 );
		
		//On supprime le dossier Moniteurs_{CI}
		$deploie_socle_technique->supprime_groupe_Moniteurs ();
		//(UX) On supprime le moniteur sshd
		//On supprime le moniteur ping
		$deploie_socle_technique->supprime_moniteur_pingMES ();
		//On supprime le dossier {CI}
		$deploie_socle_technique->supprime_groupe_CI ();
		//On supprime le {CI}
		$deploie_socle_technique->supprime_ci ();
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
