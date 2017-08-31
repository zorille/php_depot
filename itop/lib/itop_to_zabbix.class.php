<?php
/**
 * Gestion de itop.
 * @author dvargas
 */
/**
 * class itop_to_zabbix
 *
 * @package Lib
 * @subpackage itop
 */
class itop_to_zabbix extends abstract_log {
	/**
	 * var privee
	 *
	 * @access private
	 * @var string
	 */
	private $machine = "";
	/**
	 * var privee
	 *
	 * @access private
	 * @var string
	 */
	private $managementip = "";
	/**
	 * var privee
	 *
	 * @access private
	 * @var string
	 */
	private $jmx_interface = false;
	/**
	 * var privee
	 *
	 * @access private
	 * @var zabbix_host_administration
	 */
	private $objet_zabbix_host_administration = NULL;

	/**
	 * ********************* Creation de l'objet ********************
	 */
	/**
	 * Instancie un objet de type itop_to_zabbix. @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param zabbix_connexion $zabbix_connexion Reference sur une connexion au zabbix
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet gestion_connexion_url
	 * @return itop_to_zabbix
	 */
	static function &creer_itop_to_zabbix(&$liste_option, &$zabbix_connexion, $sort_en_erreur = false, $entete = __CLASS__) {
		abstract_log::onDebug_standard ( __METHOD__, 1 );
		$objet = new itop_to_zabbix ( $sort_en_erreur, $entete );
		$objet ->_initialise ( array ( 
				"options" => $liste_option, 
				"zabbix_connexion" => $zabbix_connexion ) );
		
		return $objet;
	}

	/**
	 * Initialisation de l'objet @codeCoverageIgnore
	 * @param array $liste_class
	 * @return itop_to_zabbix
	 */
	public function &_initialise($liste_class) {
		parent::_initialise ( $liste_class );
		
		$local_liste_option = clone $liste_class ["options"];
		$this ->setObjetZabbixHostAdministration ( zabbix_host_administration::creer_zabbix_host_administration ( $local_liste_option, $liste_class ["zabbix_connexion"] ->getObjetZabbixWsclient () ) ) 
			->prepare_donnees_zabbix ();
		return $this;
	}

	/**
	 * ********************* Creation de l'objet ********************
	 */
	
	/**
	 * Constructeur. @codeCoverageIgnore
	 * @param string|Bool $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete entete de log
	 * @return true
	 */
	public function __construct($sort_en_erreur = false, $entete = __CLASS__) {
		// Gestion de serveur_datas
		parent::__construct ( $sort_en_erreur, $entete );
	}

	/**
	 * Prepare les parametres par defaut necessaire a zabbix
	 * @return itop_to_zabbix
	 */
	public function prepare_donnees_zabbix() {
		$this ->getObjetZabbixHostAdministration () 
			->getListeOptions () 
			->setOption ( "zabbix_host_status", 'monitored' );
		$this ->getObjetZabbixHostAdministration () 
			->getListeOptions () 
			->setOption ( "zabbix_interfaces", '' );
		$this ->getObjetZabbixHostAdministration () 
			->getListeOptions () 
			->setOption ( "zabbix_host_groups", array () );
		$this ->getObjetZabbixHostAdministration () 
			->getListeOptions () 
			->setOption ( "zabbix_templates", array () );
		
		return $this;
	}

	/**
	 * Prepare les donnees communes a toutes les machines
	 * @param string $name
	 * @param string $interfaces
	 * @param string $proxy
	 * @return itop_to_zabbix
	 * @throws Exception
	 */
	public function prepare_commun_machine($name, $interfaces = "agent|oui|10050", $proxy = "") {
		//On recolte les donnees dans l'export rest de itop
		if (empty ( $this ->getManagementIp () )) {
			return $this ->onError ( "Il faut une IP valide de management" );
		}
// 		$this ->getObjetZabbixHostAdministration () 
// 			->getObjetZabbixHostInterfaces () 
// 			->getObjetHostInterfaceRef () 
// 			->setIP ( $this ->getManagementIp () );
		$this ->getObjetZabbixHostAdministration () 
			->getListeOptions () 
			->setOption ( "zabbix_interface_ip", $this ->getManagementIp () );
		if ($proxy != "") {
			$this ->getObjetZabbixHostAdministration () 
				->getListeOptions () 
				->setOption ( "zabbix_proxy_name", $proxy );
		}
		$this ->getObjetZabbixHostAdministration () 
			->getListeOptions () 
			->setOption ( "zabbix_interfaces", $interfaces );
		$this ->getObjetZabbixHostAdministration () 
			->getListeOptions () 
			->setOption ( "zabbix_host_host", $name );
		$this ->getObjetZabbixHostAdministration () 
			->getListeOptions () 
			->setOption ( "zabbix_host_name", $name );
		
		return $this;
	}

