<?php
/**
 * Gestion de SiteScope.
 * @author dvargas
 */
/**
 * class lecture_fvs
 *
 * @package Lib
 * @subpackage SiteScope
 */
class lecture_fvs extends abstract_log {
	/**
	 * var privee
	 *
	 * @access private
	 * @var PHPExcel_IOFactory
	 */
	private $objPHPExcelReader = null;
	/**
	 * var privee
	 *
	 * @access private
	 * @var sitescope_template_datas
	 */
	private $sis_template_datas = null;
	/**
	 * var privee
	 *
	 * @access private
	 * @var string
	 */
	private $valeur_menu = "";

	/*********************** Creation de l'objet *********************/
	/**
	 * Instancie un objet de type lecture_fvs.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param sitescope_template_datas $sis_template_datas Pointeur sur un objet sitescope_template_datas.
	 * @param PHPExcel $fichier_fvs Reference sur un objet de type PHPExcel d'un fichier fvs standard
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return lecture_fvs
	 */
	static function &creer_lecture_fvs(&$liste_option, &$sis_template_datas, $fichier_fvs, $sort_en_erreur = false, $entete = __CLASS__) {
		$objet = new lecture_fvs ( $sort_en_erreur, $entete );
		$objet->_initialise ( array (
				"options" => $liste_option,
				"sitescope_template_datas" => $sis_template_datas,
				"PHPExcel" => $fichier_fvs 
		) );
		
		return $objet;
	}

	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return lecture_fvs
	 */
	public function &_initialise($liste_class) {
		parent::_initialise ( $liste_class );
		
		if (! isset ( $liste_class ["sitescope_template_datas"] )) {
			return $this->onError ( "Il faut un objet de type sitescope_template_datas" );
		}
		$this->setSisTemplateDatas ( $liste_class ["sitescope_template_datas"] )
			->setObjetExcel ( $liste_class ["PHPExcel"] );
		return $this;
	}

	/*********************** Creation de l'objet *********************/
	
	/**
	 * Constructeur.
	 * @codeCoverageIgnore
	 * @param string|Bool $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @return true
	 */
	public function __construct($sort_en_erreur = false, $entete = __CLASS__) {
		// Gestion de abstract_log
		parent::__construct ( $sort_en_erreur, $entete );
		$this->setThrowException(false);
	}

	/**
	 * Trouve les pages Excel valident dans la FVS et ajoute le nom de l'OS dans sitescope_template_datas<br/>
	 * WINDOWS|LINUX|UNIX|HP/UX|HP-UX|HP/UX 64-bit|AIX
	 * 
	 * @param PHPExcel_Worksheet $worksheet Page Excel
	 * @return boolean
	 */
	public function valide_nom_Worksheet(&$worksheet) {
		switch ($worksheet->getTitle ()) {
			case "WINDOWS" :
				$this->getSisTemplateDatas ()
					->setOS ( "WINDOWS" );
				return true;
				break;
			case "LINUX" :
				$this->getSisTemplateDatas ()
					->setOS ( "LINUX" );
				return true;
				break;
			case "UNIX" :
			case "HP/UX" :
			case "HP-UX" :
			case "HP/UX 64-bit" :
			case "AIX" :
				$this->getSisTemplateDatas ()
					->setOS ( "UNIX : " . $worksheet->getTitle () );
				return true;
				break;
			case "Versions" :
			case "REF" :
			default :
		}
		
		return false;
	}

	/**
	 * Lit les pages Excel d'une FVS pour en extraire les donnees.
	 * @return lecture_fvs
	 */
	public function parse_fvs() {
		foreach ( $this->getObjetExcel ()
			->getWorksheetIterator () as $worksheet ) {
			
			if ($this->valide_nom_Worksheet ( $worksheet ) === false) {
				//On ne prends pas certaines feuille de la FVS
				continue;
			}
			
			foreach ( $worksheet->getRowIterator () as $row ) {
				$this->onDebug ( 'Row number - ' . $row->getRowIndex (), 2 );
				
				$this->traite_cellules_excel ( $row );
			}
		}
		
		return $this;
	}

