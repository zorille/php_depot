<?php
/**
 * Gestion de itop.
 * @author dvargas
 */
/**
 * class itop_ci
 *
 * @package Lib
 * @subpackage itop
 */
class itop_ci extends abstract_log {
	/**
	 * var privee
	 *
	 * @access private
	 * @var string
	 */
	private $format = '';
	/**
	 * var privee
	 *
	 * @access private
	 * @var array
	 */
	private $donnees = array ();

	/**
	 * ********************* Creation de l'objet ********************
	 */
	/**
	 * Instancie un objet de type itop_ci. @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet gestion_connexion_url
	 * @return itop_ci
	 */
	static function &creer_itop_ci(&$liste_option, $sort_en_erreur = true, $entete = __CLASS__) {
		abstract_log::onDebug_standard ( __METHOD__, 1 );
		$objet = new itop_ci ( $sort_en_erreur, $entete );
		$objet ->_initialise ( array (
				"options" => $liste_option ) );
		
		return $objet;
	}

	/**
	 * Initialisation de l'objet @codeCoverageIgnore
	 * @param array $liste_class
	 * @return itop_ci
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
	 * @param string $entete entete de log
	 * @return true
	 */
	public function __construct($sort_en_erreur = true, $entete = __CLASS__) {
		// Gestion de serveur_datas
		parent::__construct ( $sort_en_erreur, $entete );
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * @codeCoverageIgnore
	 */
	public function get_format() {
		return $this->format;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &set_format($format) {
		$this->format = $format;
		
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function get_donnees() {
		return $this->donnees;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &set_donnees($donnees) {
		if (is_array ( $donnees )) {
			$this->donnees = $donnees;
		}
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
		$help [__CLASS__] ["text"] [] .= "itop_ci :";
		
		return $help;
	}
}
?>
