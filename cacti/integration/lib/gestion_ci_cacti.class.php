<?php
/**
 * Gestion des tranches horaires HO et NHO chez Steria.
 * @author tlemasso
 */

/**
 * class gestion_ci_cacti
 *
 * @package Steria
 * @subpackage Cacti
 */
class gestion_ci_cacti extends abstract_log {
	/**
	 * var privee
	 *
	 * @access private
	 * @var array
	 */
	private $ref_ci_cacti = array ();
	/**
	 * var privee
	 *
	 * @access private
	 * @var cacti_wsclient
	 */
	private $cacti_ws = null;
	/**
	 * var privee
	 *
	 * @access private
	 * @var string
	 */
	private $env_travail = "test";

	/*********************** Creation de l'objet *********************/
	/**
	 * Instancie un objet de type gestion_ci_cacti.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param requete_complexe_gestion_cacti &$db_cacti Pointeur sur la base cacti_gestion
	 * @param cacti_wsclient &$cacti_ws Pointeur sur un WebService cacti
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return gestion_ci_cacti
	 */
	static function &creer_gestion_ci_cacti(&$liste_option, &$db_cacti, &$cacti_ws, $sort_en_erreur = true, $entete = __CLASS__) {
		$objet = new gestion_ci_cacti ( $sort_en_erreur, $entete );
		$objet->_initialise ( array (
				"options" => $liste_option,
				"requete_complexe_gestion_cacti" => $db_cacti,
				"cacti_wsclient" =>$cacti_ws
		) );
	
		return $objet;
	}
	
	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return gestion_ci_cacti
	 */
	public function &_initialise($liste_class) {
		parent::_initialise($liste_class);
		
		if(!isset($liste_class["cacti_wsclient"])){
			$this->onError("Il faut un objet de type cacti_wsclient");
		}
		$this->setWSCacti ( $liste_class["cacti_wsclient"] );
		return $this;
	}
	
	/*********************** Creation de l'objet *********************/

	/**
	 * Constructeur.
	 * @codeCoverageIgnore
	 * @param string|Bool $sort_en_erreur  	Prend les valeurs oui/non ou true/false
	 * @param string $entete  Entete pour les logs.
	 * @return gestion_ci_cacti
	 */
	public function __construct($sort_en_erreur = false, $entete = __CLASS__) {
		parent::__construct ( $sort_en_erreur, $entete );
		
		return $this;
	}

	/****************** GESTION DE LA BASE GESTION_CACTI ***********************/
	/**
	 * Met en OK toutes les references au CI dans les tables de gestion_cacti
	 * @param int/string $ci_id Id du CI a mettre a jour
	 * @return gestion_ci_cacti|false
	 */
	public function valide_ci_dans_gestion_cacti($ci_id) {
		if ($ci_id === "") {
			$this->onError ( "Il faut un CI Id pour valider l'ajout" );
			return false;
		}
		// On met le status a zero (ajoute dans cacti)
		$this->getDb ()
			->requete_update_standard ( 'ci', array (
				"status" => "0" 
		), array (
				"id" => $ci_id 
		) );
		$this->getDb ()
			->requete_update_standard ( 'runtime', array (
				"value" => "HOST_UP" 
		), array (
				"parent_id" => $ci_id,
				"key" => "status",
				"table_parent" => "ci" 
		) );
		$this->getDb ()
			->requete_update_standard ( 'runtime', array (
				"value" => "ADDED" 
		), array (
				"parent_id" => $ci_id,
				"key" => "status_integration",
				"table_parent" => "ci" 
		) );
		$this->getDb ()
			->requete_update_standard ( 'runtime', array (
				"value" => $this->getObjetListeDate ()
					->extraire_date_mysql_timestamp ( time () ) 
		), array (
				"parent_id" => $ci_id,
				"key" => "date_integration",
				"table_parent" => "ci" 
		) );
		
		return $this;
	}

	/**
	 * Met en OK toutes les references au CI dans les tables de gestion_cacti
	 * @param int/string $ci_id Id du CI a mettre a jour
	 * @return gestion_ci_cacti|false
	 */
	public function updated_ci_dans_gestion_cacti($ci_id) {
		if ($ci_id === "") {
			$this->onError ( "Il faut un CI Id a modifier" );
			return false;
		}
		// On met le status a zero (ajoute dans cacti)
		$this->getDb ()
			->requete_update_standard ( 'ci', array (
				"status" => "0" 
		), array (
				"id" => $ci_id 
		) );
		$this->getDb ()
			->requete_update_standard ( 'runtime', array (
				"value" => "UPDATED" 
		), array (
				"parent_id" => $ci_id,
				"key" => "status_integration",
				"table_parent" => "ci" 
		) );
		$this->getDb ()
			->requete_update_standard ( 'runtime', array (
				"value" => $this->getObjetListeDate ()
					->extraire_date_mysql_timestamp ( time () ) 
		), array (
				"parent_id" => $ci_id,
				"key" => "date_integration",
				"table_parent" => "ci" 
		) );
		
		return $this;
	}

