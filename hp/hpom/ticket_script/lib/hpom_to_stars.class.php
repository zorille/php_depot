<?php
/**
 * Gestion de hpom.
 * @author dvargas
 */
/**
 * class hpom_to_stars
 * @package HP
 * @subpackage HPOM
 */
class hpom_to_stars extends abstract_log {
	/**
	 * var privee
	 *
	 * @access private
	 * @var hpom
	 */
	private $hpom = null;
	/**
	 * var privee
	 *
	 * @access private
	 * @var fiche_categorie
	 */
	private $fiche_cat = null;
	/**
	 * var privee
	 *
	 * @access private
	 * @var stars_soap_IncidentManagement
	 */
	private $soap_stars = null;
	/**
	 * var privee
	 *
	 * @access private
	 * @var string
	 */
	private $CI_defaut = "";
	/**
	 * var privee
	 *
	 * @access private
	 * @var array
	 */
	private $retour_erreur = array ();
	/**
	 * var privee
	 *
	 * @access private
	 * @var array
	 */
	private $continue = true;

	/*********************** Creation de l'objet *********************/
	/**
	 * Instancie un objet de type hpom_to_stars.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return hpom_to_stars
	 */
	static function &creer_hpom_to_stars(&$liste_option, $sort_en_erreur = false, $entete = __CLASS__) {
		$objet = new hpom_to_stars ( $sort_en_erreur, $entete );
		return $objet->_initialise ( array (
				"options" => $liste_option 
		) );
	}

	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return hpom_to_stars
	 */
	public function &_initialise($liste_class) {
		parent::_initialise ( $liste_class );
		
		return $this;
	}

	/*********************** Creation de l'objet *********************/
	
	/**
	 * Constructeur.
	 * @codeCoverageIgnore
	 * @param string|Bool $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete entete de log
	 * @return true
	 */
	public function __construct($sort_en_erreur = false, $entete = __CLASS__) {
		// Gestion de abstract_log
		parent::__construct ( $sort_en_erreur, $entete );
	}

	/**
	 * met en place les exceptions pour le departement,et l'affectedCI
	 * @return hpom_to_stars
	 */
	public function prepare_exception_client() {
		switch ($this->getHpomObject ()
			->getCustomer ()) {
			case "CUST1" :
				$this->getHpomObject ()
					->setApplication ( "APPLICATION" )
					->setObjet ( "NONE" )
					->setDepartement ( "DEP1" );
				$this->setCIDefaut ( "CI1" );
				break;
			case "CUST2" :
				$this->getHpomObject ()
					->setApplication ( "APPLICATION" )
					->setObjet ( "NONE" )
					->setDepartement ( "DEP2" );
				$this->setCIDefaut ( "CI2" );
				break;
			default :
				$this->setCIDefaut ( $this->getHpomObject ()
					->getCustomer () . "_DEFAUT" );
		}
		
		return $this;
	}

	/**
	 * Prepare les donnees pour l'ouverture du ticket Stars
	 * @param array Parametres du serveur Stars
	 * @return array renvoi la definition d'un ticket Stars
	 */
	public function prepare_hpom_to_stars_datas($serveur_datas) {
		//On traite les exceptions
		$this->prepare_exception_client ();
		$ticket = array (
				"Service" => $this->getHpomObject ()
					->getDepartement () . "_MONITORING",
				"Contact" => $this->getHpomObject ()
					->getDepartement () . "_MONITORING_SCAUTO_1",
				"AffectedCI" => $this->getHpomObject ()
					->getAffectedCI (),
				"Title" => htmlentities ( $this->getHpomObject ()
					->getMsgText () ),
				"Description" => array (
						"Description" => htmlentities ( $this->getHpomObject ()
							->getDescription () ) 
				),
				"Company" => $this->getHpomObject ()
					->getCustomer (),
				"HPOMID" => $this->getHpomObject ()
					->getMsgId (),
				"Subarea" => $this->getHpomObject ()
					->getSubArea (),
				//donnees fiche categorie
				"Urgency" => $this->getFicheCategorieObject ()
					->getPriorite (),
				"Impact" => $this->getFicheCategorieObject ()
					->getImpact (),
				"AssignmentGroup" => $this->getFicheCategorieObject ()
					->getGroupe (),
				"KnowledgeDoc" => $this->getFicheCategorieObject ()
					->getFA () 
		);
		
		$ticket ["Area"] = $serveur_datas ["area"];
		$ticket ["Category"] = $serveur_datas ["category"];
		$ticket ["OpenedBy"] = $serveur_datas ["OpenBy"];
		$ticket ["CustomerReference1"] = $serveur_datas ["om_serveur"];
		
		$this->onDebug ( "Donnees finales du ticket :", 2 );
		$this->onDebug ( $ticket, 2 );
		return $ticket;
	}

	/**
	 * Ajoute le champ "Ticket Error" aux CMAs
	 * @param string $message
	 * @return hpom_to_stars
	 */
	public function ajoute_erreur_CMA($message) {
		$CMAs = $this->getHpomObject ()
			->getCMAs ();
		if (! isset ( $CMAs ["Ticket Error"] )) {
			$CMAs ["Ticket Error"] = $message;
		} else {
			$CMAs ["Ticket Error"] .= "\n" . $message;
		}
		if ($this->getListeOptions ()
			->getOption ( "dry-run" ) !== false) {
			$this->onWarning ( "DRY RUN : ajoute_erreur_CMA NON EXECUTE" );
			$this->onInfo ( $CMAs );
		} else {
			$this->getHpomObject ()
				->getWmiObject ()
				->setCMAs ( $CMAs );
		}
		
		return $this;
	}

