<?php
/**
 * Gestion de SiteScope.
 * @author dvargas
 */
/**
 * class sitescope_functions_locales
 *
 * @package Lib
 * @subpackage SiteScope
 */
class sitescope_functions_locales extends abstract_log {

	/*********************** Creation de l'objet *********************/
	/**
	 * Instancie un objet de type sitescope_functions_locales.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return sitescope_functions_locales
	 */
	static function &creer_sitescope_functions_locales(&$liste_option, $sort_en_erreur = false, $entete = __CLASS__) {
		$objet = new sitescope_functions_locales ( $entete, $sort_en_erreur );
		$objet->_initialise ( array (
				"options" => $liste_option 
		) );
		
		return $objet;
	}

	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return sitescope_functions_locales
	 */
	public function &_initialise($liste_class) {
		parent::_initialise ( $liste_class );
		return $this;
	}

	/*********************** Creation de l'objet *********************/
	/**
	 * Constructeur.
	 *
	 * @param string|Bool $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @return true
	 */
	public function __construct($entete = __CLASS__, $sort_en_erreur = false) {
		// Gestion de abstract_log
		parent::__construct ( $sort_en_erreur, $entete );
	}

	/**
	 * applique les requetes sur la base sitescope standard.
	 *
	 * @param requete_complexe_sitescope $db_sitescope        	
	 * @param string $requete        	
	 */
	static public function tentative_requete(&$db_sitescope, &$requete) {
		$retry = 0;
		$retour_sql = false;
		$wait = 0;
		while ( $retour_sql === false && $retry < 3 ) {
			sleep ( $wait );
			try {
				$retour_sql = $db_sitescope->faire_requete ( $requete );
			} catch ( Exception $e ) {
				abstract_log::onError_standard ( $e->getMessage (), "", $e->getCode () );
			}
			$retry ++;
			$wait ++;
		}
		
		if ($retour_sql === false) {
			abstract_log::onError_standard ( "La requete " . $requete . " n'est pas passee." );
			return false;
		}
		return true;
	}

	/**
	 * applique les requetes sur la base sitescope standard.
	 *
	 * @param requete_complexe_sitescope $db_sitescope        	
	 * @param array $liste_requetes        	
	 * @param Bool $dry_run indique si on est en dry-run
	 */
	static public function applique_sql(&$db_sitescope, $liste_requetes, $dry_run = false) {
		abstract_log::onInfo_standard ( "Nombre de lignes a supprimer :" . count ( $liste_requetes ["supprime"] ) );
		foreach ( $liste_requetes ["supprime"] as $requete ) {
			if ($dry_run !== false) {
				abstract_log::onWarning_standard ( "DRY RUN on applique la requete : " . $requete );
				continue;
			}
			sitescope_functions_locales::tentative_requete ( $db_sitescope, $requete );
		}
		abstract_log::onInfo_standard ( "Nombre de lignes a ajouter :" . count ( $liste_requetes ["ajoute"] ) );
		foreach ( $liste_requetes ["ajoute"] as $requete ) {
			if ($dry_run !== false) {
				abstract_log::onWarning_standard ( "DRY RUN on applique la requete : " . $requete );
				continue;
			}
			sitescope_functions_locales::tentative_requete ( $db_sitescope, $requete );
		}
	}

	/**
	 * Recupere la liste des planning a traiter
	 *
	 * @param requete_complexe_sitescope &$db_sitescope pointeur sur la base sitescope
	 * @return boolean array en cas d'erreur,la liste des planning.
	 */
	public function recupere_liste_planning(&$db_sitescope) {
		// On recupere la liste des tasks du serveur
		$liste_planning_serveur = $db_sitescope->requete_select_standard ( 'planning', array (), "orderby ASC" );
		if ($liste_planning_serveur === false) {
			return $this->onError ( "Erreur de requete sur le planning sitescope" );
		}
		if (count ( $liste_planning_serveur ) === 0) {
			$this->onInfo ( "Aucun planning trouvee dans la base." );
		}
		
		return $liste_planning_serveur;
	}

