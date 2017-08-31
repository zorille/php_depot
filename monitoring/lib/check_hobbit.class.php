<?php
class check_hobbit extends abstract_log {
	private $timestamp_actuel;
	private $alerte;
	private $history;
	private $config_dir;
	private $nagios;
	private $fichier_mail;
	private $liste_fichiers = array ();
	private $server_config = array ();
	private $mail_config = array ();
	private $etat_en_cours = false;
	private $sms = array ();
	private $liste_env = array ();
	private $liste_option;

	/*********************** Creation de l'objet *********************/
	/**
	 * Instancie un objet de type check_hobbit.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param string $fichier Chemin complet du fichier.
	 * @param string $creer Si le fichier n'existe pas, doit-on le creer oui/non ?
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return check_hobbit
	 */
	static function &creer_check_hobbit(&$liste_option, $sort_en_erreur = true, $entete = __CLASS__) {
		$objet = new check_hobbit ( $liste_option, $entete, $sort_en_erreur );
		$objet->_initialise ( array (
				"options" => $liste_option 
		) );
		
		return $objet;
	}

	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return check_hobbit
	 */
	public function &_initialise($liste_class) {
		parent::_initialise ( $liste_class );
		return $this;
	}

	/*********************** Creation de l'objet *********************/
	
	/**
	 * Constructeur, verifie et prepare les variables de base.
	 *
	 * @param options &$liste_option Pointeur sur les arguments
	 * @param string $entete
	 * @param string|bool $sort_en_erreur
	 * @return true
	 */
	public function __construct(&$liste_option, $entete = __CLASS__, $sort_en_erreur = true) {
		
		//Gestion de abstract_log
		parent::__construct ( $sort_en_erreur, $entete );
		
		$this->liste_option = $liste_option;
		
		$this->timestamp_actuel = time ();
		$this->alerte = array (
				"CRITICAL" => array (),
				"WARNING" => array (),
				"RECOVERED" => array () 
		);
		
		$this->history = $liste_option->renvoie_variables_standard ( array (
				"hobbit",
				"history" 
		) );
		if ($this->history === false) {
			$this->onError ( "Il faut le dossier history de Hobbit" );
		}
		
		$this->config_dir = $liste_option->renvoie_variables_standard ( array (
				"hobbit",
				"config" 
		) );
		if ($this->config_dir === false) {
			$this->onError ( "Il faut le dossier config de Hobbit" );
		}
		
		$this->nagios = $liste_option->renvoie_variables_standard ( array (
				"hobbit",
				"nagios" 
		) );
		if ($this->nagios === false) {
			$this->onError ( "Il faut un fichier nagios pour les alertes" );
		}
		
		$this->fichier_mail = $liste_option->renvoie_variables_standard ( array (
				"hobbit",
				"mail_conf" 
		) );
		if ($this->fichier_mail === false) {
			return $this->onError ( "Il faut un fichier de mail pour envoyer les alertes" );
		}
		
		return true;
	}

	/************************* Preparation de l'environnement ************************/
	/**
	 * Prepare les listes a traiter.
	 *
	 * @return true
	 */
	public function charge_liste_fichiers() {
		$this->charge_mail_config ();
		$this->onInfo ( "On lit le dossier " . $this->history );
		$liste_fichier = repertoire::lire_repertoire ( $this->history );
		$this->onDebug ( "Liste des Fichiers :", 2 );
		$this->onDebug ( $liste_fichier, 2 );
		if ($liste_fichier !== false) {
			foreach ( $liste_fichier as $fichier ) {
				if (preg_match ( "/[^.]+\.[^.]+$/", $fichier )) {
					$this->onDebug ( "Fichier ajoute : " . $fichier, 1 );
					#On prend le nom du serveur et du service
					$pos = strrpos ( $fichier, "." );
					$server = str_replace ( ",", ".", substr ( $fichier, 0, $pos ) );
					$service = substr ( $fichier, $pos + 1 );
					$this->onDebug ( "Serveur en cours : " . $server, 2 );
					$this->onDebug ( "Service en cours : " . $service, 2 );
					if ($service !== "com") {
						$this->set_liste_fichiers ( $server, $service, $this->history . "/" . $fichier );
						$this->set_server_config ( $server );
					}
				}
			}
		} else {
			$this->onError ( "Le dossier " . $this->history . " de hobbit ne peut pas etre lu." );
		}
		$this->onDebug ( "Liste des Fichiers conserves :", 2 );
		$this->onDebug ( $this->liste_fichiers, 2 );
		$this->onDebug ( "Liste des configurations :", 2 );
		$this->onDebug ( $this->server_config, 2 );
		
		return true;
	}

