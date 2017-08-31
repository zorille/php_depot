<?php
/**
 * Gestion des correspondances CODE Cacti/ID de Cacti..
 * @author dvargas
 */
/**
 * class correspondances_cacti
 *
 * @package Lib
 * @subpackage Cacti
 */
class correspondances_cacti extends abstract_log {

	/*********************** Creation de l'objet *********************/
	/**
	 * Instancie un objet de type correspondances_cacti.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return correspondances_cacti
	 */
	static function &creer_correspondances_cacti(&$liste_option, $sort_en_erreur = false, $entete = __CLASS__) {
		$objet = new correspondances_cacti ( $sort_en_erreur, $entete );
		$objet->_initialise ( array (
				"options" => $liste_option
		) );
	
		return $objet;
	}
	
	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return correspondances_cacti
	 */
	public function &_initialise($liste_class) {
		parent::_initialise($liste_class);
		return $this;
	}
	
	/*********************** Creation de l'objet *********************/

	/**
	 * Constructeur.
	 *
	 * @param string $entete Entete de log.
	 * @param string|Bool $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @return true
	 */
	public function __construct($sort_en_erreur = false, $entete = __CLASS__) {
		// Gestion de abstract_log
		parent::__construct ( $sort_en_erreur, $entete );
		$this->setThrowException(false);
	}

	/**
	 * Renvoi la methode d'availability en fonction du code cacti en base
	 * define("AVAIL_NONE", 0);
	 * define("AVAIL_SNMP_AND_PING", 1);
	 * define("AVAIL_SNMP", 2);
	 * define("AVAIL_PING", 3);
	 * define("AVAIL_SNMP_OR_PING", 4);
	 * define("AVAIL_SNMP_GET_SYSDESC", 5);
	 * define("AVAIL_SNMP_GET_NEXT", 6);
	 * @param int $code_avail Code du CI pour le champ availability_method
	 * @return string|boolean texte en fonction du code,False en cas d'erreur
	 */
	public function retrouveAvailabilityMethod($code_avail) {
		switch ($code_avail) {
			case 0 :
				return "AVAIL_NONE";
				break;
			case 1 :
				return "AVAIL_SNMP_AND_PING";
				break;
			case 2 :
				return "AVAIL_SNMP";
				break;
			case 3 :
				return "AVAIL_PING";
				break;
			case 4 :
				return "AVAIL_SNMP_OR_PING";
				break;
			case 5 :
				return "AVAIL_SNMP_GET_SYSDESC";
				break;
			case 6 :
				return "AVAIL_SNMP_GET_NEXT";
				break;
		}
		
		return $this->onError ( "Code availability_method inconnu : " . $code_avail );
	}

	/**
	 * Renvoi le type de ping en fonction du code cacti en base
	 * define("PING_ICMP", 1);
	 * define("PING_UDP", 2);
	 * define("PING_TCP", 3);
	 * @param int $code_ping Code du CI pour le champ ping_method
	 * @return string|boolean texte en fonction du code,False en cas d'erreur
	 */
	public function retrouvePingMethod($code_ping) {
		switch ($code_ping) {
			case 1 :
				return "PING_ICMP";
				break;
			case 2 :
				return "PING_UDP";
				break;
			case 3 :
				return "PING_TCP";
				break;
		}
		
		return $this->onError ( "Code ping_method inconnu : " . $code_ping );
	}

	/**
	 * Renvoi le status du CI en fonction du code cacti en base
	 * define("HOST_UNKNOWN", 0);
	 * define("HOST_DOWN", 1);
	 * define("HOST_RECOVERING", 2);
	 * define("HOST_UP", 3);
	 * define("HOST_ERROR", 4);
	 * @param int $code_status Code du CI pour le champ status
	 * @return string texte en fonction du code,"UNKNOW" en cas d'erreur
	 */
	public function retrouveStatus($code_status) {
		switch ($code_status) {
			case 0 :
				return "HOST_UNKNOWN";
				break;
			case 1 :
				return "HOST_DOWN";
				break;
			case 2 :
				return "HOST_RECOVERING";
				break;
			case 3 :
				return "HOST_UP";
				break;
			case 4 :
				return "HOST_ERROR";
				break;
		}
		
		return "UNKNOW";
	}

