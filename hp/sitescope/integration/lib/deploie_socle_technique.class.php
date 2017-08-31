<?php
/**
 * Gestion de SiteScope.
 * @author dvargas
 */
/**
 * class deploie_socle_technique
 *
 * @package Lib
 * @subpackage SiteScope
 */
class deploie_socle_technique extends abstract_log {
	/**
	 * var privee
	 *
	 * @access private
	 * @var sitescope_template_datas
	 */
	private $sis_template_datas = null;
	/**
	 * var privee
	 *
	 * @access private
	 * @var sitescope_functions_standards
	 */
	private $sitescope_functions_standards = null;
	/**
	 * var privee
	 *
	 * @access private
	 * @var sitescope_soap_configuration
	 */
	private $sitescope_soap_configuration = null;
	/**
	 * var privee
	 *
	 * @access private
	 * @var array
	 */
	private $fullPathToTemplateName = array ();
	/**
	 * var privee
	 *
	 * @access private
	 * @var array
	 */
	private $CIVariablesValues = array ();
	/**
	 * var privee
	 *
	 * @access private
	 * @var array
	 */
	private $param_sup = array ();

	/*********************** Creation de l'objet *********************/
	/**
	 * Instancie un objet de type deploie_socle_technique.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param sitescope_template_datas $sis_template_datas Pointeur sur un objet sitescope_template_datas.
	 * @param sitescope_functions_standards $sitescope_functions_standards Pointeur sur un objet sitescope_functions_standards.
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet gestion_connexion_url
	 * @return deploie_socle_technique
	 */
	static function &creer_deploie_socle_technique(&$liste_option, &$sis_template_datas, &$sitescope_functions_standards, $sort_en_erreur = false, $entete = __CLASS__) {
		$objet = new deploie_socle_technique ( $sort_en_erreur, $entete );
		return $objet->_initialise ( array (
				"options" => $liste_option,
				"sitescope_template_datas" => $sis_template_datas,
				"sitescope_functions_standards" => $sitescope_functions_standards 
		) );
	}

	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return sitescope_datas
	 */
	public function &_initialise($liste_class) {
		parent::_initialise ( $liste_class );
		$soapClient_configuration = sitescope_soap_configuration::creer_sitescope_soap_configuration ( $liste_class ["options"] );
		
		if (! isset ( $liste_class ["sitescope_template_datas"] )) {
			return $this->onError ( "Il faut un objet de type sitescope_template_datas" );
		}
		if (! isset ( $liste_class ["sitescope_functions_standards"] )) {
			return $this->onError ( "Il faut un objet de type sitescope_functions_standards" );
		}
		$this->setSisTemplateDatas ( $liste_class ["sitescope_template_datas"] )
			->setSisFonctionsStandards ( $liste_class ["sitescope_functions_standards"] )
			->setSisSoapConfiguration ( $soapClient_configuration );
		
		if ($this->retrouve_params () === false) {
			return null;
		}
		
		return $this;
	}

	/*********************** Creation de l'objet *********************/
	
	/**
	 * Constructeur.
	 * @codeCoverageIgnore
	 * @param options $liste_options Pointeur sur les arguments.
	 * @param sitescope_template_datas $sis_template_datas Pointeur sur un objet sitescope_template_datas.
	 * @param sitescope_functions_standards $sitescope_functions_standards Pointeur sur un objet sitescope_functions_standards.
	 * @param string|Bool $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @return true
	 */
	public function __construct($sort_en_erreur = false, $entete = __CLASS__) {
		// Gestion de abstract_log
		parent::__construct ( $sort_en_erreur, $entete );
		$this->setThrowException(false);
	}

	/**
	 * Valide les parametres necessaire a la class deploie_socle_technique
	 * @return boolean True est OK, False sinon.
	 */
	public function retrouve_params() {
		if ($this->getListeOptions ()
			->verifie_option_existe ( "code_client" ) === false) {
			return $this->onError ( "Il manque le parametre code_client sitescope.", "", 5100 );
		}
		
		return true;
	}