	/**
	 * Chargement du fichier de config correspondant au serveur en cours.
	 *
	 * @param string $fichier_config Chemin complet du fichier de config
	 * @return array
	 */
	protected function charge_config_fichier($fichier_config) {
		$retour = array ();
		$ligne = true;
		
		$this->onDebug ( "Nom du fichier de config : " . $fichier_config, 1 );
		if (fichier::tester_fichier_existe ( $fichier_config )) {
			$fichier = fichier::creer_fichier ( $this->getListeOptions (), $fichier_config, "non", true );
			$fichier->ouvrir ( "r" );
			
			while ( $ligne !== false ) {
				$ligne = $fichier->lit_une_ligne ();
				$this->onDebug ( "Ligne de config en cours : " . trim ( $ligne ), 2 );
				if (substr ( $ligne, 0, 1 ) == "#" || trim ( $ligne ) === "") {
					continue;
				}
				$tempo = explode ( ":", $ligne );
				$retour [$tempo [2]] = array ();
				$retour [$tempo [2]] [0] = $tempo [0];
				$retour [$tempo [2]] [1] = $tempo [1];
				$retour [$tempo [2]] [2] = $tempo [3];
				$this->liste_env [$tempo [0]] = "";
				if (! isset ( $tempo [4] )) {
					$retour [$tempo [2]] [3] = "exploit";
				} else {
					$retour [$tempo [2]] [3] = trim ( $tempo [4] );
				}
			}
			
			$fichier->close ();
		} else {
			$this->onWarning ( "Il n'y a pas de fichier de configuration pour :" . $fichier_config );
		}
		
		abstract_log::onDebug_standard ( $retour, 2 );
		
		return $retour;
	}

	/**
	 * Chargement du fichier de config des mails.
	 *
	 * @return true
	 */
	protected function charge_mail_config() {
		$retour = array ();
		$ligne = true;
		
		$this->onDebug ( "Nom du fichier de mail : " . $this->fichier_mail, 1 );
		$fichier_config = $this->config_dir . "/" . $this->fichier_mail;
		if (fichier::tester_fichier_existe ( $fichier_config )) {
			$fichier = fichier::creer_fichier ( $this->getListeOptions (), $fichier_config, "non", true );
			$fichier->ouvrir ( "r" );
			$ligne = $fichier->lit_une_ligne ();
			while ( $ligne !== false ) {
				$this->onDebug ( "Ligne de config en cours : " . trim ( $ligne ), 2 );
				if (substr ( $ligne, 0, 1 ) == "#" || trim ( $ligne ) === "") {
					$ligne = $fichier->lit_une_ligne ();
					continue;
				}
				$tempo = explode ( ":", $ligne );
				$this->mail_config [$tempo [0]] = trim ( $tempo [1] );
				$ligne = $fichier->lit_une_ligne ();
			}
			if (count ( $this->mail_config ) === 0) {
				$this->onError ( "Il n'y a pas de configuration dans le fichier de mail." );
			}
			
			$fichier->close ();
		} else {
			$this->onError ( "Il n'y a pas de fichier de configuration de mail." );
		}
		
		abstract_log::onDebug_standard ( $this->mail_config, 2 );
		
		return true;
	}

	/************************* Preparation de l'environnement ************************/
	
