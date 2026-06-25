#!/usr/bin/php
<?php
/**
 * @author dvargas
 * @package iTop
 * @subpackage extract
 */
$rep_document = dirname($argv [0]). "/../../../../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

use Zorille\framework as core;
use Zorille\framework\QueryBuilderOperator as QLOperator;
use Zorille\itop\data_models\StatusEnum;
use Zorille\itop\query_fetchers\FunctionalCiFetcher as FunctionalCiQL;
use Zorille\itop\query_fetchers\FacilitiesMonitoringFetcher as FacilitiesMonitoringQL;
use Zorille\itop;
use Zorille\itop\ItopFactory;
use Zorille\evobserve;
use Zorille\framework\abstract_log;
use Zorille\framework\options;

/** @var options $liste_option */
require_once $liste_option->getOption("rep_scripts"). '/lib/includes.php';

/**
 * @method string getItopServeurOption()
 * @method string getEvobserveServeurOption()
 * @method string getFacmonitoringFiltreOption()
 * @method string getFacmonitoringFiltreRegexOption()
 */
class EvobserveSynchroFacilitiesMonitoring extends core\MainScript
{
    private itop\ItopFactory $factory;

    #[core\Flag]
    public string $itop_serveur = '';

    #[core\Flag]
    public string $evobserve_serveur = '';

    #[core\Flag]
    public ?string $facmonitoring_filtre = '';

    #[core\Flag]
    public ?string $facmonitoring_filtre_regex = '';

    /**
     * @throws Exception
     */
    public static function help(): int
    {
        $file = basename(__FILE__);
        $help = [
            "usage" => [
                "{$file} --conf [fichiers de conf] [OPTIONS]",
                "{$file} --help"
            ],
            $file => [
                "text" => [
                    "--itop_serveur",
                    "--evobserve_serveur",
                    "--facmonitoring_filtre"
                ]
            ]
        ];
        $class_utilisees = [
            Zorille\framework\dates::class,
            evobserve_hosts::class,
            evobserve_services::class
        ];
        $help = array_merge(
            $help,
            Core\fonctions_standards::help_fonctions_standard(
                false,
                false,
                $class_utilisees
           )
       );
        Core\fonctions_standards::affichage_standard_help($help);
        return 0;
    }

    /**
     * @throws Exception
     */
    protected function main(): bool
    {
        $liste_option = $this->getListOptions();
        $this->factory = ItopFactory::new();

        // Gestion de evobserve
        $evobserve_webservice = evobserve\wsclient::creer_wsclient(
            $liste_option,
            evobserve\datas::creer_datas($liste_option)
       );
        // Fin de gestion de evobserve
        // gestion_client
        $this->onInfo("Create new gestion_client object");
        $gestion_client = itop_gestion_client::creer_itop_gestion_client($liste_option);

        try {
            // On filtre la liste des clients
            $this->onInfo("On recupere les organisation dans iTop");
            // On prepare les webservices
            $evobserve_webservice->prepare_connexion($liste_option->getOption("evobserve_serveur"));
            // on recupere les modele une seule fois
            $liste_modele_evobserve = manage_modeles_evobserve::creer_manage_modeles_evobserve(
                $liste_option,
                $evobserve_webservice,
                $gestion_client
           );
            // on traite les CI dans iTop
            $this->facilitiesMonitoring(
                $liste_option,
                $gestion_client,
                $evobserve_webservice,
                $liste_modele_evobserve
           );
            $this->facilitiesMonitoring_clean(
                $liste_option,
                $gestion_client,
                $evobserve_webservice
           );

            return true;
        } catch(Exception $e) {
            return $this->onError($e->getMessage(), "", $e->getCode());
        }
    }

