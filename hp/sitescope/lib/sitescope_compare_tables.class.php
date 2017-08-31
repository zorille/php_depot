<?php
/**
 * Gestion de SiteScope.
 * @author dvargas
 */
/**
 * class sitescope_compare_tables
 *
 * @package Lib
 * @subpackage SiteScope
 */
class sitescope_compare_tables extends abstract_log {
	/**
	 * var privee
	 *
	 * @access private
	 * @var comparaison_resultat_sql
	 */
	private $synchro_datas = array ();
	
	/*********************** Creation de l'objet *********************/
	/**
	 * Instancie un objet de type sitescope_compare_tables.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return sitescope_compare_tables
	 */
	static function &creer_sitescope_compare_tables(&$liste_option, $sort_en_erreur = false, $entete = __CLASS__) {
		$objet = new sitescope_compare_tables ( $entete, $sort_en_erreur );
		$objet->_initialise ( array (
				"options" => $liste_option
		) );
	
		return $objet;
	}
	
	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return sitescope_compare_tables
	 */
	public function &_initialise($liste_class) {
		parent::_initialise($liste_class);
		
		$this->setSynchroDatas ( comparaison_resultat_sql::creer_comparaison_resultat_sql($this->getListeOptions()) );
		return $this;
	}
	
	/*********************** Creation de l'objet *********************/
	/**
	 * Constructeur.
	 *
	 * @param options $liste_options
	 *        	Pointeur sur les arguments.
	 * @param ssh_z|ftp $connexion
	 *        	connexion ftp/ssh existante.
	 * @param string|Bool $sort_en_erreur
	 *        	Prend les valeurs oui/non ou true/false
	 * @return true
	 */
	public function __construct($entete = __CLASS__, $sort_en_erreur = false) {
		// Gestion de abstract_log
		parent::__construct ( $sort_en_erreur, $entete );
	}
	
	/**
	 * Calcul un id de 10 caracteres max
	 *
	 * @param int $ref_id        	
	 * @param int $id_sitescope        	
	 * @return string
	 */
	private function _CalculId($ref_id, $id_sitescope) {
		$start_id = 100 + $id_sitescope;
		
		$id = $start_id . $ref_id . $id_sitescope;
		return $id;
	}
	
	/**
	 * Applique la comparaison
	 *
	 * @param array $liste_source        	
	 * @param array $liste_dest        	
	 * @param string $table        	
	 * @param array $liste_champs        	
	 * @return array boolean des comparaison, false en cas d'erreur
	 */
	private function _AppliqueComparaison(&$liste_source, &$liste_dest, $table, $liste_champs) {
		if (! is_array ( $liste_dest )) {
			return $this->onError ( "Il faut un tableau issue de la table " . $table );
		}
		if ($table == "") {
			return $this->onError ( "Il faut le nom de la table " . $table );
		}
		$this->getSynchroDatas ()->synchro_table ( $liste_source, $liste_dest, $table, $liste_champs );
		$liste_modifs = array ();
		$liste_modifs ["supprime"] = $this->getSynchroDatas ()->getTableauSupprime ();
		$liste_modifs ["ajoute"] = $this->getSynchroDatas ()->getTableauAjoute ();
		
		$this->onDebug ( $liste_modifs, 2 );
		return $liste_modifs;
	}
	