	/**
	 * 
	 * @param boolean $creer_host True si on on fait la mise en supervision du CI
	 * @param boolean $return_chemin_CI True si on veux uniquement le chemin du complet du CI (sans le sous-dossier Moniteurs_CI)
	 * @return array Chemin du moniteur
	 */
	public function creer_chemin_moniteurs($creer_host, $return_chemin_CI = false) {
		$os = $this->getSisTemplateDatas ()
			->getOSGlobal ();
		if ($os == "") {
			return false;
		}
		
		$fullPathToMoniteurs = array (
				$this->getListeOptions ()
					->getOption ( "code_client" ),
				$os,
				$this->getSisTemplateDatas ()
					->getSchedule () 
		);
		if ($creer_host) {
			return $fullPathToMoniteurs;
		}
		$fullPathToMoniteurs [] .= $this->getSisTemplateDatas ()
			->getCI ();
		if ($return_chemin_CI) {
			return $fullPathToMoniteurs;
		}
		$fullPathToMoniteurs [] .= "Moniteurs_" . $this->getSisTemplateDatas ()
			->getCI ();
		return $fullPathToMoniteurs;
	}

	/**
	 * Cree le chemin pour trouver le template en fonction de l'OS
	 * @return deploie_socle_technique|false
	 */
	public function creer_entete_chemin_templates() {
		$fullPathToTemplateName = array (
				0 => "Templates",
				1 => "CLIENT" 
		);
		switch ($this->getSisTemplateDatas ()
			->getOSGlobal ()) {
			case "WINDOWS" :
				$fullPathToTemplateName [] .= "WINDOWS";
				break;
			case "LINUX" :
			case "UNIX" :
				$fullPathToTemplateName [] .= "UX";
				break;
			default :
				return false;
		}
		
		$this->setFullPathToTemplateName ( $fullPathToTemplateName );
		return $this;
	}

	/**
	* Creer La definition d'un Remote Host
	* @return deploie_socle_technique|false
	*/
	public function creer_remote_host_variables() {
		$liste_ip = $this->getSisTemplateDatas ()
			->getIPs ();
		if (count ( $liste_ip ) == 0) {
			return $this->onError ( "La liste d'IP doit être un tableau rempli d'au moins une IP" );
		}
		$this->setCIVariablesValues ( array (
				"CI" => $this->getSisTemplateDatas ()
					->getCI (),
				"IP" => $liste_ip [0],
				"schedule" => $this->getSisTemplateDatas ()
					->getSchedule () 
		) );
		
		switch ($this->getSisTemplateDatas ()
			->getOS ()) {
			case "WINDOWS" :
				$this->gere_credential ( "Windows_Credential" );
				//methode_connexion NetBios ou WMI obligatoire
				if ($this->getListeOptions ()
					->verifie_option_existe ( "methode_connexion" ) === false) {
					return $this->onError ( "Il manque le parametre methode_connexion pour sitescope.", "", 5100 );
				}
				$this->setAddParamSup ( "methode_connexion", $this->getListeOptions ()
					->getOption ( "methode_connexion" ) );
				
				break;
			
			case "LINUX" :
				$this->gere_credential ( "SSH_Credential" );
				$this->setAddParamSup ( "OS", "Linux" );
				$this->gere_keyfile ();
				
				break;
			case "UNIX : UNIX" :
			case "UNIX : AIX" :
				$this->gere_credential ( "SSH_Credential" );
				$this->setAddParamSup ( "OS", "AIX" );
				$this->gere_keyfile ();
				
				break;
			case "UNIX : HP/UX" :
			case "UNIX : HP-UX" :
			case "UNIX : HP/UX 64-bit" :
				$this->gere_credential ( "SSH_Credential" );
				$this->setAddParamSup ( "OS", "HP/UX" );
				$this->gere_keyfile ();
				
				break;
			default :
				return false;
		}
		
		return $this;
	}