	/**
	 * Met en NOK toutes les references au CI dans les tables de gestion_cacti
	 * @param int/string $ci_id Id du CI a mettre a jour
	 * @param string $msg Message d'erreur
	 * @return gestion_ci_cacti|false
	 */
	public function invalide_ci_dans_gestion_cacti($ci_id, $msg) {
		if ($ci_id === "") {
			$this->onError ( "Il faut un CI Id a modifier" );
			return false;
		}
		$this->getDb ()
			->requete_update_standard ( 'runtime', array (
				"value" => "HOST_ERROR" 
		), array (
				"parent_id" => $ci_id,
				"key" => "status",
				"table_parent" => "ci" 
		) );
		$this->getDb ()
			->requete_update_standard ( 'runtime', array (
				"value" => $msg 
		), array (
				"parent_id" => $ci_id,
				"key" => "status_integration",
				"table_parent" => "ci" 
		) );
		$this->getDb ()
			->requete_update_standard ( 'runtime', array (
				"value" => $this->getObjetListeDate ()
					->extraire_date_mysql_timestamp ( time () ) 
		), array (
				"parent_id" => $ci_id,
				"key" => "date_integration",
				"table_parent" => "ci" 
		) );
		
		return $this;
	}

	/**
	 * Supprime toutes les references au CI dans les tables de gestion_cacti
	 * @param int/string $ci_id Id du CI a supprimer
	 * @return gestion_ci_cacti|false
	 */
	public function supprime_ci_de_gestion_cacti($ci_id) {
		if ($ci_id === "") {
			$this->onError ( "Il faut un CI Id a supprimer" );
			return false;
		}
		$this->getDb ()
			->requete_delete_standard ( 'runtime', array (
				"parent_id" => $ci_id,
				"table_parent" => "ci" 
		) );
		$this->getDb ()
			->requete_delete_standard ( 'props', array (
				"parent_id" => $ci_id,
				"table_parent" => "ci" 
		) );
		$this->getDb ()
			->requete_delete_standard ( 'ci', array (
				"id" => $ci_id 
		) );
		
		return $this;
	}

	/****************** GESTION DE LA BASE GESTION_CACTI ***********************/
	
	/****************** AJOUT D'UN CI ***********************/
	/**
	 * Selectionne la liste de ci a ajouter et les ajoutent.
	 * @param array &$serveur Donnees du serveur issues de la table serveur de gestion_cacti
	 * @return gestion_ci_cacti|false
	 */
	public function ajouter_liste_ci_dans_cacti(&$serveur) {
		// On recupere la liste a supprimer
		$liste_ci_a_ajouter = $this->getDb ()
			->requete_select_standard ( 'ci', array (
				"serveur_id" => $serveur ["id"],
				"status" => "1" 
		) );
		if ($liste_ci_a_ajouter === false) {
			$this->onError ( "Erreur durant la requete sur gestion_cacti" );
			return false;
		}
		foreach ( $liste_ci_a_ajouter as $ci ) {
			$this->ajoute_ci_dans_cacti ( $ci, $serveur );
		}
		
		return $this;
	}

	/**
	 * Ajout un ci via WebService.
	 * @param &$ci donnees du ci recupere dans la table ci de gestion_cacti
	 * @param array &$serveur Donnees du serveur issues de la table serveur de gestion_cacti
	 * @return gestion_ci_cacti|false
	 */
	public function ajoute_ci_dans_cacti(&$ci, &$serveur) {
		$this->onInfo ( "Ci a ajouter en cours : " . $ci ["name"] );
		// On resette le tableau de parametre
		$this->getWSCacti ()
			->setParams ( "env", $this->getEnvTravail () );
		$this->getWSCacti ()
			->setParams ( "cacti_env", $serveur ["cacti_env"], true );
		$this->getWSCacti ()
			->setParams ( "description", $this->getWSCacti ()
			->getObjetCactiDatas ()
			->prepare_nom_ci_version_cacti ( $ci ["code_client"], $ci ["name"] ), true );
		
		if ($this->gere_proprietes_ci ( $ci ["id"], $ci ["name"] )) {
			$this->met_les_valeurs_par_defaut ( $serveur );
			
			// On ajoute le device
			$liste_retour = $this->getWSCacti ()
				->appel_ajouteDevice ();
			return $this->traite_retour_webservice ( $liste_retour, $ci ["id"], "1" );
		}
		
		return $this;
	}

