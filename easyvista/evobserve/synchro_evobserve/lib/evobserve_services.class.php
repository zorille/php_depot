<?php
/**
 * @author dvargas
 */
use Zorille\framework\abstract_log;
use Zorille\framework\options;
use Zorille\evobserve;

/**
 * class evobserve_services
 *
 * @package Euclyde
 * @subpackage evobserve_services
 */
class evobserve_services extends evobserve_companies {
	/**
	 * @access private
	 * @var evobserve\Service
	 */
	private $service = null;
	/**
	 * @access private
	 * @var array
	 */
	private $std_params = array ();
	/**
	 * @access private
	 * @var array
	 */
	private $liste_hosts = array ();
	/**
	 * @access private
	 * @var string
	 */
	private $champ_itsm_hostname = "";
	/**
	 * @access private
	 * @var string
	 */
	private $service_brand = "";
	/**
	 * @access private
	 * @var array
	 */
	private $service_donnees = array ();

	/**
	 * ********************* Creation de l'objet ********************
	 */
	/**
	 * Instancie un objet de type VMware\liste_ci.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param gestion_client $gestion_client
	 * @param evobserve\wsclient $evobserve_webservice
	 * @param wsclient_rest $itsm_webservice
	 * @param Boolean|string $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return self
	 */
	static function &creer_evobserve_services(
		options           &$liste_option,
		gestion_client    &$gestion_client,
		evobserve\wsclient &$evobserve_webservice,
		wsclient_rest     &$itsm_webservice,
		bool|string       $sort_en_erreur = false,
		string            $entete = __CLASS__): static {
		abstract_log::onDebug_standard ( __METHOD__, 1 );
		$objet = new evobserve_services ( $sort_en_erreur, $entete );
		return $objet->_initialise ( array (
				"options" => $liste_option,
				"evobserve:wsclient" => $evobserve_webservice
		) );
	}

	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return evobserve_services
	 * @throws Exception
	 */
	public function &_initialise(
        array $liste_class): static
	{
		parent::_initialise ( $liste_class );
		$this->setObjService ( evobserve\Service::creer_Service ( $liste_class ['options'], $liste_class ['evobserve:wsclient'] ) )
			->retrouve_param ();
		return $this;
	}

	/**
	 * ********************* Creation de l'objet ********************
	 */
	/**
	 * Constructeur
	 * @codeCoverageIgnore
	 * @param string $sort_en_erreur Sort en erreur.
	 * @param string $nom_module Nom du module.
	 */
	public function __construct(
		$sort_en_erreur = "non",
		string $nom_module = __CLASS__) {
		parent::__construct ( $sort_en_erreur, $nom_module );
	}

	/**
	 * Retrouve les parametres necessaire a la creation d'un service dans la ligne de commande
	 * @return $this
	 * @throws Exception
	 */
	public function retrouve_param(): static {
		$this->onDebug ( __METHOD__, 1 );
		$params = array ();
		$params ['normal_check_interval'] = $this->_valideOption ( array (
				"evobserve",
				"service",
				"normal_check_interval"
		), 5 );
		$params ['checkTimePeriod'] = $this->_valideOption ( array (
				"evobserve",
				"service",
				"checkTimePeriod"
		), 1 ); // 00h-24h LMMJVSD
		$params ['availabilityTimePeriod'] = $this->_valideOption ( array (
				"evobserve",
				"service",
				"availabilityTimePeriod"
		), 1 );
		$params ['availability_rate'] = $this->_valideOption ( array (
				"evobserve",
				"host",
				"availability_rate"
		), '100' );
		$this->manage_notifications_services ( $params );
		$this->manage_escalades ( $params );
		$this->manage_first_notification ( $params );
		$this->manage_check_time_period ( $params );
		// On enregistre
		$this->onDebug ( $params, 1 );
		return $this->setStandardParams ( $params );
	}

