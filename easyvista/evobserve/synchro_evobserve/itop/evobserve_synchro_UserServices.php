#!/usr/bin/php
<?php
/**
 * @author dvargas
 * @package iTop
 * @subpackage extract
 */
$rep_document = dirname ( $argv [0] ) . "/../../../../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

use Zorille\framework as Core;
use Zorille\itop;
use Zorille\coservit;
use Zorille\framework\abstract_log;
use Zorille\itop\data_models\StatusEnum;
use Zorille\itop\query_fetchers\ApplicationSolutionFetcher as ApplicationSolutionQL;
use Zorille\framework\QueryBuilderOperator as QLOperator;

/** @var Core\options $liste_option */
require_once $liste_option->getOption ( "rep_scripts" ) . '/lib/includes.php';

/**
 * @method string getItopServeurOption()
 * @method string getCoservitServeurOption()
 */
class SynchroApplicationSolution extends Core\MainScript
{
    #[core\Flag]
    public string $itop_serveur = '';

    #[core\Flag]
    public string $coservit_serveur = '';

    static public function help(): int
    {
        $file = basename ( __FILE__ );
        $help = array (
            "usage" => array (
                "{$file} --conf [fichiers de conf] [OPTIONS]",
                "{$file} --help"
            ),
            $file => array (
                "text" => [
                    "--itop_serveur",
                    "--coservit_serveur"
                ]
            )
        );
        $class_utilisees = array (
            Zorille\framework\dates::class,
            coservit_hosts::class,
            coservit_services::class
        );
        $help = array_merge ( $help, Core\fonctions_standards::help_fonctions_standard ( false, false, $class_utilisees ) );
        Core\fonctions_standards::affichage_standard_help ( $help );
        return 0;
    }

    /**
     * @param core\options $liste_option
     * @param itop_gestion_client $gestion_client
     * @param coservit\wsclient $coservit_webservice
     * @param manage_modeles_coservit $liste_modele_coservit
     * @return void
     * @throws Exception
     */
    private function AppServices(
        Core\options            &$liste_option,
        itop_gestion_client     &$gestion_client,
        coservit\wsclient       &$coservit_webservice,
        manage_modeles_coservit $liste_modele_coservit): void
    {
        abstract_log::onInfo_standard ( "On gere les Application Solutions de production" );
        $factory = itop\ItopFactory::new();
        /** @var itop\data_models\CMDBChangeOp[] $lastUpdatedCIs */
        $lastUpdatedCIs = $factory->createCMDBChangeOpQueryBuilder()
            ->select()
            ->join(ApplicationSolutionQL::class, 'objkey = ' . ApplicationSolutionQL::class . '.id')
            ->where('date', QLOperator::SUP, 'DATE_SUB(NOW(), INTERVAL 7 DAY)')
            ->and()
            ->where(ApplicationSolutionQL::class.'::status', QLOperator::EQUALS, StatusEnum::ACTIVE)
            ->build()->toModel()['objects'];

        $liste_ci_itop = $factory->createApplicationSolutionQueryBuilder()
            ->select()
            ->where("id", QLOperator::IN, array_map(fn($item) => $item->getObjkey(), $lastUpdatedCIs))
            ->build()->getResult()['objects'];

        abstract_log::onDebug_standard ( $liste_ci_itop, 2 );
        // On creer la liste de hosts
        // On filtre les ci pour le moment avec un nom inutilisable
        $standard_coservit_hosts = coservit_userServices::creer_coservit_userServices ( $liste_option,
            $gestion_client, $coservit_webservice );
        $standard_coservit_hosts->setObjModelesCoservit ( $liste_modele_coservit )
            ->setItopFormat ( "ApplicationSolution" )
            ->gestion_userServices ( $liste_ci_itop )
            ->gestion_dependances_userServices ();
        // ->updateCollectors ()
        // ->recupere_liste_hosts ();
    }

    /**
     * @throws Exception
     */
    protected function main(): bool
    {
        $liste_option = $this->getListOptions();

        // Gestion de coservit
        $coservit_webservice = coservit\wsclient::creer_wsclient($liste_option, coservit\datas::creer_datas($liste_option));
        // Fin de gestion de coservit
        // gestion_client
        $this->onInfo("Create new gestion_client object");
        $gestion_client = itop_gestion_client::creer_itop_gestion_client($liste_option);
        try {
            // On filtre la liste des clients
            $this->onInfo("On recupere les organisation dans iTop");
            // On prepare les webservices
            $coservit_webservice->prepare_connexion($this->getCoservitServeurOption());
            // on recupere les modele une seule fois
            $liste_modele_coservit = manage_modeles_coservit::creer_manage_modeles_coservit(
                $liste_option, $coservit_webservice, $gestion_client);
            // on traite les CI dans iTop
            $this->AppServices($liste_option, $gestion_client,
                $coservit_webservice, $liste_modele_coservit);
            return true;
        } catch (Exception $e) {
            return $this->onError($e->getMessage(), "", $e->getCode());
        }
    }
}

SynchroApplicationSolution::batch($argv);
