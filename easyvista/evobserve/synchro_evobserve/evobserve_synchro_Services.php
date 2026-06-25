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

use JetBrains\PhpStorm\ArrayShape;
use Zorille\framework as Core;
use Zorille\framework\QueryBuilderOperator as QLOperator;
use Zorille\itop\data_models\CMDBChangeOp;
use Zorille\itop\data_models\StatusEnum;
use Zorille\itop\query_fetchers\FunctionalCiFetcher as FunctionalCiQL;
use Zorille\itop\query_fetchers\AppsMonitoringFetcher as AppsMonitoringQL;
use Zorille\itop;
use Zorille\itop\ItopFactory;
use Zorille\coservit;

/** @var Core\options $liste_option */
require_once $liste_option->getOption ( "rep_scripts" ) . '/lib/includes.php';

/**
 * @method string getItopServeurOption()
 * @method string getCoservitServeurOption()
 * @method string getAppsmonitoringFiltreOption()
 * @method string getAppsmonitoringFiltreRegexOption()
 */
class CoservitSynchroAppsMonitoring extends Core\MainScript
{
    #[core\Flag]
    public string $itop_serveur = '';

    #[core\Flag]
    public ?string $coservit_serveur = '';

    #[core\Flag]
    public ?string $appsmonitoring_filtre = '';

    #[core\Flag]
    public ?string $appsmonitoring_filtre_regex = '';

    /**
     * @throws Exception
     */
    public static function help(): int
    {
        $file = basename ( __FILE__ );
        $help = [
            "usage" => [
                "{$file} --conf [fichiers de conf] [OPTIONS]",
                "{$file} --appsmonitoring_filtre Nom complet de l'AppsMonitoring a synchroniser",
                "{$file} --appsmonitoring_filtre_regex Regexp de l'AppsMonitoring a synchroniser au format '/reg/'",
                "{$file} --help"
            ],
            $file => [
                "text" => [
                    "--itop_serveur",
                    "--coservit_serveur"
                ]
            ]
        ];
        $used_classes = [
            Core\dates::class,
            coservit_hosts::class,
            coservit_services::class
        ];
        $help = array_merge($help, Core\fonctions_standards::help_fonctions_standard(
            false,
            false,
            $used_classes
        ));

        Core\fonctions_standards::affichage_standard_help ( $help );
        return 0;
    }

    /**
     * @throws Exception
     */
    protected function main(): bool
    {
        $liste_option = $this->getListOptions();

        // Gestion de coservit
        $coservit_webservice = coservit\wsclient::creer_wsclient (
            $liste_option,
            coservit\datas::creer_datas ( $liste_option )
        );
        // Fin de gestion de coservit

        // gestion_client
        $this->onInfo ( "Create new gestion_client object" );
        $gestion_client = itop_gestion_client::creer_itop_gestion_client ( $liste_option );
        // Fin de gestion de gestion_client

        try {
            // On filtre la liste des clients
            $this->onInfo ( "On recupere les organisation dans iTop" );

            // On prepare les webservices
            $coservit_webservice->prepare_connexion ( $this->getCoservitServeurOption() );

            // on recupere les modele une seule fois
            $liste_modele_coservit = manage_modeles_coservit::creer_manage_modeles_coservit (
                $liste_option,
                $coservit_webservice,
                $gestion_client
            );
            // on traite les CI dans iTop
            $this->appsMonitoring (
                $liste_option,
                $gestion_client,
                $coservit_webservice,
                $liste_modele_coservit
            );
            $this->appsMonitoringClean (
                $liste_option,
                $gestion_client,
                $coservit_webservice
            );

            return true;
        } catch ( Exception $e ) {
            return Core\abstract_log::onError_standard ( $e->getMessage (), "", $e->getCode () );
        }
    }

