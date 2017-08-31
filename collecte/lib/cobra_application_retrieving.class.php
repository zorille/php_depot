<?php

/**
 * Gestion de donnees.
 * @author dvargas
 */
/**
 * class cobra_application_retrieving
 *
 * @package Collected
 */
class cobra_application_retrieving extends abstract_log {
	/**
	 * var privee
	 * @access private
	 */
	private $donnees_source = "";
	/**
	 * var privee
	 * @access private
	 */
	private $ref_applications = array ();
	/**
	 * var privee
	 * @access private
	 */
	private $liste_donnees = array ();
	/**
	 * var privee
	 * @access private
	 * @var requete_complexe_gestion_sam
	 */
	private $db_gestion_sam = NULL;

	/**
	 * ********************* Creation de l'objet ********************
	 */
	/**
	 * Instancie un objet de type cobra_application_retrieving. @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param requete_complexe_gestion_sam $db_gestion_sam Reference sur un objet requete_complexe_gestion_sam
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return cobra_application_retrieving
	 */
	static function &creer_cobra_application_retrieving(
			&$liste_option, 
			&$db_gestion_sam, 
			$sort_en_erreur = true, 
			$entete = __CLASS__) {
		$objet = new cobra_application_retrieving ( $sort_en_erreur, $entete );
		$objet->_initialise ( array (
				"options" => $liste_option,
				"requete_complexe_gestion_sam" => $db_gestion_sam 
		) );
		
		return $objet;
	}