	/**
	 * Valide que l'ip repond a une requete SNMP
	 * @codeCoverageIgnore
	 * @param string $oid OID a tester
	 * @param string $ip Adresse IP du CI a tester
	 * @param string $snmpCommunity Communaute SNMP du CI
	 * @param string $snmpVersion Version Snmp du CI
	 * @param int $snmpTimeout Timeout pour le test
	 * @param int $snmpRetry Nombre de retry pour le test 
	 * @param string $snmpUsername Username pour le SNMP V3
	 * @param string $snmpPassword Password pour le SNMP V3
	 * @param string $snmpAuthProtocol Auth Protocol pour le SNMP V3
	 * @param string $snmpPrivProtocol Private Protocole pour le SNMP V3
	 * @param string $snmpPrivPassphrase PassPhrase pour le SNMP V3
	 * @return boolean TRUE si le snmp repond,false sinon.
	 */
	public function valideSnmp($oid, $ip, $snmpCommunity, $snmpVersion, $snmpTimeout = 1000000, $snmpRetry = 1, $snmpUsername = "", $snmpPassword = "", $snmpAuthProtocol = "", $snmpPrivProtocol = "", $snmpPrivPassphrase = "") {
		$donnees = $this->retrouveSnmp ( $oid, $ip, $snmpCommunity, $snmpVersion, $snmpTimeout, $snmpRetry, $snmpUsername, $snmpPassword, $snmpAuthProtocol, $snmpPrivProtocol, $snmpPrivPassphrase );
		if ($donnees === false) {
			return $this->onError ( "Erreur durant le check SNMP pour " . $ip, "", 5022 );
		}
		
		return true;
	}

	/**
	 * renvoi la reponse a une requete SNMP
	 * @codeCoverageIgnore
	 * @param string $oid OID a tester
	 * @param string $ip Adresse IP du CI a tester
	 * @param string $snmpCommunity Communaute SNMP du CI
	 * @param string $snmpVersion Version Snmp du CI
	 * @param int $snmpTimeout Timeout pour le test
	 * @param int $snmpRetry Nombre de retry pour le test
	 * @param string $snmpUsername Username pour le SNMP V3
	 * @param string $snmpPassword Password pour le SNMP V3
	 * @param string $snmpAuthProtocol Auth Protocol pour le SNMP V3
	 * @param string $snmpPrivProtocol Private Protocole pour le SNMP V3
	 * @param string $snmpPrivPassphrase PassPhrase pour le SNMP V3
	 * @return String|Array|False TRUE si le snmp repond,false sinon.
	 */
	public function retrouveSnmp($oid, $ip, $snmpCommunity, $snmpVersion, $snmpTimeout = 1000000, $snmpRetry = 1, $snmpUsername = "", $snmpPassword = "", $snmpAuthProtocol = "", $snmpPrivProtocol = "", $snmpPrivPassphrase = "") {
		$this->onDebug ( "Oid en cours : " . $oid, 1 );
		switch ($snmpVersion) {
			case "1" :
				$retour = snmpget ( $ip, $snmpCommunity, $oid, $snmpTimeout, $snmpRetry );
				if ($retour == "No Such Instance currently exists at this OID") {
					$message=$retour;
					$retour = snmprealwalk ( $ip, $snmpCommunity, $oid, $snmpTimeout, $snmpRetry );
					if($retour === false){
						$retour=$message;
					}
				}
				break;
			case "2" :
			case "2c" :
				$retour = snmp2_get ( $ip, $snmpCommunity, $oid, $snmpTimeout, $snmpRetry );
				if ($retour == "No Such Instance currently exists at this OID" || $retour=="No Such Object available on this agent at this OID") {
					$message=$retour;
					$retour = snmprealwalk ( $ip, $snmpCommunity, $oid, $snmpTimeout, $snmpRetry );
					if($retour === false){
						$retour=$message;
					}
				}
				break;
			case "3" :
				$retour = snmp3_get ( $ip, $snmpUsername, $snmpPassword, $snmpAuthProtocol, '', $snmpPrivProtocol, $snmpPrivPassphrase, $oid, $snmpTimeout, $snmpRetry );
				break;
			default :
				return $this->onError ( "Erreur de version de SNMP : " . $snmpVersion, "", 5006 );
		}
		
		$this->onDebug ( $retour, 2 );
		return $retour;
	}

