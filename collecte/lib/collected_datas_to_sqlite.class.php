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
class collected_datas_to_sqlite extends parse_collected_datas {
	/**
	 * var privee
	 *
	 * @access private
	 * @var requete
	 */
	private $objdb_gestion_sam = null;
	/**
	 * var privee
	 * @access private
	 */
	private $ci_id = "";

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
	static function &creer_collected_datas_to_sqlite(
			&$liste_option, 
			$sort_en_erreur = false, 
			$entete = __CLASS__) {
		$objet = new collected_datas_to_sqlite ( $sort_en_erreur, $entete );
		$objet->_initialise ( array (
				"options" => $liste_option 
		) );
		
		return $objet;
	}

	/**
	 * Initialisation de l'objet @codeCoverageIgnore
	 * @param array $liste_class
	 * @return parse_collected_datas
	 */
	public function &_initialise(
			$liste_class) {
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
	public function __construct(
			$sort_en_erreur = false, 
			$entete = __CLASS__) {
		// Gestion de abstract_log
		parent::__construct ( $sort_en_erreur, $entete );
	}

	public function parse_datas() {
		$this->parse_os ()
			->enregistrer_en_base ( "os" )
			->parse_filesystem ()
			->enregistrer_en_base ( "os" )
			->parse_network ()
			->enregistrer_en_base ( "network" )
			->parse_sockets ()
			->enregistrer_en_base ( "network" )
			->parse_hosts ()
			->enregistrer_en_base ( "os" )
			->parse_users ()
			->enregistrer_en_base ( "os" )
			->parse_group ()
			->enregistrer_en_base ( "os" )
			->parse_sudo ()
			->enregistrer_en_base ( "os" )
			->parse_cron ()
			->enregistrer_en_base ( "crontabs" )
			->parse_nagios ()
			->enregistrer_en_base ( "nagios" )
			->parse_logs ()
			->enregistrer_en_base ( "logs" )
			->parse_process ()
			->enregistrer_en_base ( "process" );
	}

	public function enregistrer_en_base(
			$nom_table) {
		if (! is_object ( $this->getObjetDbGestionSam () )) {
			return $this->onError ( "Il faut un connexion a la base sam SQLITE" );
		}
		if ($this->getCiId () == "") {
			return $this->onError ( "Il faut une machine (CI ID)" );
		} else {
			$serveur = trim ( $this->getCiId () );
		}
		
		foreach ( $this->getDonneesSortie () as $valeurs ) {
			$valeurs ["titre"] = str_replace ( $this->getSeparateur (), "^", $valeurs ["titre"] );
			foreach ( $valeurs ["valeurs"] as $valeur ) {
				if(trim($valeur)==""){
					continue;
				}
				$valeur = str_replace ( $this->getSeparateur (), "^", $valeur );
				$valeur = str_replace ( "'", "\"", $valeur );
				$this->getObjetDbGestionSam ()
					->faire_requete ( "insert into " . strtolower ( $nom_table ) . " (serveur,cle,valeur) VALUES ('" . $serveur . "','" . $valeurs ["titre"] . "','" . $valeur . "')" );
			}
		}
		
		return $this;
	}

	public function nettoyer_serveur_en_base(
			$nom_table) {
		if (! is_object ( $this->getObjetDbGestionSam () )) {
			return $this->onError ( "Il faut un connexion a la base sam SQLITE" );
		}
		if ($this->getCiId () == "") {
			return $this->onError ( "Il faut une machine (CI ID)" );
		} else {
			$serveur = trim ( $this->getCiId () );
		}
		
		$this->getObjetDbGestionSam ()
			->faire_requete ( "delete from " . strtolower ( $nom_table ) . " where serveur='" . $serveur . "';" );
		
		return $this;
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * @codeCoverageIgnore
	 * @return requete
	 */
	public function &getObjetDbGestionSam() {
		return $this->objdb_gestion_sam;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setObjetDbGestionSam(
			&$objdb_gestion_sam) {
		$this->objdb_gestion_sam = $objdb_gestion_sam;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getCiId() {
		return $this->ci_id;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function setCiId(
			$ci_id) {
		$this->ci_id = $ci_id;
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
