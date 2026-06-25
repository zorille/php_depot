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
use Zorille\framework\abstract_log;
use Zorille\itop as itop;
require_once $liste_option->getOption ( "rep_scripts" ) . '/../itop/lib/itop_liste_ci.class.php';

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
	$help [$fichier] ["text"] [] .= "Permet de valider qu'un CI dans iTop a du monitoring";
	$help [$fichier] ["text"] [] .= "--itop_serveur";
	$help [$fichier] ["text"] [] .= "--class_objet_itop process\t\t\t\tType d'objet dans iTop";
	$class_utilisees = array (
			"Zorille\framework\nagios_client",
			"Zorille\framework\contraintesHoraire",
			"Zorille\framework\fonctions_standards_moniteur",
			"Zorille\framework\dates"
	);
	$help = array_merge ( $help, Core\fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	Core\fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0";
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
	// Gestion de itop
	if ($liste_option->verifie_option_existe ( "itop_serveur", true ) === false) {
		return Core\abstract_log::onError_standard ( "Il faut un itop_serveur pour travailler." );
	}
	if ($liste_option->verifie_option_existe ( "class_objet_itop", true ) === false) {
		return Core\abstract_log::onError_standard ( "Il faut un --class_objet_itop pour travailler." );
	}
	$itop_webservice = itop\wsclient_rest::creer_wsclient_rest ( $liste_option, itop\datas::creer_datas ( $liste_option ) );
	$liste_ci = itop_liste_ci::creer_itop_liste_ci ( $liste_option );
	// Fin de gestion de itop
	if ($itop_webservice && $liste_ci) {
		try {
			// Gestion du moniteur
			$moniteur = Core\nagios_client::creer_nagios_client ( $liste_option );
			// Gestion des horaires
			$liste_dates = Core\dates::creer_dates ( $liste_option );
			$date = $liste_dates->recupere_date ( 0, "day" );
			$horaire = Core\contraintesHoraire::creer_contraintesHoraire ( $liste_option, $date );
			// On prepare les webservices
			$itop_webservice->prepare_connexion ( $liste_option->getOption ( "itop_serveur" ) );
			// On collecte iTop
			// "id NOT IN (SELECT PDU JOIN BaseMonitoring AS BM ON BM.functionalci_id=PDU.id) AND status='production' AND needmonitoring='yes'"
			$class = $liste_option->getOption ( "class_objet_itop" );
			$liste_ci_itop = $liste_ci->applique_oql ( $itop_webservice, $class, "finalclass='".$class."' AND id NOT IN (SELECT " . $class . " JOIN BaseMonitoring AS BM ON BM.functionalci_id=" . $class . ".id) AND status='production' AND needmonitoring='yes'", "id,name,org_id,friendlyname" );
			abstract_log::onDebug_standard ( $liste_ci_itop, 2 );
			// Si pas de CI de type rechercher, on envoi une erreur
			// if ($liste_ci->valide_object_existe ( $liste_ci_itop, $class ) !== true) {
			// abstract_log::onError_standard("Pas de CI de type ".$class,"",1);
			// }
			if (isset ( $liste_ci_itop ['objects'] )) {
				foreach ( $liste_ci_itop ['objects'] as $ci ) {
					$moniteur->ecrit ( "Ce CI de type " . $class . " n'a pas de monitoring : " . $ci ['fields'] ['friendlyname'], "red" );
					$moniteur->red ();
				}
			}
			// Si une alarme est activée, et que
			// soit ce n'est pas encore l'heure de l'alarme
			// soit on a passe l'heure de fin d'alarme
			if ($moniteur->renvoi_couleur () != "green" && ($horaire->valideHeureDebutAlarmeGlobal () === false || $horaire->valideHeureFinAlarmeGlobal ())) {
				// On met l'alarme en yellow
				$moniteur->yellow ();
			} elseif ($moniteur->renvoi_couleur () == "green") {
				$moniteur->ecrit ( "Pas d'erreur detect&eacute;.<br/>" );
			}
			$moniteur->affiche_status ();
		} catch ( Exception $e ) {
			return Core\abstract_log::onError_standard ( $e->getMessage (), "", $e->getCode () );
		}
	}
	return true;
}
principale ( $liste_option, $fichier_log );
Core\abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );
exit ( $fichier_log->renvoiExit () );
?>
