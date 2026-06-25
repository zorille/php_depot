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
                $fichier => array (
                    "text" => [
                        "Permet de mettre a jour la liste des ci de sitescope",
                        "\t--processus process\t\t\t\tNom du processus a retrouver en memoire",
                        "\t--logfile /var/log/{DATE}_fichier.log\tNom du fichier de log a parser",
                        "\t--logdir   /var/log \t\t\tDossier contenant une liste de fichiers de log a parser (linux)",
                        "\t--message \"message\"\t\t\t\tmessage a afficher",
                        "\t--type_os linux/win\t\t\t\tType d'os",
                        "\t--o365_serveur_mail serveur o365 pour la messagerie",
                        "\t--o365_user_message 'Damien Vargas'",
                    ]
                )
            );

            $class_utilisees = array (
                Zorille\framework\fichier::class,
                Zorille\framework\moniteur::class,
                Zorille\framework\contraintesHoraire::class,
                Zorille\framework\fonctions_standards_moniteur::class,
                Zorille\framework\dates::class,
                Zorille\o365\Message::class
            );

            $help = array_merge ( $help, Core\fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
            Core\fonctions_standards::affichage_standard_help ( $help );

            echo "[Exit]0\n";
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
        if ($liste_option->verifie_option_existe ( "moniteur_titre" ) === false)
            $liste_option->setOption ( "moniteur_titre", "Check du fichier " . $liste_option->getOption ( "logfile" ) );
        if ($liste_option->verifie_option_existe ( "message" ) === false)
            $liste_option->setOption ( "message", "Le process" );

        $liste_dates = Core\dates::creer_dates ( $liste_option );

        $date = $liste_dates->recupere_date ( 0, "day" );

        if ($liste_option->verifie_option_existe ( "type_os", true ) === false) {
            $liste_option->setOption ( "type_os", "linux" );
        }

        try {
            // Gestion du moniteur
            $moniteur = Core\mail_alert::creer_mail_alert ( $liste_option );
            // Gestion des horaires
            $horaire = Core\contraintesHoraire::creer_contraintesHoraire ( $liste_option, $date );
            // fonction standard monitoring
            $fs_monitoring = Core\fonctions_standards_moniteur::creer_fonctions_standards_moniteur ( $liste_option, $moniteur, $horaire );
            /**
             * ************ d'abord on test la presence d'un processus en memoire ********
             */
            $liste_ps = $fs_monitoring->check_processus ( $liste_option->getOption ( "processus" ), $liste_option->getOption ( "type_os" ) );
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
            if (count ( $liste_ps ) == 1 && (is_array ( $liste_fichiers ))) {
                if (count ( $liste_fichiers ) === 1) {
                    // Si le process n'est pas en cours et il n'y a pas de code Exit
                    // On grep les Warning et Error dans le fichier
                    abstract_log::onInfo_standard ( "On traite le fichier log : " . $liste_fichiers [0] );

                    if ($liste_option->verifie_option_existe ( "log_with_mail" ) === false) {
                        abstract_log::onDebug_standard ( "Parse without Emails", 1 );
                        $flag_exit = $fs_monitoring->parse_fichier_log ( $liste_fichiers [0], "Code Exit : 0<br/>\n", $liste_option->getOption ( "message" ) . " s'est termin&eacute; en erreur Exit=", false, 4096, "\n" );
                    } else {
                        abstract_log::onDebug_standard ( "Parse with Emails", 1 );
                        $flag_exit = $fs_monitoring->parse_fichier_log_with_mail ( $liste_fichiers [0], "Code Exit : 0<br/>\n", $liste_option->getOption ( "message" ) . " s'est termin&eacute; en erreur Exit=", false, 4096, "\n" );
                    }

                    if (! $flag_exit) {
                        $moniteur->ecrit ( "Il n'y a pas de Exit et plus de process en cours sur le serveur<br/>\n", "red" );
                        $moniteur->red ();
                    }
                } elseif (count ( $liste_fichiers ) === 0 && $horaire->valideHeureDebutGlobal ()) {
                    // si il n'y a pas de process et pas de log du jour dans un dossier
                    $moniteur->ecrit ( "Pas de log apr&eacute;s l'heure de d&eacute;but : " . $horaire->getHoraireDebutMax () . " dans le dossier " . $dossier_log . ".<br/>\n", "red" );
                    $moniteur->red ();
                }
            } elseif (count ( $liste_ps ) >= 1 && $horaire->valideHeureFinGlobal ()) {
                // Si il a toujour un process apres l'heure de fin de traitement
                $moniteur->ecrit ( "Il reste au moins un processus en m&eacute;moire apr&eacute; " . $horaire->getHoraireFinMax () . ".<br/>\n", "red" );
                $moniteur->red ();
            }

            // Si une alarme est activée, et que
            // soit ce n'est pas encore l'heure de l'alarme
            // soit on a passe l'heure de fin d'alarme
            if ($moniteur->renvoi_couleur () != "green" && ($horaire->valideHeureDebutAlarmeGlobal () === false || $horaire->valideHeureFinAlarmeGlobal ())) {
                // On met l'alarme en yellow
                $moniteur->yellow ();
            } elseif ($moniteur->renvoi_couleur () == "green") {
                $moniteur->ecrit ( "\nPas d'erreur detect&eacute;.<br/>\n" );
            }

            $moniteur->send ();

            return true;
        } catch ( Exception $e ) {
            return Core\abstract_log::onError_standard ( $e->getMessage (), "", $e->getCode () );
        }
    }
)();

Core\abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