	/**
	 * Recupere la liste des tasks a traiter
	 *
	 * @param requete_complexe_sitescope &$db_sitescope pointeur sur la base sitescope
	 * @return boolean array en cas d'erreur,la liste des planning.
	 */
	public function recupere_liste_tasks(&$db_sitescope) {
		// On recupere la liste des tasks du serveur
		$liste_planning_serveur = $db_sitescope->requete_select_standard ( 'tasks', array (), "id ASC" );
		if ($liste_planning_serveur === false) {
			return $this->onError ( "Erreur de requete sur les tasks de sitescope" );
		}
		if (count ( $liste_planning_serveur ) === 0) {
			$this->onInfo ( "Aucune tasks trouvee dans la base." );
		}
		
		return $liste_planning_serveur;
	}

	/**
	 * Valide que l'entree du planning est dans la bonne periode de traitement
	 *
	 * @param sitescope_tasks_functions &$sitescope_tasks
	 * @param array $planning_data 1 tuple de la table panning
	 * @return boolean number temps restant de traitement,false en cas d'erreur.
	 */
	public function gere_entree_planning(&$sitescope_tasks, &$planning_data) {
		$this->onInfo ( "On verifie la desactivation du planning : " . $planning_data ["reason"] );
		$now = $sitescope_tasks->getCurrentDate ();
		
		/*
		 * Dans l'ordre des validations : 1-immediate 2-fixe : on trouve l'heure de fin dans _until 3-temps relatif a partir de _when
		 */
		
		// 1, On valide le immediate : si ce n'est pas "immediate" alors on valide l'heure de traitement
		if ($planning_data ["immediate"] == "0") {
			// On verifie qu'il faut faire le travail maintenant
			if ($sitescope_tasks->check_planning_when ( $planning_data ["when"] ) === false) {
				$this->onInfo ( "le when est superieur a l'heure courante." );
				return false;
			}
		}
		
		// 2, on retrouve les heures de traitement
		$liste_schedule = array (
				$planning_data ["when"] => $sitescope_tasks->getObjetListeDate ()
					->timestamp_mysql_date ( $planning_data ["when"] ) 
		);
		$diff_seconde = $sitescope_tasks->check_schedule_hour ( $liste_schedule, "" );
		// Si aucun horaire ne correspond : on passe au suivant
		if ($diff_seconde === false) {
			$this->onInfo ( "Aucun horaire ne correspond." );
			return false;
		}
		
		// La task n'a pas encore eu lieu
		if ($planning_data ["fixed"] == "0") {
			// heure de fin fixe
			$ts_until = $sitescope_tasks->getObjetListeDate ()
				->timestamp_mysql_date ( $planning_data ["until"] );
			$temps_desactivation = $ts_until - $now ["ts"] - $diff_seconde;
			$this->onDebug ( "Temps de desactivation en seconde : " . $temps_desactivation, 1 );
		} else {
			// heure de fin relative
			$temps_desactivation = $sitescope_tasks->calcul_duration ( $planning_data ["duration"], $planning_data ["unit"], $diff_seconde );
			$until = $sitescope_tasks->parse_datetime ( $now ["ts"] + $temps_desactivation );
			$planning_data ["until"] = $until ["date_mysql"];
		}
		
		return $temps_desactivation;
	}

