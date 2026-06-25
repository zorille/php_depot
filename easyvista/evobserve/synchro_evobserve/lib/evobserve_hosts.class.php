<?php
/**
 * Gestion de itsm.
 * @author dvargas
 */
use Zorille\framework\abstract_log;
use Zorille\framework\options;
use Zorille\evobserve;

/**
 * class evobserve_hosts
 *
 * @package Euclyde
 * @subpackage evobserve_hosts
 */
class evobserve_hosts extends evobserve_companies {
	/**
	 * @access private
	 * @var evobserve\Host
	 */
	private $host = null;
	/**
	 * @access private
	 * @var array
	 */
	private $std_params = array ();
	/**
	 * @access private
	 * @var array
	 */
	private $host_donnees = array ();
	/**
	 * @access private
	 * @var string
	 */
	private $host_brand = "";

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
	 * @param bool|string $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return self
	 */
	static function &creer_evobserve_hosts(
		options           &$liste_option,
		gestion_client    &$gestion_client,
		evobserve\wsclient &$evobserve_webservice,
		wsclient_rest     &$itsm_webservice,
		bool|string       $sort_en_erreur = false,
		string            $entete = __CLASS__): static {
		abstract_log::onDebug_standard ( __METHOD__, 1 );
		$objet = new evobserve_hosts ( $sort_en_erreur, $entete );
		return $objet->_initialise ( array (
				"options" => $liste_option,
				"gestion_client" => $gestion_client,
				"evobserve:wsclient" => $evobserve_webservice,
				"itsm:wsclient_rest" => $itsm_webservice
		) );
	}

	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return evobserve_hosts
	 * @throws Exception
	 */
	public function &_initialise(
        array $liste_class): static {
		parent::_initialise ( $liste_class );
		$this->setObjHost ( evobserve\Host::creer_Host ( $liste_class ['options'], $liste_class ['evobserve:wsclient'] ) )
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
	 * Retrouve les parametres necessaire a la creation d'un host dans la ligne de commande
	 * @return $this
	 * @throws Exception
	 */
	public function retrouve_param(): static {
		$this->onDebug ( __METHOD__, 1 );
		$params = array ();
		$params ['host_mode'] = $this->_valideOption ( array (
				"evobserve",
				"host",
				"mode"
		), "box" );
		$params ['availability_rate'] = $this->_valideOption ( array (
				"evobserve",
				"host",
				"availability_rate"
		), '100' );
		$params ['normal_check_interval'] = $this->_valideOption ( array (
				"evobserve",
				"host",
				"normal_check_interval"
		), 5 );
		$this->manage_notifications_hosts ( $params );
		$this->manage_escalades ( $params );
		$this->manage_first_notification ( $params );
		$this->manage_check_time_period ( $params );
		// On enregistre
		$this->onDebug ( $params, 1 );
		return $this->setStandardParams ( $params );
	}

	/**
	 * Prepare les parametres pour creer ou updater un host
	 * @param array $host_itsm
	 * @return array
	 * @throws Exception
	 */
	public function prepare_params_evobserve(
		array $host_itsm): array {
		$params_host = $this->getStandardParams ();
		// On definit le code client en premier lieu, car le reste depend de ce code
		$code_client = $host_itsm ['organization_euclyde_id'] ?? $this->getObjGestionClient()
			->retrouve_codeclient_dans_fqdn($host_itsm ['fqdn']);
		// Puis les etiquettes
		$tag_ids = $this->getObjModelesEvobserve ()
			->retrouve_tags ( $host_itsm ["etiquettes"] );
		// Le companie ID
		$company = $this->recupere_site_evobserve ( $host_itsm ["fqdn"], $tag_ids, $code_client );
		$params_host ["company"] = $this->getObjCompany ()
			->retrouve_id_company ( $company );
		// Le modele de host est unitaire
		$params_host ['host_templates'] = $this->getObjModelesEvobserve ()
			->retrouve_modele_host ( $host_itsm ['modele_name'] );
		// Les collectors sont ajoute a la liste pour l'update
		$collector_name = $this->recupere_collector ( $host_itsm, $code_client );
		$params_host ["collector"] = $this->getObjBoxes ()
			->retrouve_id_boxe ( $collector_name, $params_host ["company"] );
		// on ajoute la liste de collector impactees pour la mise a jour des boxes/collector
		// $this->ajouteCollectorId ( $params_host ["collector"] );
		// Puis les parametres standards
		$params_host ['host_alias'] = $host_itsm ["name"];
		$params_host ["host_address"] = $host_itsm ["fqdn"];
		$params_host ['itsm_id'] = $host_itsm ['id']; // On prend celui de l'objet monitoring, pas du CI
		$params_host ['tags'] = $tag_ids;
		$params_host ['additional_data'] = $host_itsm ['functional_full_id'];
		$params_host ['documentation'] = "https://support.euclyde.com/pages/UI.php?operation=details&class=" . $this->getItopFormat () . "&id=" . $host_itsm ['functionalci_id'] . "&";
		$params_host ['host_category'] = $this->prepare_category ();
		if (! isset ( $host_itsm ['controle_name'] )) {
			$host_itsm ['controle_name'] = 'Device not pingable';
			$valeurs_ping = array (
					array (
							'name' => 'Seuil d\'alerte (ms)',
							'value' => '5'
					),
					array (
							'name' => 'Seuil critique (ms)',
							'value' => '8'
					)
			);
		} else {
			$valeurs_ping = array (
					array (
							'name' => 'Seuil d\'alerte (ms)',
							'value' => $host_itsm ['seuil_control_alerte']
					),
					array (
							'name' => 'Seuil critique (ms)',
							'value' => $host_itsm ['seuil_control_critique']
					)
			);
		}
		$params_host ['check_command_arguments'] = $this->getObjModelesEvobserve ()
			->host_check_command_arguments_value ( $host_itsm ["controle_name"], $valeurs_ping );
		$params_host ['check_template'] = $this->getObjModelesEvobserve ()
			->retrouve_modele_check_host ( $host_itsm ['controle_name'] );
		$params_host ['auto_handle_services'] = true;
		if (isset ( $host_itsm ["friendlyname"] )) {
			$params_host ["description"] = $host_itsm ["friendlyname"];
		} else {
			$params_host ["description"] = "";
		}
		$this->onDebug ( $params_host, 1 );
		return $params_host;
	}

	/**
	 * Gere la category de l'objet si elle existe
	 * @return false|string|number
	 * @throws Exception
	 */
	public function prepare_category(): bool|int|string {
		if (! empty ( $this->getCategory () )) {
			$categories = evobserve\HostCategories::creer_HostCategories ( $this->getListeOptions (), $this->getObjetEvobserveWsclient () );
			return $categories->retrouve_id_category ( $this->getCategory () );
		}
		return 0;
	}

	/**
	 * Retrouve les details d'un host s'il existe
	 * @param array $ci_convertie
	 * @return array|stdClass
	 * @throws Exception
	 */
	public function retrouve_detail_host(
		array $ci_convertie): array|stdClass {
		$this->onDebug ( $ci_convertie, 2 );
		$this->getObjHost ()
			->reset_donnees ();
		$host_data = array ();
		// Si le monitoring_id est present, on s'en sert
		if (! empty ( $ci_convertie ['monitoring_id'] )) {
			$this->getObjHost ()
				->setId ( $ci_convertie ['monitoring_id'] )
				->retrouve_host ();
			$host_data = $this->getObjHost ()
				->getDonnees ();
		} else {
			// On valide que le CI n'existe pas deja en recuperant l'id
			$this->getObjCompany ()
				->recupere_company_hosts ( array (
					'id' => 2,
					"comptesub" => 'true',
					"host_name" => $ci_convertie ['name']
			) );
			// On verifie qu'une reponse peut correspondre
			$liste_host = $this->getObjCompany ()
				->getHosts ();
			$local_host = null;
			$this->getObjCompany ()
				->setHosts ( $local_host );
			foreach ( $liste_host as $host ) {
				if ($host->host_alias == $ci_convertie ['name']) {
					$local_host = array (
							0 => $host
					);
					$this->getObjCompany ()
						->setHosts ( $local_host );
					break;
				}
			}
			// Si il n'y a toujours pas de réponse, on sort
			if (empty ( $this->getObjCompany ()
				->getHosts () )) {
				return array ();
			}
			// On recupere le detail du host s'il existe et n'est pas reference dans l'ITSM
			$this->onInfo ( "Le host " . $ci_convertie ['name'] . " n'est par reference, donc on ajoute la ref" );
			$this->getObjHost ()
				->setId ( $this->getObjCompany ()
				->getHosts () [0]->id )
				->retrouve_host ();
			$host_data = $this->getObjHost ()
				->getDonnees ();
			if (! empty ( $host_data )) {
				// On ajoute l'id trouve dans iTop pour la prochaine fois
				$this->update_itsm ( $this->getObjCompany ()
					->getHosts () [0]->id, $ci_convertie );
			}
		}
		return $host_data;
	}

	/**
	 * ***************************** Gestion d'un Host *******************************
	 */
	public function gestion_hosts(
			$monitoring_ci,
			$functional_ci): static {
		// On filtre les hosts deja present dans evobserve
		$this->onInfo ( "On traite le host " . $functional_ci ['name'] );
		$this->onDebug ( $monitoring_ci, 2 );
		$ci_convertie = $this->convertie_itsm_data ( $monitoring_ci, $functional_ci );
		$detail_host_evobserve = $this->retrouve_detail_host ( $ci_convertie );
		$this->onDebug ( $detail_host_evobserve, 2 );
		if (empty ( $detail_host_evobserve )) {
			// On creer l'objet car on ne l'a pas trouve
			$this->onInfo ( "On creer " . $ci_convertie ["name"] );
			$this->creer_host ( $ci_convertie );
		} else {
			// On met a jour l'objet
			$this->onInfo ( "On verifie si on doit updater " . $detail_host_evobserve->host_alias );
			try {
				$this->update_host ( $detail_host_evobserve, $ci_convertie );
			} catch ( Exception $e ) {
				if (str_contains($e->getMessage(), "getNameHistory")) {
					$this->onWarning ( $e->getMessage () );
				}
			}
		}
		return $this;
	}

	/**
	 * ***************************** Update Host *******************************
	 */
	/**
	 * Verifie s'il faut faire un update
	 * @param stdClass $host_existant Host declarer dans coservIT
	 * @param array $param_update_host Host itsm au format coservIT
	 * @return boolean
	 */
	public function valide_update(
		stdClass $host_existant,
		array    &$param_update_host): bool
	{
		$this->onDebug ( $host_existant, 2 );
		$update = false;
		if ($this->getListeOptions ()
			->verifie_option_existe ( "force_update_hosts" ) !== false) {
			$this->onInfo ( "On force l'update " );
			$update = true;
		}
		if ($param_update_host ['host_alias'] != $host_existant->host_alias) {
			$this->onInfo ( "On update le nom " . $param_update_host ['host_alias'] . " au lieu de " . $host_existant->host_alias );
			$update = true;
		}
		if ($param_update_host ['host_address'] != $host_existant->host_address) {
			$this->onInfo ( "On update le host_address " . $param_update_host ['host_address'] . " au lieu de " . $host_existant->host_address );
			$update = true;
		}
		if ($param_update_host ['company'] != $host_existant->company->id) {
			$this->onInfo ( "On update le company " . $param_update_host ['company'] . " au lieu de " . $host_existant->company->id );
			$update = true;
		}
		if ($param_update_host ['host_category'] != $host_existant->host_category->id) {
			$this->onInfo ( "On update le host_category " . $param_update_host ['host_category'] . " au lieu de " . $host_existant->host_category->id );
			$update = true;
		}
		if ($param_update_host ['host_templates'] [0] != $host_existant->host_templates [0]->id) {
			$this->onInfo ( "On update le host_templates " . $param_update_host ['host_templates'] [0] . " au lieu de " . $host_existant->host_templates [0]->id );
			$update = true;
		}
		if ($param_update_host ['collector'] != $host_existant->collector->id) {
			$this->onInfo ( "On update le collector " . $param_update_host ['collector'] . " au lieu de " . $host_existant->collector->id );
			$update = true;
		}
		if (empty ( $host_existant->additional_data ) || trim ( $param_update_host ['additional_data'] ) != $host_existant->additional_data) {
			$this->onInfo ( "On update le additional_data." );
			$update = true;
		}
		if (isset ( $host_existant->description ) && (empty ( $host_existant->description ) || trim ( $param_update_host ['description'] ) != $host_existant->description)) {
			$this->onInfo ( "On update la description." );
			$this->onDebug ( $host_existant->description . "||" . $param_update_host ['description'] . "||", 2 );
			$update = true;
		}
		if (isset ( $host_existant->itsm_id ) && (empty ( $host_existant->itsm_id ) || $param_update_host ['itsm_id'] != $host_existant->itsm_id)) {
			$this->onInfo ( "On update le itsm_id." );
			$update = true;
		}
		if ($update) {
			// recupere toutes les valeurs deja en prod
			$this->manage_notifications_hosts ( $param_update_host, $host_existant );
			$this->manage_escalades ( $param_update_host, $host_existant );
			$this->manage_first_notification ( $param_update_host, $host_existant );
			$this->manage_check_time_period ( $param_update_host, $host_existant );
			$param_update_host ['availability_rate'] = $host_existant->availability_rate;
		}
		return $update;
	}

	/**
	 * @param stdClass $detail_host_evobserve
	 * @param array $ci_convertie
	 * @return $this
	 * @throws Exception
	 */
	public function update_host(
		stdClass $detail_host_evobserve,
		array    $ci_convertie): static
	{
		$params_host = $this->prepare_params_evobserve ( $ci_convertie );
		if ($this->valide_update ( $detail_host_evobserve, $params_host )) {
			$this->onInfo ( "On update " . $ci_convertie ['name'] );
			if ($this->getListeOptions ()
				->verifie_option_existe ( "dry-run-host" ) !== false) {
				$this->onInfo ( "DRY-RUN : Pas d'update du host " . $ci_convertie ['name'] );
				return $this;
			}
			$this->onDebug ( $detail_host_evobserve, 2 );
			$this->onDebug ( $params_host, 2 );
			$this->getObjHost ()
				->reset_donnees ()
				->setId ( $detail_host_evobserve->id )
				->updateHost ( $params_host );
			$this->ajouteCollectorId ( $params_host ["collector"] )
				->setUpdateBoxes ( true );
		}
		return $this;
	}

	/**
	 * ***************************** Ajout Host *******************************
	 */
	/**
	 * @param $ci_convertie
	 * @return $this
	 * @throws Exception
	 */
	public function creer_host(
			$ci_convertie): static
	{
		$params_host = $this->prepare_params_evobserve ( $ci_convertie );
		$this->onInfo ( "On ajoute " . $ci_convertie ['name'] );
		if ($this->getListeOptions ()
			->verifie_option_existe ( "dry-run-host" ) !== false) {
			$this->onInfo ( "DRY-RUN : Pas de creation du host " . $ci_convertie ['name'] );
			// $this->onInfo ( $params_host);
			return $this;
		}
		$this->getObjHost ()
			->creerHost ( $params_host );
		$this->ajouteCollectorId ( $params_host ["collector"] )
			->setUpdateBoxes ( true );
		if (! empty ( $this->getObjHost ()
			->getId () )) {
			// On ajoute l'id trouve dans iTop pour la prochaine fois
			$this->update_itsm ( $this->getObjHost ()
				->getId (), $ci_convertie );
		}
		$this->setHostDonnees ( $this->getObjHost ()
			->getDonnees () )
			->creer_userServices ();
		return $this;
	}

	/**
	 * ***************************** Suppression Host *******************************
	 */
	public function nettoie_hosts(
			$monitoring_ci): static
	{
		$this->onDebug ( $monitoring_ci, 1 );
		if (isset ( $monitoring_ci ['monitoring_id'] )) {
			// On supprime l'objet
			$this->onInfo ( "On supprime " . $monitoring_ci ["name"] );
			$this->supprime_host ( $monitoring_ci ['monitoring_id'], $monitoring_ci );
		}
		return $this;
	}

	/**
	 * @param string $evobserve_host_id
	 * @param $monitoring_ci
	 * @return evobserve_hosts|bool
	 * @throws Exception
	 */
	public function supprime_host(
		string $evobserve_host_id,
		       $monitoring_ci): static|bool
	{
		$this->onDebug ( "On supprime " . $evobserve_host_id, 1 );
		if ($this->getListeOptions ()
			->verifie_option_existe ( "dry-run-host" ) !== false) {
			$this->onInfo ( "DRY-RUN : Pas d'update du host " . $evobserve_host_id );
			return $this;
		}
		try {
			$this->getObjHost ()
				->setId ( $evobserve_host_id )
				->deleteHost ();
		} catch ( Exception $e ) {
			if (!str_contains($e->getMessage(), 'RESOURCE_NOT_FOUND')) {
				return $this->onError ( $e->getMessage (), "", $e->getCode () );
			}
		}
		if (! empty ( $evobserve_host_id )) {
			// On ajoute l'id trouve dans iTop pour la prochaine fois
			$this->update_itsm ( "", $monitoring_ci );
		}
		return $this;
	}

	/**
	 * ***************************** User Service (to clean) *******************************
	 */
	/**
	 * @throws Exception
	 */
	public function creer_userServices(): static
	{
		$UserService = evobserve\UserService::creer_UserService ( $this->getListeOptions (), $this->getObjetEvobserveWsclient () );
		$donnees_host = $this->getHostDonnees ();
		$this->onInfo ( "On ajoute le userService de " . $donnees_host->host_alias );
		$tagids = array ();
		foreach ( $donnees_host->tags as $tag ) {
			$tagids [] = $tag->id;
		}
		$parametres = array (
				"company" => $donnees_host->company->id,
				"name" => "Host_" . $donnees_host->host_alias,
				"availability_rate" => $donnees_host->availability_rate,
				"availability_time_period" => 1,
				"business_impact" => $donnees_host->business_impact,
				"tags" => $tagids,
				"displayed" => true,
				"blocking_hosts" => array (
						$donnees_host->id
				),
				"degrading_unit_services" => $donnees_host->services
		);
		if (isset ( $donnees_host->additional_data )) {
			$parametres ["description"] = $donnees_host->additional_data;
		} else {
			$parametres ["description"] = $donnees_host->description;
		}
		$UserService->creerUserService ( $parametres );
		return $this;
	}

	/**
	 * @throws Exception
	 */
	public function suprimer_userServices(
			$nom,
			$companie): static
	{
		$UserService = evobserve\UserService::creer_UserService ( $this->getListeOptions (), $this->getObjetEvobserveWsclient () );
		$this->onInfo ( "On supprime le userService de " . $nom );
		$UserService->retrouve_UserServicesList ( array (
				"companies" => array (
						$companie
				)
		) );
		foreach ( $UserService->getDonnees () as $UserService ) {
			if ($UserService->name == $nom) {
				$UserService->deleteUserService ( $UserService->id );
			}
		}
		return $this;
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * @codeCoverageIgnore
	 * @return evobserve\Host|null
	 */
	public function &getObjHost(): ?evobserve\Host
	{
		return $this->host;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setObjHost(
			$ObjHost): static
	{
		$this->host = $ObjHost;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getStandardParams(): array
	{
		return $this->std_params;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setStandardParams(
			$std_params): static
	{
		$this->std_params = $std_params;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getHostDonnees(): array|stdClass
	{
		return $this->host_donnees;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setHostDonnees(
			$host_donnees): static
	{
		$this->host_donnees = $host_donnees;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getHostMarque(): string
	{
		return $this->host_brand;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setHostMarque(
			$host_brand): static
	{
		$this->host_brand = $host_brand;
		return $this;
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * Affiche le help.<br> @codeCoverageIgnore
	 */
	static public function help(): array|string {
		$help = parent::help ();
		$help [__CLASS__] ["text"] = array ();
		$help [__CLASS__] ["text"] [] .= "evobserve_hosts :";
		$help [__CLASS__] ["text"] [] .= "	--force_update_hosts  Force la mise a jour des CIs";
		$help [__CLASS__] ["text"] [] .= "	--dry-run-host  Ne met pas a jour le monitoring des Hosts";
		return $help;
	}
}