	/**
	 * Compare les donnees issue du webservice "getFullConfiguration" avec les donnees issue de la table sitescope_ci.
	 *
	 * @param array $liste_machines
	 *        	Tableau contenant la liste des machines issue du webservice
	 * @param array $liste_sitescope_ci
	 *        	Tableau contenant la liste des machines issue de la table sitescope_ci
	 * @param
	 *        	string Nom de la table ci
	 * @param int $id_sitescope
	 *        	Numero du sitescope
	 * @param string $customer
	 *        	Nom du client
	 * @return array false des modification a faire, false en cas d'erreur et rien a faire
	 */
	public function compare_sitescope_ci($liste_machines, $liste_sitescope_ci, $table, $id_sitescope, $customer) {
		if ($liste_sitescope_ci === false) {
			return $this->onError ( "Pas de donnees dans la table sitescope_ci" );
		}
		$liste_machine_source = array ();
		$liste_champs = array (
				"id",
				"customer",
				"ci_name",
				"trouve" 
		);
		$pos = 0;
		if (! is_array ( $liste_machines )) {
			return $this->onError ( "Il faut un tableau contenant la liste des machines issue du webservice" );
		}
		foreach ( $liste_machines as $name => $machine ) {
			if ($name == "machines") {
				continue;
			}
			// |id|serveur_id|ref_id|_method|_os|_name|_id|_host|_status| trouve |
			$liste_machine_source [$pos] ["id"] = $this->_CalculId ( $machine ["type_os"] . $machine ["_id"], $id_sitescope );
			$liste_machine_source [$pos] ["customer"] = $customer;
			$liste_machine_source [$pos] ["ci_name"] = trim ( $machine ["_name"] );
			$liste_machine_source [$pos] ["trouve"] = 1;
			
			$pos ++;
		}
		
		return $this->_AppliqueComparaison ( $liste_machine_source, $liste_sitescope_ci, $table, $liste_champs );
	}
	
	/**
	 * Compare les donnees issue du webservice "getFullConfiguration" avec les donnees issue de la table ci.
	 *
	 *
	 * @param array $liste_machines
	 *        	Tableau contenant la liste des machines issue du webservice
	 * @param array $liste_ci
	 *        	Tableau contenant la liste des machines issue de la table ci
	 * @param
	 *        	string Nom de la table ci
	 * @param int $id_sitescope
	 *        	Numero du sitescope
	 * @return array false des modification a faire, false en cas d'erreur et rien a faire
	 */
	public function compare_ci($liste_machines, $liste_ci, $table, $id_sitescope) {
		if ($liste_ci === false) {
			return $this->onError ( "Pas de donnees dans la table ci" );
		}
		$liste_machine_source = array ();
		$liste_champs = array (
				"id",
				"serveur_id",
				"_name"
		);
		$pos = 0;
		if (! is_array ( $liste_machines )) {
			return $this->onError ( "Il faut un tableau contenant la liste des machines issue du webservice" );
		}
		foreach ( $liste_machines as $name => $machine ) {
			if ($name == "machines") {
				continue;
			}
			// |id|serveur_id|ref_id|_method|_os|_name|_id|_host|_status| trouve |
			$liste_machine_source [$pos] ["id"] = $this->_CalculId ( $machine ["type_os"] . $machine ["_id"], $id_sitescope );
			$liste_machine_source [$pos] ["serveur_id"] = $id_sitescope;
			$liste_machine_source [$pos] ["_name"] = $machine ["_name"];
			
			$pos ++;
		}
		
		return $this->_AppliqueComparaison ( $liste_machine_source, $liste_ci, $table, $liste_champs );
	}
	
