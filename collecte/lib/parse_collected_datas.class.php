<?php

/**
 * Gestion de donnees.
 * @author dvargas
 */
/**
 * class parse_collected_datas
 *
 * @package Collected
 */
class parse_collected_datas extends abstract_log {
	/**
	 * var privee
	 * @access private
	 */
	private $donnees_source = array ();
	/**
	 * var privee
	 * @access private
	 */
	private $donnees_sortie = array ();
	/**
	 * var privee
	 * @access private
	 */
	private $separateur = "__ZSEPARATOR__";

	/**
	 * ********************* Creation de l'objet ********************
	 */
	/**
	 * Instancie un objet de type parse_collected_datas. @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return parse_collected_datas
	 */
	static function &creer_parse_collected_datas(&$liste_option, $sort_en_erreur = false, $entete = __CLASS__) {
		$objet = new parse_collected_datas ( $sort_en_erreur, $entete );
		$objet ->_initialise ( array ( 
				"options" => $liste_option ) );
		
		return $objet;
	}

	/**
	 * Initialisation de l'objet @codeCoverageIgnore
	 * @param array $liste_class
	 * @return parse_collected_datas
	 */
	public function &_initialise($liste_class) {
		parent::_initialise ( $liste_class );
		
		return $this;
	}

	/**
	 * ********************* Creation de l'objet ********************
	 */
	
	/**
	 * Constructeur. @codeCoverageIgnore
	 * @param string|Bool $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @return true
	 */
	public function __construct($sort_en_erreur = false, $entete = __CLASS__) {
		// Gestion de abstract_log
		parent::__construct ( $sort_en_erreur, $entete );
	}

	public function parse_os() {
		$donnees = array ();
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			switch (trim ( $row_donnee ["commande"] )) {
				case 'hostname' :
					$donnees [0] ["titre"] = 'Fqdn';
					$donnees [0] ["valeurs"] = array ( 
							trim ( $row_donnee ["resultat"] ) );
					break;
				case 'cat /etc/redhat-release' :
					$donnees [1] ["titre"] = 'Release';
					$donnees [1] ["valeurs"] = array ( 
							trim ( $row_donnee ["resultat"] ) );
					break;
				case 'cat /proc/cpuinfo |grep processor |wc -l' :
					$donnees [2] ["titre"] = 'Nb Procs';
					$donnees [2] ["valeurs"] = array ( 
							trim ( $row_donnee ["resultat"] ) );
					break;
				case 'cat /proc/meminfo |grep MemTotal' :
					$donnees [3] ["titre"] = 'Memory';
					$donnees [3] ["valeurs"] = array ( 
							trim ( str_replace ( "MemTotal:", "", $row_donnee ["resultat"] ) ) );
					break;
				case 'uname -a' :
					$donnees [3] ["titre"] = 'Uname';
					$donnees [3] ["valeurs"] = array ( 
							trim ( $row_donnee ["resultat"] ) );
					break;
			}
		}
		
