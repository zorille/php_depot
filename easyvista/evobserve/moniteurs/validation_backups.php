#!/usr/bin/php
<?php
/**
 * Verifie un rotate grace au logs PHP.
 * @author dvargas
 * @package Monitoring
 */
$rep_document = dirname ( $argv [0] ) . "/../../../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";
use Zorille\framework as Core;
use Zorille\itop as itop;
use Zorille\o365 as o365;
use Exception as Exception;
require_once $liste_option->getOption ( "rep_scripts" ) . '/../lib/gestion_client.class.php';
require_once $liste_option->getOption ( "rep_scripts" ) . '/../itop/lib/itop_liste_ci.class.php';
require_once $liste_option->getOption ( "rep_scripts" ) . '/../itop/lib/itop_gestion_client.class.php';
require_once $liste_option->getOption ( "rep_scripts" ) . '/../export_mensuel/lib/gestion_export.class.php';
require_once $liste_option->getOption ( "rep_scripts" ) . '/../export_mensuel/lib/gestion_export_backups.class.php';

/**
 * @ignore Affiche le help.<br> Cette fonction fait un exit. Arguments reconnus :<br> --help
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
	$help [$fichier] ["text"] [] .= "Permet de mettre a jour la liste des ci de sitescope";
	$help [$fichier] ["text"] [] .= "\t--message \"message\"\t\t\t\tmessage a afficher";
	$help [$fichier] ["text"] [] .= "\t--o365_serveur_mail serveur o365 pour la messagerie";
	$help [$fichier] ["text"] [] .= "\t--o365_user_message 'Damien Vargas'";
	$class_utilisees = array (
			"Zorille\framework\fichier",
			"Zorille\framework\moniteur",
			"Zorille\framework\contraintesHoraire",
			"Zorille\framework\fonctions_standards_moniteur",
			"Zorille\framework\dates",
			'gestion_export_backups',
			"Zorille\o365\Message"
	);
	$help = array_merge ( $help, Core\fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	Core\fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}
// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();
// Le fichier de log est cree
Core\abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

/**
 * Main programme Code retour en 2xxx en cas d'erreur
 * @ignore
 *
 * @param Core\options $liste_option
 * @param Core\logs $fichier_log
 * @return boolean
 */
function principale(
		&$liste_option,
		&$fichier_log) {
	// Gestion des horaires
	$horaire = array ();
	if ($liste_option->verifie_option_existe ( "heure_debut", true ) === false) {
		$horaire ["debut"] = "00:00:00";
	} else {
		$horaire ["debut"] = $liste_option->getOption ( "heure_debut" );
	}
	if ($liste_option->verifie_option_existe ( "heure_fin", true ) === false) {
		$horaire ["fin"] = "23:59:59";
	} else {
		$horaire ["fin"] = $liste_option->getOption ( "heure_fin" );
	}
	// Gestion de itop
	if ($liste_option->verifie_option_existe ( "itop_serveur", true ) === false) {
		return Core\abstract_log::onError_standard ( "Il faut un itop_serveur pour travailler." );
	}
	$itop_webservice = itop\wsclient_rest::creer_wsclient_rest ( $liste_option, itop\datas::creer_datas ( $liste_option ) );
	$liste_ci = itop_liste_ci::creer_itop_liste_ci ( $liste_option );
	// Fin de gestion de itop
	// gestion_client
	Core\abstract_log::onInfo_standard ( "Create new gestion_client object" );
	$gestion_client = itop_gestion_client::creer_itop_gestion_client ( $liste_option );
	// Fin de la gestion des webservices
	if ($itop_webservice && $liste_ci && $gestion_client) {
		try {
			// Gestion du moniteur
			$moniteur = Core\mail_alert::creer_mail_alert ( $liste_option );
			// On filtre la liste des clients
			Core\abstract_log::onInfo_standard ( "On recupere les organisation dans iTop" );
			$itop_webservice->prepare_connexion ( $liste_option->getOption ( "itop_serveur" ) );
			// On retrouve le client dans iTop
			$itop_webservice->prepare_connexion ( $liste_option->getOption ( "itop_serveur" ) );
            $factory = itop\ItopFactory::new();
			$liste_ci->recupere_Organization ( "name,code,status,euclyde_id,friendlyname", factory: $factory )
				->recupere_CustomerContract ( factory: $factory );
			$gestion_client->service_par_client_euclyde ( $liste_ci->getListeCi () ['Organization'], $liste_ci->getListeCi () ['CustomerContract'] );
			// liste backups
			Core\abstract_log::onInfo_standard ( "On recupere les backups dans Veeam" );
			$gestion_backups = gestion_export_backups::creer_gestion_export_backups ( $liste_option, $gestion_client );
			$html = Core\html::creer_html ( $liste_option );
			$gestion_backups->setPeriodeHoraire ( $horaire )
				->recupere_donnees_veeam ();
			$have_failed = false;
			$message = $html->creer_titre ( "<bold>Liste des jobs en erreur du jour :</bold>" );
			$message .= $html->creer_entete_tableau ( "border=1" );
			$message .= $html->creer_une_ligne_de_tableau ( array (
					'Nom du job',
					'Statut du job',
					'Code Client',
					'Date de d&eacute;but du job'
			) );
			foreach ( $gestion_backups->getListeBackups () as $codeClient => $data ) {
				Core\abstract_log::onInfo_standard ( "On recupere les donnees de backup de " . $codeClient );
				$message .= $gestion_backups->retrouve_failed ( $codeClient, $html, $have_failed );
			}
			$message .= $html->creer_end_tableau () . "</br>";
			if ($have_failed) {
				$moniteur->ecrit ( $message );
				$moniteur->red ();
				$moniteur->send ();
			}
		} catch ( Exception $e ) {
			return Core\abstract_log::onError_standard ( $e->getMessage (), "", $e->getCode () );
		}
	} else {
		return Core\abstract_log::onError_standard ( "Erreur dans les variables necessaires" );
	}
	return true;
}
principale ( $liste_option, $fichier_log );
Core\abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );
exit ( $fichier_log->renvoiExit () );
?>