	/**
	 * Compare les donnees issue du webservice "getFullConfiguration" avec les donnees issue de la table tree.
	 *
	 * @param array $liste_machines
	 *        	Tableau contenant la liste des machines issue du webservice
	 * @param array $liste_ci
	 *        	Tableau contenant la liste des machines issue de la table ci
	 * @param
	 *        	string Nom de la table ci
	 * @param int $id_sitescope
	 *        	Numero du sitescope
	 * @return array false des modification a faire, false en cas d'erreur et rien a faire
	 */
	public function compare_tree($liste_groupes, $liste_tree, $table, $id_sitescope) {
		if ($liste_tree === false) {
			return $this->onError ( "Pas de donnees dans la table tree" );
		}
		$liste_machine_source = array ();
		$liste_champs = array (
				"id",
				"serveur_id",
				"parent_id",
				"_name",
				"_fullpathname" 
		);
		$pos = 0;
		if (! is_array ( $liste_groupes )) {
			return $this->onError ( "Il faut un tableau contenant la liste des groupes issue du webservice" );
		}
		foreach ( $liste_groupes as $name => $propriete ) {
			if ($name == "machines") {
				continue;
			}
			$liste_machine_source [$pos] ["id"] = $propriete ["id"];
			$liste_machine_source [$pos] ["serveur_id"] = $id_sitescope;
			if ($propriete ["id_parent"] == - 1) {
				$id_parent = "-1";
			} else {
				$id_parent = $propriete ["id_parent"];
			}
			$liste_machine_source [$pos] ["parent_id"] = $id_parent;
			if (! isset ( $propriete ["_name"] )) {
				$propriete ["_name"] = $propriete ["fullpathname"];
			}
			$liste_machine_source [$pos] ["_name"] = $propriete ["_name"];
			$liste_machine_source [$pos] ["_fullpathname"] = str_replace ( "SiteScopeRoot!", "", $propriete ["fullpathname"] );
			// $liste_machine_source [$pos] ["_fullpathname"] = $propriete ["fullpathname"];
			
			$pos ++;
		}
		
		return $this->_AppliqueComparaison ( $liste_machine_source, $liste_tree, $table, $liste_champs );
	}
	
	/**
	 * Compare les donnees issue du webservice "getFullConfiguration" avec les donnees issue de la table prpops.
	 *
	 * @param array $liste_machines
	 *        	Tableau contenant la liste des machines issue du webservice
	 * @param array $liste_ci
	 *        	Tableau contenant la liste des machines issue de la table ci
	 * @param
	 *        	string Nom de la table ci
	 * @param int $id_sitescope
	 *        	Numero du sitescope
	 * @return array false des modification a faire, false en cas d'erreur et rien a faire
	 */
	public function compare_tree_props($liste_groupes, $liste_props, $table, $id_sitescope) {
		$liste_machine_source = array ();
		$pos = 100;
		if (! is_array ( $liste_groupes )) {
			return $this->onError ( "Il faut un tableau contenant la liste des groupes issue du webservice" );
		}
		foreach ( $liste_groupes as $name => $liste_data ) {
			$tree_id = $this->_CalculMonitorId ( $liste_data, $id_sitescope );
			
			foreach ( $liste_data as $key => $value ) {
				
				if ($name == "machines") {
					continue;
				}
				if (is_string ( $value ) || is_numeric ( $value )) {
					$value = trim ( $value );
					if ($value == "") {
						continue;
					}
				} else {
					continue;
				}
				$liste_machine_source [$pos] ["parent_id"] = $tree_id;
				$liste_machine_source [$pos] ["_key"] = $key;
				$liste_machine_source [$pos] ["_value"] = $value;
				$liste_machine_source [$pos] ["table_parent"] = 'tree';
				$pos ++;
			}
		}
		return $this->compare_props( $liste_machine_source, $liste_props, $table, $id_sitescope );
	}
	
	/**
	 * Calcul l'id d'un moniteur
	 *
	 * @param array $propriete        	
	 * @param array $liste_propriete        	
	 */
	private function _CalculMonitorId(&$liste_propriete, $id_sitescope) {
		if (isset ( $liste_propriete ["entitySnapshot_properties"] )) {
			$propriete = $liste_propriete ["entitySnapshot_properties"];
		} else {
			$propriete = $liste_propriete;
		}
		
		if (! is_array ( $propriete )) {
			$this->onDebug ( $propriete, 1 );
			return $this->onError ( "Il n'y a pas de proprietes pour calculer un id" );
		}
		
		// Gestion de l'id
		if (isset ( $propriete ["_externalId"] )) {
			// car l'external id est unique
			return $propriete ["_externalId"];
		} elseif (isset ( $propriete ["id"] )) {
			// id maison fait durant la recuperation des donnees du webservice
			return $propriete ["id"];
		} elseif (isset ( $propriete ["_name"] )) {
			// On fabrique un externalid a partir du _name
			if (isset ( $propriete ["_internalId"] )) {
				$id = $propriete ["_internalId"] . $propriete ["_name"];
			} else {
				$id = $propriete ["_name"];
			}
			$md5 = md5 ( $id );
			return substr ( $md5, 0, 8 ) . '-' . substr ( $md5, 8, 4 ) . '-' . substr ( $md5, 12, 4 ) . '-' . substr ( $md5, 16, 4 ) . '-' . substr ( $md5, 20, 12 );
		} elseif (isset ( $propriete ["_id"] )) {
			return $this->_CalculId ( $propriete ["_id"] . $liste_propriete ["group_id"], $id_sitescope );
		} else {
			$id = $this->_CalculId ( $liste_propriete ["group_id"], $id_sitescope );
		}
		
		return $id;
	}
	
