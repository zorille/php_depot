#!/usr/bin/php
<?php
/**
 * Verifie un rotate grace au logs PHP.
 *
 * @author dvargas
 * @package Monitoring
 */
$rep_document = dirname($argv[0]) . "/../../../..";

/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

use Zorille\framework as Core;
use Zorille\framework\abstract_log;
use Zorille\enedis as enedis;

/**
 *
 * @var Core\options $liste_option
 */
$liste_option = $liste_option ?? null;
/**
 *
 * @var Core\logs $fichier_log
 */
$fichier_log = $fichier_log ?? null;

// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe("help"))
    (/**
     *
     * @ignore Affiche le help.<br> Cette fonction fait un exit. Arguments reconnus :<br> --help
     */
    function () {
        $fichier = basename(__FILE__);
        $help = array(
            "usage" => array(
                $fichier . " --conf [fichiers de conf] [OPTIONS]",
                $fichier . " --help"
            ),
            $fichier => array()
        );
        $help[$fichier]["text"] = array();
        $help[$fichier]["text"][] .= "Permet de mettre a jour la liste des ci de sitescope";
        $help[$fichier]["text"][] .= "\t--processus process\t\t\t\tNom du processus a retrouver en memoire";
        $help[$fichier]["text"][] .= "\t--logfile /var/log/{DATE}_fichier.log\tNom du fichier de log a parser";
        $help[$fichier]["text"][] .= "\t--logdir   /var/log \t\t\tDossier contenant une liste de fichiers de log a parser (linux)";
        $help[$fichier]["text"][] .= "\t--message \"message\"\t\t\t\tmessage a afficher";
        $help[$fichier]["text"][] .= "\t--type_os linux/win\t\t\t\tType d'os";
        $help[$fichier]["text"][] .= "\t--valide_date_changement_max \t\t\t\tTemps en minutes au dela duquel le dernier update du fichier est trop vieux";

        $class_utilisees = array(
            "Zorille\framework\fichier",
            "Zorille\framework\moniteur",
            "Zorille\framework\contraintesHoraire",
            "Zorille\framework\fonctions_standards_moniteur",
            "Zorille\framework\dates",
            "Zorille\o365\Message"
        );
        $help = array_merge($help, Core\fonctions_standards::help_fonctions_standard(false, true, $class_utilisees));
        Core\fonctions_standards::affichage_standard_help($help);
        echo "[Exit]0";
        exit(0);
    })();

// Le fichier de log est cree
Core\abstract_log::onInfo_standard("Heure de depart : " . date("d/m/Y H:i:s", time()));

(/**
 * Main programme Code retour en 2xxx en cas d'erreur
 *
 * @ignore
 *
 * @return boolean
 */
function () use (&$liste_option, &$fichier_log) {

    if ($liste_option->verifie_option_existe("enedis_serveur") === false) {
        return abstract_log::onError_standard("Il faut un parametre --enedis_serveur pour travailler.");
    }
    if ($liste_option->verifie_option_existe("enedis_pdm") === false) {
        return abstract_log::onError_standard("Il faut un parametre --enedis_pdm pour travailler.");
    }
    if ($liste_option->verifie_option_existe("date") === false) {
        return abstract_log::onError_standard("Il faut un parametre --date pour travailler.");
    }
    $enedis_ws = enedis\wsclient::creer_wsclient($liste_option, enedis\datas::creer_datas($liste_option));
    try {
        // Gestion du moniteur
        $moniteur = Core\nagios_client::creer_nagios_client($liste_option);

        $enedis_ws->prepare_connexion($liste_option->getOption("enedis_serveur"));
        $resultat = $enedis_ws->daily_comsumption(array(
            "usage_point_id" => $liste_option->getOption("enedis_pdm"),
            "start" => date("Y-m-d", strtotime($liste_option->getOption("date"))),
            "end" => date("Y-m-d")
        ));
        $consumption = 0;
        // On transforme en heure pleine
        abstract_log::onDebug_standard(date("Y-m-d", strtotime($liste_option->getOption("date"))) . " 00:00:00", 1);
        $search_ts = strtotime(date("Y-m-d", strtotime($liste_option->getOption("date"))) . " 00:00:00");
        foreach ($resultat->meter_reading->interval_reading as $compteur) {
            abstract_log::onDebug_standard($compteur, 2);
            $data_ts = strtotime($compteur->date . " 00:00:00");
            abstract_log::onDebug_standard($search_ts . "==" . $data_ts, 2);
            if ($search_ts == $data_ts) {
                abstract_log::onDebug_standard("TS validated", 1);
                $consumption += $compteur->value;
            }
        }
        // $consumption is in W for every 5 Min
        $consumption = round($consumption / 1000,2);

        /**
         * ************ On Gere la recuperation d'information ********
         */
        // Si une alarme est activée, et que
        // soit ce n'est pas encore l'heure de l'alarme
        // soit on a passe l'heure de fin d'alarme
        if ($moniteur->renvoi_couleur() != "green" && ($horaire->valideHeureDebutAlarmeGlobal() === false || $horaire->valideHeureFinAlarmeGlobal())) {
            // On met l'alarme en yellow
            $moniteur->yellow();
        } elseif ($moniteur->renvoi_couleur() == "green") {
            $moniteur->ecrit("Pas d'erreur detect&eacute;.<br/>");
        }
        $moniteur->ecrit(" kWh=" . $consumption . "|kWh=" . $consumption, "green");
        $moniteur->affiche_status();
    } catch (Exception $e) {
        return Core\abstract_log::onError_standard($e->getMessage(), "", $e->getCode());
    }
    return true;
})();

Core\abstract_log::onInfo_standard("Heure de fin : " . date("d/m/Y H:i:s", time()));

exit($fichier_log->renvoiExit());