	/**
	 * Retrouve le template client en fonction du type d'os de hobinv
	 * @param string $type_os Type d'os fourni par hobinv
	 * @param string $ci_name  Nom du CI pour l'affichage
	 * @return gestion_ci_cacti|false
	 */
	public function retrouve_template($type_os, $ci_name) {
		switch ($type_os) {
			case "WINDOWS" :
				$this->getWSCacti ()
					->setParams ( "template", "WINDOWS", true );
				break;
			case "LINUX" :
				$this->getWSCacti ()
					->setParams ( "template", "LINUX", true );
				break;
			case "HP-UX" :
			case "SOLARIS" :
			case "AS400" :
			case "NCR System V" :
			case "UNIX" :
				$this->getWSCacti ()
					->setParams ( "template", "UNIX", true );
				break;
			case "NETWORK" :
			case "Networks" :
				$this->getWSCacti ()
					->setParams ( "template", "NETWORK", true );
				break;
			default :
				$this->onError ( "Pas de template defini pour le ci " . $ci_name . " os : " . $type_os, "", 5005 );
				return false;
		}
		
		return $this;
	}

	/****************** AJOUT D'UN CI ***********************/
	
	/****************** MODIFICATION D'UN CI ***********************/
	/**
	 * Selectionne la liste de ci a modifier et les modifient.
	 * @param array &$serveur Donnees du serveur issues de la table serveur de gestion_cacti
	 * @return gestion_ci_cacti|false
	 */
	public function modifier_liste_ci_dans_cacti(&$serveur) {
		// On recupere la liste a supprimer
		$liste_ci_a_ajouter = $this->getDb ()
			->requete_select_standard ( 'ci', array (
				"serveur_id" => $serveur ["id"],
				"status" => "2" 
		) );
		if ($liste_ci_a_ajouter === false) {
			$this->onError ( "Erreur durant la requete sur gestion_cacti" );
			return false;
		}
		foreach ( $liste_ci_a_ajouter as $ci ) {
			$this->modifier_ci_dans_cacti ( $ci, $serveur );
		}
		
		return $this;
	}

	/**
	 * modifier un ci via WebService.
	 * @param &$ci donnees du ci recupere dans la table cide gestion_cacti
	 * @param array &$serveur Donnees du serveur issues de la table serveur de gestion_cacti
	 * @return gestion_ci_cacti|false
	 */
	public function modifier_ci_dans_cacti(&$ci, &$serveur) {
		$this->onInfo ( "Ci a modifier en cours : " . $ci ["name"] );
		// On resette le tableau de parametre
		$this->getWSCacti ()
			->setParams ( "env", $this->getEnvTravail () );
		$this->getWSCacti ()
			->setParams ( "cacti_env", $serveur ["cacti_env"], true );
		$this->getWSCacti ()
			->setParams ( "client", $serveur ["cacti_env"], true );
		$this->getWSCacti ()
			->setParams ( "description", $ci ["name"], true );
		$this->getWSCacti ()
			->setParams ( "update_ref", "description", true );
		
		if ($this->gere_proprietes_ci ( $ci ["id"], $ci ["name"] )) {
			// On update le device
			$this->valide_changement_ip ( $ci ["id"] );
			
			$liste_retour = $this->getWSCacti ()
				->appel_udateDevice ();
			return $this->traite_retour_webservice ( $liste_retour, $ci ["id"], "2" );
		}
		
		return $this;
	}

	/**
	 * Valide si c'est un chagement de type IP
	 * @param string $ci_id
	 * @return gestion_ci_cacti|false
	 */
	public function valide_changement_ip($ci_id) {
		$liste_runtime = $this->getDb ()
			->requete_select_standard ( 'runtime', array (
				"parent_id" => $ci_id,
				"table_parent" => 'ci' 
		) );
		if ($liste_runtime === false) {
			$this->onError ( "Erreur durant la requete sur gestion_cacti" );
			return false;
		}
		foreach ( $liste_runtime as $runtime ) {
			if (isset ( $runtime ["status_integration"] ) && $runtime ["status_integration"] == "IP TO UPDATE") {
				$this->getWSCacti ()
					->setParams ( "update_ref", "ip", true );
				break;
			}
		}
		
		return $this;
	}