	/**
	 * Compare les donnees issue du webservice "getFullConfiguration" avec les donnees issue de la table leaf.
	 *
	 * @param array $liste_machines
	 *        	Tableau contenant la liste des machines issue du webservice
	 * @param array $liste_ci
	 *        	Tableau contenant la liste des machines issue de la table ci
	 * @param
	 *        	string Nom de la table ci
	 * @param int $id_sitescope
	 *        	Numero du sitescope
	 * @return array false des modification a faire, false en cas d'erreur et rien a faire
	 */
	public function compare_leaf($liste_moniteurs, $liste_leaf, $table, $id_sitescope) {
		if ($liste_leaf === false) {
			return $this->onError ( "Pas de donnees dans la table leaf" );
		}
		$liste_machine_source = array ();
		$liste_champs = array (
				"id",
				"serveur_id",
				"parent_id",
				"_name",
				"deleted" 
		);
		$pos = 0;
		if (! is_array ( $liste_moniteurs )) {
			return $this->onError ( "Il faut un tableau contenant la liste des moniteurs issue du webservice" );
		}
		$tag = 0;
		foreach ( $liste_moniteurs as $name => $liste_data ) {
			foreach ( $liste_data as $liste_propriete ) {
				
				if ($name == "machines") {
					continue;
				}
				
				// Gestion de l'id
				$liste_machine_source [$pos] ["id"] = $this->_CalculMonitorId ( $liste_propriete, $id_sitescope );
				
				$liste_machine_source [$pos] ["serveur_id"] = $id_sitescope;
				$liste_machine_source [$pos] ["parent_id"] = $liste_propriete ["group_id"];
				
				if (! isset ( $liste_propriete ["entitySnapshot_properties"] ["_name"] )) {
					$liste_propriete ["entitySnapshot_properties"] ["_name"] = $liste_propriete ["fullpathname"];
				}
				$liste_machine_source [$pos] ["_name"] = htmlentities ( $liste_propriete ["entitySnapshot_properties"] ["_name"], ENT_COMPAT, 'UTF-8' );
				// $liste_machine_source [$pos] ["_fullpathname"] = htmlentities($liste_propriete ["fullpathname"],ENT_COMPAT,'UTF-8');
				$liste_machine_source [$pos] ["deleted"] = '0';
				
				$pos ++;
			}
		}
		
		return $this->_AppliqueComparaison ( $liste_machine_source, $liste_leaf, $table, $liste_champs );
	}
	
