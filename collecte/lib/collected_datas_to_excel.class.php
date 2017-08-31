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
class collected_datas_to_excel extends parse_collected_datas {
	/**
	 * var privee
	 *
	 * @access private
	 * @var PHPExcel_IOFactory
	 */
	private $objPHPExcelReader = null;

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
	static function &creer_collected_datas_to_excel(&$liste_option, $sort_en_erreur = false, $entete = __CLASS__) {
		$objet = new collected_datas_to_excel ( $sort_en_erreur, $entete );
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

	public function parse_datas() {
		$this ->parse_os () 
			->creer_excel_onglet ( "OS" ) 
			->parse_filesystem () 
			->creer_excel_onglet ( "FileSystems" ) 
			->parse_network () 
			->creer_excel_onglet ( "Networks" ) 
			->parse_sockets () 
			->creer_excel_onglet ( "Sockets" ) 
			->parse_hosts () 
			->creer_excel_onglet ( "Hosts" ) 
			->parse_users () 
			->creer_excel_onglet ( "Users" ) 
			->parse_group () 
			->creer_excel_onglet ( "Groups" ) 
			->parse_sudo () 
			->creer_excel_onglet ( "Sudoers" ) 
			->parse_cron () 
			->creer_excel_onglet ( "Crontabs" ) 
			->parse_nagios () 
			->creer_excel_onglet ( "Nagios" ) 
			->parse_logs () 
			->creer_excel_onglet ( "Logs_files" ) 
			->parse_process () 
			->creer_excel_onglet ( "Processus" );
	}

	public function creer_excel_onglet($nom) {
		if (! is_object ( $this ->getObjetExcel () )) {
			return $this;
		}
		$sheet = $this ->getObjetExcel () 
			->createSheet ();
		$sheet ->setTitle ( $nom );
		foreach ( $this ->getDonneesSortie () as $column => $valeurs ) {
			$row = 1;
			if (strpos ( $valeurs ["titre"], $this ->getSeparateur () ) !== false) {
				$liste_champs = explode ( $this ->getSeparateur (), $valeurs ["titre"] );
				for($col = 0; $col < count ( $liste_champs ); $col ++) {
					$sheet ->setCellValueByColumnAndRow ( $col, $row, $liste_champs [$col] );
					$sheet ->getColumnDimensionByColumn ( $col ) 
						->setAutoSize ( true );
				}
			} else {
				$sheet ->setCellValueByColumnAndRow ( $column, $row, $valeurs ["titre"] );
				$sheet ->getColumnDimensionByColumn ( $column ) 
					->setAutoSize ( true );
			}
			$row ++;
			foreach ( $valeurs ["valeurs"] as $valeur ) {
				if (strpos ( $valeur, $this ->getSeparateur () ) !== false) {
					$liste_champs = explode ( $this ->getSeparateur (), $valeur );
					for($col = 0; $col < count ( $liste_champs ); $col ++) {
						$sheet ->setCellValueByColumnAndRow ( $col, $row, $liste_champs [$col] );
					}
				} else {
					$sheet ->setCellValueByColumnAndRow ( $column, $row, $valeur );
				}
				$row ++;
			}
		}
		
		return $this;
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * @codeCoverageIgnore
	 * @return PHPExcel
	 */
	public function &getObjetExcel() {
		return $this->objPHPExcelReader;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setObjetExcel(&$objet_fichier_excel) {
		$this->objPHPExcelReader = $objet_fichier_excel;
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