	/**
	 * Creer la machine dans zabbix
	 * @param string $name
	 * @param string $interfaces
	 * @param string $proxy
	 * @param array $groupes Liste des groupes
	 * @param array $templates Liste des templates
	 * @return itop_to_zabbix
	 */
	public function creer_machine($name, $interfaces, $proxy, $groupes, $templates) {
		//On recolte les donnees dans l'export rest de itop
		$this ->prepare_commun_machine ( $name, $interfaces, $proxy );
		$this ->getObjetZabbixHostAdministration () 
			->getListeOptions () 
			->setOption ( "zabbix_host_groups", $groupes );
		$this ->getObjetZabbixHostAdministration () 
			->getListeOptions () 
			->setOption ( "zabbix_templates", $templates );
		//On ajoute le host dans Zabbix
		$this ->getObjetZabbixHostAdministration () 
			->ajoute_host ();
		
		return $this;
	}

	public function ajoute_VirtualMachine($ci, $proxy = "") {
		//On recolte les donnees dans l'export rest de itop
		$this ->creer_machine ( $ci ['name'], 'agent|oui|10050', $proxy, array ( 
				$this ->getMachineName (), 
				'Virtual Machines', 
				'System' ), array ( 
				'Template OS ' . $ci ['osfamily_name'] ) );
		
		return $this;
	}

	public function ajoute_Server($ci, $proxy = "") {
		//On recolte les donnees dans l'export rest de itop
		$this ->creer_machine ( $ci ['name'], 'agent|oui|10050', $proxy, array ( 
				$this ->getMachineName (), 
				'System' ), array ( 
				'Template OS ' . $ci ['osfamily_name'] ) );
		
		return $this;
	}

	public function update_donnees_machine($name, $interfaces, $proxy, $groupes, $templates) {
		if ($this ->getListeOptions () 
			->verifie_option_existe ( "machine_unique" ) !== false) {
			$this ->creer_interface_JMX ();
			$this ->getObjetZabbixHostAdministration () 
				->getListeOptions () 
				->setOption ( "zabbix_host_groups", $groupes );
			$this ->getObjetZabbixHostAdministration () 
				->getListeOptions () 
				->setOption ( "zabbix_templates", $templates );
			$this ->getObjetZabbixHostAdministration () 
				->ajoute_group () 
				->ajoute_template ();
		} else {
			$this ->onInfo ( "On creer la machine " . $name );
			$this ->creer_machine ( $name, $interfaces, $proxy, $groupes, $templates );
		}
		
		return $this;
	}

	public function creer_interface_JMX() {
		if ($this ->getJmxInterface () === false) {
			$this ->prepare_commun_machine ( $this ->getMachineName (), 'JMX|oui|8888', "" );
			$this ->getObjetZabbixHostAdministration () 
				->ajoute_hostInterface ();
			$this ->setJmxInterface ( true );
		}
		return $this;
	}

	public function ajoute_Middleware($ci, $proxy = "") {
		//On recolte les donnees dans l'export rest de itop
		if (preg_match ( "/^(JBoss |Java |JCore )(?P<Apps>.*)$/", $ci ['name'], $valeur ) === 1) {
			$interfaces = 'JMX|oui|8888';
			$templates = array ( 
					'Template JMX Generic' );
		} else {
			$interfaces = 'agent|oui|10050';
			$templates = array ( 
					'Template ' . $ci ['name'] );
		}
		$groupes = array ( 
				$this ->getMachineName (), 
				$ci ['name'] );
		
		$this ->update_donnees_machine ( $ci ['friendlyname'], $interfaces, $proxy, $groupes, $templates );
		
		return $this;
	}

	public function ajoute_PCSoftware($ci, $proxy = "") {
		//On recolte les donnees dans l'export rest de itop
		$interfaces = 'agent|oui|10050';
		$templates = array ( 
				'Template ' . $ci ['name'] );
		$groupes = array ( 
				$this ->getMachineName (), 
				$ci ['name'] );
		
		$this ->update_donnees_machine ( $ci ['friendlyname'], $interfaces, $proxy, $groupes, $templates );
		
		return $this;
	}