	/**
	 * @throws Exception
	 */
	public function prepare_params_evobserve(
			$service_itsm): bool|array {
		if (empty ( $service_itsm [$this->getChampItopHostname ()] )) {
			return $this->onError ( "L'id du host ( champ : " . $this->getChampItopHostname () . " ) est introuvable pour le service " . $service_itsm ['name'], $service_itsm, 1 );
		}
		$host_id = $service_itsm [$this->getChampItopHostname ()];
		$model = $this->getObjModelesEvobserve ()
			->retrouve_modele_service ( $service_itsm ['modele_name'] );
		// On met les valeurs du moniteur
		$check_command_arguments = $this->getObjModelesEvobserve ()
			->service_check_command_arguments ( $service_itsm ['service_params'] );
		$params_service = $this->getStandardParams ();
		$params_service ['name'] = $service_itsm ['name'];
		if (isset ( $service_itsm ['functionalci_id_finalclass_recall'] ) && $service_itsm ['functionalci_id_finalclass_recall'] == 'PDU' && isset ( $service_itsm ['modele_name'] ) && str_contains($service_itsm ['modele_name'], 'PDU-ENERGIE')) {
			// Specificite des PDU
			$params_service ['documentation'] = "https://support.euclyde.com/pages/UI.php?operation=details&class=PDU&id=" . $service_itsm ['functionalci_id'] . "&";
			$params_service ['additional_data'] = "PDU::" . $service_itsm ['functionalci_id'] . "::";
			if (preg_match ( '/- [a-z,A-Z].*: ([0-9]{5}.*)$/', $service_itsm ['functionalci_id_friendlyname'], $match ) !== false) {
				if (isset ( $match [1] )) {
					$params_service ['additional_data'] .= $match [1];
				}
			} else {
				$params_service ['additional_data'] .= $service_itsm ['organization_euclyde_id'];
			}
		} else {
			$params_service ['documentation'] = "https://support.euclyde.com/pages/UI.php?operation=details&class=" . $this->getItopFormat () . "&id=" . $service_itsm ['id'] . "&";
			$params_service ['additional_data'] = $this->getItopFormat () . "::" . $service_itsm ['id'] . "::" . $service_itsm ['organization_euclyde_id'];
		}
		$params_service ['host'] = $host_id;
		$params_service ['service_template'] = $model;
		$params_service ['check_command_arguments'] = $check_command_arguments;
		$this->onDebug ( $params_service, 1 );
		return $params_service;
	}

	/**
	 * Gere la category de l'objet si elle existe
	 * @return false|string|number
	 * @throws Exception
	 */
	public function prepare_category(): bool|int|string {
		if (! empty ( $this->getCategory () )) {
			$categories = evobserve\ServiceCategories::creer_ServiceCategories ( $this->getListeOptions (), $this->getObjetEvobserveWsclient () );
			return $categories->retrouve_id_category ( $this->getCategory () );
		}
		return 3;
	}

	/**
	 * Retrouve les details d'un host s'il existe
	 * @param array $ci_convertie
	 * @return array|stdClass
	 * @throws Exception
	 */
	public function retrouve_detail_service(
		array $ci_convertie): array|stdClass {
		$this->onDebug ( $ci_convertie, 2 );
		$this->getObjService ()
			->reset_donnees ();
		$service_data = array ();
		// Si le monitoring_id est present, on s'en sert
		if (! empty ( $ci_convertie ['monitoring_id'] )) {
			$this->getObjService ()
				->setId ( $ci_convertie ['monitoring_id'] )
				->retrouve_service ();
			$service_data = $this->getObjService ()
				->getDonnees ();
		} else {
			// On valide que le CI n'existe pas deja en recuperant l'id
			$this->getObjCompany ()
				->recupere_company_services ( array (
					'id' => 2,
					"comptesub" => 'true',
					"service_name" => $ci_convertie ['name']
			) );
			// si il n'y a pas de service de supervision, il faut le creer donc on sort
			if (empty ( $this->getObjCompany ()
				->getServices () )) {
				return array ();
			}
			// On recupere le detail du service s'il existe et n'est pas reference dans itsm
			$this->onInfo ( "Le service " . $ci_convertie ['name'] . " n'est par reference dans iTop, donc on ajoute la ref" );
			$this->getObjService ()
				->setId ( $this->getObjCompany ()
				->getServices () [0]->id )
				->retrouve_service ();
			$service_data = $this->getObjService ()
				->getDonnees ();
			if (! empty ( $service_data )) {
				// On ajoute l'id trouve dans iTop pour la prochaine fois
				$this->update_itsm ( $this->getObjCompany ()
					->getServices () [0]->id, $ci_convertie );
			}
		}
		return $service_data;
	}

