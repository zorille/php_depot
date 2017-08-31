#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage sitescope
 */
$INCLUDE_SITESCOPE = true;

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
	$help [$fichier] ["text"] [] .= "Permet d'ajouter des moniteurs du socle technique des machines sur un sitescope";
	$help [$fichier] ["text"] [] .= "\t--sitescope_utilise Nom du sitescope a utiliser";
	$help [$fichier] ["text"] [] .= "\t--fichier_csv Chemin complet du fichier CSV a utiliser";
	$help [$fichier] ["text"] [] .= "               Contenu du fichier CSV :";
	$help [$fichier] ["text"] [] .= "               Champ 1 : Nom du CI dans sitescope";
	$help [$fichier] ["text"] [] .= "               Champ 2 : IP du CI dans sitescope";
	$help [$fichier] ["text"] [] .= "               Champ 3 : OS du CI (WINDOWS/LINUX/UNIX)";
	$help [$fichier] ["text"] [] .= "               Champ 4 : Schedule du CI (NORMAL/ETENDU/PERMANENT)";
	$help [$fichier] ["text"] [] .= "               Champ 5 : Type de moniteur : IP/Disk/DNS/Service/Script";
	$help [$fichier] ["text"] [] .= "               Champ 6 a X : Parametres du type de moniteur :";
	$help [$fichier] ["text"] [] .= "                 Champ 6 : IP pour le type IP";
	$help [$fichier] ["text"] [] .= "                 Champ 6 à X : liste des disques/point de montage pour le type Disk";
	$help [$fichier] ["text"] [] .= "                 Champ 6 : IP du serveur DNS pour le type DNS";
	$help [$fichier] ["text"] [] .= "                 Champ 7 : FQDN a resoudre pour le type DNS";
	$help [$fichier] ["text"] [] .= "                 Champ 6 à X : liste des services/process pour le type Service";
	$help [$fichier] ["text"] [] .= "                 Champ 6 à X : liste des scripts pour le type Script";
	
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
		return abstract_log::onError_standard ( "Pas de configuration pour le serveur : " . $liste_option->getOption ( "sitescope_utilise" ) );
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
	
	//$fichier_csv = fichier::creer_fichier ( $liste_option, $liste_option->getOption ( "fichier_csv" ) );
	//$fichier_csv->ouvrir ( "r" );
	

	//foreach ( $fichier_csv->lit_une_ligne () as $ligne ) {
	foreach ( $liste_entree_csv as $ligne ) {
		abstract_log::onDebug_standard ( $ligne, 1 );
		//On gere les lignes vide ou commentees
		if (empty ( $ligne ) || strpos ( $ligne, "#" ) === 0) {
			continue;
		}
		
		$ligne_param = explode ( ";", trim ( $ligne ) );
		if ($ligne_param === false || count ( $ligne_param ) < 5) {
			abstract_log::onError_standard ( "Ligne inutilisable : " . $ligne );
			continue;
		}
		abstract_log::onDebug_standard ( $ligne_param, 0 );
		/**
		 * On prepare les donnees minimale pour creer le moniteur
		 * $ligne_param[0]=Nom du CI
		 * $ligne_param[1]=IP du CI
		 * $ligne_param[2]=OS du CI (WINDOWS/LINUX/UNIX)
		 * $ligne_param[3]=Schedule du CI
		 * $ligne_param[4]=Type de moniteur : IP/Disk/DNS/Service/Script
		 */
		$sis_template_datas->setCI ( $ligne_param [0] );
		$sis_template_datas->setIPs ( array (
				$ligne_param [1] 
		) );
		$sis_template_datas->setOS ( $ligne_param [2] );
		$sis_template_datas->setSchedule ( $ligne_param [3] );
		abstract_log::onDebug_standard ( $sis_template_datas->getCI (), 1 );
		abstract_log::onDebug_standard ( $sis_template_datas->getOS (), 1 );
		abstract_log::onDebug_standard ( $sis_template_datas->getIPs (), 1 );
		abstract_log::onDebug_standard ( $sis_template_datas->getSchedule (), 1 );
		$deploie_socle_technique->prepare_donnees_remote_server ();
		
		//Puis on gere le nouveau moniteur
		switch ($ligne_param [4]) {
			case "IP" :
				/**
				 * $ligne_param[5]=IP supplementaire
				 */
				if ($sis_template_datas->AjouteIP ( $ligne_param [5] ) !== false) {
					abstract_log::onDebug_standard ( $sis_template_datas->getIPs (), 1 );
					//On creer les IP supplementaires
					$deploie_socle_technique->creer_moniteur_ping ();
				}
				break;
			case "Disk" :
				/**
				 * $ligne_param[5...x]=Disk(point de montage linus/unix) supplementaire
				 */
				for($i = 5; $i < count ( $ligne_param ); $i ++) {
					$sis_template_datas->AjouteDisk ( $ligne_param [$i] );
				}
				abstract_log::onDebug_standard ( $sis_template_datas->getDisks (), 1 );
				//Les disques/FS
				$deploie_socle_technique->creer_moniteur_disk ();
				break;
			case "DNS" :
				/**
				 * $ligne_param[5]=DNS IP de la DNS a utiliser
				 * $ligne_param[6]=FQDN a verifier
				 */
				$sis_template_datas->setDNS ( $ligne_param [5] );
				$sis_template_datas->setFQDN ( $ligne_param [6] );
				abstract_log::onDebug_standard ( $sis_template_datas->getDNS (), 1 );
				abstract_log::onDebug_standard ( $sis_template_datas->getFQDN (), 1 );
				//Les verification DNS
				$deploie_socle_technique->creer_moniteur_DNS ();
				break;
			case "Service" :
				/**
				 * $ligne_param[5....x]=Service (Process sous Linux/Unix) a ajouter
				 */
				for($i = 5; $i < count ( $ligne_param ); $i ++) {
					$sis_template_datas->AjouteService ( $ligne_param [$i] );
				}
				abstract_log::onDebug_standard ( $sis_template_datas->getServices (), 1 );
				//Les services/process
				$deploie_socle_technique->creer_moniteur_process ();
				break;
			case "Script" :
				/**
				 * $ligne_param[5....x]=Scrip uniquement sous Linux/Unix a ajouter
				 */
				for($i = 5; $i < count ( $ligne_param ); $i ++) {
					$sis_template_datas->setScript ( $ligne_param [$i] );
				}
				abstract_log::onDebug_standard ( $sis_template_datas->getScripts (), 1 );
				//Les scripts
				$deploie_socle_technique->creer_moniteur_scripts ();
				break;
		}
		
		//On nettoie pour la prochaine boucle
		$sis_template_datas->reset_datas ();
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