	/**
	 * Ajoute le champ "Ticket Number" aux CMAs
	 * @param string $IncidentID
	 * @return hpom_to_stars
	 */
	public function ajoute_ticket_number_CMA($IncidentID) {
		$CMAs = $this->getHpomObject ()
			->getCMAs ();
		$CMAs ["Ticket Number"] = $IncidentID;
		if ($this->getListeOptions ()
			->getOption ( "dry-run" ) !== false) {
			$this->onWarning ( "DRY RUN : ajoute_erreur_CMA NON EXECUTE" );
			$this->onInfo ( $CMAs );
		} else {
			$this->getHpomObject ()
				->getWmiObject ()
				->setCMAs ( $CMAs );
		}
		
		return $this;
	}

	public function traite_retour_stars($status, $message, $IncidentID) {
		if (strtoupper ( $status ) != "SUCCESS") {
			$liste_erreur = $this->getListeErreur ();
			switch ($message) {
				case "The assignment group is invalid." :
					if (isset ( $liste_erreur ["groupe"] )) {
						//Si on a la meme erreur avec le groupe par deafut, on stoppe la creation de ticket
						$this->setContinue ( false );
						return $this->onError ( "Impossible de creer le ticket avec le groupe " . $liste_erreur ["groupe"] . ", meme avec le goupe par defaut.", "", 2005 );
					}
					$this->AjouteListeErreur ( "groupe", $this->getFicheCategorieObject ()
						->getGroupe () );
					$this->getFicheCategorieObject ()
						->setGroupe ( "GROUP1" );
					break;
				case "Configuration Item " & $this->getHpomObject ()
					->getAffectedCI () & " is invalid." :
					if (isset ( $liste_erreur ["AffectedCI"] )) {
						$this->setContinue ( false );
						return $this->onError ( "Impossible de creer le ticket avec le CI " . $liste_erreur ["AffectedCI"] . ", meme avec le AffectedCI par defaut.", "", 2005 );
					}
					$this->AjouteListeErreur ( "AffectedCI", $this->getHpomObject ()
						->getAffectedCI () );
					$this->getHpomObject ()
						->setAffectedCI ( $this->getCIDefaut () );
					break;
				case "Cannot find a valid combination of data for category, subcategory and product type." :
					if (isset ( $liste_erreur ["subcategory"] )) {
						$this->setContinue ( false );
						return $this->onError ( "Impossible de creer le ticket a cause de la sous-categorie " . $liste_erreur ["subcategory"] . ", meme avec la sous-categorie par defaut.", "", 2005 );
					}
					$this->AjouteListeErreur ( "subcategory", $this->getHpomObject ()
						->getSubArea () );
					$this->getHpomObject ()
						->setSubArea ( "APPLICATION_NONE" );
					break;
				default :
					$this->setContinue ( false );
					return $this->onError ( "Impossible de creer le ticket", $message, 2005 );
					
			}
			
			$this->ajoute_erreur_CMA ( $message );
			return false;
		}
		
		$this->ajoute_ticket_number_CMA ( $IncidentID );
		$this->setContinue ( false );
		
		return $this;
	}

	/**
	 * Fait l'appel a stars
	 * @return hpom_to_stars
	 */
	public function creer_ticket_stars() {
		//On recupere les donnees de definition du stars
		$serveur_datas = $this->getSoapStarsObject ()
			->valide_presence_stars_data ( "MUT_Stars3" );
		//On cree l'incident
		while ( $this->getContinue () ) {
			$retour_Stars3 = $this->getSoapStarsObject ()
				->CreateIncident ( $this->prepare_hpom_to_stars_datas ( $serveur_datas ) );
			$this->traite_retour_stars ( $retour_Stars3 ["status"], $retour_Stars3 ["message"], $retour_Stars3 ["IncidentID"] );
		}
		
		return $this;
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * @codeCoverageIgnore
	 */
	public function &getHpomObject() {
		return $this->hpom;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setHpomObject(&$hpom) {
		$this->hpom = $hpom;
		
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &getFicheCategorieObject() {
		return $this->fiche_cat;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setFicheCategorieObject(&$fiche_cat) {
		$this->fiche_cat = $fiche_cat;
		
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &getSoapStarsObject() {
		return $this->soap_stars;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setSoapStarsObject(&$soap_stars) {
		$this->soap_stars = $soap_stars;
		
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getCIDefaut() {
		return $this->CI_defaut;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setCIDefaut($CI_defaut) {
		$this->CI_defaut = $CI_defaut;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getListeErreur() {
		return $this->retour_erreur;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setListeErreur($retour_erreur) {
		$this->retour_erreur = $retour_erreur;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &AjouteListeErreur($nom, $valeur) {
		$this->retour_erreur [$nom] = $valeur;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getContinue() {
		return $this->continue;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setContinue($continue) {
		$this->continue = $continue;
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
		$help [__CLASS__] ["text"] [] .= "Necessite un objet Soap Stars, un objet hpom et un objet fiche_categorie";
		
		return $help;
	}
}
?>