	/**
	 * ***************************** Gestion d'un Service *******************************
	 */

	/**
	 * @throws Exception
	 */
	public function gestion_services(
			$monitoring_ci,
			$functionalci_itsm): static {
		$this->onDebug ( $monitoring_ci, 2 );
		// On filtre les services deja present dans evobserve
		$ci_convertie = $this->convertie_itsm_data ( $monitoring_ci, $functionalci_itsm );
		$this->onDebug ( $ci_convertie, 2 );
		$detail_service_evobserve = $this->retrouve_detail_service ( $ci_convertie );
		$this->onDebug ( $detail_service_evobserve, 2 );
		if (empty ( $detail_service_evobserve )) {
			// On creer l'objet
			$this->onInfo ( "On creer " . $ci_convertie ["name"] );
			$this->creer_service ( $ci_convertie );
		} else {
			// On creer l'objet
			$this->onInfo ( "On verifie si on doit updater " . $ci_convertie ["name"] );
			$this->update_service ( $detail_service_evobserve, $ci_convertie );
		}
		return $this;
	}

	/**
	 * ***************************** Update d'un Service *******************************
	 */
	public function valide_update(
			$service_existant,
			&$service_itsm): bool {
		$this->onDebug ( $service_existant, 2 );
		$update = false;
		if ($this->getListeOptions ()
			->verifie_option_existe ( "force_update_services" ) !== false) {
			$this->onInfo ( "On force l'update " );
			$update = true;
		}
		if (trim ( $service_itsm ['name'] ) != trim ( $service_existant->name )) {
			$this->onInfo ( "On update le nom " . $service_itsm ['name'] . " au lieu de " . $service_existant->name );
			$update = true;
		}
		if ($service_itsm ['host'] != $service_existant->host->id) {
			$this->onInfo ( "On update le host " . $service_itsm ['host'] . " au lieu de " . $service_existant->host->id );
			$update = true;
		}
		if (empty ( $service_existant->additional_data ) || trim ( $service_itsm ['additional_data'] ) != $service_existant->additional_data) {
			$this->onInfo ( "On update le additional_data." );
			$update = true;
		}
		if ($update) {
			// recupere toutes les valeurs deja en prod
			$this->manage_notifications_services ( $service_itsm, $service_existant );
			$this->manage_escalades ( $service_itsm, $service_existant );
			$this->manage_first_notification ( $service_itsm, $service_existant );
			$this->manage_check_time_period ( $service_itsm, $service_existant );
			$service_itsm ['availability_rate'] = $service_existant->availability_rate;
		}
		$this->onDebug ( $service_itsm, 2 );
		return $update;
	}

	/**
	 * @param $detail_service_evobserve
	 * @param $monitoring_ci
	 * @return evobserve_services
	 * @throws Exception
	 */
	public function update_service(
			$detail_service_evobserve,
			$monitoring_ci): static {
		$params_service = $this->prepare_params_evobserve ( $monitoring_ci );
		if ($this->valide_update ( $detail_service_evobserve, $params_service )) {
			$this->onInfo ( "On update " . $params_service ['name'] );
			if ($this->getListeOptions ()
				->verifie_option_existe ( "dry-run-service" ) !== false) {
				$this->onInfo ( "DRY-RUN : Pas d'update du service " . $params_service ['name'] );
				return $this;
			}
			$this->onDebug ( $detail_service_evobserve, 2 );
			$this->onDebug ( $params_service, 2 );
			$this->getObjService ()
				->reset_donnees ()
				->setId ( $detail_service_evobserve->id )
				->updateService ( $params_service );
			$this->setUpdateBoxes ( true );
		}
		return $this;
	}

	/**
	 * ***************************** Creation d'un Service *******************************
	 */