	/**
	 * Gere la keyfile
	 * @return deploie_socle_technique
	 */
	public function gere_keyfile() {
		if ($this->getListeOptions ()
			->verifie_option_existe ( "keyfile" ) !== false) {
			$keyfile = $this->getListeOptions ()
				->getOption ( "keyfile" );
			if ($keyfile != false) {
				$this->setAddParamSup ( "keyfile", $keyfile );
			} else {
				return $this->onError ( "Le keyfile n'est pas defini" );
			}
		}
		
		return $this;
	}

	/**
	 * Gere les credentials
	 * @param string $credential_standard
	 * @return deploie_socle_technique
	 */
	public function gere_credential($credential_standard) {
		if ($this->getListeOptions ()
			->verifie_option_existe ( "credential" ) === false) {
			$this->setAddParamSup ( "credential", $credential_standard );
		} else {
			$this->setAddParamSup ( "credential", $this->getListeOptions ()
				->getOption ( "credential" ) );
		}
		
		return $this;
	}

	/**
	 * Creer les moniteurs de type Ping supplementaire
	 * @return deploie_socle_technique
	 */
	public function creer_moniteur_ping() {
		$liste_ip = $this->getSisTemplateDatas ()
			->getIPs ();
		if (count ( $liste_ip ) <= 1) {
			return $this;
		}
		$fullPathToTemplateName = array_merge ( $this->getFullPathToTemplateName (), array (
				"Ping" 
		) );
		for($i = 1; $i < count ( $liste_ip ); $i ++) {
			$CIPingIP = array (
					"titre" => $this->getNomMoniteur ( "Ping", $i ),
					"CI" => $this->getSisTemplateDatas ()
						->getCI (),
					"IP" => $liste_ip [$i],
					"schedule" => $this->getSisTemplateDatas ()
						->getSchedule () 
			);
			
			$data_id_sis = $this->getSisSoapConfiguration ()
				->deploySingleTemplateWithResult ( $fullPathToTemplateName, $CIPingIP, $this->creer_chemin_moniteurs ( false ), true, true );
		}
		return $this;
	}

	/**
	 * Creer le CI avec les moniteurs ping/CPU/Memory et sshd pour les UX
	 * @return deploie_socle_technique
	 */
	public function creer_moniteur_ping_MES() {
		if ($this->getListeOptions ()
			->verifie_option_existe ( "keyfile" ) !== false) {
			$keyfile = "_keyfile";
		} else {
			$keyfile = "";
		}
		$fullPathToTemplateName = array_merge ( $this->getFullPathToTemplateName (), array (
				"Ping_MES" . $keyfile 
		) );
		$ActualVariablesValues = $this->getCIVariablesValues ();
		$ActualVariablesValues ["titre_Ping"] = $this->getNomMoniteur ( "Ping", 0 );
		$ActualVariablesValues ["titre_CPU"] = $this->getNomMoniteur ( "CPU" );
		$ActualVariablesValues ["titre_Memory"] = $this->getNomMoniteur ( "Memory" );
		$ActualVariablesValues ["titre_sshd"] = $this->getNomMoniteur ( "Process", "sshd" );
		
		$data_id_sis = $this->getSisSoapConfiguration ()
			->deploySingleTemplateWithResult ( $fullPathToTemplateName, $ActualVariablesValues, $this->creer_chemin_moniteurs ( true ), true, true );
		
		return $this;
	}

	/**
	 * Cree les moniteurs de type disk/FileSystem
	 * @return deploie_socle_technique
	 */
	public function creer_moniteur_disk() {
		$fullPathToTemplateName = array_merge ( $this->getFullPathToTemplateName (), array (
				"Disk" 
		) );
		foreach ( $this->getSisTemplateDatas ()
			->getDisks () as $disk ) {
			$ActualVariablesValues = $this->getCIVariablesValues ();
			$ActualVariablesValues["titre"]=$this->getNomMoniteur ( "Disk", $disk );
			$ActualVariablesValues["disk"]=$disk;
			
			$data_id_sis = $this->getSisSoapConfiguration ()
				->deploySingleTemplateWithResult ( $fullPathToTemplateName, $ActualVariablesValues, $this->creer_chemin_moniteurs ( false ), false, false );
		}
		return $this;
	}