	public function ajoute_OtherSoftware($ci, $proxy = "") {
		return $this ->ajoute_PCSoftware ( $ci, $proxy );
	}

	public function ajoute_WebServer($ci, $proxy = "") {
		//On recolte les donnees dans l'export rest de itop
		if (preg_match ( "/^(JBoss |Java |JCore )(?P<Apps>.*)$/", $ci ['friendlyname'], $valeur ) === 1) {
			$interfaces = 'JMX|oui|8888';
			$templates = array ( 
					'Template JMX Generic' );
		} elseif (preg_match ( "/^Tomcat/", $ci ['name'], $valeur ) === 1) {
			$interfaces = 'JMX|oui|8888';
			$templates = array ( 
					'Template JMX Tomcat', 
					'Template JMX Generic' );
		} else {
			$interfaces = 'agent|oui|10050';
			$templates = array ( 
					'Template ' . $ci ['name'] );
		}
		$groupes = array ( 
				$this ->getMachineName (), 
				$ci ['name'] );
		
		$this ->update_donnees_machine ( $ci ['friendlyname'], $interfaces, $proxy, $groupes, $templates );
		
		return $this;
	}

	/**
	 * 
	 * @param unknown $datas
	 * @return itop_to_zabbix
	 * @throws Exception
	 */
	public function creer_liste_cis($liste_cis) {
		foreach ( $liste_cis as $ci ) {
			//--zabbix_interface_ip 122.122.122.122 --zabbix_interfaces 'agent|oui|10050' --zabbix_host_host test_host
			// --zabbix_host_name test_host --zabbix_host_status monitored --zabbix_host_groups 'VirtualMachine' --zabbix_templates 'Template OS Linux'
			switch ($ci ['class']) {
				case 'VirtualMachine' :
					//On enregistre l'ip qui sera valable pour tous les CIs de type VM
					$this ->setManagementIp ( $ci ['managementip'] );
					if (isset ( $ci ['proxy'] )) {
						$proxy = $ci ['proxy'];
					} else {
						$proxy = "";
					}
					$this ->ajoute_VirtualMachine ( $ci, $proxy );
					break;
				case 'Server' :
					//On enregistre l'ip qui sera valable pour tous les CIs de type Server
					$this ->setManagementIp ( $ci ['managementip'] );
					if (isset ( $ci ['proxy'] )) {
						$proxy = $ci ['proxy'];
					} else {
						$proxy = "";
					}
					$this ->ajoute_Server ( $ci, $proxy );
					break;
				case 'Middleware' :
					$this ->ajoute_Middleware ( $ci, $proxy );
					break;
				case 'PCSoftware' :
					$this ->ajoute_PCSoftware ( $ci, $proxy );
					break;
				case 'OtherSoftware' :
					$this ->ajoute_OtherSoftware ( $ci, $proxy );
					break;
				case 'WebServer' :
					$this ->ajoute_WebServer ( $ci, $proxy );
					break;
			}
		}
		
		return $this;
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * @codeCoverageIgnore
	 */
	public function getMachineName() {
		return $this->machine;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setMachineName($machine_name) {
		$this->machine = $machine_name;
		
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getManagementIp() {
		return $this->managementip;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setManagementIp($managementip) {
		$this->managementip = $managementip;
		
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getJmxInterface() {
		return $this->jmx_interface;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setJmxInterface($jmx_interface) {
		$this->jmx_interface = $jmx_interface;
		
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &getObjetZabbixHostAdministration() {
		return $this->objet_zabbix_host_administration;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setObjetZabbixHostAdministration(&$objet_zabbix_host_administration) {
		$this->objet_zabbix_host_administration = $objet_zabbix_host_administration;
		
		return $this;
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	
	/**
	 * Affiche le help.<br> @codeCoverageIgnore
	 */
	static public function help() {
		$help = parent::help ();
		
		$help [__CLASS__] ["text"] = array ();
		$help [__CLASS__] ["text"] [] .= "itop_to_zabbix :";
		$help [__CLASS__] ["text"] [] .= "Par defaut, cree un machine par CI applicatif dans Zabbix";
		$help [__CLASS__] ["text"] [] .= "--machine_unique Cree une seule machine dans Zabbix est applique tous les templates";
		
		return $help;
	}
}
?>
