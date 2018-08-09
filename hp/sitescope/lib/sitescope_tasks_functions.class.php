<?php
/**
 * Gestion de SiteScope.
 * @author dvargas
 */
/**
 * class sitescope_tasks_functions
 *
 * @package Lib
 * @subpackage SiteScope
 */
class sitescope_tasks_functions extends abstract_log {
	/**
	 * var privee
	 *
	 * @access private
	 * @var array
	 */
	private $now = array ();
	/**
	 * var privee
	 *
	 * @access private
	 * @var dates
	 */
	private $liste_date = null;
	
	/*********************** Creation de l'objet *********************/
	/**
	 * Instancie un objet de type sitescope_tasks_functions.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return sitescope_tasks_functions
	 */
	static function &creer_sitescope_tasks_functions(&$liste_option, $sort_en_erreur = false, $entete = __CLASS__) {
		$objet = new sitescope_tasks_functions ( $sort_en_erreur, $entete );
		$objet->_initialise ( array (
				"options" => $liste_option
		) );
	
		return $objet;
	}
	
	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return sitescope_tasks_functions
	 */
	public function &_initialise($liste_class) {
		parent::_initialise($liste_class);
		
		$this->setObjetListeDate(dates::creer_dates ( $this->getListeOptions () ));
		return $this;
	}
	
	/*********************** Creation de l'objet *********************/
	/**
	 * Constructeur.
	 *
	 * @param string $entete Entete de log.
	 * @param string|Bool $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @return true
	 */
	public function __construct($sort_en_erreur = false,$entete = __CLASS__) {
		// Gestion de abstract_log
		parent::__construct ( $sort_en_erreur, $entete );
		
		$this->setCurrentDate ( $this->parse_datetime ( time () ) );
		return $this;
	}
	
	/**
	 * Utilise un timestamp pour creer un tableau de date.
	 *
	 * @param int $ts_date Timestamp pour extraire les informations.
	 * @return array Tableau contenant les informations de date.
	 */
	public function parse_datetime($ts_date) {
		$now = array ();
		$now ["ts"] = $ts_date;
		$now ["date_mysql"] = date ( "Y-m-d H:i:s", $ts_date ); // dd-MM-yyyy HH:mm:ss
		$now ["year"] = date ( "Y", $ts_date );
		$now ["month"] = date ( "m", $ts_date );
		$now ["day"] = date ( "d", $ts_date );
		$now ["hour"] = date ( "H", $ts_date );
		$now ["minute"] = date ( "i", $ts_date );
		$now ["week_of_year"] = date ( "W", $ts_date );
		$now ["day_of_week"] = date ( "w", $ts_date ); // 0 pour dimanche
		
		return $now;
	}
	
	/**
	 * Valide qu'une desactivation est deja faite
	 *
	 * @param string $schedule_type        	
	 * @param string $mysql_last_done        	
	 * @return boolean True le traitement est fait, FALSE le traitement n'est pas fait
	 */
	public function valide_periode_already_done($schedule_type, $mysql_last_done , $duration, $unit) {
		// On valide que la desactivation n'a pas deja eu lieu
		$now = $this->getCurrentDate ();
		$last_done = $this->parse_datetime ( $this->getObjetListeDate()->timestamp_mysql_date($mysql_last_done) );
		switch ($schedule_type) {
			case "D" :
				/*if (($last_done ["day"] == $now ["day"]) && ($last_done ["month"] == $now ["month"]) && ($last_done ["year"] == $now ["year"])) {
					$this->onInfo ( "Daily already done" );
					return true;
				}*/
				//Le daily peut avoir lieu plusieurs fois par jour (si plusieurs horaires)
				return false;
				break;
			case "W" :
				if (($last_done ["week_of_year"] == $now ["week_of_year"]) && ($last_done ["year"] == $now ["year"])) {
					$this->onDebug ( "Weekly deja fait",1 );
					return true;
				}
				break;
			case "M" :
				if (($last_done ["month"] == $now ["month"]) && ($last_done ["year"] == $now ["year"])) {
					$this->onDebug ( "Monthly deja fait",1 );
					return true;
				}
				break;
			case "Y" :
				if (($last_done ["year"] == $now ["year"])) {
					$this->onDebug ( "Yearly deja fait",1 );
					return true;
				}
				break;
			case "P" :
				//C'est du cycling
				//On calcul la frequence
				$differenciel_en_s=$this->calcul_duration($duration, $unit);
				$ts_new_cycle=$last_done["ts"]+$differenciel_en_s;
				//Si le timestamp actuel est inferieur au timestamp du nouveau cycle
				if($now["ts"]<$ts_new_cycle){
					$this->onDebug ( "Cycle deja fait",1 );
					return true;
				}
				break;
			default :
				$this->onWarning ( "schedule_type inconnu : " . $schedule_type );
				return true;
		}
		
		return false;
	}
	