	/**
	 * Valide que l'entree du planning est dans la bonne periode de traitement
	 *
	 * @param sitescope_tasks_functions &$sitescope_tasks
	 * @param array $planning_data 1 tuple de la table panning
	 * @return boolean number temps restant de traitement,false en cas d'erreur.
	 */
	public function gere_entree_task(&$sitescope_tasks, &$task_data) {
		$this->onInfo ( "On verifie la desactivation de la task : " . $task_data ["id"] . " " . $task_data ["reason"] );
		$now = $sitescope_tasks->getCurrentDate ();
		
		// 1 - on valide que la date du jour est comprise dans le schedule de la task
		// on valide que le jour de la semaine en cours fait partie des jours selectionnes
		if ($sitescope_tasks->check_schedule_day_of_week ( $task_data ["schedule_days_of_week"] ) === false) {
			// Le jour de la semaine en cours ne correspond pas.
			$this->onInfo ( "Le jour de la semaine en cours ne correspond pas." );
			return false;
		}
		
		// on valide que le mois en cours fait partie des mois selectionnes
		if ($sitescope_tasks->check_schedule_months_of_year ( $task_data ["schedule_months_of_year"] ) === false) {
			// Le mois en cours ne correspond pas.
			$this->onInfo ( "Le mois en cours ne correspond pas." );
			return false;
		}
		
		// maintenant on valide que le mois/jour en cours fait partie des mois/jours selectionnes
		if ($sitescope_tasks->check_schedule_day_of_year ( $task_data ["schedule_days_of_month"] ) === false) {
			// Le jour en cours ne correspond pas.
			$this->onInfo ( "Le jour en cours ne correspond pas." );
			return false;
		}
		
		// on valide que le traitement Weekly,Monthly,Yearly en cours n'a pas deja ete fait
		if ($sitescope_tasks->valide_periode_already_done ( $task_data ["schedule_type"], $task_data ["last_done"], $task_data ["schedule_cycle"], $task_data ["schedule_cycle_type"] ) === true) {
			// Traitement deja fait
			$this->onInfo ( "Traitement deja fait" );
			return false;
		}
		
		// En deuxieme, on retrouve les heures de traitement et on valide que la desactivation n'a pas deja eu lieu
		if ($task_data ["schedule_type"] == "P") {
			$diff_seconde = $sitescope_tasks->retrouve_cycling_hour ( $task_data ["schedule_times"], $task_data ["schedule_cycle"], $task_data ["schedule_cycle_type"] );
		} else {
			$liste_schedule = $sitescope_tasks->retrouve_schedule_hour ( $task_data ["schedule_times"] );
			$diff_seconde = $sitescope_tasks->check_schedule_hour ( $liste_schedule, $task_data ["last_done"] );
		}
		// Si aucun horaire ne correspond : on passe au suivant
		if ($diff_seconde === false) {
			$this->onInfo ( "Aucun horaire ne correspond." );
			return false;
		}
		
		// La task n'a pas encore eu lieu, on fait les traitements
		$temps_desactivation = $sitescope_tasks->calcul_duration ( $task_data ["duration"], $task_data ["unit"], $diff_seconde );
		$until = $sitescope_tasks->parse_datetime ( $now ["ts"] + $temps_desactivation );
		$task_data ["until"] = $until ["date_mysql"];
		
		return $temps_desactivation;
	}

	/**
	 * Valide le temps de desactivation > a 1 minute
	 *
	 * @param int $temps_desactivation        	
	 * @return boolean true si vrai,false sinon
	 */
	public function gere_temps_desactivation($temps_desactivation) {
		// Si le temps de desactivation est negatif ou inferieur a 1 minute, c'est qu'on a passe la periode.
		if ($temps_desactivation < 0) {
			$this->onInfo ( "Pas de desactivation : temps prevu ecoule." );
			return false;
		} elseif ($temps_desactivation <= 60) {
			$this->onInfo ( "Pas de desactivation : temps prevu ecoule." );
			return false;
		}
		
		return true;
	}

	/**
	 * 
	 * @param requete_complexe_sitescope $db_sitescope Pointeur sur la base sitescope
	 * @param sitescope_tasks_functions $sitescope_tasks
	 * @param sitescope_fonctions_standards $sitescope_fs
	 * @param array $task_data Tableau contenant les champs pour histo_planning
	 */
	public function gere_appel_ws(&$db_sitescope, &$sitescope_tasks, &$sitescope_fs, &$task_data, &$liste_noms_sis, $temps_desactivation, $now) {
		// Les disableds prennent un tableau en entree du webservice
		$path = $sitescope_tasks->prepare_chemin_moniteur ( $db_sitescope, $task_data ["source_id"], $task_data ["isgroup"], true );
		
		if (isset ( $liste_noms_sis [$task_data ["serveur_id"]] )) {
			switch ($task_data ["operation"]) {
				case "DISABLE" :
					$this->onDebug ( "Operation=DISABLE", 2 );
					if ($this->gere_temps_desactivation ( $temps_desactivation ) === false) {
						//temps prevu ecoule
						// On ajoute l'entree dans histo_planning
						$this->gere_histo_planning ( $db_sitescope, $sitescope_tasks, $task_data, $now ["date_mysql"], "0", "1", "Pas de désactivation : temps prévu écoulé" );
						//On sort
						return true;
					}
					
					//Si une desactivation est en cours
					if ($this->valide_desactivation_en_cours ( $db_sitescope, $sitescope_tasks, $task_data, $now )) {
						//On sort
						return true;
					}
					
					$retour_sans_erreur = $sitescope_fs->applique_disable_sur_un_sitescope ( $liste_noms_sis [$task_data ["serveur_id"]], $task_data ["type"], $temps_desactivation, $task_data ["isgroup"], $path, $task_data ["reason"], 0 );
					if ($retour_sans_erreur === false) {
						$this->setMessage ( $sitescope_fs->getMessage () );
					}
					break;
				case "ENABLE" :
					$this->onDebug ( "Operation=ENABLE", 2 );
					$retour_sans_erreur = $sitescope_fs->applique_enable_sur_un_sitescope ( $liste_noms_sis [$task_data ["serveur_id"]], $task_data ["type"], $task_data ["isgroup"], $path, $task_data ["reason"] );
					if ($retour_sans_erreur === false) {
						$this->setMessage ( $sitescope_fs->getMessage () );
					}
					break;
				default :
					return $this->onError ( "Operation inconnue : " . $task_data ["operation"] );
			}
		} else {
			return $this->onError ( "Sitescope " . $task_data ["serveur_id"] . " introuvable" );
		}
		
		if ($retour_sans_erreur === false) {
			// On a trouve une erreur
			$task_data ["until"] = NULL;
			$this->gere_histo_planning ( $db_sitescope, $sitescope_tasks, $task_data, $now ["date_mysql"], "0", "1", $this->getMessage () );
			return false;
		}
		
		//Desactivation sans erreur
		$this->gere_histo_planning ( $db_sitescope, $sitescope_tasks, $task_data, $now ["date_mysql"], "1", "0", "" );
		
		return true;
	}