    /**
     * @param options $liste_option
     * @param itop_gestion_client $gestion_client
     * @param evobserve\wsclient $evobserve_webservice
     * @param manage_modeles_evobserve $liste_modele_evobserve
     * @return void
     * @throws Exception
     */
    private function facilitiesMonitoring(
        options                 $liste_option,
        itop_gestion_client     $gestion_client,
        evobserve\wsclient       $evobserve_webservice,
        manage_modeles_evobserve $liste_modele_evobserve): void
    {
        abstract_log::onInfo_standard("On gere les FacilitiesMonitoring de production");
        $queryBuilder = $this->factory->createFacilitiesMonitoringQueryBuilder();
        $itop_webservice = $queryBuilder->getWsClient();

        /** @var itop\data_models\CMDBChangeOp[] $updated_liste_ci_itop */
        $updated_liste_ci_itop = $this->factory->createCMDBChangeOpQueryBuilder()
            ->select()
            ->join(FacilitiesMonitoringQL::class, 'objkey = ' . FacilitiesMonitoringQL::class . '.id')
            ->join(FunctionalCiQL::class, FacilitiesMonitoringQL::class . '.functionalci_id = ' . FunctionalCiQL::class . '.id')
            ->where('date', QLOperator::SUP, 'DATE_SUB(NOW(), INTERVAL 7 DAY)')
            ->and()
            ->where(FunctionalCiQL::class.'::obsolescence_flag', QLOperator::EQUALS, '0')
            ->and()
            ->where(FacilitiesMonitoringQL::class.'::status', QLOperator::EQUALS, StatusEnum::ACTIVE)
            ->build()->toModel()['objects'];

        $id_list = array_map(fn ($item) => $item->getObjkey(), $updated_liste_ci_itop);

        // SELECT FacilitiesMonitoring AS FM JOIN FunctionalCI AS CI ON FM.functionalci_id=CI.id
        /** @var itop\data_models\FacilitiesMonitoring[] $liste_ci_itop */
        $liste_ci_itop = empty($id_list) ? [] : $queryBuilder
            ->select(
                "id","name",
                "org_id","organization_euclyde_id",
                "functionalci_id","monitoring_id",
                "functionalci_id_finalclass_recall","category",
                "etiquettes","monitoring_box_name",
                "monitoring_host_standard_account","monitoring_host_snmp",
                "monitoring_collecte_elect","modele_name",
                "controle_name","seuil_control_alerte",
                "seuil_control_critique"
            )
            ->join(FunctionalCiQL::class, 'functionalci_id = ' . FunctionalCiQL::class . '.id')
            ->where(FunctionalCiQL::class.'::obsolescence_flag', QLOperator::EQUALS, '0')
            ->and()
            ->where('status', QLOperator::EQUALS, StatusEnum::ACTIVE)
            ->and()
            ->where('id', QLOperator::IN, $id_list)
            ->build()->toModel()['objects'];

        $this->onDebug($liste_ci_itop);
        $standard_evobserve_hosts = evobserve_hosts::creer_evobserve_hosts(
            $liste_option,
            $gestion_client,
            $evobserve_webservice,
            $itop_webservice
        );
        $standard_evobserve_hosts->setObjModelesEvobserve($liste_modele_evobserve);

        foreach ($liste_ci_itop as $monitoring_ci) {
            if (
                $this->getFacmonitoringFiltreOption() &&
                $this->getFacmonitoringFiltreOption() != $monitoring_ci->getName()
            ) continue;

            $query = $this->factory::createFromClassName($monitoring_ci->getFunctionalciIdFinalclassRecall())
                ->select("id","name","fqdn","friendlyname","location_name","org_id","brand_name")
                ->where('id', QLOperator::EQUALS, $monitoring_ci->getFunctionalCiId());
            $functionalci_itop = $query->build()->toModel()['objects'];

            $this->getListOptions()->onDebug($functionalci_itop, 2);
            $this->ajoute_host(
                $monitoring_ci,
                array_shift($functionalci_itop),
                $standard_evobserve_hosts
            );
        }

        $standard_evobserve_hosts->updateCollectors();
    }

    /**
     * @param options $liste_option
     * @param itop_gestion_client $gestion_client
     * @param evobserve\wsclient $evobserve_webservice
     * @return void
     * @throws Exception
     */
    private function facilitiesMonitoring_clean(
        options             $liste_option,
        itop_gestion_client $gestion_client,
        evobserve\wsclient   $evobserve_webservice): void
    {
        $this->onInfo("On supprime les FacilitiesMonitoring obsoletes");

        /** @var itop\data_models\CMDBChangeOp[] $updated_liste_ci_itop */
//        $updated_liste_ci_itop = $this->factory->createCMDBChangeOpQueryBuilder()
//            ->select()
//            ->join(FacilitiesMonitoringQL::class, 'objkey = ' . FacilitiesMonitoringQL::class . '.id')
//            ->join(FunctionalCiQL::class, FacilitiesMonitoringQL::class . '.functionalci_id = ' . FunctionalCiQL::class . '.id')
//            ->where('date', QLOperator::SUP, 'DATE_SUB(NOW(), INTERVAL 7 DAY)')
//            ->and()->where(FunctionalCiQL::class.'::obsolescence_flag',
//                        QLOperator::EQUALS, '0')
//            ->and()->where(FacilitiesMonitoringQL::class.'::status',
//                        QLOperator::EQUALS, StatusEnum::ACTIVE)
//            ->build()->toModel()['objects'];

        $queryBuilder = $this->factory->createFacilitiesMonitoringQueryBuilder();
        $itop_webservice = $queryBuilder->getWsClient();

//        $id_list = array_map(static fn ($item) => $item->getObjkey(), $updated_liste_ci_itop);

        /** @var itop\data_models\FacilitiesMonitoring[] $liste_ci_itop */
        $liste_ci_itop = /*empty($id_list) ? [] :*/ $queryBuilder
            ->select(
                "id","name",
                "org_id","organization_euclyde_id",
                "functionalci_id","monitoring_id",
                "functionalci_id_finalclass_recall","category"
            )
            ->where('status', QLOperator::EQUALS, StatusEnum::INACTIVE)
            ->and()->where('monitoring_id', QLOperator::NOT_IN, ['', 0])
//            ->and()->where('id', QLOperator::IN, $id_list)
            ->build()->toModel()['objects'];

        $standard_evobserve_hosts = evobserve_hosts::creer_evobserve_hosts(
            $liste_option,
            $gestion_client,
            $evobserve_webservice,
            $itop_webservice
        );

        foreach ($liste_ci_itop as $monitoring_ci) {
            $standard_evobserve_hosts->setCategory($monitoring_ci->getCategory())
                ->setItopFormat($monitoring_ci->getFunctionalciIdFinalclassRecall())
                ->nettoie_hosts($monitoring_ci->toArray());
        }
    }

    /**
     * @param itop\data_models\FacilitiesMonitoring $monitoring_ci
     * @param itop\data_model $functionalci_itop
     * @param evobserve_hosts $standard_evobserve_hosts
     * @return array|null
     */
    private function ajoute_host(
        itop\data_models\FacilitiesMonitoring $monitoring_ci,
        itop\data_model                       $functionalci_itop,
        evobserve_hosts                        $standard_evobserve_hosts): ?array
    {
        $standard_evobserve_hosts->setCategory($monitoring_ci->getCategory())
            ->setItopFormat($monitoring_ci->getFunctionalciIdFinalclassRecall())
            ->gestion_hosts($monitoring_ci->toArray(), $functionalci_itop->toArray());

        return $standard_evobserve_hosts->getObjCompany()->getHosts();
    }
}

EvobserveSynchroFacilitiesMonitoring::batch($argv);