	/************************* Verification de l'etat de hobbit ************************/
	public function check_etat_hobbit() {
		$ecart_timing = 300;
		
		foreach ( $this->liste_fichiers as $server => $liste_service ) {
			foreach ( $liste_service as $service => $fichier ) {
				#On retrouve la couleur du point de monitoring dans le fichier et le timestamp
				$this->charge_donnees_fichier ( $fichier );
				
				if ($this->etat_en_cours === false) {
					$this->onWarning ( "Il n'y a pas de donnees dans le fichier history pour : " . $server . " " . $service );
				} else {
					$diff_timing = $this->timestamp_actuel - $this->etat_en_cours ["current"] ["timestamp"];
					$this->onDebug ( "Ecart de timing : " . $diff_timing, 1 );
					if ($this->etat_en_cours ["current"] ["color"] !== "red" && $diff_timing > $ecart_timing) {
						#Si le timestamp est superieur a 5 min pour tous les etats sauf "red", on passe
						$this->onDebug ( "Il n'y a pas de mise a jour pour : " . $server . " " . $service, 1 );
					} else {
						#sinon, on traite :
						$this->onDebug ( "On traite : " . $server . " " . $service, 1 );
						if ($diff_timing > $ecart_timing) {
							$send_mail = false;
						} else {
							$send_mail = true;
						}
						$this->traite_alertes ( $server, $service, $send_mail );
					}
				}
			}
		}
		
		$this->traite_sms_nagios ();
		
		return true;
	}

	/**
	 * Charge l'etat a partir des fichiers history.
	 *
	 * @param string $fichier_data
	 * @return TRUE
	 */
	protected function charge_donnees_fichier($fichier_data) {
		$ligne = true;
		$liste = array ();
		$this->etat_en_cours = array ();
		
		$fichier = fichier::creer_fichier ( $this->getListeOptions (), $fichier_data, "non", true );
		$fichier->ouvrir ( "r" );
		$liste = $fichier->charge_fichier ( "non", true );
		$fichier->close ();
		$this->onDebug ( "Donnees pour le fichier : " . $fichier_data, 1 );
		$this->onDebug ( $liste, 2 );
		
		if (count ( $liste ) > 0) {
			#S'il y a l'ancienne couleur (+ de 2 entrees)
			if (count ( $liste ) > 1) {
				$donnee_ancienne = $this->decoupe_ligne_log_hobbit ( $liste [count ( $liste ) - 2] );
				$this->onDebug ( "Donnees anciennes :", 2 );
				$this->onDebug ( $donnee_ancienne, 2 );
				if ($donnee_ancienne && count ( $donnee_ancienne ) == 8) {
					$this->etat_en_cours ["previous"] ["color"] = $donnee_ancienne [5];
					$this->etat_en_cours ["previous"] ["timestamp"] = $donnee_ancienne [6];
				} else {
					$this->etat_en_cours = false;
				}
			} else {
				#Sinon on creer une ancienne valeur fictive
				$this->etat_en_cours ["previous"] ["color"] = "green";
				$this->etat_en_cours ["previous"] ["timestamp"] = time ();
			}
			
			$donnee_recente = $this->decoupe_ligne_log_hobbit ( $liste [count ( $liste ) - 1] );
			$this->onDebug ( "Donnees recente :", 2 );
			$this->onDebug ( $donnee_recente, 2 );
			if ($donnee_recente && count ( $donnee_recente ) == 7) {
				$this->etat_en_cours ["current"] ["color"] = $donnee_recente [5];
				$this->etat_en_cours ["current"] ["timestamp"] = $donnee_recente [6];
			} else {
				$this->etat_en_cours = false;
			}
		} else {
			$this->etat_en_cours = false;
		}
		$this->onDebug ( $this->etat_en_cours, 1 );
		
		return true;
	}

	/**
	 * Decoupe une ligne d'history de hobbit.
	 *
	 * @param string $ligne Ligne d'history de hobbit
	 * @return array|false La ligne decoupe, FALSE sinon
	 */
	protected function decoupe_ligne_log_hobbit($ligne) {
		$this->onDebug ( "Ligne traite : " . $ligne, 2 );
		$donnee = explode ( " ", trim ( $ligne ) );
		if (count ( $donnee ) > 6) {
			$retour = array ();
			foreach ( $donnee as $data ) {
				if (trim ( $data ) != "") {
					$retour [] .= $data;
				}
			}
		} else {
			$retour = false;
		}
		$this->onDebug ( "decoupe_ligne_log_hobbit resultat : ", 2 );
		$this->onDebug ( $retour, 2 );
		
		return $retour;
	}

