<?php
/**
 * @author dvargas
 */
use Zorille\framework\abstract_log;
use Zorille\framework\options;
use Zorille\evobserve;

/**
 * class evobserve_companies
 *
 * @package Euclyde
 * @subpackage evobserve_companies
 */
class evobserve_companies extends standard_evobserve {
	/**
	 * @access private
	 * @var evobserve\Company
	 */
	private $company = null;
	/**
	 * @access private
	 * @var array
	 */
	private $boxes = array ();
	/**
	 * @access private
	 * @var boolean
	 */
	private $update_boxes = false;

	/**
	 * ********************* Creation de l'objet ********************
	 */
	/**
	 * Instancie un objet de type VMware\liste_ci.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param gestion_client $gestion_client
	 * @param evobserve\wsclient $evobserve_webservice
	 * @param Boolean|string $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return self
	 */
	static function &creer_evobserve_companies(
		options           &$liste_option,
		gestion_client    &$gestion_client,
		evobserve\wsclient &$evobserve_webservice,
		bool|string       $sort_en_erreur = false,
		string            $entete = __CLASS__): static {
		abstract_log::onDebug_standard ( __METHOD__, 1 );
		$objet = new evobserve_companies ( $sort_en_erreur, $entete );
		return $objet->_initialise ( array (
				"options" => $liste_option,
				"gestion_client" => $gestion_client,
				"evobserve:wsclient" => $evobserve_webservice
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
        array $liste_class): static
	{
		parent::_initialise ( $liste_class );
		$this->setObjCompany ( evobserve\Company::creer_Company ( $liste_class ['options'], $liste_class ['evobserve:wsclient'] ) );
		$this->getObjCompany ()
			->setId ( 2 );
		$this->recupere_liste_site ();
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
	 * @throws Exception
	 */
	public function recupere_liste_hosts(): static
	{
		$this->getObjCompany ()
			->recupere_company_hosts ( array (
				'id' => $this->getObjCompany ()
					->getId (),
				"comptesub" => 'true'
		) );
		return $this;
	}

	/**
	 * @throws Exception
	 */
	public function recupere_liste_services(): static
	{
		$this->onDebug ( __METHOD__, 1 );
		$this->getObjCompany ()
			->recupere_company_services ( array (
				'id' => $this->getObjCompany ()
					->getId (),
				"comptesub" => 'true'
		) );
		return $this;
	}

	/**
	 * @throws Exception
	 */
	public function recupere_liste_site(): static
	{
		$this->onDebug ( __METHOD__, 1 );
		$this->getObjCompany ()
			->recupere_company_tree ( array (
				'id' => $this->getObjCompany ()
					->getId ()
		) );
		return $this;
	}

	/**
	 * ***************************** Gestion des collecteurs *******************************
	 */

	/**
	 * @throws Exception
	 */
	public function updateCollectors(): static
	{
		$this->onDebug ( __METHOD__, 1 );
		$boxes = evobserve\Boxes::creer_Boxes ( $this->getListeOptions (), $this->getObjetEvobserveWsclient () );
		if (! $this->getUpdateBoxes ()) {
			// Pas d'update donc on quitte la fonction
			$this->onInfo ( "Pas d'update de box a faire" );
			return $this;
		}
		if (empty ( $this->getListeBoxes () )) {
			$this->onInfo ( "Liste integrale des collecteurs" );
			// Si je n'ai pas de liste de boxe, j'update toutes les boxes par defaut (en cas de service ajoute par exemple)
			$boxes->updateConfigurationToutesBoxes ();
			return $this;
		}
		$this->onDebug ( "Liste filtree des collecteurs " . print_r ( $this->getListeBoxes (), true ), 1 );
		foreach ( $this->getListeBoxes () as $box_name => $boxId ) {
			$this->onInfo ( "On update " . $box_name );
			$boxes->updateConfiguration ( array (
					"collectorIds" => array (
							$boxId
					)
			) );
		}
		return $this;
	}

	public function ajouteCollectorId(
			$id): static
	{
		$liste_collectors = $this->getListeBoxes ();
		$liste_collectors [$id] = $id;
		return $this->setListeBoxes ( $liste_collectors );
	}

	/**
	 * ***************************** Gestion des classes standard a creer/updater *******************************
	 */
	public function manage_update_notifications(
			&$params,
			$params_prod): static
	{
		$this->onDebug ( __METHOD__, 1 );
		if (! empty ( $params_prod ) && $params_prod->notifications_enabled) {
			$params ['notifications'] ['enabled'] = true;
			if (isset ( $params_prod->notification_options )) {
				foreach ( $params_prod->notification_options as $pos => $option ) {
					$params ['notifications'] ['options'] [$pos] = $option->id;
				}
			}
			if (isset ( $params_prod->contacts )) {
				foreach ( $params_prod->contacts as $pos => $option ) {
					$params ['notifications'] ['contacts'] [$pos] = $option->id;
				}
			}
			if (isset ( $params_prod->contact_groups )) {
				foreach ( $params_prod->contact_groups as $pos => $option ) {
					$params ['notifications'] ['contact_groups'] [$pos] = $option->id;
				}
			}
			$params ['notifications'] ['time_period'] = $params_prod->notification_time_period->id;
			if (isset ( $params_prod->notification_interval )) {
				$params ['notifications'] ['interval'] = $params_prod->notification_interval;
			}
			if (isset ( $params_prod->low_flap_threshold )) {
				$params ['notifications'] ['low_flap_threshold'] = $params_prod->low_flap_threshold;
			}
			if (isset ( $params_prod->high_flap_threshold )) {
				$params ['notifications'] ['high_flap_threshold'] = $params_prod->high_flap_threshold;
			}
		}
		return $this;
	}

	/*
	 * {
	 check_command_arguments	[...]
	 action_command_arguments	[...]
	 first_notification_delay	integer
	 services	[...]
	 id	integer
	 company	Company4{...}
	 collector	Collector2{...}
	 host_alias	string
	 host_mode	string
	 host_address	string
	 host_category	HostCategory2{...}
	 documentation	string
	 instructions	string
	 additional_data	string
	 itsm_id	string
	 description	string
	 normal_check_interval	integer
	 max_check_attempts	integer
	 retry_check_interval	integer
	 tags	[...]
	 action_template	EventTemplate{...}
	 check_time_period	TimePeriod2{...}
	 notification_time_period	TimePeriod2{...}
	 availability_time_period	TimePeriod2{...}
	 business_impact	integer
	 availability_rate	number($float)
	 check_template	ServiceTemplate{...}
	 monitoring_account_overloaded	boolean
	 active_checks_enabled	boolean
	 passive_checks_enabled	boolean
	 parent_hosts	[...]
	 children_hosts	[...]
	 }
	 */
	public function manage_notifications_hosts(
			&$params,
			$params_prod = ""): static
	{
		$this->onDebug ( __METHOD__, 1 );
		if (! empty ( $params_prod ) && $params_prod->notifications_enabled) {
			$this->manage_update_notifications ( $params, $params_prod );
		} else {
			$params ['notifications'] = array ();
			$params ['notifications'] ['enabled'] = true;
			$params ['notifications'] ['time_period'] = 96;
			// intervel entre 2 notifs
			$params ['notifications'] ['interval'] = 5;
			// $params ['notifications']['time_period']['name']='EDC_24-7_LMMJVSD';
			$params ['notifications'] ['options'] [0] = 'd';
			// $params ['notifications']['options'][0]['value']='Down';
			$params ['notifications'] ['options'] [1] = 'u';
			// $params ['notifications']['options'][1]['id']='Unknown';
			$params ['notifications'] ['contacts'] [0] = 11;
			// $params ['notifications']['contacts'][0]['name']='Noc_Euclyde';
			$params ['notifications'] ['contact_groups'] [0] = 1;
			// $params ['notifications']['contact_groups'][0]['name']='NOC';
		}
		return $this;
	}

	public function manage_notifications_services(
			&$params,
			$params_prod = array ()): static
	{
		$this->onDebug ( __METHOD__, 1 );
		if (! empty ( $params_prod ) && $params_prod->notifications_enabled) {
			$this->manage_update_notifications ( $params, $params_prod );
		} else {
			$params ['notifications'] = array ();
			$params ['notifications'] ['enabled'] = true;
			$params ['notifications'] ['time_period'] = 96;
			// $params ['notifications']['time_period']['name']='EDC_24-7_LMMJVSD';
			// intervel entre 2 notifs
			$params ['notifications'] ['interval'] = 5;
			$params ['notifications'] ['options'] [0] = 'c';
			// $params ['notifications']['options'][0]['value']='Critical';
			$params ['notifications'] ['options'] [1] = 'u';
			// $params ['notifications']['options'][1]['id']='Unknown';
			$params ['notifications'] ['options'] [2] = 'w';
			// $params ['notifications']['options'][1]['id']='Warning';
			$params ['notifications'] ['contacts'] [0] = 11;
			// $params ['notifications']['contacts'][0]['name']='Noc_Euclyde';
			$params ['notifications'] ['contact_groups'] [0] = 1;
			// $params ['notifications']['contact_groups'][0]['name']='NOC';
		}
		return $this;
	}

	public function manage_escalades(
			&$params,
			$params_prod = ""): static
	{
		$this->onDebug ( __METHOD__, 1 );
		if (! empty ( $params_prod ) && isset ( $params_prod->escalations ) && ! empty ( $params_prod->escalations )) {
			foreach ( $params_prod->escalations as $pos => $escalade ) {
				$this->onDebug ( $escalade, 2 );
				if (isset ( $escalade->first_notification )) {
					$params ['escalations'] [$pos] ['first_notification'] = $escalade->first_notification;
				}
				$params ['escalations'] [$pos] ['level'] = $escalade->level;
				if (isset ( $escalade->notification_interval )) {
					$params ['escalations'] [$pos] ['notification_interval'] = $escalade->notification_interval;
				}
				if (isset ( $escalade->contacts )) {
					foreach ( $escalade->contacts as $pos => $option ) {
						$params ['escalations'] [$pos] ['contacts'] [$pos] = $option->id;
					}
				}
				if (isset ( $escalade->contact_groups )) {
					foreach ( $escalade->contact_groups as $pos => $option ) {
						$params ['escalations'] [$pos] ['contact_groups'] [$pos] = $option->id;
					}
				}
			}
		} else {
			$params ['escalations'] = array ();
		}
		return $this;
	}

	public function manage_first_notification(
			&$params,
			$params_prod = ""): static
	{
		$this->onDebug ( __METHOD__, 1 );
		if (! empty ( $params_prod )) {
			// le first_notification_delay est un information suite a ces parametres
			if (isset ( $escalade->retry_check_interval )) {
				$params ['retry_check_interval'] = $params_prod->retry_check_interval;
			}
			if (isset ( $escalade->max_check_attempts )) {
				$params ['max_check_attempts'] = $params_prod->max_check_attempts;
			}
		} else {
			$params ['retry_check_interval'] = $params ['normal_check_interval'];
			$params ['max_check_attempts'] = 1;
		}
		return $this;
	}

	public function manage_check_time_period(
			&$params,
			$params_prod = ""): static
	{
		$this->onDebug ( __METHOD__, 1 );
		if (! empty ( $params_prod )) {
			// le first_notification_delay est un information suite a ces parametres
			if (isset ( $escalade->check_time_period )) {
				$params ['check_time_period'] = $params_prod->check_time_period->id;
			}
			if (isset ( $escalade->availability_time_period )) {
				$params ['availability_time_period'] = $params_prod->availability_time_period->id;
			}
			if (isset ( $escalade->normal_check_interval )) {
				$params ['normal_check_interval'] = $params_prod->normal_check_interval;
			}
		} else {
			$params ['check_time_period'] = 96;
			$params ['availability_time_period'] = 96;
		}
		return $this;
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * @codeCoverageIgnore
	 * @return evobserve\Company|null
	 */
	public function &getObjCompany(): ?evobserve\Company
	{
		return $this->company;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setObjCompany(
			$ObjCompany): static
	{
		$this->company = $ObjCompany;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function getListeBoxes(): array
	{
		return $this->boxes;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setListeBoxes(
			$boxes): static
	{
		$this->boxes = $boxes;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 * @return boolean
	 */
	public function getUpdateBoxes(): bool
	{
		return $this->update_boxes;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setUpdateBoxes(
			$update_boxes): static
	{
		$this->update_boxes = $update_boxes;
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
		$help [__CLASS__] ["text"] [] .= "evobserve_companies :";
		return $help;
	}
}