    /**
     * @throws Exception
     */
    private function appsMonitoring(
        Core\options            $liste_option,
        itop_gestion_client     $gestion_client,
        coservit\wsclient       $coservit_webservice,
        manage_modeles_coservit $liste_modele_coservit): void
    {
        $this->onInfo ( "On gere les AppsMonitoring de production" );
        $factory = ItopFactory::new();

        $queryBuilder = $factory->createCMDBChangeOpQueryBuilder();
		if ($liste_option->verifie_option_existe ( "itop_interval" ) === false) {
			$interval=7;
		} else {
			$interval=$liste_option->getOption ( "itop_interval" );
		}

        $results = $queryBuilder
            ->select()
            ->join(AppsMonitoringQL::class, 'objkey = ' . AppsMonitoringQL::class . '.id')
            ->join(FunctionalCiQL::class, AppsMonitoringQL::class . '.functionalci_id = ' . FunctionalCiQL::class . '.id')
            ->where('date', QLOperator::SUP, 'DATE_SUB(NOW(), INTERVAL '.$interval.' DAY)')
            ->and()
            ->where(FunctionalCiQL::class.'::obsolescence_flag', QLOperator::EQUALS, '0')
            ->and()
            ->where(AppsMonitoringQL::class.'::status', QLOperator::EQUALS, StatusEnum::ACTIVE)
            ->build()->toModel()['objects'];

        /** @var array $liste_ci_itop */
        $liste_ci_itop = array_map(
            #[ArrayShape([
                'changeBase' => CMDBChangeOp::class,
                'change' => itop\data_models\CMDBChangeOpSetAttributeScalar::class|itop\data_models\CMDBChangeOpCreate::class|itop\data_models\CMDBChangeOpSetAttributeText::class|itop\data_models\CMDBChangeOpSetAttributeLinksAddRemoveType::class|itop\data_models\CMDBChangeOpSetAttributeLinksAddRemove::class,
                'object' => itop\data_models\AppsMonitoring::class
            ])]
            static function(CMDBChangeOp $item): array {
                if($item->getFinalclass() === 'CMDBChangeOpSetAttributeText')
                {
                	$query1 = ItopFactory::createFromClassName($item->getFinalclass())
                    	->select(
                        	"id","attcode", "prevdata",
                        	"finalclass", "friendlyname",
                        	"change", "date", "userinfo",
                        	"user_id", "objclass", "objkey",
                        	"change_friendlyname", "user_id_friendlyname"
                    	)
                    	->where('id', QLOperator::EQUALS, $item->getId());

                } else if($item->getFinalclass() === 'CMDBChangeOpCreate')
                {
                    $query1 = ItopFactory::createFromClassName($item->getFinalclass())
                            ->select(
                                    "id",
                                    "finalclass", "friendlyname",
                                    "change", "date", "userinfo",
                                    "user_id", "objclass", "objkey",
                                    "change_friendlyname", "user_id_friendlyname"
                            )
                            ->where('id', QLOperator::EQUALS, $item->getId());

                } else {
                        $query1 = ItopFactory::createFromClassName($item->getFinalclass())
                            ->select(
                                "id","attcode", "oldvalue",
                                "newvalue", "finalclass", "friendlyname",
                                "change", "date", "userinfo",
                                "user_id", "objclass", "objkey",
                                "change_friendlyname", "user_id_friendlyname"
                            )
                    ->where('id', QLOperator::EQUALS, $item->getId());
                }
                $query2 = ItopFactory::createFromClassName($item->getObjclass())
                    ->select(
                        "id", "name", "org_id", "finalclass",
                        "organization_euclyde_id", "functionalci_id",
                        "functionalci_id_finalclass_recall", "status",
                        "functionalci_id_friendlyname",
                        "monitoring_id", "modele_name",
                        "monitoring_host_id", "monitoring_host_name",
                        "monitoring_host_id_finalclass_recall",
                        "monitoring_apps_standard_account",
                        "service_params", "functionalci_id_finalclass_recall"
                    )
                    ->where("id", QLOperator::EQUALS, $item->getObjkey());

                return [
                    'changeBase' => $item,
                    'change' => array_pop($query1->build()->toModel()['objects']),
                    'object' => array_pop($query2->build()->toModel()['objects'])
                ];
            },
            $results
        );

        $this->onDebug($liste_ci_itop, 2);

        $itop_webservice = $queryBuilder->getWsClient();
        $standard_coservit_service = coservit_services::creer_coservit_services(
            $liste_option, $gestion_client,
            $coservit_webservice, $itop_webservice
        );
        $standard_coservit_service->setObjModelesCoservit($liste_modele_coservit);

        /**
         * @var array{
         *     changeBase: CMDBChangeOp,
         *     change: itop\data_models\CMDBChangeOpSetAttributeScalar|itop\data_models\CMDBChangeOpSetAttributeText|itop\data_models\CMDBChangeOpSetAttributeLinksAddRemoveType|itop\data_models\CMDBChangeOpSetAttributeLinksAddRemove,
         *     object: itop\data_models\AppsMonitoring
         * } $monitoring_ci
         */
        foreach ($liste_ci_itop as $monitoring_ci) {
            $this->onDebug ("{$this->getAppsmonitoringFiltreOption()} != {$monitoring_ci['object']->getName()}", 1,);
            if (
                (
                    $this->getAppsmonitoringFiltreOption() &&
                    $this->getAppsmonitoringFiltreOption() != $monitoring_ci['change']->getName()
                ) ||
                (
                    $this->getAppsmonitoringFiltreRegexOption() &&
                    preg_match($this->getAppsmonitoringFiltreRegexOption(), $monitoring_ci['change']->getName()) == 0
                )
            ) continue;

            $class = $monitoring_ci['object']->getMonitoringHostIdFinalclassRecall();
            $functionalCi_itop = ItopFactory::createFromClassName($class)
                ->select("id", "name", "monitoring_id")
                ->where("id", QLOperator::EQUALS, $monitoring_ci['object']->getMonitoringHostId());
            $functionalCi_itop = $functionalCi_itop->build()->toModel()['objects'];

            $this->onDebug ( $functionalCi_itop );

            $this->ajoute_service(
                $monitoring_ci['object'],
                array_shift($functionalCi_itop),
                $standard_coservit_service
            );
        }
        $standard_coservit_service->updateCollectors();
    }

    /**
     * @param core\options $liste_option
     * @param itop_gestion_client $gestion_client
     * @param coservit\wsclient $coservit_webservice
     * @return void
     * @throws Exception
     */
    private function appsMonitoringClean(
            Core\options        $liste_option,
            itop_gestion_client $gestion_client,
            coservit\wsclient $coservit_webservice
    ): void
    {
        $this->onInfo ( "On supprime les AppsMonitoring obsoletes" );
        $itop_webservice = $this->getFactory(Core\FactoriesEnum::ITOP)
                ->createAppsMonitoringQueryBuilder();
        $liste_ci_itop = $itop_webservice
            ->select(
                "id", "name", "org_id", "organization_euclyde_id",
                "functionalci_id", "monitoring_id",
                "functionalci_id_finalclass_recall", "finalclass", "org_id"
            )
            ->where('status', QLOperator::EQUALS, 'inactive')
            ->and()->where('monitoring_id', QLOperator::NOT_IN, ['', 0])
            ->build()->getResult()['objects'];

        // On creer la liste de hosts
        $standard_coservit_services = coservit_services::creer_coservit_services (
            $liste_option, $gestion_client,
            $coservit_webservice, $itop_webservice
        );
        foreach ( $liste_ci_itop as $monitoring_ci ) {
            $standard_coservit_services
                ->setItopFormat ( $monitoring_ci ['functionalci_id_finalclass_recall'] )
                ->nettoie_services ( $monitoring_ci );
        }
    }

    /**
     * @param itop\data_models\AppsMonitoring $monitoring_ci
     * @param itop\data_model $functionalCi_itop
     * @param coservit_services $standard_coservit_services
     * @return array|null
     * @throws Exception
     */
    private function ajoute_service(
        itop\data_models\AppsMonitoring $monitoring_ci,
        itop\data_model                 $functionalCi_itop,
        coservit_services               $standard_coservit_services): ?array
    {
        $standard_coservit_services->setItopFormat ( $monitoring_ci->getFinalclass() )
            ->setChampItopHostname ( "functional_monitoring_id" )
            ->gestion_services ( $monitoring_ci->toArray(), $functionalCi_itop->toArray() );

        return $standard_coservit_services->getObjCompany()->getHosts();
    }
}

CoservitSynchroAppsMonitoring::batch($argv);
