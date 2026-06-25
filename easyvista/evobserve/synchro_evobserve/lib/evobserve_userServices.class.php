<?php
/**
 * @author dvargas
 */
use Zorille\framework\abstract_log;
use Zorille\framework\options;
use Zorille\evobserve;
/**
 * class evobserve_userServices
 *
 * @package Euclyde
 * @subpackage evobserve_userServices
 */
class evobserve_userServices extends evobserve_companies {
	/**
	 * @access private
	 * @var evobserve\UserService
	 */
	private $userService = null;
	/**
	 * @access private
	 * @var array
	 */
	private $std_params = array ();
	/**
	 * @access private
	 * @var array
	 */
	private $liste_userServices = array ();
	/**
	 * @access private
	 * @var array
	 */
	private $liste_userServices_filtres = array ();
	/**
	 * @access private
	 * @var string
	 */
	private $userService_brand = "";
	/**
	 * @access private
	 * @var array
	 */
	private $userService_donnees = array ();

	/**
	 * ********************* Creation de l'objet ********************
	 */
	/**
	 * Instancie un objet de type VMware\liste_ci.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param gestion_client $gestion_client
	 * @param evobserve\wsclient $evobserve_wsclient
	 * @param bool|string $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return self
	 */
	static function &creer_evobserve_userServices(
		options           &$liste_option,
		gestion_client    &$gestion_client,
		evobserve\wsclient &$evobserve_wsclient,
		bool|string       $sort_en_erreur = false,
		string            $entete = __CLASS__): static {
		abstract_log::onDebug_standard ( __METHOD__, 1 );
		$objet = new evobserve_userServices ( $sort_en_erreur, $entete );
		return $objet->_initialise ( array (
				"options" => $liste_option,
				"evobserve:wsclient" => $evobserve_wsclient
		) );
	}

	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return self
	 * @throws Exception
	 */
	public function &_initialise(
        array $liste_class): static {
		parent::_initialise ( $liste_class );
		$this->setObjUserService ( evobserve\UserService::creer_UserService ( $liste_class ['options'], $liste_class ['evobserve:wsclient'] ) )
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
	 * Retrouve les parametres necessaire a la creation d'un userService dans la ligne de commande
	 * @return $this
	 */
	public function retrouve_param(): static {
		$this->onDebug ( __METHOD__, 1 );
		$params = array ();
		return $this->setStandardParams ( $params );
	}

	/**
	 * NE RECUPERE PAS LES UserServices de type "hidden"
	 * @return evobserve_userServices
	 * @throws Exception
	 */
	public function recupere_liste_userServices(): static
	{
		$this->getObjCompany ()
			->recupere_company_tree ( array (
				'id' => $this->getObjCompany ()
					->getId ()
		) );
		$companies = array (
				$this->getObjCompany ()
					->getId ()
		);
		foreach ( $this->getObjCompany ()
			->getCompanies () as $company ) {
			$companies [] = $company->id;
		}
		$this->getObjUserService ()
			->retrouve_UserServicesList ( array (
				"companies" => $companies
		) );
		$this->onDebug ( $this->getObjUserService ()
			->getDonnees (), 2 );
		return $this->setListeUserServices ( $this->getObjUserService ()
			->getDonnees () );
	}

	/**
	 * @throws Exception
	 */
	public function prepare_params_evobserve(
			$userService_itsm): array
	{
		$this->getObjUserService ()
			->reset_donnees ();
		$tag_ids = $this->getObjModelesEvobserve ()
			->prepare_tags_userService ( $userService_itsm ["name"] );
		if (count ( $tag_ids ) == 0) {
			$this->onDebug ( "Pas de Tag donc le nom n'est pas utilisable pour creer un userService", 1 );
			return array ();
		}
		$params_userService = $this->getStandardParams ();
		$companies = (array)$this->getObjCompany();
		$params_userService ["company"] = $this->recupere_site_evobserve_par_nom ( $companies, $userService_itsm ["name"] );
		$params_userService ['name'] = $userService_itsm ['friendlyname'];
		$params_userService ["description"] = "NE PAS SUPPRIMER : " . $this->getItopFormat () . "::" . $userService_itsm ['id'] . " &\n" . "https://support.euclyde.com/pages/UI.php?operation=details&class=" . $this->getItopFormat () . "&id=" . $userService_itsm ['id'] . "&";
		$params_userService ['tags'] = $tag_ids;
		$params_userService ['availability_time_period'] = 1;
		$params_userService ['displayed'] = 'true';
		$params_userService ['availability_rate'] = '95';
		$params_userService ["business_impact"] = $this->getObjModelesEvobserve ()
			->retrouve_id_business_impact ( $userService_itsm ['business_criticity'] );
		$this->onDebug ( $params_userService, 1 );
		return $params_userService;
	}

	/**
	 * @throws Exception
	 */
	public function recupere_userService(
			$userService_name): bool {
		$liste_userServices = $this->getListeUserServices ();
		foreach ( $liste_userServices as $userService ) {
			if ($userService->name == $userService_name) {
				return $userService->id;
			}
		}
		return $this->onError ( "UserService introuvable dans la liste : " . $userService_name, "", 1 );
	}

	/**
	 * Filtre les userServices recupere dans iTop pour gere l'ajout ou l'update dans evobserve. Update iTop avec l'id de evobserve s'il n'est pas present.
	 * @param array $liste_userServices_itsm
	 * @return bool|evobserve_userServices
	 * @throws Exception
	 */
	public function filtre_userServices_existant_dans_evobserve(
		array $liste_userServices_itsm): evobserve_userServices|bool|static {
		$this->onDebug ( $liste_userServices_itsm, 2 );
		$liste_convertie = $this->convertie_itsm_data ( $liste_userServices_itsm );
		// On recupere la liste des userServices dans evobserve
		$this->recupere_liste_userServices ();
		// on nettoie les presents
		if (empty ( $this->getListeUserServices () )) {
			return $this->onError ( "Liste de userServices vide", "", 1 );
		}
		foreach ( $this->getListeUserServices () as $userService ) {
			$this->onDebug ( $userService->name, 1 );
			if (! isset ( $userService->description )) {
				$this->onWarning ( "Pas de description pour : " . $userService->name );
				continue;
			}
			if (!preg_match('/NE PAS SUPPRIMER : (?<itsm>' . $this->getItopFormat() . '::[0-9].*) &/', $userService->description, $match)) {
				// $this->onWarning ( "Pas de liaison via additionnal_data : " . $userService->name );
				$this->onDebug ( "Pas de liaison via la documentation pour : " . $userService->name, 1 );
				continue;
			}
			// Si le userService de evobserve existe dans itsm
			if (isset ( $liste_convertie [$match ['itsm']] )) {
				$this->onDebug ( "Objet bien une reference, donc on verifie s'il faut faire un update de coservIT de " . $userService->name, 1 );
				$liste_convertie [$match ['itsm']] ['evobserve_userService_id'] = $userService->id;
			}
		}
		// Les userServices vide doivent etre ajoute, les autres ont un status de mise a jour
		$this->onDebug ( $liste_convertie, 2 );
		return $this->setListeUserServicesFiltres ( $liste_convertie );
	}

	/**
	 * @throws Exception
	 */
	public function creer_userService(
			$userService_itsm): static {
		$params_userService = $this->prepare_params_evobserve ( $userService_itsm );
		$this->onDebug ( $params_userService, 1 );
		if (empty ( $params_userService )) {
			return $this;
		}
		$this->onInfo ( "On ajoute " . $userService_itsm ['name'] );
		if ($this->getListeOptions ()
			->verifie_option_existe ( "dry-run-userService" ) !== false) {
			$this->onInfo ( "DRY-RUN : Pas d'ajout du userService " . $userService_itsm ['name'] );
			return $this;
		}
		$this->getObjUserService ()
			->creerUserService ( $params_userService );
		$this->setUserServiceDonnees ( $this->getObjUserService ()
			->getDonnees () );
		return $this;
	}

	/**
	 * @throws Exception
	 */
	public function gestion_userServices(
			$liste_userServices_itsm): static {
		// On filtre les userServices deja present dans evobserve
		$this->filtre_userServices_existant_dans_evobserve ( $liste_userServices_itsm );
		foreach ( $this->getListeUserServicesFiltres () as $userService_itsm ) {
			$this->onDebug ( $userService_itsm, 2 );
			// Evobserve : Le nom doit contenir uniquement des caracteres alphanumeriques, des espaces et les caracteres . _ - ( ) / : # [ ] * & +
			if (! isset ( $userService_itsm ['evobserve_userService_id'] )) {
				// On creer l'objet s'il n'existe pas, on le cree
				$this->onInfo ( "On creer " . $userService_itsm ["name"] );
				$this->creer_userService ( $userService_itsm );
			}
		}
		return $this;
	}

	/**
	 * *********************************** Gestion des updates *********************************
	 */
	public function prepare_params_evobserve_a_partir_evobserve(
			$userService_evobserve): array {
		$this->getObjUserService ()
			->reset_donnees ();
		// print_r ( $userService_evobserve );
		$params_userService = array ();
		$params_userService ["company"] = $userService_evobserve->company->id;
		$params_userService ['name'] = $userService_evobserve->name;
		$params_userService ["description"] = $userService_evobserve->description;
		$params_userService ['tags'] = array ();
		foreach ( $userService_evobserve->tags as $tag ) {
			$params_userService ['tags'] [] = $tag->id;
		}
		$params_userService ['availability_time_period'] = $userService_evobserve->availability_time_period->id;
		$params_userService ['displayed'] = $userService_evobserve->displayed;
		$params_userService ['availability_rate'] = '95';// $userService_evobserve->availability_rate;
		$params_userService ["business_impact"] = $userService_evobserve->business_impact;
		if (isset ( $userService_evobserve->blocking_hosts )) {
			$params_userService ["blocking_hosts"] = $userService_evobserve->blocking_hosts;
		}
		if (isset ( $userService_evobserve->degrading_hosts )) {
			$params_userService ["degrading_hosts"] = $userService_evobserve->degrading_hosts;
		}
		if (isset ( $userService_evobserve->blocking_unit_services )) {
			$params_userService ["blocking_unit_services"] = $userService_evobserve->blocking_unit_services;
		}
		if (isset ( $userService_evobserve->degrading_unit_services )) {
			$params_userService ["degrading_unit_services"] = $userService_evobserve->degrading_unit_services;
		}
		if (isset ( $userService_evobserve->blocking_user_services )) {
			$params_userService ["blocking_user_services"] = $userService_evobserve->blocking_user_services;
		}
		if (isset ( $userService_evobserve->degrading_user_services )) {
			$params_userService ["degrading_user_services"] = $userService_evobserve->degrading_user_services;
		}
		$this->onDebug ( $params_userService, 1 );
		return $params_userService;
	}

	/**
	 * @param $userService_evobserve
	 * @return evobserve_userServices
	 */
	public function update_userService(
			$userService_evobserve): static {
		$this->getObjUserService ()
			->reset_donnees ();
		$params_userService = $this->prepare_params_evobserve_a_partir_evobserve ( $userService_evobserve );
		$this->onInfo ( "On update " . $userService_evobserve->name );
		if ($this->getListeOptions ()
			->verifie_option_existe ( "dry-run-userService" ) !== false) {
			$this->onInfo ( "DRY-RUN : Pas d'update du userService " . $userService_evobserve->name );
			return $this;
		}
		try{
		$this->getObjUserService ()
			->setId ( $userService_evobserve->id )
			->updateUserService ( $params_userService );
		} catch ( Exception $e ) {
			$this->onWarning("Impossible de gerer : ".$userService_evobserve->name);
		}
		return $this;
	}

	/**
	 * Retrouve tous les userServices present dans evobserve
	 * @return array|bool
	 * @throws Exception
	 */
	public function retrouve_userServices_dans_evobserve(): array|bool {
		$liste_convertie = array ();
		// On recupere la liste des userServices dans evobserve
		$this->recupere_liste_userServices ();
		// on nettoie les presents
		if (empty ( $this->getListeUserServices () )) {
			return $this->onError ( "Liste de userServices vide", "", 1 );
		}
		foreach ( $this->getListeUserServices () as $userService ) {
			$this->onDebug ( $userService->name, 1 );
			if (! isset ( $userService->description )) {
				// $this->onDebug ( "Pas de description pour : " . $userService->name,1 );
				continue;
			}
			if (!preg_match('/(NE PAS SUPPRIMER : |)(?<itsm>[a-zA-Z].*::[0-9]{1,8})/', $userService->description, $match)) {
				$this->onDebug ( "Pas de liaison via la documentation pour : " . $userService->name, 1 );
				continue;
			}
			// Si le userService de evobserve existe dans itsm
			$liste_convertie [$match ['itsm']] = $userService;
		}
		// Les userServices vide doivent etre ajoute, les autres ont un status de mise a jour
		$this->onDebug ( $liste_convertie, 2 );
		return $liste_convertie;
	}

	/**
	 * @throws Exception
	 */
	public function prepare_dependencies(): bool|array {
		$liste_ApplicationSolutions = $this->getListeUserServicesFiltres ();
		$liste_userService_evobserve = $this->retrouve_userServices_dans_evobserve ();
		foreach ( $liste_ApplicationSolutions as $AS_to_update ) {
			// print_r ( $AS_to_update );
			foreach ( $AS_to_update ['functionalcis_list'] as $dependency ) {
				$codeItopCI = $dependency ['functionalci_id_finalclass_recall'] . "::" . $dependency ['functionalci_id'];
				$codeItopAS = $this->getItopFormat () . "::" . $AS_to_update ['id'];
				if (isset ( $liste_userService_evobserve [$codeItopCI] ) && isset ( $liste_userService_evobserve [$codeItopAS] )) {
					$liste_userService_evobserve [$codeItopAS]->business_impact = $this->getObjModelesEvobserve ()
						->retrouve_id_business_impact ( $AS_to_update ['business_criticity'] );
					// Gestion de la redondance
					$type = match ($AS_to_update ['redundancy']) {
						'disabled' => 'blocking',
						default => 'degrading',
					};
					//pour les dependances, il y a uniquement des userServices
					$type .= "_user_services";
					/* switch ($dependance ['functionalci_id_finalclass_recall']) { case 'ApplicationSolution' : $type .= "_user_services"; break; default : $type .= "_hosts"; } */
					// $this->getListeUserServicesFiltres()
					if (! isset ( $liste_userService_evobserve [$codeItopAS]->$type )) {
						$liste_userService_evobserve [$codeItopAS]->$type = array ();
						$liste_userService_evobserve [$codeItopAS]->evobserve_dependances = true;
					}
					$liste_userService_evobserve [$codeItopAS]->$type [] .= $liste_userService_evobserve [$codeItopCI]->id;
				} else {
					$this->onWarning ( "Un des deux paramettres n'existe pas dans CoservIT : " . $codeItopCI . " " . $codeItopAS );
				}
			}
		}
		// $liste_userService_evobserve = $this->retrouve_userServices_dans_evobserve ();
		// foreach ( $liste_userServices_relations_itsm as $dependance ) {
		// // Si un UserService existe
		// $codeItopCI = $dependance ['functionalci_id_finalclass_recall'] . "::" . $dependance ['functionalci_id'];
		// $codeItopAS = $this->getItopFormat () . "::" . $dependance ['applicationsolution_id'];
		// if ($dependance ['applicationsolution_id'] == '11568') {
		// print_r ( $liste_userService_evobserve [$codeItopCI] );
		// exit ( 0 );
		// }
		// if (isset ( $liste_userService_evobserve [$codeItopCI] ) && isset ( $liste_userService_evobserve [$codeItopAS] )) {
		// // Gestion de la redondance
		// // $this->getListeUserServicesFiltres()
		// if (! isset ( $liste_userService_evobserve [$codeItopAS]->blocking_user_services )) {
		// $liste_userService_evobserve [$codeItopAS]->blocking_user_services = array ();
		// $liste_userService_evobserve [$codeItopAS]->evobserve_dependances = true;
		// }
		// $liste_userService_evobserve [$codeItopAS]->blocking_user_services [] .= $liste_userService_evobserve [$codeItopCI]->id;
		// } else {
		// $this->onWarning ( "Un des deux paramettres n'existe pas dans CoservIT : " . $codeItopCI . " " . $codeItopAS );
		// }
		// }
		return $liste_userService_evobserve;
	}

	/**
	 * @throws Exception
	 */
	public function gestion_dependances_userServices(): static {
		// On prepare les dependances de chacun
		$liste_userService_lie = $this->prepare_dependencies ();
		foreach ( $liste_userService_lie as $userService_evobserve ) {
			$this->onDebug ( $userService_evobserve, 2 );
			// Evobserve : Le nom doit contenir uniquement des caracteres alphanumeriques, des espaces et les caracteres . _ - ( ) / : # [ ] * & +
			if (isset ( $userService_evobserve->evobserve_dependances )) {
				// Puis on autodate avec les dependances
				$this->onInfo ( "On update avec les dependances de : " . $userService_evobserve->name );
				$this->update_userService ( $userService_evobserve );
			}
		}
		return $this;
	}

	/**
	 * @throws Exception
	 */
	public function nettoie_userServices(
			$liste_userServices_obsoletes_itsm): static {
		// On filtre les userServices deja present dans evobserve
		$this->onDebug ( $liste_userServices_obsoletes_itsm, 2 );
		$liste_userServices = $this->filtre_userServices_existant_dans_evobserve ( $liste_userServices_obsoletes_itsm );
		$this->onDebug ( $liste_userServices, 1 );
		foreach ( $liste_userServices as $userService_itsm ) {
			$this->onDebug ( $userService_itsm, 2 );
			if (isset ( $userService_itsm ['evobserve_userService_id'] )) {
				// On supprime l'objet
				$this->onInfo ( "On supprime " . $userService_itsm ["name"] );
				$this->supprime_userService ( $userService_itsm ['evobserve_userService_id'] );
			}
		}
		return $this;
	}

	/**
	 * @param string $evobserve_userService_id
	 * @return $this
	 */
	public function supprime_userService(
		string $evobserve_userService_id): static {
		$this->onInfo ( "On supprime le userService " . $evobserve_userService_id );
		if ($this->getListeOptions ()
			->verifie_option_existe ( "dry-run-userService" ) !== false) {
			$this->onInfo ( "DRY-RUN : Pas de suppression du userService " . $evobserve_userService_id );
			return $this;
		}
		$this->getObjUserService ()
			->setId ( $evobserve_userService_id )
			->deleteUserService ();
		return $this;
	}

	/**
	 * NOT USED pour le moment
	 * @return evobserve_userServices
	 * @throws Exception
	 */
	public function creer_userService_for_host(): static {
		$donnees_userService = $this->getUserServiceDonnees ();
		$this->onInfo ( "On ajoute le userService de " . $donnees_userService->userService_alias );
		$tagids = array ();
		foreach ( $donnees_userService->tags as $tag ) {
			$tagids [] = $tag->id;
		}
		$parametres = array (
				"company" => $donnees_userService->company->id,
				"name" => "UserService_" . $donnees_userService->userService_alias,
				"availability_rate" => $donnees_userService->availability_rate,
				"availability_time_period" => 1,
				"business_impact" => $donnees_userService->business_impact,
				"tags" => $tagids,
				"displayed" => false,
				"blocking_userServices" => array (
						$donnees_userService->id
				),
				"degrading_unit_services" => $donnees_userService->services
		);
		if (isset ( $donnees_userService->additional_data )) {
			$parametres ["description"] = $donnees_userService->additional_data;
		} else {
			$parametres ["description"] = $donnees_userService->description;
		}
		$this->getObjUserService ()
			->creerUserService ( $parametres );
		return $this;
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * @codeCoverageIgnore
	 * @return evobserve\UserService|null
	 */
	public function &getObjUserService(): ?evobserve\UserService {
		return $this->userService;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setObjUserService(
			$ObjUserService): static {
		$this->userService = $ObjUserService;
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
	public function getListeUserServices(): array {
		return $this->liste_userServices;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setListeUserServices(
			$liste_userServices): static {
		$this->liste_userServices = $liste_userServices;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getListeUserServicesFiltres(): array {
		return $this->liste_userServices_filtres;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setListeUserServicesFiltres(
			$liste_userServices_filtres): static {
		$this->liste_userServices_filtres = $liste_userServices_filtres;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getUserServiceDonnees(): array {
		return $this->userService_donnees;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setUserServiceDonnees(
			$userService_donnees): static {
		$this->userService_donnees = $userService_donnees;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getUserServiceMarque(): string {
		return $this->userService_brand;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setUserServiceMarque(
			$userService_brand): static {
		$this->userService_brand = $userService_brand;
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
			'evobserve_userServices :',
			'	--force_update_userServices  Force la mise a jour des CIs',
			'	--dry-run-userService  Ne met pas a jour le monitoring des userServices'
		];
		return $help;
	}
}