	/****************** MODIFICATION D'UN CI ***********************/
	
	/****************** SUPPRESSION D'UN CI ***********************/
	/**
	 * Selectionne la liste de ci a supprimer et les suppriment.
	 * @param int $serveur_id Id du serveur dans la table serveur de gestion_cacti
	 * @param string $code_client Code du client (cacti_env)
	 * @return gestion_ci_cacti|false
	 */
	public function supprime_liste_ci_de_cacti($serveur_id, $code_client) {
		// On recupere la liste a supprimer
		$liste_ci_a_supprimer = $this->getDb ()
			->requete_select_standard ( 'ci', array (
				"serveur_id" => $serveur_id,
				"status" => "3" 
		) );
		if ($liste_ci_a_supprimer === false) {
			$this->onError ( "Erreur durant la requete sur gestion_cacti" );
			return false;
		}
		foreach ( $liste_ci_a_supprimer as $ci ) {
			$ci_runtime = $this->getDb ()
				->requete_select_standard ( 'runtime', array (
					"parent_id" => $ci ["id"],
					"key" => "date_integration",
					"table_parent" => "ci" 
			) );
			if ($ci_runtime === false) {
				$this->onError ( "Erreur durant la requete sur gestion_cacti" );
				return false;
			}
			
			foreach ( $ci_runtime as $runtime ) {
				//On nettoie toutes les entrees dont la date de suppression est inferieur a maintenant
				if ($this->getObjetListeDate ()
					->timestamp_mysql_date ( $runtime ["value"] ) < time ()) {
					$this->supprime_ci_de_cacti ( $ci, $code_client );
				}
			}
		}
		
		return $this;
	}

	/**
	 * Supprime un ci via WebService.
	 * @param &$ci donnees du ci recupere dans la table cide gestion_cacti
	 * @param string $code_client Code du client (cacti_env)
	 * @return gestion_ci_cacti|false
	 */
	public function supprime_ci_de_cacti(&$ci, $code_client) {
		$this->onInfo ( "Ci a supprimer en cours : " . $ci ["name"] );
		$liste_ip_ci = $this->getDb ()
			->requete_select_standard ( 'props', array (
				"parent_id" => $ci ["id"],
				"table_parent" => "ci" 
		) );
		if ($liste_ip_ci === false) {
			$this->onError ( "Erreur durant la requete sur gestion_cacti" );
			return false;
		}
		foreach ( $liste_ip_ci as $ip ) {
			// On resette le tableau de parametre
			$this->getWSCacti ()
				->setParams ( "env", $this->getEnvTravail () );
			$this->getWSCacti ()
				->setParams ( "cacti_env", $code_client, true );
			$this->getWSCacti ()
				->setParams ( "ip", $ip ["value"], true );
			
			$liste_retour = $this->getWSCacti ()
				->appel_supprimeDevice ();
			$this->traite_retour_webservice ( $liste_retour, $ci ["id"], "3" );
		}
		
		return $this;
	}

	/****************** SUPPRESSION D'UN CI ***********************/
	
	/****************** GESTION DU WEBSERVICE ***********************/
	/**
	 * Fonction de traitement du retour du webservice pour gestion_cacti
	 * @param array|false $liste_retour
	 * @param int|string $ci_id Id du CI avec son code retour
	 * @param int $status_ci Status du ci : 0 ADDED, 1 TO ADD, 2 TO UPDATE, 3 TO DELETE 
	 * @return gestion_ci_cacti|false
	 */
	public function traite_retour_webservice(&$liste_retour, $ci_id, $status_ci) {
		if ($liste_retour === false) {
			$this->onError ( "Probleme avec l'appel cURL", $liste_retour, 2001 );
			return false;
		}
		if ($liste_retour ["success"] == true) {
			// On met le status a zero dans gestion_cacti (ajoute dans cacti)
			switch ($status_ci) {
				case "0" :
				//Status du ci deja ajoute
				case "1" :
					//Status d'ajout du ci
					return $this->valide_ci_dans_gestion_cacti ( $ci_id );
					break;
				case "2" :
					//Status d'update du ci
					return $this->updated_ci_dans_gestion_cacti ( $ci_id );
					break;
				case "3" :
					return $this->supprime_ci_de_gestion_cacti ( $ci_id );
					break;
				default :
					$this->onError ( "Status de CI inconnu : " . $status_ci );
					return false;
			}
		} else {
			//On met a jour le status dans gestion_cacti
			$this->onDebug ( $liste_retour ["return_code"] . " : " . $liste_retour ["message"], 1 );
			//En cas de doublon, il n'y a pas d'erreur dans cacti
			if (strpos ( $liste_retour ["message"], "Doublon en base." ) === false) {
				$this->invalide_ci_dans_gestion_cacti ( $ci_id, $liste_retour ["message"] );
			} else {
				$this->valide_ci_dans_gestion_cacti ( $ci_id );
			}
		}
		
		return $this;
	}