	/**
	 * Cree les moniteurs de type Process
	 * @return deploie_socle_technique
	 */
	public function creer_moniteur_process() {
		switch ($this->getSisTemplateDatas ()
			->getOS ()) {
			case "WINDOWS" :
				$fullPathToTemplateName = array_merge ( $this->getFullPathToTemplateName (), array (
						"Services" 
				) );
				$services_list = "/";
				foreach ( $this->getSisTemplateDatas ()
					->getServices () as $service ) {
					if (trim ( $service ) == "") {
						continue;
					}
					if ($services_list != "/") {
						$services_list .= "|";
					}
					$services_list .= "^" . $service . "$";
				}
				$ActualVariablesValues = $this->getCIVariablesValues ();
				$ActualVariablesValues["titre"]=$this->getNomMoniteur ( "Services", "systeme" );
				$ActualVariablesValues["services"]=$services_list . "/";
				
				$data_id_sis = $this->getSisSoapConfiguration ()
					->deploySingleTemplateWithResult ( $fullPathToTemplateName, $ActualVariablesValues, $this->creer_chemin_moniteurs ( false ) );
				break;
			default :
				$fullPathToTemplateName = array_merge ( $this->getFullPathToTemplateName (), array (
						"Process" 
				) );
				foreach ( $this->getSisTemplateDatas ()
					->getServices () as $process ) {
					//On gere le sshd separement durant la creation du serveur host
					if (strpos ( $process, "sshd" ) === false) {
						$ActualVariablesValues = $this->getCIVariablesValues ();
						$ActualVariablesValues["titre"]=$this->getNomMoniteur ( "Process", $process );
						$ActualVariablesValues["process"]=$process;
						$data_id_sis = $this->getSisSoapConfiguration ()
							->deploySingleTemplateWithResult ( $fullPathToTemplateName, $ActualVariablesValues, $this->creer_chemin_moniteurs ( false ) );
					}
				}
				break;
		}
		
		return $this;
	}

	/**
	 * Cree les moniteurs de type DNS
	 * @return deploie_socle_technique
	 */
	public function creer_moniteur_DNS() {
		$dns = $this->getSisTemplateDatas ()
			->getDNS ();
		$fqdn = $this->getSisTemplateDatas ()
			->getFQDN ();
		//si aucune dns n'est fourni, il n'y a pas de moniteur DNS
		if ($dns == "" || $fqdn == "") {
			return $this;
		}
		
		$fullPathToTemplateName = array_merge ( $this->getFullPathToTemplateName (), array (
				"DNS" 
		) );
		$ActualVariablesValues = $this->getCIVariablesValues ();
		$ActualVariablesValues["titre"]=$this->getNomMoniteur ( "DNS", $fqdn );
		$ActualVariablesValues["IP_DNS_Primaire"]=$dns;
		$ActualVariablesValues["fqdn"]=$fqdn;
		$data_id_sis = $this->getSisSoapConfiguration ()
			->deploySingleTemplateWithResult ( $fullPathToTemplateName, $ActualVariablesValues, $this->creer_chemin_moniteurs ( false ), false, false );
		
		return $this;
	}

	/**
	 * Cree les moniteurs de type scripts BACKUP/FSTAB/INODE/LVM/READONLY
	 * @return deploie_socle_technique
	 */
	public function creer_moniteur_scripts() {
		switch ($this->getSisTemplateDatas ()
			->getOS ()) {
			case "WINDOWS" :
				//Pas de script pour Windows
				break;
			default :
				foreach ( $this->getSisTemplateDatas ()
					->getScripts () as $nom => $todo ) {
					if ($todo === false) {
						continue;
					}
					$fullPathToTemplateName = array_merge ( $this->getFullPathToTemplateName (), array (
							"Scripts" 
					) );
					$ActualVariablesValues = $this->getCIVariablesValues ();
					$ActualVariablesValues["titre"]=$this->getNomMoniteur ( "Script", $nom );
					$ActualVariablesValues["script_name"]=$nom;
					$data_id_sis = $this->getSisSoapConfiguration ()
						->deploySingleTemplateWithResult ( $fullPathToTemplateName, $ActualVariablesValues, $this->creer_chemin_moniteurs ( false ), false, false );
				}
		}
		
		return $this;
	}