	/**
	 * Valide le format de l'adresse IP et ou la bonne resolution de cette adresse en cas de hostname
	 * @param string $ip
	 * @return boolean True si l'ip est valide,false sinon
	 */
	public function valideIP($ip) {
		$this->onDebug ( "On valide l'IP.", 1 );
		// On retrouve l'ip :
		if (filter_var ( $ip, FILTER_VALIDATE_IP ) === false) {
			// Sinon on tente une resolution DNS
			$reel_ip = gethostbyname ( $ip );
			if ($reel_ip == $ip) {
				// Pas de resolution, donc pas de test SNMP
				$this->onDebug ( "L'adresse IP n'est pas correcte.", 1 );
				return false;
			}
		}
		$this->onDebug ( "L'adresse IP est correcte.", 1 );
		return true;
	}

	/**
	 * Retrouve le type d'OS en fonction du sysDesc
	 * @codeCoverageIgnore
	 * @param string $ip Adresse IP du CI a tester
	 * @param string $snmpCommunity Communaute SNMP du CI
	 * @param string $snmpVersion Version Snmp du CI
	 * @param int $snmpTimeout Timeout pour le test
	 * @param int $snmpRetry Nombre de retry pour le test
	 * @param string $snmpUsername Username pour le SNMP V3
	 * @param string $snmpPassword Password pour le SNMP V3
	 * @param string $snmpAuthProtocol Auth Protocol pour le SNMP V3
	 * @param string $snmpPrivProtocol Private Protocole pour le SNMP V3
	 * @param string $snmpPrivPassphrase PassPhrase pour le SNMP V3
	 * @return String|false L'OS trouve (LINUX|UNIX|WINDOWS),false sinon
	 */
	public function retrouveTypeOSParSnmp($ip, $snmpCommunity, $snmpVersion, $snmpTimeout = 1000000, $snmpRetry = 1, $snmpUsername = "", $snmpPassword = "", $snmpAuthProtocol = "", $snmpPrivProtocol = "", $snmpPrivPassphrase = "") {
		$this->onDebug ( "retrouveTypeOSParSnmp", 1 );
		$donnees = $this->retrouveSnmp ( "sysDescr.0", $ip, $snmpCommunity, $snmpVersion, $snmpTimeout, $snmpRetry, $snmpUsername, $snmpPassword, $snmpAuthProtocol, $snmpPrivProtocol, $snmpPrivPassphrase );
		if ($donnees === false) {
			return false;
		}
		
		if(preg_match("/windows/i", $donnees)){
			$this->onDebug ( "OS trouve : WINDOWS", 1 );
			return "WINDOWS";
		} elseif(preg_match("/(linux|x86_64|suse|redhat|debian)/i", $donnees)){
			$this->onDebug ( "OS trouve : LINUX", 1 );
			return "LINUX";
		} elseif(preg_match("/(unix|AIX|Solaris|HPUX|MVS|opensvc|SCO|SUN|True64|Veritas|VMS|VSE|bsd)/i", $donnees)){
			$this->onDebug ( "OS trouve : UNIX", 1 );
			return "UNIX";
		}
		
		return false;
	}

	/******************************* ACCESSEURS ********************************/

	/******************************* ACCESSEURS ********************************/
	
	/**
	 * Affiche le help.<br>
	 * @codeCoverageIgnore
	 */
	static public function help() {
		$help = parent::help ();
		
		$help [__CLASS__] ["text"] = array ();
		
		return $help;
	}
}
?>