	/**
	 * Ajoute a l'appel WS la liste connue des proprietes d'un ci
	 * @param int/string $ci_id Id du CI a mettre a jour
	 * @param string $ci_name  Nom du CI pour l'affichage
	 * @return gestion_ci_cacti|false
	 */
	public function gere_proprietes_ci($ci_id, $ci_name) {
		$liste_props = $this->getDb ()
			->requete_select_standard ( 'props', array (
				"table_parent" => 'ci',
				"parent_id" => $ci_id 
		) );
		if ($liste_props === false) {
			$this->onError ( "Erreur durant la requete sur gestion_cacti" );
			return false;
		}
		foreach ( $liste_props as $props ) {
			switch ($props ["key"]) {
				case "type_os" :
					if ($this->retrouve_template ( $props ["value"], $ci_name ) === false) {
						$this->invalide_ci_dans_gestion_cacti ( $ci_id, "Pas de template correspondant a : " . $props ["value"] );
						return false;
					}
					break;
				case "hostname" :
					$this->getWSCacti ()
						->setParams ( "ip", $props ["value"], true );
					break;
				case "snmp_community" :
					$this->getWSCacti ()
						->setParams ( "snmp_community", $props ["value"], true );
					break;
				case "snmp_version" :
					$this->getWSCacti ()
						->setParams ( "snmp_version", $props ["value"], true );
					break;
				case "snmp_username" :
					$this->getWSCacti ()
						->setParams ( "snmp_username", $props ["value"], true );
					break;
				case "snmp_password" :
					$this->getWSCacti ()
						->setParams ( "snmp_password", $props ["value"], true );
					break;
				case "snmp_auth_protocol" :
					$this->getWSCacti ()
						->setParams ( "snmp_auth_protocol", $props ["value"], true );
					break;
				case "snmp_priv_passphrase" :
					$this->getWSCacti ()
						->setParams ( "snmp_priv_passphrase", $props ["value"], true );
					break;
				case "snmp_priv_protocol" :
					$this->getWSCacti ()
						->setParams ( "snmp_priv_protocol", $props ["value"], true );
					break;
				case "snmp_context" :
					$this->getWSCacti ()
						->setParams ( "snmp_context", $props ["value"], true );
					break;
				case "availability_method" :
					$this->getWSCacti ()
						->setParams ( "availability_method", $props ["value"], true );
					break;
			}
		}
		
		return $this;
	}

	/**
	 * Valide la presence de valeur minimales et met les valeurs par defaut sinon.
	 * @param array &$serveur Donnees du serveur issues de la table serveur de gestion_cacti
	 * @return gestion_ci_cacti
	 */
	public function met_les_valeurs_par_defaut(&$serveur) {
		$liste_param = $this->getWSCacti ()
			->getParams ();
		if (! isset ( $liste_param ["availability_method"] )) {
			$this->getWSCacti ()
				->setParams ( "availability_method", $serveur ["availability_method"], true );
		}
		
		return $this;
	}

	/****************** GESTION DU WEBSERVICE ***********************/
	
	/******************************* ACCESSEURS ********************************/
	/**
	 * @codeCoverageIgnore
	 */
	public function getRefCiCacti() {
		return $this->ref_ci_cacti;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setRefCiCacti($ref_ci_cacti) {
		$this->ref_ci_cacti = $ref_ci_cacti;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &AjouteRefCiCacti($ci) {
		$this->ref_ci_cacti [$ci] = 1;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function valide_ci_existe_dans_ref_cacti($ci) {
		return isset ( $this->ref_ci_cacti [$ci] );
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &getWSCacti() {
		return $this->cacti_ws;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setWSCacti($cacti_ws) {
		$this->cacti_ws = $cacti_ws;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getEnvTravail() {
		return $this->env_travail;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setEnvTravail($env_travail) {
		$this->env_travail = $env_travail;
		return $this;
	}

	/******************************* ACCESSEURS ********************************/
	
	/**
	 * @static
	 * @codeCoverageIgnore
	 * @param string $echo 	Affiche le help
	 * @return string Renvoi le help
	 */
	static function help() {
		$help = parent::help ();
		
		$help [__CLASS__] ["text"] = array ();
		
		return $help;
	}
}
// Fin de la class
?>