	/**
	 * Prepare les donnees du remote serveur et du chemin du template
	 */
	public function prepare_donnees_remote_server() {
		//On reset les valeurs
		$this->creer_entete_chemin_templates ()
			->creer_remote_host_variables ();
		
		return $this;
	}

	/**
	 * Mise en monitoring du serveur
	 * @return deploie_socle_technique
	 */
	public function integre_serveur() {
		$this->onDebug ( "integre_serveur", 2 );
		//On reset les valeurs
		$this->prepare_donnees_remote_server ();
		
		//On creer le CI
		$this->creer_moniteur_ping_MES ();
		
		//On creer les IP supplementaires
		$this->creer_moniteur_ping ();
		//Les disques/FS
		$this->creer_moniteur_disk ();
		//Les verification DNS
		$this->creer_moniteur_DNS ();
		//Les services/process
		$this->creer_moniteur_process ();
		//Les scripts
		$this->creer_moniteur_scripts ();
		
		$this->getSisFonctionsStandards ()
			->transmet_disable_sitescope ( $this->getSisSoapConfiguration (), 'ALERT', 31536000, 1, $this->creer_chemin_moniteurs ( false, true ), "MEP", 0 );
		
		return $this;
	}

	/******************************* SUPPRESSION *******************************/
	/**
	 * Supprime le groupe Moniteurs_CI d'un CI donne
	 * @return deploie_socle_technique
	 */
	public function supprime_groupe_Moniteurs() {
		$data_id_sis = $this->getSisSoapConfiguration ()
			->deleteGroupEx ( $this->creer_chemin_moniteurs ( false, false ) );
		
		return $this;
	}

	/**
	 * Supprime le groupe du CI 
	 * @return deploie_socle_technique
	 */
	public function supprime_groupe_CI() {
		$data_id_sis = $this->getSisSoapConfiguration ()
			->deleteGroupEx ( $this->creer_chemin_moniteurs ( false, true ) );
		
		return $this;
	}

	/**
	 * Supprime les moniteurs lies a la mise en supervision (MES) 
	 * @return deploie_socle_technique
	 */
	public function supprime_moniteur_pingMES() {
		$chemin_moniteur = $this->creer_chemin_moniteurs ( false, true );
		$pos = count ( $chemin_moniteur );
		
		switch ($this->getSisTemplateDatas ()
			->getOS ()) {
			case "WINDOWS" :
				break;
			default :
				$chemin_moniteur [$pos] = $this->getNomMoniteur ( 'Process', 'sshd' );
				$data_id_sis = $this->getSisSoapConfiguration ()
					->deleteMonitorEx ( $chemin_moniteur );
		}
		
		return $this;
	}

	/**
	 * Supprime le remote server de sitescope
	 * @return deploie_socle_technique
	 */
	public function supprime_ci() {
		$os = $this->getSisTemplateDatas ()
			->getOSGlobal ();
		//$OS =="Windows" && ! $OS =="Unix"
		switch ($os) {
			case "WINDOWS" :
				$os_sis = $os;
				break;
			default :
				$os_sis = "UNIX";
		}
		$data_id_sis = $this->getSisSoapConfiguration ()
			->deleteRemote ( $os_sis, $this->getSisTemplateDatas ()
			->getCI () );
		
		return $this;
	}

	/******************************* SUPPRESSION *******************************/
	