	/************************* Verification de l'etat de hobbit ************************/
	
	/************************* Gestion des alertes ************************/
	public function traite_alertes($server, $service, $red_send_mail) {
		$message_mail = "";
		$titre_mail = "";
		$active_mail_nagios = false;
		
		switch ($this->etat_en_cours ["current"] ["color"]) {
			case "red" :
				#Si la couleur est red
				$this->onDebug ( "Couleur pour " . $server . " " . $service . " : RED", 1 );
				if (isset ( $this->server_config [$server] [$service] )) {
					switch ($this->server_config [$server] [$service] [1]) {
						case "1" :
							//alerte de niveau 1 (mail+sms)
							$message_sms = date ( "H:i:s", $this->etat_en_cours ["current"] ["timestamp"] ) . " : " . $server . "." . $service . " est en ERREUR.";
							if (! isset ( $this->sms [$this->server_config [$server] [$service] [0]] )) {
								$this->sms [$this->server_config [$server] [$service] [0]] = $message_sms;
							} else {
								$this->sms [$this->server_config [$server] [$service] [0]] .= $message_sms;
							}
							$this->onDebug ( "Niveau 1, SMS active", 1 );
						case "2" :
							//alerte de niveau 2 (mail)
							$active_mail_nagios = true;
							$this->onDebug ( "Niveau 2, mail a nagios + predef", 1 );
							break;
						case "3" :
							$this->onDebug ( "Niveau 3", 1 );
							break;
					}
					if ($red_send_mail) {
						$message_mail = "Le service " . $service . " pour le serveur : " . $server . " est en ERREUR.";
						if ($this->server_config [$server] [$service] [2] !== "") {
							$message_mail .= "\nAppliquer la procedure : " . $this->server_config [$server] [$service] [2] . ".";
						}
						$titre_mail = "Hobbit : " . $server . "." . $service . " CRITICAL (RED)";
					}
				} else {
					$this->onWarning ( "Pas de configuration pour ce service " . $server . " " . $service . "." );
				}
				
				break;
			case "blue" :
				#Si la couleur est blue
				$this->onDebug ( "Couleur pour " . $server . " " . $service . " : BLUE", 1 );
				$message_mail = "Le service " . $service . " pour le serveur : " . $server . " est en DISABLED.";
				$titre_mail = "Hobbit : " . $server . "." . $service . " DISABLED (BLUE)";
				break;
			case "purple" :
				#Si la couleur est purple
				$this->onDebug ( "Couleur pour " . $server . " " . $service . " : PURPLE", 1 );
				$message_mail = "Le service " . $service . " pour le serveur : " . $server . " n'est pas mis a jour.";
				$titre_mail = "Hobbit : " . $server . "." . $service . " NOT UPDATED (PURPLE)";
				break;
			case "green" :
				#Si la couleur est green
				#On l'ajoute a la liste des traitements en recovered
				$this->onDebug ( "Couleur pour " . $server . " " . $service . " : GREEN", 1 );
				switch ($this->etat_en_cours ["previous"] ["color"]) {
					case "red" :
						$message_mail = "Le service " . $service . " pour le serveur : " . $server . " est OK.";
						$titre_mail = "Hobbit : " . $server . "." . $service . " RECOVERED";
						break;
					case "blue" :
						$message_mail = "Le service " . $service . " pour le serveur : " . $server . " est OK.";
						$titre_mail = "Hobbit : " . $server . "." . $service . " RE-ACTIVATED";
				}
				break;
			default :
				$this->onWarning ( "Couleur inconnue :" . $this->etat_en_cours ["current"] ["color"] );
		}
		
		if ($message_mail !== "" && $titre_mail !== "") {
			$this->onDebug ( "active Mail Nagios : " . (($active_mail_nagios == true) ? "TRUE" : "FALSE"), 1 );
			$this->envoie_mail ( $server, $service, $titre_mail, $message_mail, $active_mail_nagios );
			$this->onDebug ( "Mail envoye : " . $message_mail, 2 );
		}
		
		return $active_sms;
	}