	/**
	 * Compare les donnees issue du webservice "getFullConfiguration" avec les donnees issue de la table prpops.
	 *
	 * @param array $liste_machines
	 *        	Tableau contenant la liste des machines issue du webservice
	 * @param array $liste_ci
	 *        	Tableau contenant la liste des machines issue de la table ci
	 * @param
	 *        	string Nom de la table ci
	 * @param int $id_sitescope
	 *        	Numero du sitescope
	 * @return array false des modification a faire, false en cas d'erreur et rien a faire
	 */
	public function compare_moniteurs_props($liste_moniteurs, $liste_props, $table, $id_sitescope) {
		$liste_machine_source = array ();
		$pos = 100;
		if (! is_array ( $liste_moniteurs )) {
			return $this->onError ( "Il faut un tableau contenant la liste des moniteurs issue du webservice" );
		}
		foreach ( $liste_moniteurs as $name => $liste_data ) {
			foreach ( $liste_data as $liste_propriete ) {
				
				if ($name == "machines") {
					continue;
				}
				
				$leaf_id = $this->_CalculMonitorId ( $liste_propriete, $id_sitescope );
				
				foreach ( $liste_propriete ["entitySnapshot_properties"] as $name => $value ) {
					if (is_string ( $value ) || is_numeric ( $value )) {
						$value = trim ( $value );
						if ($value == "") {
							continue;
						}
					} else {
						continue;
					}
					$liste_machine_source [$pos] ["parent_id"] = $leaf_id;
					$liste_machine_source [$pos] ["_key"] = $name;
					$liste_machine_source [$pos] ["_value"] = $value;
					$liste_machine_source [$pos] ["table_parent"] = "leaf";
					$pos ++;
				}
			}
		}
		
		return $this->compare_props($liste_machine_source, $liste_props, $table, $id_sitescope);
	}
	
	/**
	 * Compare les donnees issue du webservice "getFullConfiguration" avec les donnees issue de la table prpops.
	 *
	 * @param array $liste_machines
	 *        	Tableau contenant la liste des machines issue du webservice
	 * @param array $liste_ci
	 *        	Tableau contenant la liste des machines issue de la table ci
	 * @param
	 *        	string Nom de la table ci
	 * @param int $id_sitescope
	 *        	Numero du sitescope
	 * @return array false des modification a faire, false en cas d'erreur et rien a faire
	 */
	public function compare_creds($liste_creds, $liste_db_creds, $table, $id_sitescope) {
		if ($liste_db_creds === false) {
			return $this->onError ( "Pas de donnees dans la table credentials" );
		}
		$liste_champs = array (
				"serveur_id",
				"type",
				"name",
				"_key",
				"_value"
		);
	
		return $this->_AppliqueComparaison ( $liste_creds, $liste_db_creds, $table, $liste_champs );
	}
	
	/**
	 * Compare les donnees issue du webservice "getFullConfiguration" avec les donnees issue de la table prpops.
	 *
	 * @param array $liste_machines
	 *        	Tableau contenant la liste des machines issue du webservice
	 * @param array $liste_ci
	 *        	Tableau contenant la liste des machines issue de la table ci
	 * @param
	 *        	string Nom de la table ci
	 * @param int $id_sitescope
	 *        	Numero du sitescope
	 * @return array false des modification a faire, false en cas d'erreur et rien a faire
	 */
	public function compare_prefs($liste_prefs, $liste_db_prefs, $table, $id_sitescope) {
		if ($liste_db_prefs === false) {
			return $this->onError ( "Pas de donnees dans la table preferences" );
		}
		$liste_champs = array (
				"serveur_id",
				"type",
				"_key",
				"_value"
		);

		return $this->_AppliqueComparaison ( $liste_prefs, $liste_db_prefs, $table, $liste_champs );
	}
	
	/**
	 * Compare les donnees issue du webservice "getFullConfiguration" avec les donnees issue de la table prpops.
	 *
	 * @param array $liste_machines
	 *        	Tableau contenant la liste des machines issue du webservice
	 * @param array $liste_ci
	 *        	Tableau contenant la liste des machines issue de la table ci
	 * @param
	 *        	string Nom de la table ci
	 * @param int $id_sitescope
	 *        	Numero du sitescope
	 * @return array false des modification a faire, false en cas d'erreur et rien a faire
	 */
	public function compare_props($liste_props, $liste_db_props, $table, $id_sitescope) {
		if ($liste_db_props === false) {
			return $this->onError ( "Pas de donnees dans la table props" );
		}
		$liste_champs = array (
				"parent_id",
				"_key",
				"_value",
				"table_parent" 
		);
		
		return $this->_AppliqueComparaison ( $liste_props, $liste_db_props, $table, $liste_champs );
	}
	