	/**
	 * Valide que le mois courant apparait dans la liste des mois schedules.
	 *
	 * @param string $schedule_months_of_year Liste des mois separes par ','
	 * @return boolean TRUE un mois correspond, FALSE aucun mois ne correspondent
	 */
	public function check_schedule_months_of_year($schedule_months_of_year) {
		$now = $this->getCurrentDate ();
		if ($schedule_months_of_year != "") {
			$liste_mois = explode ( ",", $schedule_months_of_year );
			if ($liste_mois === false) {
				$this->onWarning ( "Explode en erreur avec : " . $schedule_months_of_year );
				return false;
			}
			foreach ( $liste_mois as $mois_a_valider ) {
				if ($mois_a_valider == "") {
					continue;
				}
				if ($now ["month"] == $mois_a_valider) {
					$this->onDebug ( "Il y a un Mois qui correspond",1 );
					return true;
				}
			}
		} else {
			$this->onWarning ( "Pas de mois valide dans le champ : " . $schedule_months_of_year );
			return false;
		}
		
		$this->onDebug ( "Pas de Mois correspondant",1 );
		return false;
	}
	
	/**
	 * Valide que le jour courant apparait dans la liste des jours schedules.
	 *
	 * @param string $schedule_days_of_year Liste des jours separes par ','
	 * @return boolean TRUE un jour correspond, FALSE aucun jours ne correspondent
	 */
	public function check_schedule_day_of_year($schedule_days_of_year) {
		$now = $this->getCurrentDate ();
		if ($schedule_days_of_year != "") {
			$liste_jours = explode ( ",", $schedule_days_of_year );
			if ($liste_jours === false) {
				$this->onWarning ( "Explode en erreur avec : " . $schedule_days_of_year );
				return false;
			}
			foreach ( $liste_jours as $jour_a_valider ) {
				if ($jour_a_valider == "") {
					continue;
				}
				if ($now ["day"] == $jour_a_valider) {
					$this->onDebug ( "Il y a un Jour qui correspond",1 );
					return true;
				}
			}
		} else {
			$this->onWarning ( "Pas de jour valide dans le champ : " . $schedule_days_of_year );
			return false;
		}
		
		$this->onDebug ( "Pas de Jour correspondant", 1 );
		return false;
	}
	
	/**
	 * Valide que le jour de la semaine courante apparait dans la liste des jours de la semaine schedules.
	 *
	 * @param string $schedule_days_of_week Liste des jours de la semaine separes par ','
	 * @return boolean TRUE le jour de la semaine correspond, FALSE aucun jours ne correspondent
	 */
	public function check_schedule_day_of_week($schedule_days_of_week) {
		$now = $this->getCurrentDate ();
		if ($schedule_days_of_week != "") {
			$liste_jours = explode ( ",", $schedule_days_of_week );
			if ($liste_jours === false) {
				$this->onWarning ( "Explode en erreur avec : " . $schedule_days_of_week );
				return false;
			}
			foreach ( $liste_jours as $jour_a_valider ) {
				if ($jour_a_valider == "") {
					continue;
				}
				if ($now ["day_of_week"] == $jour_a_valider) {
					$this->onDebug ( "Il y a un Jour de la semaine qui correspond", 1 );
					return true;
				}
			}
		} else {
			$this->onWarning ( "Pas de jour valide dans le champ : " . $schedule_days_of_week );
			return false;
		}
		
		$this->onDebug ( "Pas de Jour de la semaine correspondant", 1 );
		return false;
	}
	
