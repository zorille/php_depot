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

/**
 * @var Core\options $liste_option
 */
$liste_option = $liste_option ?? null;
/**
 * @var Core\logs $fichier_log
 */
$fichier_log = $fichier_log ?? null;

// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
    (
        /**
         * @ignore Affiche le help.<br> Cette fonction fait un exit. Arguments reconnus :<br> --help
         */
        function () {
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
            $help [$fichier] ["text"] [] .= "\t--processus process\t\t\t\tNom du processus a retrouver en memoire";
            $help [$fichier] ["text"] [] .= "\t--logfile /var/log/{DATE}_fichier.log\tNom du fichier de log a parser";
            $help [$fichier] ["text"] [] .= "\t--logdir   /var/log \t\t\tDossier contenant une liste de fichiers de log a parser (linux)";
            $help [$fichier] ["text"] [] .= "\t--message \"message\"\t\t\t\tmessage a afficher";
            $help [$fichier] ["text"] [] .= "\t--type_os linux/win\t\t\t\tType d'os";
            $help [$fichier] ["text"] [] .= "\t--valide_date_changement_max \t\t\t\tTemps en minutes au dela duquel le dernier update du fichier est trop vieux";

            $class_utilisees = array (
                "Zorille\framework\fichier",
                "Zorille\framework\moniteur",
                "Zorille\framework\contraintesHoraire",
                "Zorille\framework\fonctions_standards_moniteur",
                "Zorille\framework\dates",
                "Zorille\o365\Message"
            );
            $help = array_merge ( $help, Core\fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
            Core\fonctions_standards::affichage_standard_help ( $help );
            echo "[Exit]0";
            exit ( 0 );
        }
    )();

// Le fichier de log est cree
Core\abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

(
    /**
     * Main programme Code retour en 2xxx en cas d'erreur
     * @ignore
     *
     * @return boolean
     */
    function () use(&$liste_option, &$fichier_log) {
        if ($liste_option->verifie_option_existe ( "processus" ) === false)
            $liste_option->setOption ( "processus", "no_process" );
        if ($liste_option->verifie_option_existe ( "logfile" ) === false)
            $liste_option->setOption ( "logfile", "tempo.log" );
        if ($liste_option->verifie_option_existe ( "message" ) === false)
            $liste_option->setOption ( "message", "Le process" );
        $liste_dates = Core\dates::creer_dates ( $liste_option );
        $date = $liste_dates->recupere_date ( 0, "day" );
        if ($liste_option->verifie_option_existe ( "type_os", true ) === false) {
            $liste_option->setOption ( "type_os", "linux" );
        }
        try {
            // Gestion du moniteur
            $moniteur = Core\nagios_client::creer_nagios_client ( $liste_option );
            // Gestion des horaires
            $horaire = Core\contraintesHoraire::creer_contraintesHoraire ( $liste_option, $date );
            // fonction standard monitoring
            $fs_monitoring = Core\fonctions_standards_moniteur::creer_fonctions_standards_moniteur ( $liste_option, $moniteur, $horaire );
            /**
             * ************ d'abord on test la presence d'un processus en memoire ********
             */
            $liste_ps = $fs_monitoring->check_processus ( $liste_option->getOption ( "processus" ), $liste_option->getOption ( "type_os" ), "parser_log_nagios.php" );
            /**
             * ************ On Gere la liste des fichiers********
             */
            $nom_fichier = $liste_option->getOption ( "logfile" );
            $liste_fichiers = array ();
            // Si le fichier est date, on a une gestion de logrotate
            if (strpos ( $nom_fichier, "{DATE}_" )) {
                // on trouve le log du rotate le plus recent
                $nom_fichier = str_replace ( "{DATE}", $date, $nom_fichier );
            }
            // On recupere le dernier fichier dans le dossier
            $dossier_log = $liste_option->getOption ( "logdir" );
            $CMD = "ls -tr " . $dossier_log . " | grep " . $nom_fichier . " | grep -v err";
            Core\abstract_log::onDebug_standard ( "Recherche en cours : " . $CMD, 2 );
            $recup_des_fichiers = Core\fonctions_standards::applique_commande_systeme ( $CMD, "non" );
            if ($recup_des_fichiers) {
                $liste_fichiers = array (
                    $dossier_log . "/" . $recup_des_fichiers [count ( $recup_des_fichiers ) - 1]
                );
            }
            Core\abstract_log::onDebug_standard ( "Liste des fichiers trouves : ", 2 );
            Core\abstract_log::onDebug_standard ( $liste_fichiers, 2 );
            /**
             * ************ On Gere la recuperation d'information ********
             */
            // Si les process sont termines (car il faut eviter les acces concurrents sur le fichier de log) et qu'il y a des fichiers dans la liste
            if (count ( $liste_ps ) == 1 && (is_array ( $liste_fichiers ) && count ( $liste_fichiers ) === 1)) {
                //On valide la date du fichier
                // /usr/bin/stat /var/log/euclyde_bi/91000_synchro_monitoring_AppsMonitoring.log |grep Modif. |sed 's/Modif. : //'
                $CMD = "/usr/bin/stat -c '%Y' " . $liste_fichiers [0] ;
                Core\abstract_log::onDebug_standard ( "Fichier en cours : " .$liste_fichiers [0], 1 );
                $date_fichier_en_cours = Core\fonctions_standards::applique_commande_systeme ( $CMD, "non" );
                if ($liste_option->verifie_option_existe ( "valide_date_changement_max" ) !== false && isset($date_fichier_en_cours[1])){
                    abstract_log::onDebug_standard ( $date_fichier_en_cours, 2 );
                    $now=time();
                    $diff=$now-$date_fichier_en_cours[1];
                    if(($diff)/60>$liste_option->getOption( "valide_date_changement_max" )){
                        $moniteur->ecrit ( "Dernier accés au log trop ancien<br/>", "red" );
                        $moniteur->red ();
                    }
                }
                // Si le process n'est pas en cours et il n'y a pas de code Exit
                // On grep les Warning et Error dans le fichier
                abstract_log::onInfo_standard ( "On traite le fichier log : " . $liste_fichiers [0] );
                if ($liste_option->verifie_option_existe ( "log_with_mail" ) === false) {
                    abstract_log::onDebug_standard ( "Parse without Emails", 1 );
                    $flag_exit = $fs_monitoring->parse_fichier_log ( $liste_fichiers [0], "Code Exit : 0<br/>", $liste_option->getOption ( "message" ) . " s'est termin&eacute; en erreur Exit=", false, 4096, "\n" );
                } else {
                    abstract_log::onDebug_standard ( "Parse with Emails", 1 );
                    $flag_exit = $fs_monitoring->parse_fichier_log_with_mail ( $liste_fichiers [0], "Code Exit : 0<br/>", $liste_option->getOption ( "message" ) . " s'est termin&eacute; en erreur Exit=", false, 4096, "\n" );
                }
                if (! $flag_exit) {
                    $moniteur->ecrit ( "Il n'y a pas de Exit et plus de process en cours sur le serveur", "red" );
                    $moniteur->red ();
                }
            } elseif (count ( $liste_ps ) == 1 && (is_array ( $liste_fichiers ) && count ( $liste_fichiers ) === 0)) {
                // si il n'y a pas de process et pas de log du jour dans un dossier
                if ($horaire->valideHeureDebutGlobal ()) {
                    $moniteur->ecrit ( "Pas de log apr&eacute;s l'heure de d&eacute;but : " . $horaire->getHoraireDebutMax () . " dans le dossier " . $dossier_log . ".<br/>", "red" );
                    $moniteur->red ();
                }
            } elseif (count ( $liste_ps ) >= 1 && $horaire->valideHeureFinGlobal ()) {
                // Si il a toujour un process apres l'heure de fin de traitement
                $moniteur->ecrit ( "Il reste au moins un processus en m&eacute;moire apr&eacute; " . $horaire->getHoraireFinMax () . ".<br/>", "red" );
                $moniteur->red ();
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
        return true;
    }
)();

Core\abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