	/**
	 * Compare les donnees issue du webservice "getFullConfiguration" avec les donnees issue de la table prpops.
	 *
	 * @param array $liste_machines
	 *        	Tableau contenant la liste des machines issue du webservice
	 * @param array $liste_ci
	 *        	Tableau contenant la liste des machines issue de la table ci
	 * @param
	 *        	string Nom de la table ci
	 * @param int $id_sitescope
	 *        	Numero du sitescope
	 * @return array false des modification a faire, false en cas d'erreur et rien a faire
	 */
	public function compare_runtime($liste_runtime, $liste_db_runtime, $table, $id_sitescope) {
		if ($liste_db_runtime === false) {
			return $this->onError ( "Pas de donnees dans la table runtime" );
		}
		$liste_champs = array (
				"parent_id",
				"_key",
				"_value",
				"table_parent" 
		);
		
		return $this->_AppliqueComparaison ( $liste_runtime, $liste_db_runtime, $table, $liste_champs );
	}
	
	/**
	 * Compare les donnees issue du webservice "getFullConfiguration" avec les donnees issue de la table alert.
	 *
	 * @param array $liste_machines
	 *        	Tableau contenant la liste des machines issue du webservice
	 * @param array $liste_ci
	 *        	Tableau contenant la liste des machines issue de la table ci
	 * @param
	 *        	string Nom de la table ci
	 * @param int $id_sitescope
	 *        	Numero du sitescope
	 * @return array false des modification a faire, false en cas d'erreur et rien a faire
	 */
	public function compare_alert($liste_moniteurs, $liste_alert, $table, $id_sitescope) {
		$this->onInfo ( "ID en cours : " . $id_sitescope );
		if ($liste_alert === false) {
			return $this->onError ( "Pas de donnees dans la table alert" );
		}
		$liste_machine_source = array ();
		$liste_champs = array (
				"serveur_id",
				"parent_id",
				"_name",
				"_fullpathname",
				"deleted" 
		);
		$pos = 100;
		if (! is_array ( $liste_moniteurs )) {
			return $this->onError ( "Il faut un tableau contenant la liste des moniteurs issue du webservice" );
		}
		foreach ( $liste_moniteurs as $name => $liste_data ) {
			foreach ( $liste_data as $liste_propriete ) {
				
				if ($name == "machines") {
					continue;
				}
				if (! isset ( $liste_propriete ["snapshot_alertSnapshotChildren"] )) {
					$this->onDebug ( $liste_propriete, 2 );
					$this->onWarning ( "Il n'y a pas d'alert pour ce moniteur : " . $liste_propriete ["entitySnapshot_name"] );
					continue;
				}
				$parent_id = $this->_CalculMonitorId ( $liste_propriete, $id_sitescope );
				foreach ( $liste_propriete ["snapshot_alertSnapshotChildren"] as $liste_alerte ) {
					$liste_machine_source [$pos] ["serveur_id"] = $id_sitescope;
					$liste_machine_source [$pos] ["parent_id"] = $parent_id;
					$liste_machine_source [$pos] ["_name"] = $liste_alerte ["entitySnapshot_name"];
					$liste_machine_source [$pos] ["_fullpathname"] = $liste_propriete ["fullpathname"];
					$liste_machine_source [$pos] ["deleted"] = '0';
					
					$pos ++;
				}
			}
		}
		
		return $this->_AppliqueComparaison ( $liste_machine_source, $liste_alert, $table, $liste_champs );
	}
	
	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	public function getSynchroDatas() {
		return $this->synchro_datas;
	}
	public function setSynchroDatas($synchro_datas) {
		$this->synchro_datas = $synchro_datas;
	}
	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	
	/**
	 * Affiche le help.<br>
	 */
	static public function help() {
		$help = parent::help ();
		
		$help [__CLASS__] ["text"] = array ();
		
		return $help;
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see lib/fork/message#__destruct()
	 */
	public function __destruct() {
	}
}
?>