	/**
	 *
	 * @param requete_complexe_sitescope &$db_sitescope Pointeur sur la base sitescope
	 * @param array &$task_data Tableau contenant les champs pour histo_planning
	 */
	public function valide_desactivation_en_cours(&$db_sitescope, &$sitescope_tasks, &$task_data, $now) {
		return false;
		
		//Si une desactivation est en cours
		$fullpathname = $sitescope_tasks->prepare_chemin_moniteur ( $db_sitescope, $task_data ["source_id"], $task_data ["isgroup"], false );
		$reponse = $db_sitescope->requete_select_standard ( 'histo_planning', array (
				"fullpathname" => $fullpathname,
				"until" => ">" . $task_data ["until"] 
		) );
		if (count ( $reponse ) > 0) {
			//temps prevu ecoule
			// On ajoute l'entree dans histo_planning
			$this->onWarning ( "Pas de désactivation : une desactivation est deja en cours" );
			$this->gere_histo_planning ( $db_sitescope, $sitescope_tasks, $task_data, $now ["date_mysql"], "0", "1", "Pas de désactivation : une désactivation est déjà en cours" );
			//On sort
			return true;
		}
		
		return false;
	}

	/**
	 * 
	 * @param requete_complexe_sitescope &$db_sitescope Pointeur sur la base sitescope
	 * @param sitescope_tasks_functions &$sitescope_tasks
	 * @param array &$task_data Tableau contenant les champs pour histo_planning
	 * @param string $when date au format mysql
	 * @param int $done 0 ou 1 si le traitement est fait
	 * @param int $has_error 0 ou 1 s'il y a une erreur
	 * @param string $error_log Message d'erreur
	 */
	public function gere_histo_planning(&$db_sitescope, &$sitescope_tasks, &$task_data, $when, $done, $has_error, $error_log) {
		//On recupere le chemin lisible pour l'historique
		$fullpathname = $sitescope_tasks->prepare_chemin_moniteur ( $db_sitescope, $task_data ["source_id"], $task_data ["isgroup"], false );
		$this->onInfo ( "pour le moniteur : " . $fullpathname );
		
		if (! isset ( $task_data ["task_id"] )) {
			$task_data ["task_id"] = "0";
		}
		$histo_plannig = array (
				"id" => $task_data ["id"],
				"task_id" => $task_data ["task_id"],
				"serveur_id" => $task_data ["serveur_id"],
				"fullpathname" => $fullpathname,
				"user" => $task_data ["user"],
				"reason" => $task_data ["reason"],
				"fixed" => $task_data ["fixed"],
				"duration" => $task_data ["duration"],
				"unit" => $task_data ["unit"],
				"operation" => $task_data ["operation"],
				"type" => $task_data ["type"],
				"isgroup" => $task_data ["isgroup"],
				"immediate" => $task_data ["immediate"],
				"when" => $when,
				"customer" => $task_data ["customer"] 
		);
		
		$histo_plannig ["until"] = $task_data ["until"];
		$histo_plannig ["done"] = $done;
		$histo_plannig ["has_error"] = $has_error;
		$histo_plannig ["error_log"] = $error_log;
		$db_sitescope->requete_insert_standard ( 'histo_planning', $histo_plannig );
	}

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
