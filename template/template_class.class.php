<?php
/**
 * @author dvargas
 */
/**
 * class template_class
 * @package oneshoot
 */
class template_class extends abstract_log {
	/**
	 * var privee
	 *
	 * @access private
	 * @var int
	 */
	private $private_var = 0;
	/*********************** Creation de l'objet *********************/
	/**
	 * Instancie un objet de type template_class.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return template_class
	 */
	static function &creer_template_class(&$liste_option, $sort_en_erreur = false, $entete = __CLASS__) {
		abstract_log::onDebug_standard ( __METHOD__, 1 );
		$objet = new template_class ( $sort_en_erreur, $entete  );
		return $objet->_initialise ( array (
				"options" => $liste_option
		) );
	}

	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return template_class
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
	 * @return true
	 */
	public function __construct($sort_en_erreur = false, $entete = __CLASS__ ) {
		// Gestion de abstract_log
		parent::__construct ( $sort_en_erreur, $entete );
	}

	/**
	 * @return template_class
	 */
	public function methode1() {
		$this->onDebug ( __METHOD__, 1 );
		/**
		 * CODE DE LA METHODE
		 */
		return $this;
	}
	
	/******************************* ACCESSEURS ********************************/
	/**
	 * @codeCoverageIgnore
	 */
	public function getPrivate_var() {
		return $this->private_var;
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	public function &setPrivate_var($private_var) {
		$this->private_var = $private_var;
		return $this;
	}
	/******************************* ACCESSEURS ********************************/
	
	/**
	 * Affiche le help.<br>
	 * @codeCoverageIgnore
	 */
	static public function help() {
		$help = parent::help ();
		
		$help [__CLASS__] ["text"] = array ();
		$help [__CLASS__] ["text"] [] .= "--template_class";
		
		return $help;
	}
}
?>