	protected function traite_sms_nagios() {
		$this->onDebug ( "SMS en cours : " . print_r ( $this->sms, true ), 1 );
		$this->onDebug ( "liste env : " . print_r ( $this->liste_env, true ), 1 );
		$message_ok = "0|Tout est OK dans Hobbit";
		
		if (count ( $this->liste_env ) > 0) {
			foreach ( $this->liste_env as $env => $inutile ) {
				$fichier = fichier::creer_fichier ( $this->getListeOptions (), $this->config_dir . "/" . $env . "_" . $this->nagios, "oui", true );
				$fichier->ouvrir ( "w" );
				if (isset ( $this->sms [$env] ) && $this->sms [$env] !== "") {
					$fichier->ecrit ( "2|" . $this->sms [$env] );
				} else {
					$fichier->ecrit ( $message_ok );
				}
				$fichier->close ();
			}
		}
		
		//temporaire : on garde l'ancien fichier
		/*$fichier=fichier::creer_fichier($this->getListeOptions(),$this->config_dir."/".$this->nagios,"oui",true);
		$fichier->ouvrir("w");
		if(count($this->sms)!=0){
			$message="";
			foreach($this->sms as $data){
				$message.=$data;
			}
			$fichier->ecrit("2|".$message);
		} else {
			$fichier->ecrit($message_ok);
		}
		$fichier->close();*/
		
		return true;
	}

	protected function envoie_mail($server, $service, $titre, $message, $active_mail_nagios = false) {
		$mail = array ();
		
		$liste_option_local = clone $this->liste_option;
		
		$this->onDebug ( "envoie_mail active", 1 );
		if (isset ( $this->server_config [$server] [$service] [3] )) {
			$this->onDebug ( "envoie_mail group :" . $this->server_config [$server] [$service] [3], 1 );
			$liste = explode ( " ", $this->server_config [$server] [$service] [3] );
			foreach ( $liste as $group ) {
				if (isset ( $this->mail_config [$group] )) {
					$this->onDebug ( "envoi mail a " . $this->mail_config [$group], 1 );
					$mail [] .= $this->mail_config [$group];
				}
			}
			$this->onDebug ( "mail_to :" . print_r ( $mail, true ), 2 );
			if (count ( $mail ) > 0) {
				$liste_option_local->set_option ( "mail_to", $mail );
			}
		}
		
		if ($active_mail_nagios) {
			$this->onDebug ( "envoi mail a nagios", 1 );
			$liste_option_local->set_option ( "mail_cc", $this->mail_config ["nagios"] );
		}
		
		fonctions_standards_mail::envoieMail_standard ( $liste_option_local, $titre, array (
				"text" => $message 
		) );
		return true;
	}

	/************************* Gestion des alertes ************************/
	
	/************************* Accesseurs ************************/
	/**
	 * Set la liste des fichiers history de hobbit
	 *
	 * @param string $server
	 * @param string $service
	 * @param string $fichier
	 */
	protected function set_liste_fichiers($server, $service, $fichier) {
		if (! isset ( $this->liste_fichiers [$server] )) {
			$this->liste_fichiers [$server] = array ();
		}
		$this->liste_fichiers [$server] [$service] = $fichier;
		
		return true;
	}

	protected function set_server_config($serveur) {
		#On charge la config correspondante
		if (! isset ( $this->server_config [$serveur] )) {
			$this->server_config [$serveur] = $this->charge_config_fichier ( $this->config_dir . "/" . $serveur . "_config" );
		}
		
		return true;
	}

	/************************* Accesseurs ************************/
	
	/**
	 * @static
	 *
	 * @param string $echo Affiche le help
	 * @return string Renvoi le help
	 */
	static function help() {
		$help = parent::help ();
		
		$help [__CLASS__]["text"] [] .= "Verifie l'etat d'un point de monitoring hobbit";
		
		return $help;
	}
}
?>