	/**
	 * Pour chaque type de cellule (Disk,DNS ... etc) appelle la fonction de sitescope_template_datas correspondant avec les bonnes valeurs.
	 * @param PHPExcel_Worksheet_Row $ExcelRow ligne d'une page Excel
	 * @return boolean
	 */
	public function traite_cellules_excel(&$ExcelRow) {
		$fonction = "";
		
		$cellIterator = $ExcelRow->getCellIterator ();
		if(! $cellIterator instanceOf PHPExcel_Worksheet_CellIterator){
			return $this->onError("impossible de creer un PHPExcel_Worksheet_CellIterator");
		}
		// Boucle toutes les cellules meme si elle n'existe pas
		$cellIterator->setIterateOnlyExistingCells ( false );
		$pos = 1;
		foreach ( $cellIterator as $cell ) {
			// @codeCoverageIgnoreStart
			if (! is_null ( $cell )) {
				$valeur = trim ( $cell->getValue () );
				if ($pos === 1) {
					//On traite la valeur de la premiere cellule
					$tempo_valeur = $this->getValeurMenu ();
					if ($valeur == "" && $tempo_valeur != "") {
						$valeur = $tempo_valeur;
					}
					$this->onDebug ( "Type de donnees : " . $valeur, 2 );
					$fonction=$this->selectionne_fonction($valeur);
					if($fonction===false){
							return false;
					}
					$this->onDebug ( "Type de donnees : " . $valeur, 2 );
					$pos = 2;
				} elseif ($pos === 2) {
					if($this->prepare_valeur_dans_template ( $fonction, $valeur )){
						return true;
					}
					continue;
				}
			}
			// @codeCoverageIgnoreEnd
		}
		
		return true;
	}
	
	/**
	 * choisie la fonction de l'objet SisTemplateDatas en fonction du champ de la FVS
	 * @param string $valeur Valeur du champ de la FVS
	 * @return string|boolean
	 */
	public function selectionne_fonction($valeur){
		switch ($valeur) {
			case "Serveur" :
				$this->setValeurMenu ( $valeur );
				return "setCI";
				break;
			case "Plage Horaire" :
				$this->setValeurMenu ( $valeur );
				return "setSchedule";
				break;
			case "Adresse ip" :
				$this->setValeurMenu ( $valeur );
				return "AjouteIP";
				break;
			case "conn" :
				$this->setValeurMenu ( $valeur );
				return "AjouteIPconn";
				break;
			case "Disk" :
				$this->setValeurMenu ( $valeur );
				return "AjouteDisk";
				break;
			case "DNS" :
				$this->setValeurMenu ( $valeur );
				return "setDNS";
				break;
			case "FQDN" :
				$this->setValeurMenu ( $valeur );
				return "setFQDN";
				break;
			case "Services" :
				$this->setValeurMenu ( $valeur );
				return "AjouteService";
				break;
			case "Scriptx" :
				$this->setValeurMenu ( $valeur );
				return "setScript";
				break;
			default :
				//On passe a la cellule suivante
				$this->onDebug ( "Type de donnee NON utilisee : " . $valeur, 2 );
				$this->setValeurMenu ( "" );
				
		}
		return false;
	}

	/**
	 * set les valeurs dans l'objet SisTemplateDatas
	 * @param string $methode methode de l'objet SisTemplateDatas
	 * @param string $valeur valeur a affectee
	 * @return boolean
	 */
	public function prepare_valeur_dans_template($methode, $valeur) {
		if ($methode != "setNO") {
			if ($methode == "setCI" && preg_match ( "/(compléter|completer)/", $valeur ) !== 0) {
				//page non utilisé
				$this->onDebug ( "page non utilise", 2 );
				return true;
			}
			if ($methode == "setCPU" || $methode == "setMemory") {
				// @codeCoverageIgnoreStart
				$this->getSisTemplateDatas ()
					->$methode ( true );
				// @codeCoverageIgnoreEnd
			} elseif ($methode == "AjouteIPconn" || $methode == "AjouteIP") {
				$valeur = str_replace ( "'", "", $valeur );
				$valeur = str_replace ( "()", "", $valeur );
				$valeur = trim ( str_replace ( "[]", "", $valeur ) );
				if ($valeur != "0.0.0.0" && filter_var ( $valeur, FILTER_VALIDATE_IP ) !== false) {
					$this->getSisTemplateDatas ()
						->AjouteIP ( $valeur );
					return false;
				}
			} else {
				// @codeCoverageIgnoreStart
				$this->getSisTemplateDatas ()
					->$methode ( $valeur );
				// @codeCoverageIgnoreEnd
			}
			return true;
		}
		
		$this->onDebug ( "Pas de valeur utilisable", 2 );
		return true;
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
	 * @codeCoverageIgnore
	 */
	public function &getSisTemplateDatas() {
		return $this->sis_template_datas;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setSisTemplateDatas(&$sis_template_datas) {
		$this->sis_template_datas = $sis_template_datas;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getValeurMenu() {
		return $this->valeur_menu;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function setValeurMenu($valeur_menu) {
		$this->valeur_menu = $valeur_menu;
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
		
		return $help;
	}
}
?>