	/**
	 * Valide que le jour de la semaine courante apparait dans la liste des jours de la semaine schedules.
	 *
	 * @param string onWarning Liste des horaires de schedule ','
	 * @return array false de timestamp de traitement pour le jour en cours, FALSE en cas d'erreur
	 */
	public function retrouve_schedule_hour($schedule_times) {
		$retour = array ();
		$now = $this->getCurrentDate ();
		
		$liste_heures = explode ( ",", $schedule_times );
		if ($liste_heures === false) {
			$this->onWarning ( "Explode en erreur avec : " . $schedule_times );
			return false;
		}
		
		foreach ( $liste_heures as $heure_a_valider ) {
			if ($heure_a_valider == "") {
				continue;
			}
			$liste_periode = explode ( ":", $heure_a_valider );
			if (is_array ( $liste_periode ) && count ( $liste_periode ) == 2) {
				$retour [$liste_periode [0].':'.$liste_periode [1]] = mktime ( $liste_periode [0], $liste_periode [1], 0, $now ["month"], $now ["day"], $now ["year"] );
			} else {
				$this->onWarning ( "Explode impossible de l'heure a valider" );
			}
		}
		
		return $retour;
	}
	
	/**
	 * recupere l'heure du prochain cycle.
	 *
	 * @param string onWarning Liste des horaires de schedule ','
	 * @return array false de timestamp de traitement pour le jour en cours, FALSE en cas d'erreur
	 */
	public function retrouve_cycling_hour($schedule_times,$duration,$unit) {
		$retour = array ();
		if($schedule_times==""){
			$this->onWarning("Pas de schedule_times");
			return false;
		}
		
		$now = $this->getCurrentDate ();
		$diff_seconde=$this->calcul_duration($duration, $unit);

		$liste_heures = explode ( ",", $schedule_times );
		//Il ne faut qu'une heure de depart
		if ($liste_heures === false || count($liste_heures)>1) {
			$this->onWarning ( "Explode en erreur avec : " . $schedule_times );
			return false;
		}
	
		$liste_periode = explode ( ":", $liste_heures[0] );
		if (is_array ( $liste_periode ) && count ( $liste_periode ) == 2) {
			$start_hour=mktime ( $liste_periode [0], $liste_periode [1], 0, $now ["month"], $now ["day"], $now ["year"] );
			$cycle=$start_hour;
			while($cycle<$now["ts"]){
				$cycle+=$diff_seconde;
			}
		 	 $diff_sec_restant=$cycle-$now["ts"];
			
			return $diff_sec_restant;
		} else {
			$this->onWarning ( "Explode impossible de l'heure a valider" );
		}
	
		return $retour;
	}
	
	/**
	 * Valide que la date courante est superieur au when de la base 
	 *
	 * @param string $when date _when de mysql
	 * @return bool TRUE l'horaire est valide, FALSE l'horaire n'est pas valide
	 */
	public function check_planning_when($when) {
		$now = $this->getCurrentDate ();
	
		// On recupere le timestamp du last_done
		$ts_when=$this->getObjetListeDate()->timestamp_mysql_date($when);
		$this->ondebug ( "Timestamp When : " . $ts_when, 1 );		
			
		//On valide que l'heure actuelle est superieur a l'heure de planification
		if ($now ["ts"] >= $ts_when) {
			$this->onDebug("Horaire valide.",1);
			return true;
		}
			
		$this->onDebug ( "l'horaire ne correspond pas",1 );
		return false;
	}
	
	/**
	 * Valide que le jour de la semaine courante apparait dans la liste des jours de la semaine schedules et n'a pas deja ete traite
	 *
	 * @param array $liste_schedule Liste des timestamps de schedule
	 * @param string $last_done date last_done de mysql 
	 * @return int|false Le nombre de seconde de difference, FALSE si rien ne correspond
	 */
	public function check_schedule_hour($liste_schedule, $last_done) {
		$now = $this->getCurrentDate ();
		
		// On valide la liste de schedules
		if ($liste_schedule === false || count ( $liste_schedule ) == 0) {
			$this->onWarning ( "Pas de schedule defini" );
			return false;
		}
		
		// On recupere le timestamp du last_done
		if($last_done==""){
			$ts_last_done=0;
		} else {
			$ts_last_done=$this->getObjetListeDate()->timestamp_mysql_date($last_done);
		}
		$this->ondebug ( "Timestamp Last Done : " . $ts_last_done, 1 );
		
		foreach ( $liste_schedule as $human_readable=>$ts_scheduled_time ) {
			if ($ts_scheduled_time == "") {
				continue;
			}
			$this->onDebug("Horaire teste : ".$human_readable." Timestamp : ".$ts_scheduled_time, 1);
			
			//on valide que la desactivation n'a pas deja eu lieu
			if($ts_last_done > 0 && $ts_scheduled_time <= $ts_last_done){
				$this->onDebug("la desactivation de ".$human_readable." a deja eu lieu", 1);
				continue;
			}
			
			//On valide que l'heure actuelle est superieur a l'heure de planification
			if ($now ["ts"] >= $ts_scheduled_time) {
				$this->onDebug("Horaire valide : ".$human_readable,1);
				return $now ["ts"]-$ts_scheduled_time;
			}
		}
		
		$this->onDebug ( "Pas d'horaire correspondant",1 );
		return false;
	}
	
