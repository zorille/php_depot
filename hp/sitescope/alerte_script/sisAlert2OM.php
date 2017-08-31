<?php
/**
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage sitescope
 */
if (! isset ( $argv ) && ! isset ( $argc )) {
	fwrite ( STDOUT, "Il n'y a pas de parametres en argument.\r\n" );
	exit ( 0 );
}

//Deplacement pour joindre le repertoire lib
$deplacement = "/../../TOOLS";
$rep_document = dirname ( $argv [0] ) . $deplacement;

//On reconstruit la liste des arguments au format "Framework PHP"
$chemin_script = $argv [1];
$argv [1] = '--chemin_script="' . $argv [1] . '"';
$argv [2] = '--moniteur_name="' . $argv [2] . '"';
$argv [3] = '--moniteur_status="' . $argv [3] . '"';
$argv [4] = '--fichier_alerte=' . $argv [4];
$argv [5] = '--moniteur_id="' . $argv [5] . '"';
$argv [6] = '--moniteur_groupe="' . $argv [6] . '"';
$argv [] .= '--conf';
$argv [] .= $deplacement . '/conf_clients/hpom/prod_hpom_windows.xml';
$argv [] .= '--verbose';

$argc = count ( $argv );
$INCLUDE_HPOM = true;
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

/**
 *
 * @ignore Affiche le help.<br>
 *         Cette fonction fait un exit.
 *         Arguments reconnus :<br>
 *         --help
 */
