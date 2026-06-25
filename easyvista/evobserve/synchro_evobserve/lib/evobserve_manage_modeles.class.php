<?php
/**
 * @author dvargas
 */
use Zorille\framework\abstract_log;
use Zorille\framework\options;
use Zorille\evobserve;
/**
 * class evobserve_manage_modeles
 *
 * @package Euclyde
 * @subpackage evobserve_manage_modeles
 */
class evobserve_manage_modeles extends abstract_log {
	/**
	 * @access private
	 * @var evobserve\Tags
	 */
	private $objtags = null;
	/**
	 * @access private
	 * @var evobserve\ModeleHosts
	 */
	private $objModeleHost = null;
	/**
	 * @access private
	 * @var evobserve\ModeleServices
	 */
	private $objModeleService = null;

	/**
	 * ********************* Creation de l'objet ********************
	 */
	/**
	 * Instancie un objet
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param Boolean|string $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return $this
	 * @throws Exception
	 */
	static function &creer_evobserve_manage_modeles(
		options           &$liste_option,
		evobserve\wsclient &$evobserve_webservice,
		gestion_client    &$gestion_client,
		bool|string       $sort_en_erreur = false,
		string            $entete = __CLASS__): evobserve_manage_modeles|static
	{
		abstract_log::onDebug_standard ( __METHOD__, 1 );
		$objet = new evobserve_manage_modeles ( $sort_en_erreur, $entete );
		return $objet->_initialise ( array (
				"options" => $liste_option,
				"evobserve:wsclient" => $evobserve_webservice
		) );
	}

	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return evobserve_manage_modeles
	 * @throws Exception
	 */
	public function &_initialise(
        array $liste_class): static
	{
		parent::_initialise ( $liste_class );
		$this->setObjetEvobserveTags ( evobserve\Tags::creer_Tags ( $liste_class ['options'], $liste_class ['evobserve:wsclient'] ) )
			->setObjetEvobserveModelesHost ( evobserve\ModeleHosts::creer_ModeleHosts ( $liste_class ['options'], $liste_class ['evobserve:wsclient'] ) )
			->setObjetEvobserveModelesService ( evobserve\ModeleServices::creer_ModeleServices ( $liste_class ['options'], $liste_class ['evobserve:wsclient'] ) );
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
	 * Extrait des parametres d'un liste d'option
	 * @codeCoverageIgnore
	 * @param array|string $chemin_option
	 * @return boolean string array
	 * @throws Exception
	 */
	protected function _valideOption(
		array|string $chemin_option,
		             $valeur_defaut = false): mixed
	{
		$this->onDebug ( __METHOD__, 1 );
		// Si je n'ai pas de valeur par defaut, je verifie la presence de la variable
		if ($valeur_defaut === false && $this->getListeOptions ()
			->verifie_variable_standard ( $chemin_option ) === false) {
			if (is_array ( $chemin_option )) {
				$chemin_option = implode ( "_", $chemin_option );
			}
			return $this->onError ( "Il manque le parametre : " . $chemin_option );
		}
		// On revoi la valeur de la variable
		$datas = $this->getListeOptions ()
			->renvoi_variables_standard ( $chemin_option, $valeur_defaut );
		if (is_array ( $datas ) && isset ( $datas ["#comment"] )) {
			unset ( $datas ["#comment"] );
		}
		return $datas;
	}

	/**
	 * @throws Exception
	 */
	public function retrouve_tags(
			$liste_tags): array
	{
		$tag_ids = array ();
		$liste = explode ( "\n", $liste_tags );
		foreach ( $liste as $tag ) {
			if (! empty ( $tag )) {
				$tag_ids [] = $this->getObjetEvobserveTags ()
					->retrouve_id_tag ( trim ( $tag ) );
			}
		}
		return $tag_ids;
	}

	/**
	 * @throws Exception
	 */
	public function prepare_tags(
			$name): array
	{
		$tag_ids = array ();
		//Gestion des TAGs
		return $tag_ids;
	}

	/**
	 * @throws Exception
	 */
	public function prepare_tags_userService(
			$name): array
	{
		$tag_ids = array ();
		$liste_tags = [];//Retrouve la liste de tag
		foreach ( $liste_tags as $tag ) {
			try {
				$tag = trim ( $tag );
				if (! empty ( $tag )) {
					$tag_ids [] = $this->getObjetEvobserveTags ()
						->retrouve_id_tag ( $tag );
				}
			} catch ( Exception $e ) {
				// Si le tag n'existe pas, on boucle sans erreur
				$this->onWarning ( "Le Tag " . $tag . " n'existe pas." );
				continue;
			}
		}
		return $tag_ids;
	}

	/**
	 * ***************************** HOSTS *******************************
	 */
	/**
	 * Prepare la liste de modele du host a ajouter
	 * @param $modele_host
	 * @return array|int ID du modele de host
	 * @throws Exception
	 */
	public function retrouve_modele_host(
			$modele_host): array|int
	{
		return array (
				$this->getObjetEvobserveModelesHost ()
					->retrouve_id_modeleHost ( $modele_host )
		);
	}

	/**
	 * Prepare la liste de modele du host a ajouter
	 * @param array $params
	 * @param $nom_host
	 * @param $format
	 * @param $tag
	 * @param string $brand
	 * @return evobserve_manage_modeles
	 * @throws Exception
	 */
	public function prepare_modeles_host(
		array  &$params,
		       $nom_host,
		       $format,
		       $tag,
		string $brand = ""): static
	{
		// Un check_template est unitaire
		$check_template = match ($format) {
			'VirtualMachine' => 'Device not pingable',
			default => $this->_valideOption(array(
				"evobserve",
				"host",
				"check_template"
			), 'ping'),
		};
		$params ['check_template'] = $this->getObjetEvobserveModelesHost ()
			->retrouve_id_checkTemplate ( $check_template );
		// Le modele de host est unitaire
		$params ['host_templates'] = array (
				$this->getObjetEvobserveModelesHost ()
					->retrouve_id_modeleHost ( $this->prepare_nom_modele_host ( $nom_host, $format, $tag, $brand ) )
		);
		return $this;
	}

	public function host_check_command_arguments_value(
			$type,
			$liste_valeur) {
		switch ($type) {
			case 'Ping' :
				return $this->modele_ping ( $liste_valeur );
			case 'Device not pingable' :
			default :
			// Device not pingable
		}
		return array ();
	}

	/**
	 * @throws Exception
	 */
	public function host_check_command_arguments(
			$nom_host,
			$format,
			$tag,
			$brand,
			$liste_valeur): mixed {
		$host = $this->prepare_nom_modele_host ( $nom_host, $format, $tag, $brand );
		$function_host = str_replace ( "-", "_", $host );
		$this->onDebug ( "Nom du modele de host recherche : " . $function_host, 1 );
		if (method_exists ( "evobserve_manage_modeles", $function_host )) {
			return $this->$function_host ( $liste_valeur );
		}

		return $this->modele_ping ( $liste_valeur );
	}

	private function prepare_nom_modele_host(
			$nom_host,
			$format,
			$tag,
			$brand = "") {
		$this->onDebug ( __METHOD__, 1 );
		switch ($format) {
			case 'VCenter' :
			case 'VirtualMachine' :
				$this->onDebug ( "Nom du modele de host pour " . $nom_host . " : " . "EDC-VMWare-" . $format . "-" . $tag, 1 );
				return "EDC-VMWare-" . $format . "-" . $tag;
				break;
			case 'Hypervisor' :
				$this->onDebug ( "Nom du modele de host pour " . $nom_host . " : " . "EDC-VMWare-ESXi-" . $tag, 1 );
				return "EDC-VMWare-ESXi-" . $tag;
				break;
			default :
				return $this->prepare_nom_modele ( $nom_host, $format, $tag, $brand, 'EDC-Equipement generique' );
		}
	}

	/**
	 * ***************************** Modeles host *******************************
	 */
	/**
	 * Prepare la liste de modele du host a ajouter
	 * @param $modele_check
	 * @return integer ID du modele de host
	 * @throws Exception
	 */
	public function retrouve_modele_check_host(
			$modele_check): int
	{
		return $this->getObjetEvobserveModelesHost ()
			->retrouve_id_checkTemplate ( $modele_check );
	}

	public function modele_ping(
			&$liste_valeur) {
		$this->onDebug ( __METHOD__, 1 );
		if (empty ( $liste_valeur [0] ['name'] ) || $liste_valeur [0] ['name'] != 'Seuil d\'alerte (ms)') {
			$liste_valeur = array (
					array (
							'name' => 'Seuil d\'alerte (ms)',
							'value' => 8
					),
					array (
							'name' => 'Seuil critique (ms)',
							'value' => 10
					)
			);
		}
		return $liste_valeur;
	}

	/**
	 * ***************************** Modeles host VMWare *******************************
	 */
	/**
	 * on ne ping pas les VM monitoree via VCenter
	 * @param array $liste_valeur
	 * @return array
	 */
	public function EDC_VMWare_VirtualMachine_VIRTUALISATION(
		array $liste_valeur): array
	{
		$this->onDebug ( __METHOD__, 1 );
		return array ();
	}

	/**
	 * ***************************** Modeles host Network *******************************
	 */
	/**
	 * ***************************** Modeles business impact *******************************
	 */
	public function retrouve_id_business_impact(
			$business_impact): int
	{
		switch (strtolower ( $business_impact )) {
			case 'low' :
				return 0;
			case 'medium' :
				return 1;
		}
		// Par default 'high'
		return 2;
	}

	/**
	 * ***************************** SERVICES *******************************
	 */
	/**
	 * Prepare la liste de modele du host a ajouter
	 * @param $modele_service
	 * @return integer ID du modele de host
	 * @throws Exception
	 */
	public function retrouve_modele_service(
			$modele_service): int
	{
		return $this->getObjetEvobserveModelesService ()
			->retrouve_id_ModeleServices ( $modele_service );
	}

	/**
	 * Prepare la liste de modele du service a ajouter
	 * @param $nom_host
	 * @param $format
	 * @param $tag
	 * @param string $brand
	 * @return array
	 * @throws Exception
	 */
	public function prepare_modeles_service(
		$nom_host,
		$format,
		$tag,
		string $brand = ""): array
	{
		return $this->getObjetEvobserveModelesService ()
			->retrouve_id_ModeleServices ( $this->prepare_nom_modele_service ( $nom_host, $format, $tag, $brand ) );
	}

	public function service_check_command_arguments(
			$service_params): array
	{
		$retour = array ();
		$params = explode ( "\n", $service_params );
		foreach ( $params as $param ) {
			$values = explode ( "::", $param );
			if (empty ( $values [0] ) || ! isset ( $values [1] ) || empty ( $values [1] )) {
				continue;
			}
			switch ($values [0]) {
				case 'normal_check_interval' :
				case 'check_time_period' :
				case 'notification_time_period' :
				case 'availability_time_period' :
				case 'availability_rate' :
				case 'business_impact' :
					break;
				default :
					$retour [] = array (
							'name' => $values [0],
							'value' => (isset ( $values [1] )) ? $values [1] : ''
					);
			}
		}
		$this->onDebug ( $retour, 2 );
		return $retour;
	}

	// public function service_check_command_arguments(
	// $nom_host,
	// $format,
	// $tag,
	// $brand,
	// $liste_valeur) {
	// $service = $this->prepare_nom_modele_service ( $nom_host, $format, $tag );
	// $function_service = str_replace ( "-", "_", $service );
	// if (method_exists ( "evobserve_manage_modeles", $function_service )) {
	// return $this->$function_service ( $liste_valeur );
	// }
	// return $this->onError ( "Pas de valeurs de monitoring a fournir par defaut.", "", 1 );
	// }
	/**
	 * @param string $nom_host
	 * @param string $format
	 * @param string $tag
	 * @param string $brand
	 * @param string $default
	 * @return string
	 * @exception
	 * @throws Exception
	 */
	private function prepare_nom_modele_service(
		string $nom_host,
		string $format,
		string $tag,
		string $brand = "",
		string $default = ""): string
	{
		$this->onDebug ( __METHOD__, 1 );
		return $this->prepare_nom_modele ( $nom_host, $format, $tag, $brand, $default );
	}

	/**
	 * @throws Exception
	 */
	private function prepare_nom_modele(
			$nom_host,
			$format,
			$tag,
			$brand = "",
			$default = "") {
		if (! empty ( $brand )) {
			$modele = "EDC-" . $brand . "-" . $format . "-" . $tag;
		} else {
			$type = $this->getObjGestionClient ()
				->retrouve_type_dans_nom ( $nom_host );
			if (! empty ( $type )) {
				$modele = "EDC-" . $type . "-" . $format . "-" . $tag;
			} elseif (! empty ( $default )) {
				$modele = $default;
			} else {
				return $this->onError ( "Pas modele definissable avec les informations fournis : " . $nom_host . " " . $format . " " . $tag . " " . $brand . " " . $default );
			}
		}
		$this->onDebug ( "Nom du modele de service : " . $modele, 1 );
		return $modele;
	}

	/**
	 * ***************************** Modeles services *******************************
	 */
	public function EDC_PDPM_PDU_ENERGIE(
			$liste_valeur): array
	{
		$this->onDebug ( __METHOD__, 1 );
		$retour = array (
			array (
				'name' => 'OID PDPM product index',
				'value' => $liste_valeur ['oid']
			)
		);
		if (isset ( $liste_valeur ['prefixe'] )) {
			$retour[] = array(
				'name' => 'Préfixe',
				'value' => $liste_valeur ['prefixe']
			);
		}
		if (isset ( $liste_valeur ['unite'] )) {
			$retour[] = array(
				'name' => 'Unité',
				'value' => $liste_valeur ['unite']
			);
		}
		return $retour;
	}

	public function EDC_DIRIS_PDU_ENERGIE(
			$liste_valeur): array
	{
		$this->onDebug ( __METHOD__, 1 );
		$retour = array (
				array (
						'name' => 'OID Diris product index',
						'value' => $liste_valeur ['oid']
				)
		);
		if (isset ( $liste_valeur ['prefixe'] )) {
			$retour[] = array(
				'name' => 'Préfixe',
				'value' => $liste_valeur ['prefixe']
			);
		}
		if (isset ( $liste_valeur ['unite'] )) {
			$retour[] = array(
				'name' => 'Unité',
				'value' => $liste_valeur ['unite']
			);
		}
		return $retour;
	}

	public function EDC_WAGO_PDU_ENERGIE(
			$liste_valeur): array
	{
		$this->onDebug ( __METHOD__, 1 );
		$retour = array (
				array (
						'name' => 'Id Wago de collecte',
						'value' => $liste_valeur ['oid']
				),
				array (
						'name' => 'Diviseur',
						'value' => $liste_valeur ['diviseur']
				)
		);
		if (isset ( $liste_valeur ['prefixe'] )) {
			$retour[] = array(
				'name' => 'Préfixe',
				'value' => $liste_valeur ['prefixe']
			);
		}
		if (isset ( $liste_valeur ['unite'] )) {
			$retour[] = array(
				'name' => 'Unité',
				'value' => $liste_valeur ['unite']
			);
		}
		return $retour;
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * @codeCoverageIgnore
	 * @return evobserve\Tags|null
	 */
	public function &getObjetEvobserveTags(): ?evobserve\Tags
	{
		return $this->objtags;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setObjetEvobserveTags(
			&$objtags): static
	{
		$this->objtags = $objtags;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 * @return evobserve\ModeleHosts|null
	 */
	public function &getObjetEvobserveModelesHost(): ?evobserve\ModeleHosts
	{
		return $this->objModeleHost;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setObjetEvobserveModelesHost(
			&$objModeleHost): static
	{
		$this->objModeleHost = $objModeleHost;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 * @return evobserve\ModeleServices|null
	 */
	public function &getObjetEvobserveModelesService(): ?evobserve\ModeleServices
	{
		return $this->objModeleService;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setObjetEvobserveModelesService(
			&$objModeleService): static
	{
		$this->objModeleService = $objModeleService;
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
		$help [__CLASS__] ["text"] [] .= "evobserve_manage_modeles :";
		return $help;
	}
}