	/**
	 * @param $ci_convertie
	 * @return $this
	 * @throws Exception
	 */
	public function creer_service(
			$ci_convertie): static {
		$params_service = $this->prepare_params_evobserve ( $ci_convertie );
		$this->onDebug ( $params_service, 1 );
		$this->onInfo ( "On ajoute " . $params_service ['name'] );
		if ($this->getListeOptions ()
			->verifie_option_existe ( "dry-run-service" ) !== false) {
			$this->onInfo ( "DRY-RUN : Pas d'ajout du service " . $params_service ['name'] );
			return $this;
		}
		$this->getObjService ()
			->creerService ( $params_service );
		$this->setUpdateBoxes ( true );
		if (! empty ( $this->getObjService ()
			->getId () )) {
			// On ajoute l'id trouve dans iTop pour la prochaine fois
			$this->update_itsm ( $this->getObjService ()
				->getId (), $ci_convertie );
		}
		$this->setServiceDonnees ( $this->getObjService ()
			->getDonnees () );
		return $this;
	}

	/**
	 * ***************************** Suppression d'un Service *******************************
	 */
	/**
	 * @throws Exception
	 */
	public function nettoie_services(
			$monitoring_ci): static {
		// On filtre les services deja present dans evobserve
		$this->onDebug ( $monitoring_ci, 1 );
		if (isset ( $monitoring_ci ['monitoring_id'] )) {
			// On supprime l'objet
			$this->onInfo ( "On supprime " . $monitoring_ci ["name"] );
			$this->supprime_service ( $monitoring_ci ['monitoring_id'], [
                'class' => $monitoring_ci['finalclass'],
                'name' => $monitoring_ci['name'],
                'org_id' => $monitoring_ci['org_id'],
                'monitoring_id' => $monitoring_ci['monitoring_id'],
            ] );
		}
		return $this;
	}

    /**
     * @param string $evobserve_service_id
     * @param array $ci_convertie
     * @return $this
     * @throws Exception
     */
	public function supprime_service(
		string $evobserve_service_id,
        array $ci_convertie
    ): static {
		$this->onInfo ( "On supprime le service " . $evobserve_service_id );
		if ($this->getListeOptions ()
			->verifie_option_existe ( "dry-run-service" ) !== false) {
			$this->onInfo ( "DRY-RUN : Pas de suppression du service " . $evobserve_service_id );
			return $this;
		}
		$this->getObjService ()
			->setId ( $evobserve_service_id )
			->deleteService ();
        if (! empty ( $this->getObjService ()
            ->getId () )) {
            // On ajoute l'id trouve dans iTop pour la prochaine fois
            $this->update_itsm ( 0, $ci_convertie );
        }
		return $this;
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * @codeCoverageIgnore
	 * @return evobserve\Service|null
	 */
	public function &getObjService(): ?evobserve\Service
	{
		return $this->service;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setObjService(
			$ObjService): static {
		$this->service = $ObjService;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getStandardParams(): array {
		return $this->std_params;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setStandardParams(
			$std_params): static {
		$this->std_params = $std_params;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getListeHosts(): array {
		return $this->liste_hosts;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setListeHosts(
			$liste_hosts): static {
		$this->liste_hosts = $liste_hosts;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getChampItopHostname(): string {
		return $this->champ_itsm_hostname;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setChampItopHostname(
			$champ_itsm_hostname): static {
		$this->champ_itsm_hostname = $champ_itsm_hostname;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getServiceDonnees(): array {
		return $this->service_donnees;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setServiceDonnees(
			$service_donnees): static {
		$this->service_donnees = $service_donnees;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getServiceMarque(): string {
		return $this->service_brand;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setServiceMarque(
			$service_brand): static {
		$this->service_brand = $service_brand;
		return $this;
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * Affiche le help.<br> @codeCoverageIgnore
	 */
	static public function help(): array|string
	{
		$help = parent::help ();
		$help [__CLASS__] ["text"] = [
			'evobserve_services :',
			'	--force_update_services  Force la mise a jour des CIs',
			'	--dry-run-service  Ne met pas a jour le monitoring des services'
		];
		return $help;
	}
}