	/******************************* GESTION MONITEURS *******************************/
	/**
	 * Envoi le nom d'un moniteur normalise<br/>
	 *    Ping : L'ip est celle contenu dans Sitescope_template.
	 * @param string $type Ping|Process
	 * @param string $donnee Nom du Process pour un type process
	 * @return string
	 */
	public function getNomMoniteur($type, $donnee = "") {
		switch ($type) {
			case 'CPU' :
				return "CPU sur " . $this->getSisTemplateDatas ()
					->getCI ();
				break;
			case 'Disk' :
				switch ($this->getSisTemplateDatas ()
					->getOSGlobal ()) {
					case "WINDOWS" :
						return "Disk [" . $donnee . "] sur " . $this->getSisTemplateDatas ()
							->getCI ();
						break;
					default :
						return "FileSystem [" . $donnee . "] sur " . $this->getSisTemplateDatas ()
							->getCI ();
				}
				break;
			case 'DNS' :
				return "DNS [" . $donnee . "] sur " . $this->getSisTemplateDatas ()
					->getCI ();
				break;
			case 'Memory' :
				return "Memory sur " . $this->getSisTemplateDatas ()
					->getCI ();
				break;
			case 'Ping' :
				$liste_ip = $this->getSisTemplateDatas ()
					->getIPs ();
				return "Ping [" . $liste_ip [$donnee] . "] sur " . $this->getSisTemplateDatas ()
					->getCI ();
				break;
			case 'Process' :
				return "Process [" . $donnee . "] sur " . $this->getSisTemplateDatas ()
					->getCI ();
				break;
			case 'Script' :
				return "Script [" . $donnee . "] sur " . $this->getSisTemplateDatas ()
					->getCI ();
				break;
			case 'Services' :
				return "Services " . $donnee . " sur " . $this->getSisTemplateDatas ()
					->getCI ();
				break;
		}
		
		return "";
	}

	/******************************* GESTION MONITEURS *******************************/
	
	/******************************* ACCESSEURS ********************************/
	/**
	 * @codeCoverageIgnore
	 */
	public function &getSisTemplateDatas() {
		return $this->sis_template_datas;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setSisTemplateDatas(&$sis_template_datas) {
		$this->sis_template_datas = $sis_template_datas;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &getSisFonctionsStandards() {
		return $this->sitescope_functions_standards;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setSisFonctionsStandards(&$sitescope_functions_standards) {
		$this->sitescope_functions_standards = $sitescope_functions_standards;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &getSisSoapConfiguration() {
		return $this->sitescope_soap_configuration;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setSisSoapConfiguration(&$sitescope_soap_configuration) {
		$this->sitescope_soap_configuration = $sitescope_soap_configuration;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &getFullPathToTemplateName() {
		return $this->fullPathToTemplateName;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setFullPathToTemplateName($fullPathToTemplateName) {
		$this->fullPathToTemplateName = $fullPathToTemplateName;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getCIVariablesValues() {
		return array_merge ( $this->CIVariablesValues, $this->getParamSup () );
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setCIVariablesValues($CIVariablesValues) {
		$this->CIVariablesValues = $CIVariablesValues;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getParamSup() {
		return $this->param_sup;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setParamSup($param_sup) {
		$this->param_sup = $param_sup;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setAddParamSup($champ_sup, $param_sup) {
		$this->param_sup [$champ_sup] = $param_sup;
		return $this;
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	
	/**
	 * Affiche le help.<br>
	 * @codeCoverageIgnore
	 */
	static public function help() {
		$help = parent::help ();
		
		$help [__CLASS__] ["text"] = array ();
		$help [__CLASS__] ["text"] [] .= "\t--code_client Code du client a utiliser dans sitescope";
		$help [__CLASS__] ["text"] [] .= "\t--methode_connexion Pour Windows uniquement : NetBios ou WMI";
		$help [__CLASS__] ["text"] [] .= "\t--credential nom complet (sans espace) du credential a utiliser";
		$help [__CLASS__] ["text"] [] .= "\t--keyfile \"Chemin_complet_du_keyfile\" utilise le keyfile donnee en argument";
		
		return $help;
	}
}
?>
