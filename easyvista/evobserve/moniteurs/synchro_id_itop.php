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
use Zorille\framework\fonctions_standards_sgbd;

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
            $help [$fichier] ["text"] [] .= "\t--o365_serveur_mail serveur o365 pour la messagerie";
            $help [$fichier] ["text"] [] .= "\t--o365_user_message 'Damien Vargas'";
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
        try {
            $connexion = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
            $bd_itop = fonctions_standards_sgbd::recupere_db_itop ( $connexion );
            if ($bd_itop) {
                $resultat = $bd_itop->select_table_params ( 'ticket' );
                foreach($resultat as $row){
                    $AUTO_INCREMENT=$row["AUTO_INCREMENT"];
                    abstract_log::onDebug_standard("AUTO_INCREMENT=".$AUTO_INCREMENT,1);
                }
                //select * from key_value_store
                $where = array ();
                $bd_itop->fabrique_where ( $where, 'key_value_store', "key_name", 'Ticket' );
                $resultat_2 = $bd_itop->selectionner('value', 'key_value_store', $where);
                foreach($resultat_2 as $row){
                    $key_value_store=$row["value"];
                    abstract_log::onDebug_standard("key_value_store=".$key_value_store,1);
                }
                //regle key_value_store+1=AUTO_INCREMENT
                if(($key_value_store+1)!=$AUTO_INCREMENT){
                    abstract_log::onInfo_standard("La regle n'est pas respectee, on met a jour l'auto_increment");
                    $bd_itop->creer_alter('ticket', 'AUTO_INCREMENT='.($key_value_store+1),'');
                    $bd_itop->faire_requete();
                    //alter table ticket AUTO_INCREMENT = $key_value_store+1;
                } else {
                    abstract_log::onInfo_standard("La regle est respectee, on ne fait rien");
                }
            }
        } catch ( Exception $e ) {
            return Core\abstract_log::onError_standard ( $e->getMessage (), "", $e->getCode () );
        }
        return true;
    }
)();

Core\abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