function help() {
	$fichier = basename ( __FILE__ );
	$help = array (
			"usage" => array (
					$fichier . " --conf [fichiers de conf] [OPTIONS]",
					$fichier . " --help" 
			),
			$fichier => array () 
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Permet d'ouvrir un ticket alerte dans HPOM via SiteScope";
	$help [$fichier] ["text"] [] .= "argument 1 : chemin_script";
	$help [$fichier] ["text"] [] .= "argument 2 : moniteur_name";
	$help [$fichier] ["text"] [] .= "argument 3 : moniteur_status";
	$help [$fichier] ["text"] [] .= "argument 4 : fichier_alerte";
	$help [$fichier] ["text"] [] .= "argument 5 : moniteur_id";
	$help [$fichier] ["text"] [] .= "argument 6 : moniteur_groupe";
	
	$class_utilisees = array (
			"hpom_client"
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\r\n";
	exit ( 0 );
}
/**
 * @class gestion_alerte_sis
 * @author davargas
 *
 */
class gestion_alerte_sis {

	static function parse_fichier_alerte(&$tableau_fichier_alerte) {
		$donnees = array ();
		
		//flag __SOD__ .... __EOD__ pour le multi-ligne
		$param_en_cours = "";
		$flag_alert2om = false;
		foreach ( $tableau_fichier_alerte as $ligne ) {
			//pour chaque ligne
			$ligne = trim ( $ligne );
			if ($ligne === "") {
				continue;
			}
			
			//Si la ligne ne contient pas ":"
			if ($param_en_cours !== "" && strpos ( $ligne, ":" ) === false) {
				//alors on groupe le texte au precedent
				$donnees [$param_en_cours] .= "\n" . $ligne;
				continue;
			}
			
			//traitement des regexp qui peuvent contenir des \n et des ":"
			if ($flag_alert2om === false && $ligne == "alert2om_start:tag") {
				//flag de depart des regexp
				$flag_alert2om = true;
				continue;
			} elseif ($flag_alert2om === true && $ligne == "alert2om_end:tag") {
				//flag de fin des regexp
				$flag_alert2om = false;
				continue;
			} elseif ($flag_alert2om === true && strpos ( $ligne, "alert2om_" ) === false) {
				//On ajoute la ligne qui ne demarre pas alert2om_regexp_value a la valeur precedente de alert2om_regexp_value
				$donnees [$param_en_cours] .= "\n" . $ligne;
				continue;
			}
			
			//On retrouve les parametres
			$liste_data = explode ( ":", $ligne, 2 );
			if ($liste_data && count ( $liste_data ) == 2) {
				$param_en_cours = strtolower ( $liste_data [0] );
				$donnees [$param_en_cours] = trim ( $liste_data [1] );
			} else {
				//En cas d'erreur on desactive les flags
				abstract_log::onError_standard ( "La ligne n'est pas decoupable : " . $ligne );
			}
		}
		
		return $donnees;
	}

	static function decoupe_nom_moniteur($moniteur_name) {
		$msg_text = array ();
		#get the object that is first word of incident title
		# get the monitor name  e.g. "MySQL mysqld on nvv004.nvv.sms"
		$liste_nom = explode ( " ", $moniteur_name, 2 );
		if ($liste_nom !== false && count ( $liste_nom ) == 2) {
			$msg_text ["objet"] = $liste_nom [0];
			$msg_text ["fin_nom_moniteur"] = $liste_nom [1];
		} else {
			$msg_text ["objet"] = $moniteur_name;
			$msg_text ["fin_nom_moniteur"] = "";
		}
		
		//Dans tous les cas on decoupe l'instance du titre
		if (preg_match ( '/^.* \[(?P<instance>.*)\]/', $moniteur_name, $matches ) != 0) {
			$msg_text ["instances"] [0] = $matches ["instance"];
		} else {
			$msg_text ["instances"] [0] = "";
		}
		
		return $msg_text;
	}

	static function prepare_liste_des_moniteurs_en_erreur(&$donnees, &$message) {
		$msg_text = gestion_alerte_sis::decoupe_nom_moniteur ( $donnees ["alert2om_monitor"] );
		
		#get the incident description for specific resources
		#in case of log file or windows events analysis
		switch ($donnees ["alert2om_class"]) {
			case "LogMonitor" :
			case "NTEventLogMonitor" :
			case "ScriptMonitor" :
				//Match Value Labels: titre,detail,instance
				if (isset ( $donnees ["match value labels"] ) && $donnees ["match value labels"] != "") {
					$liste_value = explode ( ",", $donnees ["match value labels"] );
					if ($liste_value !== false) {
						foreach ( $liste_value as $pos => $value ) {
							if (isset ( $donnees ["alert2om_regexp_value" . $pos] )) {
								$donnees [$value] = &$donnees ["alert2om_regexp_value" . $pos];
							}
						}
					} else {
						abstract_log::onError_standard ( "La liste des value n'est pas decoupable : " . $donnees ["match value labels"] );
						exit ( 4 );
					}
				}
				
				//Si il y a un titre, on l'ajoute au message
				if (isset ( $donnees ["OMtitre"] ) && $donnees ["OMtitre"] != "") {
					$message .= "\n" . $donnees ["OMtitre"];
				}
				
				//Si il y a un detail : on l'ajoute
				if (isset ( $donnees ["OMdetail"] ) && $donnees ["OMdetail"] != "") {
					$message .= "\n" . $donnees ["OMdetail"];
				}
				
				//Si il y a une instance, on l'ajoute
				if (isset ( $donnees ["OMinstance"] ) && $donnees ["OMinstance"] != "") {
					//On modifie le titre de l'alarme pour les instances de base de donnees
					$msg_text ["instances"] [0] = $donnees ["OMinstance"];
					return $msg_text;
				}
				
				//Si il y a un hostname dans les value labels : on remplace le CI par ce hostname
				if (isset ( $donnees ["OMhostname"] ) && $donnees ["OMhostname"] != "") {
					$donnees ["alert2om_ci"] = $donnees ["OMhostname"];
				}
				
				break;
			case "AutoServicesMonitor" :
				//On decoupe la liste des services en erreur
				$liste_service = explode ( ";", $donnees ["services currently not running"] );
				if ($liste_service !== false) {
					$msg_text ["instances"] = $liste_service;
					return $msg_text;
				} else {
					abstract_log::onError_standard ( "La liste des services n'est pas decoupable : " . $donnees ["services currently not running"] );
					exit ( 5 );
				}
				break;
			default :
				$message .= "\n" . $donnees ["alert2om_state"];
		}
		
		return $msg_text;
	}

	static function traite_nom_client(&$donnees, $nom_client) {
		$fichier = str_replace ( '"', '', $donnees ["dossier_scripts"] ) . "/client.txt";
		if (fichier::tester_fichier_existe ( $fichier )) {
			$donnee_client = fichier::Lit_integralite_fichier_en_tableau ( $fichier );
			if (is_array ( $donnee_client ) && count ( $donnee_client ) >= 1) {
				return $donnee_client [0];
			}
		}
		
		return $nom_client;
	}

	static function traite_nom_CI(&$donnees, $nom_CI) {
		if (! isset ( $donnees ["alert2om_ci"] )) {
			abstract_log::onError_standard ( "il manque le parametre alert2om_ci dans le fichier alert (Template à jour ?)." );
			exit ( 1 );
		} else {
			if ($donnees ["alert2om_ci"] == "Applications_" . $nom_CI || $donnees ["alert2om_ci"] == "Databases_" . $nom_CI || $donnees ["alert2om_ci"] == "Messagerie_" . $nom_CI || $donnees ["alert2om_ci"] == "Moniteurs_" . $nom_CI || $donnees ["alert2om_ci"] == "SAP_" . $nom_CI || $donnees ["alert2om_ci"] == "Urls_" . $nom_CI) {
				return $nom_CI;
			}
		}
		
		return $donnees ["alert2om_ci"];
	}

	static function decoupe_donnees_client(&$donnees) {
		$nom_split = array ();
		//Decoupe les donnees dans le chemin du moniteur : CLI: WINDOWS: PERMANENT...
		$liste_nom = explode ( ":", $donnees ["alert2om_fullgroup"] );
		if ($liste_nom !== false) {
			if (isset ( $liste_nom [0] )) {
				$nom_split ["client"] = gestion_alerte_sis::traite_nom_client ( $donnees, trim ( $liste_nom [0] ) );
			} else {
				$nom_split ["client"] = gestion_alerte_sis::traite_nom_client ( $donnees, "UNKNOWN" );
			}
			
			if (isset ( $liste_nom [1] )) {
				$nom_split ["application"] = trim ( $liste_nom [1] );
			} else {
				$nom_split ["application"] = "APPLICATION";
			}
			
			if (isset ( $liste_nom [2] )) {
				$nom_split ["schedule"] = trim ( $liste_nom [2] );
			} else {
				$nom_split ["schedule"] = "PERMANENT";
			}
			
			if (isset ( $liste_nom [3] )) {
				$nom_split ["alert2om_ci"] = gestion_alerte_sis::traite_nom_CI ( $donnees, trim ( $liste_nom [3] ) );
			} else {
				$nom_split ["alert2om_ci"] = $donnees ["alert2om_ci"];
			}
		} else {
			abstract_log::onError_standard ( "Le chemin du moniteur n'est pas decoupable : " . $donnees ["alert2om_fullgroup"] );
			exit ( 1 );
		}
		
		return $nom_split;
	}
}
// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();
	
	// Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

/**
 * Main programme
 * Code retour en 2xxx en cas d'erreur
 * @ignore
 *
 * @param options $liste_option        	
 * @param logs $fichier_log        	
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	$fichier_log->setIsErrorStdout ( true );
	
	$tableau_fichier_alerte = fichier::Lit_integralite_fichier_en_tableau ( $liste_option->getOption ( "fichier_alerte" ) );
	if ($tableau_fichier_alerte === false) {
		abstract_log::onError_standard ( "Le fichier : " . $liste_option->getOption ( "fichier_alerte" ) . " n'est pas lisible.", "", 2 );
		return false;
	}
	
	//On parse les donnees du fichier d'alerte
	$donnees = gestion_alerte_sis::parse_fichier_alerte ( $tableau_fichier_alerte );
	$donnees ["dossier_scripts"] = $liste_option->getOption ( "chemin_script" );
	
	if (! isset ( $donnees ["alert2om_sitescopeurl"] )) {
		abstract_log::onError_standard ( "il manque le parametre alert2om_sitescopeurl dans le fichier alert (Template à jour ?)." );
		return false;
	}
	if (! isset ( $donnees ["alert2om_fullgroup"] )) {
		abstract_log::onError_standard ( "il manque le parametre alert2om_fullgroup dans le fichier alert (Template à jour ?)." );
		return false;
	}
	if (! isset ( $donnees ["alert2om_class"] )) {
		abstract_log::onError_standard ( "il manque le parametre alert2om_class dans le fichier alert (Template à jour ?)." );
		return false;
	}
	if (! isset ( $donnees ["alert2om_state"] )) {
		abstract_log::onError_standard ( "il manque le parametre alert2om_state dans le fichier alert (Template à jour ?)." );
		return false;
	}
	if (! isset ( $donnees ["alert2om_ci"] )) {
		abstract_log::onError_standard ( "il manque le parametre alert2om_ci dans le fichier alert (Template à jour ?)." );
		return false;
	}
	if (! isset ( $donnees ["alert2om_severity"] )) {
		abstract_log::onError_standard ( "il manque le parametre alert2om_severity dans le fichier alert (Template à jour ?)." );
		return false;
	}
	
	//On assure la presence de cette variable
	if (! isset ( $donnees ["alert2om_monitor"] )) {
		$donnees ["alert2om_monitor"] = $liste_option->getOption ( "moniteur_name" );
	}
	
	$message = "This alert was generated by sitescope\n" . $donnees ["alert2om_sitescopeurl"] . "\nPath to monitor:" . $donnees ["alert2om_fullgroup"] . ": " . $donnees ["alert2om_monitor"];
	$msg_text = gestion_alerte_sis::prepare_liste_des_moniteurs_en_erreur ( $donnees, $message );
	$donnees_client = gestion_alerte_sis::decoupe_donnees_client ( $donnees );
	
	try {
		$hpom_client = hpom_client::creer_hpom_client ( $liste_option, false );
		
		$hpom_client->setMsgGrp ( $donnees_client ["client"] )
			->setNode ( $donnees_client ["alert2om_ci"] )
			->gestion_severite ( $donnees ["alert2om_severity"] )
			->setApplication ( $donnees_client ["application"] )
			->setObjet ( $msg_text ["objet"] )
			->setInstances ( $msg_text ["instances"] )
			->AjouteOption ( "incident_descr", $message );
		
		//S'il y a un titre, on l'ajoute a la fin du msg_text
		if (isset ( $donnees ["OMtitre"] )) {
			$hpom_client->setAppendMsgText ( $donnees ["OMtitre"] );
		}
		
		$hpom_client->envoi_hpom_datas ();
	} catch ( Exception $e ) {
		//Erreur deja affichee
		return false;
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