	/**
	 * Initialisation de l'objet @codeCoverageIgnore
	 * @param array $liste_class
	 * @return cobra_application_retrieving
	 */
	public function &_initialise(
			$liste_class) {
		parent::_initialise ( $liste_class );
		
		$this->setObjetGestionSam ( $liste_class ["requete_complexe_gestion_sam"] )
			->retrouve_liste_applications ();
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
	public function __construct(
			$sort_en_erreur = true, 
			$entete = __CLASS__) {
		// Gestion de abstract_log
		parent::__construct ( $sort_en_erreur, $entete );
	}

	public function ajoute_donnees_dans_listeDonnees(
			$app, 
			$ci_id, 
			$nom, 
			$fullpathname) {
		$liste_finale = $this->getListeDonnees ();
		
		if (! isset ( $liste_finale [$app] )) {
			$liste_finale [$app] = array ();
		}
		if (! isset ( $liste_finale [$app] [$ci_id] )) {
			$liste_finale [$app] [$ci_id] = array ();
		}
		if (! isset ( $liste_finale [$app] [$ci_id] [$nom] )) {
			$liste_finale [$app] [$ci_id] [$nom] = $fullpathname;
		}
		
		$this->setListeDonnees ( $liste_finale );
		return $this;
	}

	public function retrouve_liste_applications() {
		$liste_apps = $this->getObjetGestionSam ()
			->requete_select_standard ( "serveur" );
		$ref_app = array ();
		foreach ( $liste_apps as $app ) {
			$ref_app [$app ["name"]] = $app ["id"];
			$this->getObjetGestionSam ()
				->requete_delete_standard ( "tree", array (
					"ci_id" => $app ["id"] 
			) );
		}
		
		return $this->setRefApplications ( $ref_app );
	}

	public function retrieving_Tower_Watson(
			$Ci_Id) {
		if (preg_match ( '/.*jcorejava.*(?<apps>tw\.[a-zA-Z]+\.[a-zA-Z]+)$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "Tower Watson", $Ci_Id, $donnees ["apps"], $donnees ["apps"] );
		}
		
		return $this;
	}

	public function retrieving_jcore(
			$Ci_Id) {
		if (preg_match ( '/.*jcorejava.*\\s+(?<apps>[a-zA-Z.]+)$/', $this->getDonneesSource (), $donnees )) {
			if (strpos ( $this->getDonneesSource (), ".tw." ) === false) {
				$donnees ["apps"] = str_replace ( "com.", "", $donnees ["apps"] );
				$donnees ["apps"] = str_replace ( "ctd.", "", $donnees ["apps"] );
				$donnees ["apps"] = str_replace ( "core.", "", $donnees ["apps"] );
				$donnees ["apps"] = str_replace ( ".Main", "", $donnees ["apps"] );
				$this->ajoute_donnees_dans_listeDonnees ( "CoreServer", $Ci_Id, str_replace ( ".", " ", $donnees ["apps"] )." (Jcore)", $donnees ["apps"] );
			}
		}
		
		return $this;
	}

	public function retrieving_ccore(
			$Ci_Id) {
		if (preg_match ( '/.*\/core\/bin\/(?<apps>[a-zA-Z_]+)\\s+-c.*$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "CoreServer", $Ci_Id, str_replace ( "_", " ", $donnees ["apps"] )." (Ccore)", $donnees ["apps"] );
		}
		
		return $this;
	}

	public function retrieving_kannel(
			$Ci_Id) {
		if (preg_match ( '/.*kannel\/sbin\/(?<apps>[a-zA-Z]+box)\\s+.*$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "CoreServer", $Ci_Id, str_replace ( "_", " ", $donnees ["apps"] )." (Kannel)", $donnees ["apps"] );
		}
		
		return $this;
	}

	public function retrieving_susan(
			$Ci_Id) {
		if (preg_match ( '/^jboss\:.*jboss\.node\.name=(?<apps>susan[a-zA-Z0-9_]+)(|\\s+.*)$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "Susan", $Ci_Id, str_replace ( "_", " ", $donnees ["apps"] ), $donnees ["apps"] );
		} elseif (preg_match ( '/^jboss\:.*jboss\.node\.name=(?<apps>pdf[a-zA-Z0-9_]+)(|\\s+.*)$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "Susan", $Ci_Id, str_replace ( "_", " ", $donnees ["apps"] ), $donnees ["apps"] );
		} elseif (preg_match ( '/^jboss\:.*jboss\.node\.name=(?<apps>bus)(|\\s+.*)$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "Susan", $Ci_Id, str_replace ( "_", " ", $donnees ["apps"] ), $donnees ["apps"] );
		} elseif (preg_match ( '/^jboss\:.*jboss\.node\.name=(?<apps>webservices)(|\\s+.*)$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "Susan", $Ci_Id, str_replace ( "_", " ", $donnees ["apps"] ), $donnees ["apps"] );
		} elseif (preg_match ( '/^jboss\:.*jboss\.node\.name=(?<apps>crash_services)(|\\s+.*)$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "Susan", $Ci_Id, str_replace ( "_", " ", $donnees ["apps"] ), $donnees ["apps"] );
		} elseif (preg_match ( '/^jboss\:.*jboss\.node\.name=(?<apps>maia)(|\\s+.*)$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "Maia", $Ci_Id, str_replace ( "_", " ", $donnees ["apps"] ), $donnees ["apps"] );
		} elseif (preg_match ( '/^jboss\:.*jboss\.node\.name=(?<apps>gprs)(|\\s+.*)$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "Susan", $Ci_Id, str_replace ( "_", " ", $donnees ["apps"] ), $donnees ["apps"] );
		} elseif (preg_match ( '/^jboss\:.*jboss\.node\.name=(?<apps>sms)(|\\s+.*)$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "Susan", $Ci_Id, str_replace ( "_", " ", $donnees ["apps"] ), $donnees ["apps"] );
		} elseif (preg_match ( '/^jboss\:.*jboss\.node\.name=(?<apps>sms)(|\\s+.*)$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "Susan", $Ci_Id, str_replace ( "_", " ", $donnees ["apps"] ), $donnees ["apps"] );
		} elseif (preg_match ( '/^jboss\:.*\/home\/jboss\/(?<apps>node-smart-susan-[0-9]+)(|\\s+.*)$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "Susan", $Ci_Id, str_replace ( "_", " ", $donnees ["apps"] ), $donnees ["apps"] );
		} elseif (preg_match ( '/^jboss\:.*\[Server:(?<apps>[a-zA-Z0-9_-]+)\]\\s+.*fleet.*$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "Fleet", $Ci_Id, str_replace ( "_", " ", $donnees ["apps"] ), $donnees ["apps"] );
		} elseif (preg_match ( '/^jboss\:.*\[Process Controller\]\\s+.*$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "Fleet", $Ci_Id, "Jboss DC", "Jboss_DC" );
		} elseif (preg_match ( '/^jboss\:.*$/', $this->getDonneesSource (), $donnees )) {
			$this->onWarning ( "Process Jboss not assigned : " . $this->getDonneesSource () );
		}
		
		return $this;
	}

	public function retrieving_logmon(
			$Ci_Id) {
		if (preg_match ( '/^nexo\:.*logmon\\s+.*$/', $this->getDonneesSource (), $donnees )) {
			$this->ajoute_donnees_dans_listeDonnees ( "Monitoring", $Ci_Id, "Logmon", "Logmon" );
		}
		
		return $this;
	}

	public function update_db() {
		$liste_apps = $this->getRefApplications ();
		foreach ( $this->getListeDonnees () as $APP => $donnees_app ) {
			foreach ( $donnees_app as $Ci_Id => $liste_app_process ) {
				foreach ( $liste_app_process as $valeur => $fullpathname ) {
					$this->getObjetGestionSam ()
						->requete_insert_standard ( 'tree', array (
							'id' => fonctions_standards::uuid_perso ( $Ci_Id ),
							'ci_id' => $liste_apps [$APP],
							'parent_id' => $Ci_Id,
							'name' => $valeur,
							'fullpathname' => $fullpathname 
					) );
				}
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
	public function getDonneesSource() {
		return $this->donnees_source;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setDonneesSource(
			$donnees) {
		$this->donnees_source = $donnees;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getRefApplications() {
		return $this->ref_applications;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setRefApplications(
			$donnees) {
		$this->ref_applications = $donnees;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getListeDonnees() {
		return $this->liste_donnees;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setListeDonnees(
			$donnees) {
		$this->liste_donnees = $donnees;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 * @return requete_complexe_gestion_sam
	 */
	public function &getObjetGestionSam() {
		return $this->db_gestion_sam;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setObjetGestionSam(
			$donnees) {
		$this->db_gestion_sam = $donnees;
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
