<?php
/**
 * Collecte des donnees.
 * @author dvargas
 */
/**
 * class collect_data_ssh
 *
 * @package Collected
 */
class collect_data_ssh extends abstract_log {
	/**
	 * var privee
	 * @access private
	 * @var fonctions_standards_flux
	 */
	private $fonctions_standards_flux = null;

	/**
	 * ********************* Creation de l'objet ********************
	 */
	/**
	 * Instancie un objet de type collect_data_ssh. @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return collect_data_ssh
	 */
	static function &creer_collect_data_ssh(
			&$liste_option,
			$sort_en_erreur = false,
			$entete = __CLASS__) {
		$objet = new collect_data_ssh ( $sort_en_erreur, $entete );
		$objet->_initialise ( array (
				"options" => $liste_option
		) );
		return $objet;
	}

	/**
	 * Initialisation de l'objet @codeCoverageIgnore
	 * @param array $liste_class
	 * @return collect_data_ssh
	 */
	public function &_initialise(
			$liste_class) {
		parent::_initialise ( $liste_class );
		return $this->setObjetFonctionsStandardsFlux ( fonctions_standards_flux::creer_fonctions_standards_flux ( $liste_class ['options'] ) );
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

	/**
	 * @param string $commande
	 * @return array
	 */
	public function retrouve_commande(
			$commande) {
		$row_donnees = array ();
		$row_donnees ["commande"] = $commande;
		$this->onInfo ( "Commande : " . $row_donnees ["commande"] );
		if (strpos ( $row_donnees ["commande"], "sudo" ) !== false) {
			$datas = $this->getObjetFonctionsStandardsFlux ()
				->getConnexion ()
				->ssh_shell_commande ( $row_donnees ["commande"] );
			if (is_array ( $datas ) && isset ( $datas ["output"] )) {
				$row_donnees ["resultat"] = $this->parse_tty ( $datas ["output"], $row_donnees ["commande"] );
			} else {
				$row_donnees ["resultat"] = "";
			}
		} else {
			$datas = $this->getObjetFonctionsStandardsFlux ()
				->getConnexion ()
				->ssh_commande ( $row_donnees ["commande"] );
			if (is_array ( $datas ) && isset ( $datas ["output"] )) {
				$row_donnees ["resultat"] = $datas ["output"];
			} else {
				$row_donnees ["resultat"] = "";
			}
		}
		return array (
				$row_donnees
		);
	}

	/**
	 * Parse le tty si necessaire
	 * @param string $resultat
	 * @param string $commande
	 * @return string
	 */
	public function parse_tty(
			$resultat,
			$commande) {
		$splitted_datas = explode ( "\n", $resultat );
		$flag = false;
		$counter = count ( $splitted_datas );
		for($i = 0; $i < $counter; $i ++) {
			if (strpos ( $splitted_datas [$i], " ~]$ " . substr ( $commande, 0, 15 ) ) !== false) {
				unset ( $splitted_datas [$i] );
				$flag = true;
				continue;
			}
			if ($flag) {
				if (strpos ( $splitted_datas [$i], "~]$" ) !== false) {
					unset ( $splitted_datas [$i] );
					$flag = false;
					continue;
				}
			} else {
				unset ( $splitted_datas [$i] );
			}
		}
		abstract_log::onDebug_standard ( $splitted_datas, 2 );
		return implode ( "\n", $splitted_datas );
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * @codeCoverageIgnore
	 * @return fonctions_standards_flux
	 */
	public function &getObjetFonctionsStandardsFlux() {
		return $this->fonctions_standards_flux;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setObjetFonctionsStandardsFlux(
			&$fonctions_standards_flux) {
		$this->fonctions_standards_flux = $fonctions_standards_flux;
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