	/**
	 * Calcul le temps de desactivation en seconde moins le differenciel s'il existe
	 * @param int $duration Temps defini en Unit
	 * @param string $unit Unit de type m,h,d,w (minutes,heures,jours,semaines)
	 * @param int $diff_seconde differentiel en seconde
	 * @return integer nombre de seconde des unites de temps
	 */
	public function calcul_duration($duration, $unit, $diff_seconde=0) {
		switch ($unit) {
			case "m" :
				$duration *= 60;
				break;
			case "h" :
				$duration *= 3600;
				break;
			case "d" :
				$duration = $duration * 3600 * 24;
				break;
			case "w" :
				$duration = $duration * 3600 * 24 * 7;
				break;
			default:
				return $this->onError ( "Unite inconnue : " . $unit );
		}

		$difference=$duration-$diff_seconde;
		$this->onDebug("Temps de desactivation en seconde : ".$difference, 1);
		return $difference;
	}
	
	/**
	 * Retrouve le chemin tree/leaf a partir d'un id et d'un type isgroup
	 * @param requete_complexe_sitescope &$db_sitescope
	 * @param string $source_id id recherche en fonction de isgroup
	 * @param int $isgroup 0 c'est une leaf, 1 c'est un tree
	 * @return array|string Tableau si $decoupe=true, string sinon
	 */
	public function prepare_chemin_moniteur(&$db_sitescope,$source_id,$isgroup,$decoupe=true){
		$fullpathname="";
		$param="!";
		
		if($source_id==""){
			return $this->onError("Il faut un source_id");
		}		
		
		if($isgroup=="0"){
			$liste_leaf=$db_sitescope->requete_select_standard ( 'leaf', array( 
					"id" => $source_id
			), "id ASC" );
			if($liste_leaf===false || count($liste_leaf)===0){
				return $this->onError("Pas de leaf correspondant a l'id ".$source_id);
			}
			foreach($liste_leaf as $leaf){
				$fullpathname=$leaf["name"];
				$source_id=$leaf["parent_id"];
			}
		}
		
		$liste_tree=$db_sitescope->requete_select_standard ( 'tree', array( 
					"id" => $source_id
			), "id ASC" );
		if($liste_tree===false || count($liste_tree)===0){
			return $this->onError("Pas de tree correspondant a l'id ".$source_id);
		}
		
		$path="";
		foreach($liste_tree as $tree){
			if($decoupe){
				$path=$this->decoupe_chemin_moniteur($tree["fullpathname"],$fullpathname, $param);
			}  else {
				$path=$tree["fullpathname"];
				if($fullpathname!=""){
					$path.=$param.$fullpathname;
				}
			}
		}
		
		
		
		return $path;
	}
	
	/**
	 * Construit le chemin sous forme de tableau
	 * @param string $fullpathname
	 * @return array
	 */
	public function decoupe_chemin_moniteur($fullpathname,$moniteur_name="",$param="!"){

		$path = explode ( $param, $fullpathname );
	
		//On vire le SitescopeRoot
		if($path[0]=="SiteScopeRoot"){
			array_shift($path);
		}
	
		//Gestion des moniteurs avec accent
		if($moniteur_name!=""){
			// php 5.4 $path[$taille]=html_entity_decode($path[$taille],ENT_COMPAT | ENT_HTML401,'UTF-8');
			$moniteur_name=html_entity_decode($moniteur_name,ENT_COMPAT,'UTF-8');
			array_push($path,$moniteur_name);
		}

		return $path;
	}
	
	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	public function getCurrentDate() {
		return $this->now;
	}
	
	public function setCurrentDate($array_date) {
		$this->now = $array_date;
		return $this;
	}
	
	public function &getObjetListeDate() {
		return $this->liste_date;
	}
	
	public function &setObjetListeDate(&$liste_dates) {
		$this->liste_date = $liste_dates;
		return $this;
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