		return $this ->setDonneesSortie ( $donnees );
	}

	public function parse_hosts() {
		$donnees = array ();
		$donnees [0] ["titre"] = "hosts";
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			switch (trim ( $row_donnee ["commande"] )) {
				case 'cat /etc/hosts' :
					$liste_data = explode ( "\n", $row_donnee ["resultat"] );
					for($i = 0; $i < count ( $liste_data ); $i ++) {
						$donnees [0] ["valeurs"] [$i + 1] = trim ( $liste_data [$i] );
					}
					break 2;
			}
		}
		
		return $this ->setDonneesSortie ( $donnees );
	}

	public function parse_users() {
		$donnees = array ();
		$donnees [0] ["titre"] = "User" . $this ->getSeparateur () . "id" . $this ->getSeparateur () . "group_id" . $this ->getSeparateur () . "fullname";
		$pos = 0;
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			switch (trim ( $row_donnee ["commande"] )) {
				case 'cat /etc/passwd' :
					$liste_data = explode ( "\n", $row_donnee ["resultat"] );
					for($i = 0; $i < count ( $liste_data ); $i ++) {
						$liste_data [$i] = trim ( $liste_data [$i] );
						$datas = explode ( ":", $liste_data [$i] );
						if (count ( $datas ) > 4) {
							$donnees [0] ["valeurs"] [$pos] = $datas [0] . $this ->getSeparateur () . $datas [2] . $this ->getSeparateur () . $datas [3] . $this ->getSeparateur () . $datas [4];
							$pos ++;
						}
					}
					break 2;
			}
		}
		
		return $this ->setDonneesSortie ( $donnees );
	}

	public function parse_group() {
		$donnees = array ();
		$donnees [0] ["titre"] = "Group" . $this ->getSeparateur () . "Group_id" . $this ->getSeparateur () . "Users";
		$pos = 0;
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			switch (trim ( $row_donnee ["commande"] )) {
				case 'cat /etc/group' :
					$liste_data = explode ( "\n", $row_donnee ["resultat"] );
					for($i = 0; $i < count ( $liste_data ); $i ++) {
						$liste_data [$i] = trim ( $liste_data [$i] );
						$datas = explode ( ":", $liste_data [$i] );
						if (count ( $datas ) > 3) {
							$donnees [0] ["valeurs"] [$pos] = $datas [0] . $this ->getSeparateur () . $datas [2] . $this ->getSeparateur () . $datas [3];
						}
						$pos ++;
					}
					break 2;
			}
		}
		
		return $this ->setDonneesSortie ( $donnees );
	}

	public function parse_chkconfig() {
		$donnees = array ();
		$donnees [0] ["titre"] = "chkconfig";
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			switch (trim ( $row_donnee ["commande"] )) {
				case 'sudo /sbin/chkconfig --list' :
					$liste_data = explode ( "\n", $row_donnee ["resultat"] );
					for($i = 0; $i < count ( $liste_data ); $i ++) {
						$donnees [0] ["valeurs"] [$i] = trim ( $liste_data [$i] );
					}
					break 2;
			}
		}
		
		return $this ->setDonneesSortie ( $donnees );
	}

	public function parse_sudo() {
		$donnees = array ();
		$donnees [0] ["titre"] = "sudoers";
		$donnees [0] ["valeurs"] = array ();
		$pos = 0;
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			$liste_data = explode ( "\n", $row_donnee ["resultat"] );
			for($i = 0; $i < count ( $liste_data ); $i ++) {
				if ($liste_data [$i] == "" || strpos ( $liste_data [$i], "#" ) === 0) {
					continue;
				}
				$donnees [0] ["valeurs"] [$pos] = trim ( $liste_data [$i] );
				$pos ++;
			}
		}
		
		return $this ->setDonneesSortie ( $donnees );
	}

	public function parse_rpm() {
		$donnees = array ();
		$donnees [0] ["titre"] = "rpm";
		$pos = 0;
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			$liste_data = explode ( "\n", $row_donnee ["resultat"] );
			for($i = 0; $i < count ( $liste_data ); $i ++) {
				$liste_data [$i] = trim ( $liste_data [$i] );
				$donnees [0] ["valeurs"] [$pos] = $liste_data [$i];
				$pos ++;
			}
		}
		
		return $this ->setDonneesSortie ( $donnees );
	}

	public function parse_cron() {
		$donnees = array ();
		$pos = 0;
		$column = 0;
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			$row_donnee ["commande"] = trim ( $row_donnee ["commande"] );
			if (strpos ( $row_donnee ["commande"], "cat /var/spool/cron/" ) !== false) {
				$liste_data = explode ( "\n", $row_donnee ["resultat"] );
				for($i = 0; $i < count ( $liste_data ); $i ++) {
					$liste_data [$i] = trim ( $liste_data [$i] );
					if ($liste_data [$i] == "" || strpos ( $liste_data [$i], "#" ) === 0) {
						continue;
					}
					if (strpos ( $liste_data [$i], "cat /var/spool/cron/" ) !== false) {
						$column ++;
						$user = str_replace ( "cat /var/spool/cron/", "", $liste_data [$i] );
						$donnees [$column] ["titre"] = $user;
						$donnees [$column] ["valeurs"] [$pos] = "";
						continue;
					}
					$donnees [$column] ["valeurs"] [$pos] = $liste_data [$i];
					$pos ++;
				}
			}
		}
		
		return $this ->setDonneesSortie ( $donnees );
	}

	public function parse_network() {
		$donnees_finale = array ();
		$pos = 0;
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			$row_donnee ["commande"] = trim ( $row_donnee ["commande"] );
			if (strpos ( $row_donnee ["commande"], "ifconfig -a" ) !== false) {
				$donnees_finale [0] ["titre"] = "Card" . $this ->getSeparateur () . "HWaddr" . $this ->getSeparateur () . "IP" . $this ->getSeparateur () . "Mask";
				$liste_data = explode ( "\n", $row_donnee ["resultat"] );
				$liste_eth = array ();
				$card = "";
				for($i = 0; $i < count ( $liste_data ); $i ++) {
					$liste_data [$i] = trim ( $liste_data [$i] );
					if (preg_match ( '/^(?<card>[a-zA-Z0-9:]+)\\s+.*HWaddr\\s(?<address>.*)$/', $liste_data [$i], $donnees )) {
						$card = $donnees ["card"];
						$liste_eth [$card] ["address"] = $donnees ["address"];
					} elseif (preg_match ( '/^lo\\s+.*Loopback$/', $liste_data [$i], $donnees )) {
						$card = "lo";
						$liste_eth [$card] ["address"] = "Local Loopback";
					} elseif (preg_match ( '/inet addr\:(?<ip>[0-9.]+)\\s+.*Mask\:(?<mask>.*)/', $liste_data [$i], $donnees )) {
						$liste_eth [$card] ["ip"] = $donnees ["ip"];
						$liste_eth [$card] ["mask"] = $donnees ["mask"];
					}
				}
				
				foreach ( $liste_eth as $card => $datas ) {
					$donnees_finale [0] ["valeurs"] [$pos] = $card;
					if (isset ( $datas ["address"] )) {
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $datas ["address"];
					} else {
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur ();
					}
					if (isset ( $datas ["ip"] )) {
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $datas ["ip"];
					} else {
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur ();
					}
					if (isset ( $datas ["mask"] )) {
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $datas ["mask"];
					} else {
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur ();
					}
					$pos ++;
				}
			} else if (strpos ( $row_donnee ["commande"], "lsof -i" ) !== false) {
				$donnees_finale [0] ["titre"] = "ESTABLISHED" . $this ->getSeparateur () . "ip" . $this ->getSeparateur () . "port" . $this ->getSeparateur () . "protocol" . $this ->getSeparateur () . "apps" . $this ->getSeparateur () . "pid";
				$pos ++;
				$liste_data = explode ( "\n", $row_donnee ["resultat"] );
				for($i = 0; $i < count ( $liste_data ); $i ++) {
					$liste_data [$i] = trim ( $liste_data [$i] );
					if (preg_match ( '/^(?<apps>[a-zA-Z0-9_\-.:\/]+)\\s+(?<pid>[0-9]+)\\s+.*(?<protocol>(TCP|UDP)+)\\s+.*->(?<ip>[0-9.]+):(?<port>[0-9]+)\\s+/', $liste_data [$i], $donnees )) {
						$donnees_finale [0] ["valeurs"] [$pos] = $donnees ['ip'];
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $donnees ["port"];
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $donnees ["protocol"];
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $donnees ["apps"];
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $donnees ["pid"];
						$pos ++;
					}
				}
			}
		}
		
		return $this ->setDonneesSortie ( $donnees_finale );
	}

	public function parse_sockets() {
		$donnees_finale = array ();
		$pos = 0;
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			$row_donnee ["commande"] = trim ( $row_donnee ["commande"] );
			if (strpos ( $row_donnee ["commande"], "ss -lptnu" ) !== false) {
				$donnees_finale [0] ["titre"] = "OPEN" . $this ->getSeparateur () . "ip" . $this ->getSeparateur () . "port" . $this ->getSeparateur () . "protocol" . $this ->getSeparateur () . "apps" . $this ->getSeparateur () . "pid";
				$pos ++;
				$liste_data = explode ( "\n", $row_donnee ["resultat"] );
				$liste_eth = array ();
				for($i = 0; $i < count ( $liste_data ); $i ++) {
					$liste_data [$i] = trim ( $liste_data [$i] );
					if (preg_match ( "/^(?<proto>(tcp|udp))\\s+.*\\s+(?<net>([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+|\*|\:\:)\:\d+)\\s+users:\(\(\"(?<apps>.*)\",(?<pid>\d+),\d+/", $liste_data [$i], $donnees )) {
						$local = count ( $liste_eth );
						$liste_eth [$local] ["protocol"] = $donnees ["proto"];
						$liste = explode ( ":", $donnees ["net"] );
						$liste_eth [$local] ["ip"] = $liste [0];
						$liste_eth [$local] ["port"] = $liste [count ( $liste ) - 1];
						$liste_eth [$local] ["apps"] = $donnees ["apps"];
						$liste_eth [$local] ["pid"] = $donnees ["pid"];
					}
				}
				
				foreach ( $liste_eth as $datas ) {
					$donnees_finale [0] ["valeurs"] [$pos] = $datas ['ip'];
					$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $datas ["port"];
					$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $datas ["protocol"];
					$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $datas ["apps"];
					$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $datas ["pid"];
					$pos ++;
				}
			}
		}
		
		return $this ->setDonneesSortie ( $donnees_finale );
	}

	public function parse_filesystem() {
		$donnees_finale = array ();
		$pos = 0;
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			$row_donnee ["commande"] = trim ( $row_donnee ["commande"] );
			if ($row_donnee ["commande"] == "df -PT") {
				$donnees_finale [0] ["titre"] = "Filesystem" . $this ->getSeparateur () . "Mount point" . $this ->getSeparateur () . "Type" . $this ->getSeparateur () . "Size";
				$pos ++;
				$liste_data = explode ( "\n", $row_donnee ["resultat"] );
				for($i = 0; $i < count ( $liste_data ); $i ++) {
					$liste_data [$i] = trim ( $liste_data [$i] );
					if (preg_match ( '/^(?<fs>[a-zA-Z0-9_\-.:\/]+)\\s+(?<type>[a-zA-Z0-9]+)\\s+.*\\s+(?<size>[0-9]+)\\s+.*\\s+(?<mount>[a-zA-Z0-9_\-.\/]+)$/', $liste_data [$i], $donnees )) {
						$donnees_finale [0] ["valeurs"] [$pos] = $donnees ["fs"];
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $donnees ["mount"];
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $donnees ["type"];
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $donnees ["size"];
						$pos ++;
					}
				}
			}
		}
		
		return $this ->setDonneesSortie ( $donnees_finale );
	}

	public function parse_disk() {
		$donnees_finale = array ();
		$pos = 0;
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			$row_donnee ["commande"] = trim ( $row_donnee ["commande"] );
			if ($row_donnee ["commande"] == "cat /proc/partitions") {
				$donnees_finale [0] ["titre"] = "Disk";
				$pos ++;
				$liste_data = explode ( "\n", $row_donnee ["resultat"] );
				for($i = 0; $i < count ( $liste_data ); $i ++) {
					$liste_data [$i] = trim ( $liste_data [$i] );
					if (preg_match ( '/^.*\\s(?<size>[0-9]+)\\s(?<disk>xvd[a-z]|sd[a-z])$/', $liste_data [$i], $donnees )) {
						$donnees_finale [0] ["valeurs"] [$pos] = $donnees ["disk"];
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $donnees ["size"];
						$pos ++;
					}
				}
			}
		}
		
		return $this ->setDonneesSortie ( $donnees_finale );
	}

	public function parse_process() {
		$donnees_finale = array ();
		$pos = 0;
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			$row_donnee ["commande"] = trim ( $row_donnee ["commande"] );
			if (strpos ( $row_donnee ["commande"], "ps -efawww" ) !== false) {
				$pos = 1;
				$donnees_finale [0] ["titre"] = "User" . $this ->getSeparateur () . "Pid" . $this ->getSeparateur () . "Processus";
				$pos ++;
				$liste_data = explode ( "\n", $row_donnee ["resultat"] );
				$liste_finale = array ();
				for($i = 0; $i < count ( $liste_data ); $i ++) {
					$liste_data [$i] = trim ( $liste_data [$i] );
					if (preg_match ( '/^(?<user>[a-zA-Z0-9-_]+)\\s+(?<pid>[0-9]+)\\s+.*[0-9]{2}\:[0-9]{2}\:[0-9]{2}\\s(?<process>.*)$/', $liste_data [$i], $donnees )) {
						$liste_finale [$donnees ["user"]] [$donnees ["process"]] = $donnees ["pid"];
					}
				}
				foreach ( $liste_finale as $user => $liste_usage ) {
					foreach ( $liste_usage as $proc => $pid ) {
						$donnees_finale [0] ["valeurs"] [$pos] = $user;
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $pid;
						$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $proc;
						$pos ++;
					}
				}
			}
		}
		
		return $this ->setDonneesSortie ( $donnees_finale );
	}

	public function parse_nagios() {
		$donnees_finale = array ();
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			$row_donnee ["commande"] = trim ( $row_donnee ["commande"] );
			if (strpos ( $row_donnee ["commande"], "nagios" ) !== false) {
				if (strpos ( $row_donnee ["commande"], "nrpe" ) !== false) {
					$pos = 1;
					$donnees_finale [0] ["titre"] = "NRPE";
					$donnees_finale [0] ["valeurs"] [$pos] = "";
					$liste_data = explode ( "\n", $row_donnee ["resultat"] );
					for($i = 0; $i < count ( $liste_data ); $i ++) {
						$liste_data [$i] = trim ( $liste_data [$i] );
						if ($liste_data [$i] == "" || strpos ( $liste_data [$i], "#" ) === 0) {
							continue;
						}
						$donnees_finale [0] ["valeurs"] [$pos] = $liste_data [$i];
						$pos ++;
					}
				} elseif (strpos ( $row_donnee ["commande"], "var/spool/cron" )) {
					$pos = 1;
					$donnees_finale [1] ["titre"] = "CRON";
					$donnees_finale [1] ["valeurs"] [$pos] = "";
					$liste_data = explode ( "\n", $row_donnee ["resultat"] );
					for($i = 0; $i < count ( $liste_data ); $i ++) {
						$liste_data [$i] = trim ( $liste_data [$i] );
						if ($liste_data [$i] == "" || strpos ( $liste_data [$i], "#" ) === 0) {
							continue;
						}
						$donnees_finale [1] ["valeurs"] [$pos] = $liste_data [$i];
						$pos ++;
					}
				}
			}
		}
		
		return $this ->setDonneesSortie ( $donnees_finale );
	}

	public function parse_nrpe_nagios() {
		$donnees = array ();
		$donnees [0] ["titre"] = "xinetd";
		$donnees [0] ["valeurs"] = array ();
		$pos = 0;
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			switch (trim ( $row_donnee ["commande"] )) {
				case 'cat /etc/xinetd.d/nrpe' :
					$liste_data = explode ( "\n", $row_donnee ["resultat"] );
					for($i = 0; $i < count ( $liste_data ); $i ++) {
						if ($liste_data [$i] == "" || strpos ( $liste_data [$i], "#" ) === 0 || strpos ( $liste_data [$i], "service nrpe" ) === 0 || strpos ( $liste_data [$i], "{" ) === 0 || strpos ( $liste_data [$i], "}" ) === 0) {
							continue;
						}
						$donnees [0] ["valeurs"] [$pos] = trim ( $liste_data [$i] );
						$pos ++;
					}
					break 2;
			}
		}
		
		return $this ->setDonneesSortie ( $donnees );
	}

	public function parse_plugins_nagios() {
		$donnees = array ();
		$donnees [0] ["titre"] = "plugin";
		$donnees [0] ["valeurs"] = array ();
		$pos = 0;
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			switch (trim ( $row_donnee ["commande"] )) {
				case 'ls /usr/local/nagios/libexec/' :
					$liste_data = explode ( "\n", $row_donnee ["resultat"] );
					for($i = 0; $i < count ( $liste_data ); $i ++) {
						$donnees [0] ["valeurs"] [$pos] = trim ( $liste_data [$i] );
						$pos ++;
					}
					break 2;
			}
		}
		
		return $this ->setDonneesSortie ( $donnees );
	}

	public function parse_logs() {
		$donnees_finale = array ();
		foreach ( $this ->getDonneesSource () as $row_donnee ) {
			$row_donnee ["commande"] = trim ( $row_donnee ["commande"] );
			if (strpos ( $row_donnee ["commande"], "_log|\.log|\.txt|\.out" ) !== false) {
				$pos = 1;
				$donnees_finale [0] ["titre"] = "Log" . $this ->getSeparateur () . "User" . $this ->getSeparateur () . "Application";
				$pos ++;
				$liste_data = explode ( "\n", $row_donnee ["resultat"] );
				$liste_finale = array ();
				for($i = 0; $i < count ( $liste_data ); $i ++) {
					$liste_data [$i] = trim ( $liste_data [$i] );
					
					if (preg_match ( '/^(?<appli>[a-zA-Z0-9-_]+)\\s+\\d+\\s+(?<user>[a-zA-Z0-9-_]+)\\s.* \/(?<log>.*)/', $liste_data [$i], $donnees )) {
						$liste_finale [$donnees ["log"]] [$donnees ["user"]] [$donnees ["appli"]] = 1;
					}
				}
				foreach ( $liste_finale as $log => $liste_usage ) {
					foreach ( $liste_usage as $user => $liste_appli ) {
						foreach ( $liste_appli as $appli => $inutile ) {
							$donnees_finale [0] ["valeurs"] [$pos] = "/" . $log;
							$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $user;
							$donnees_finale [0] ["valeurs"] [$pos] .= $this ->getSeparateur () . $appli;
							$pos ++;
						}
					}
				}
			}
		}
		
		return $this ->setDonneesSortie ( $donnees_finale );
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * @codeCoverageIgnore
	 */
	public function getDonneesSource() {
		return $this->donnees_source;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function setDonneesSource($donnees) {
		$this->donnees_source = $donnees;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getDonneesSortie() {
		return $this->donnees_sortie;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function setDonneesSortie($donnees) {
		$this->donnees_sortie = $donnees;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getSeparateur() {
		return $this->separateur;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function setSeparateur($separateur) {
		$this->separateur = $separateur;
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
		
		return $help;
	}
}
?>
