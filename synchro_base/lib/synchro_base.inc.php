<?php
/**
 * Permet la synchro de 2 bases differentes
 * @author dvargas
 */
/**
 * class synchro_base
 * @package SynchroBase
 */
class synchro_base extends abstract_log {

	/*********************** Creation de l'objet *********************/
	/**
	 * Instancie un objet de type synchro_base.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return synchro_base
	 */
	static function &creer_synchro_base(&$liste_option, $sort_en_erreur = false, $entete = __CLASS__) {
		$objet = new synchro_base ( $sort_en_erreur, $entete );
		$objet->_initialise ( array (
				"options" => $liste_option 
		) );
		
		return $objet;
	}

	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return synchro_base
	 */
	public function &_initialise($liste_class) {
		parent::_initialise ( $liste_class );
		return $this;
	}

	/*********************** Creation de l'objet *********************/
	/**
	 * Constructeur.
	 *
	 * @param bool $sort_en_erreur
	 */
	public function __construct($sort_en_erreur = false, $entete = __CLASS__) {
		//Gestion de abstract_log
		parent::__construct ( $sort_en_erreur, $entete );
	}

	/**
	 * Applique les requetes et renvoi un tableau "de hash" representant la liste des tuples : <br>
	 * $tableau["champ1"]["champ2"].....["champN"]=1
	 *
	 * @param requete $connexion Connexion ouverte sur une base (objet BD).
	 * @param string $requete Requete a appliquer sur la base.
	 * @return array|false Tableau de resultat (liste des tuples), FALSE sinon.
	 */
	private function _GetData($connexion, $requete) {
		$this->onDebug ( "Application de la requete : " . $requete, 2 );
		try {
			$resultat = $connexion->faire_requete ( $requete );
		} catch ( Exception $e ) {
			return $this->onError ( $e->getMessage (), "", $e->getCode () );
		}
		if (! $resultat)
			$CODE_RETOUR = FALSE;
		else
			$CODE_RETOUR = "";
			//On joue avec les adresses memoires pour creer le tableau de hash
		foreach ( $resultat as $row ) {
			$liste = &$CODE_RETOUR;
			foreach ( $row as $key => $value ) {
				if (! is_int ( $key )) {
					if (is_string ( $value ) && $value == '')
						$value = 'ZVIDE';
					elseif ($value == NULL)
						$value = 'ZNULL';
					$value = $this->_encodeDonnee ( $value );
					if (! isset ( $liste [$value] ))
						$liste [$value] = array ();
					$liste = &$liste [$value];
				}
			}
			$liste = 1;
		}
		
		if ($CODE_RETOUR === FALSE)
			return $this->onError ( "Probleme durant la requete " . $requete, "" );
		else
			$this->onDebug ( "La requete " . $requete . " est OK.", 1 );
		
		$this->onDebug ( "Resultat de la requete : " . $requete, 2 );
		$this->onDebug ( $CODE_RETOUR, 2 );
		return $CODE_RETOUR;
	}

	/**
	 * Fonction qui compare 2 tableaux de resultats.<br>
	 * Les tableaux a comparer doivent avoir une "profondeur" egale (meme nombre de champ dans la requete SQL).<br>
	 * Cette fonction modifie le tableau2 (image de la table d'arrivee) en supprimant les tuples egaux,
	 * et, en mettant 1 pour les tuples a supprimer et 0 pour les tuples a ajouter.
	 *
	 * @param array $tableau1 Liste des tuples de la table a synchroniser (table d'origine).
	 * @param array &$tableau2 Pointeur vers la liste des tuples de la table de destination.
	 * @return true
	 */
	private function _CompareTuple($tableau1, &$tableau2) {
		if (is_array ( $tableau1 ) && count ( $tableau1 ) > 0) {
			foreach ( $tableau1 as $key => $value ) {
				if (! isset ( $tableau2 [$key] ))
					$tableau2 [$key] = array ();
				if (is_array ( $value )) {
					$this->_CompareTuple ( $value, $tableau2 [$key] );
					if (is_array ( $tableau2 [$key] ) && count ( $tableau2 [$key] ) == 0)
						unset ( $tableau2 [$key] );
				} elseif (! is_array ( $tableau2 [$key] ) && $tableau2 [$key] == 1)
					unset ( $tableau2 [$key] );
				else
					$tableau2 [$key] = 0;
			}
		}
		
		return true;
	}

	/**
	 * Fonction qui prend le tableau apres comparaison et renvoi
	 * la liste de tuple a supprimer pour le code 1 et la liste des
	 * tuple a ajouter pour le code 0.
	 *
	 * @param array $tableau Tableau renvoye par _CompareTuple.
	 * @param int $code Prend les valeurs 0 ou 1.
	 * @return array Liste des tuples a modifier en fonction du code.
	 */
	private function _retrouveDonneesAModifier($tableau, $code) {
		$CODE_RETOUR = array ();
		$flag = false;
		if (is_array ( $tableau ) && count ( $tableau ) > 0) {
			//permet de connaitre la ligne du tableau resultat
			$i = 1;
			//Pour chaque case du tableau de depart
			foreach ( $tableau as $key => $value ) {
				//On passe au sous case
				$liste = $this->_retrouveDonneesAModifier ( $value, $code );
				$CODE_RETOUR [0] = $liste [0];
				if ($liste [0]) {
					//si la taille est a 1, on charge la valeur
					if (count ( $liste ) == 1) {
						$CODE_RETOUR [$i] = $key;
						$flag = true;
						$i ++;
					} else {
						for($j = 1; $j < count ( $liste ); $j ++) {
							$CODE_RETOUR [$i] = $key . "'param'" . $liste [$j];
							
							$flag = true;
							$i ++;
						}
					}
				}
			}
		} elseif ($tableau == $code)
			$CODE_RETOUR [0] = true;
		else
			$CODE_RETOUR [0] = false;
		
		if ($flag)
			$CODE_RETOUR [0] = true;
		
		return $CODE_RETOUR;
	}

	/**
	 * Fonction qui recupere la liste des champ du select pour faire le INSERT.
	 *
	 * @param string $requete Requete contenant des champs.
	 * @return array Liste des champs de la requete.
	 */
	private function _recupererListeChamp($requete) {
		$liste_string = substr ( $requete, 0, stripos ( $requete, "FROM " ) );
		$liste = explode ( ",", trim ( $liste_string ) );
		if ($liste && count ( $liste ) > 0) {
			//On vire le select et autres distinct ..
			$tempo = explode ( " ", $liste [0] );
			$liste [0] = $tempo [(count ( $tempo ) - 1)];
			foreach ( $liste as $pos => $champ ) {
				if (stripos ( $champ, " AS " ) !== false) {
					$tempo2 = explode ( " ", $champ );
					$liste [$pos] = $tempo2 [3];
				}
			}
			$CODE_RETOUR = $liste;
		} else
			$CODE_RETOUR = false;
		
		return $CODE_RETOUR;
	}

	/**
	 * Encode une string
	 * @param string $data
	 * @return string
	 */
	private function _encodeDonnee($data) {
		$RETOUR = str_replace ( "\'", "'", $data );
		$RETOUR = str_replace ( "\\", "", $RETOUR );
		$RETOUR = urlencode ( trim ( $RETOUR ) );
		
		return $RETOUR;
	}

	/**
	 * Decode une string
	 * @param string $data
	 * @return string
	 */
	private function _decodeDonnee($data) {
		$RETOUR = str_replace ( "'", "''", urldecode ( $data ) );
		
		return $RETOUR;
	}

	/**
	 * Supprime les tuples en trop dans la base de destination.
	 *
	 * @param array $tableau_comparee Tableau renvoye par _CompareTuple.
	 * @param requete $connexion_dest Connexion vers la base de destination.
	 * @param string $table Table a modifier dans la base de destination.
	 * @param array $liste_champs Liste des champs de la table de destination.
	 * @return Bool TRUE si OK, FALSE sinon.
	 */
	private function _removeDestData($tableau_comparee, $connexion_dest, $table, $liste_champs) {
		$CODE_RETOUR = true;
		$liste_a_supprimer = $this->_retrouveDonneesAModifier ( $tableau_comparee, 1 );
		$nb_ligne = count ( $liste_a_supprimer );
		$nb_en_cours = $nb_ligne - 1;
		$this->onInfo ( "Il y a " . $nb_en_cours . " lignes a supprimer." );
		$this->onDebug ( "Liste des lignes a supprimer : ", 1 );
		$this->onDebug ( $liste_a_supprimer, 1 );
		if ($nb_ligne > 1) {
			for($i = 1; $i < $nb_ligne; $i ++) {
				$liste_variable = explode ( "'param'", $liste_a_supprimer [$i] );
				if (count ( $liste_variable ) == count ( $liste_champs )) {
					$ligne = "";
					for($j = 0; $j < count ( $liste_champs ); $j ++) {
						if ($liste_variable [$j] != "ZNULL") {
							if ($ligne != "")
								$ligne .= " AND ";
							
							if ($liste_variable [$j] == "ZVIDE")
								$ligne .= $liste_champs [$j] . "=''";
							else {
								$ligne .= $liste_champs [$j] . "='" . $this->_decodeDonnee ( $liste_variable [$j] ) . "'";
							}
						}
					}
					$requete = "DELETE FROM " . $table . " WHERE " . $ligne . " ;";
					$this->onInfo ( $table . " : suppression de : " . $ligne );
					$this->onDebug ( "Requete appliquee : " . $requete, 1 );
					try {
						$connexion_dest->faire_requete ( $requete );
					} catch ( Exception $e ) {
						return $this->onError ( $e->getMessage (), "", $e->getCode () );
					}
					$CODE_RETOUR = true;
				} else
					$CODE_RETOUR = false;
			}
		}
		if ($CODE_RETOUR === FALSE)
			return $this->onError ( "Probleme durant La suppression de " . $nb_en_cours . " lignes de la BD sur dest.", "" );
		else
			$this->onInfo ( "La suppression de " . $nb_en_cours . " lignes de la BD est OK sur dest." );
		
		return $CODE_RETOUR;
	}

	/**
	 * Ajoute les tuples manquants dans la base de destination.
	 *
	 * @param array $tableau_comparee Tableau renvoye par _CompareTuple.
	 * @param requete $connexion_dest Connexion vers la base de destination.
	 * @param string $table Table a modifier dans la base de destination.
	 * @param array $liste_champs Liste des champs de la table de destination.
	 * @return Bool TRUE si OK, FALSE sinon.
	 */
	private function _AddDestData($tableau_comparee, $connexion_dest, $table, $liste_champs) {
		$CODE_RETOUR = true;
		$liste_a_supprimer = $this->_retrouveDonneesAModifier ( $tableau_comparee, 0 );
		$this->onDebug ( "Liste des lignes a ajouter : ", 1 );
		$this->onDebug ( $liste_a_supprimer, 1 );
		
		$nb_ligne = count ( $liste_a_supprimer );
		$nb_en_cours = $nb_ligne - 1;
		$this->onInfo ( "Lignes a ajouter : " . $nb_en_cours );
		if ($nb_ligne > 1) {
			for($i = 1; $i < $nb_ligne; $i ++) {
				$liste_variable = explode ( "'param'", $liste_a_supprimer [$i] );
				if (count ( $liste_variable ) == count ( $liste_champs )) {
					$ligne = "";
					$insert = "";
					for($j = 0; $j < count ( $liste_champs ); $j ++) {
						if ($ligne != "")
							$ligne .= ",";
						$ligne .= $liste_champs [$j];
						if ($insert != "")
							$insert .= ",";
						$insert .= "'" . $this->_decodeDonnee ( $liste_variable [$j] ) . "'";
						$insert = str_replace ( "'ZNULL'", "NULL", $insert );
						$insert = str_replace ( "'ZVIDE'", "''", $insert );
					}
					$requete = "INSERT INTO " . $table . " (" . $ligne . ") VALUE (" . $insert . ") ;";
					$this->onInfo ( $table . " : ajout de : " . $insert );
					$this->onDebug ( "Requete appliquee : " . $requete, 1 );
					try {
						$connexion_dest->faire_requete ( $requete );
					} catch ( Exception $e ) {
						return $this->onError ( $e->getMessage (), "", $e->getCode () );
					}
					$CODE_RETOUR = true;
				} else {
					return $this->onError ( "Le nombre de champ du set ne correspond pas au nombre de champs a inserer." );
				}
			}
		}
		
		if ($CODE_RETOUR === FALSE)
			return $this->onError ( "Probleme durant l'ajout de " . $nb_en_cours . " lignes dans la BD sur dest.", "" );
		else
			$this->onInfo ( "L'ajout de " . $nb_en_cours . " lignes dans la BD est OK sur dest." );
		
		return $CODE_RETOUR;
	}

	/********************************************************************/
	/**
	 * Concatene deux tableaux.
	 *
	 * @param array $tableau1 Tableau quelconque.
	 * @param array $tableau2 Tableau quelconque.
	 * @return array Renvoi les tableaux concatenes.
	 */
	private function _concateneTableau($tableau1, $tableau2) {
		if (is_array ( $tableau2 )) {
			foreach ( $tableau2 as $key => $data ) {
				if (is_array ( $data ) && count ( $data ) > 0)
					$tableau1 [$key] = $this->_concateneTableau ( $tableau1 [$key], $data );
				else
					$tableau1 [$key] = $data;
			}
		}
		
		return $tableau1;
	}

	/**
	 * Synchronise des comptes de cumuls.
	 * 
	 * @param options &$liste_option Pointeur sur les arguments.
	 * @param db &$connexion_entree Connexion vers la base d'origine.
	 * @param db &$connexion_sortie Connexion vers la base de destination.
	 * @param array &$connexion liste de connexions connue
	 */
	private function _gereCumulAccount(&$liste_option, &$connexion_entree, &$connexion_sortie, &$connexion) {
		//Gestion des CumulsVirtuels via l'objet gestion_bd_source
		if ($liste_option->verifie_option_existe ( "cumulvirtuel" ) !== false) {
			$this->onInfo ( "Gestion des Cumuls Virtuels." );
			$liste_option->prepare_variable_standard ( array (
					"cumulvirtuel",
					"requete_serial" 
			));
			$liste_option->prepare_variable_standard ( array (
					"cumulvirtuel",
					"mode" 
			), "php" );
			$liste_option->prepare_variable_standard (  array (
					"cumulvirtuel",
					"table" 
			), "CumulAccount" );
			$bd_source = fonctions_standards_sgbd::recupere_db_source ( $connexion );
			if ($bd_source) {
				$liste_serial_source = $this->_GetData ( $connexion_entree, $liste_option->getOption ( array (
						"cumulvirtuel",
						"requete_serial" 
				) ) );
				if ($liste_option->getOption ( array (
						"cumulvirtuel",
						"mode" 
				) ) == "sql") {
					$this->onDebug ( "Mode SQL", 1 );
					$liste_cumul_virtuel = $bd_source->recupere_liste_serial ();
				} else {
					//mode PHP par defaut
					$this->onDebug ( "Mode PHP", 1 );
					$liste_cumul_virtuel = $bd_source->retrouve_liste_serial_php ();
				}
				try {
					$connexion_sortie->faire_requete ( "DELETE FROM " . $liste_option->getOption ( array (
							"cumulvirtuel",
							"table"
					) ) );
				} catch ( Exception $e ) {
					return $this->onError ( $e->getMessage (), "", $e->getCode () );
				}
				$pos = 0;
				foreach ( $liste_serial_source as $master_serial => $inutile ) {
					if (isset ( $liste_cumul_virtuel [$master_serial] )) {
						$pos ++;
						if (is_array ( $liste_cumul_virtuel [$master_serial] )) {
							foreach ( $liste_cumul_virtuel [$master_serial] as $serial => $inutile ) {
								try {
									$connexion_sortie->faire_requete ( "INSERT INTO " . $liste_option->getOption ( array (
											"cumulvirtuel",
											"table"
									) ) . " VALUES ('" . $master_serial . "','" . $serial . "')" );
								} catch ( Exception $e ) {
									return $this->onError ( $e->getMessage (), "", $e->getCode () );
								}
							}
						} else {
							try {
								$connexion_sortie->faire_requete ( "INSERT INTO " . $liste_option->getOption ( array (
										"cumulvirtuel",
										"table"
								) ) . " VALUES ('" . $master_serial . "','" . $liste_cumul_virtuel [$master_serial] . "')" );
							} catch ( Exception $e ) {
								return $this->onError ( $e->getMessage (), "", $e->getCode () );
							}
						}
					}
				}
				$this->onInfo ( "Il y a " . $pos . " Cumuls Virtuel." );
			}
			$this->onInfo ( "Fin de la gestion des Cumuls Virtuels." );
		}
		
		return true;
	}

	/********************************************************************/
	
	/**
	 * Ordonnance la synchronisation de chaque table du fichier de configuration.
	 *
	 * @param options &$liste_option Pointeur sur les arguments.
	 * @param requete $connexion_entree Connexion vers la base d'origine.
	 * @param requete $connexion_sortie Connexion vers la base de destination.
	 * @param array &$connexion liste de connexions connue
	 * @return Bool TRUE si OK, FALSE sinon.
	 */
	public function synchro_table(&$liste_option, &$connexion_entree, &$connexion_sortie, &$connexion) {
		$CODE_RETOUR = false;
		$this->onInfo ( "La synchro demarre." );
		$tables = $liste_option->getOption ( "tables" );
		if ($tables && is_array ( $tables )) {
			foreach ( $tables as $table => $donnees ) {
				if (! isset ( $donnees ["table"] ) || ! isset ( $donnees ["requete_entree"] ) || ! isset ( $donnees ["requete_sortie"] ))
					continue;
				$this->onInfo ( "On synchronise la table : " . $donnees ["table"] );
				
				if (! isset ( $donnees ["type"] ))
					$donnees ["type"] = "delete";
					//On recupere les donnees dans la table d'entree
				$liste_data_source = $this->_GetData ( $connexion_entree, $donnees ["requete_entree"] );
				if (isset ( $donnees ["requete_entree_secondaire"] ) && $donnees ["requete_entree_secondaire"] != "") {
					$liste_data_secondaire = $this->_GetData ( $connexion_entree, $donnees ["requete_entree_secondaire"] );
					if (count ( $liste_data_secondaire ) > 0)
						$liste_data_source = $this->_concateneTableau ( $liste_data_source, $liste_data_secondaire );
				}
				//On recupere les donnees dans la table de sortie
				$liste_data_dest = $this->_GetData ( $connexion_sortie, $donnees ["requete_sortie"] );
				//on compare les donnees
				$this->onDebug ( "On compare les donnees entre les bases.", 1 );
				$this->_CompareTuple ( $liste_data_source, $liste_data_dest );
				$this->onDebug ( "Donnees a modifier :", 2 );
				$this->onDebug ( $liste_data_dest, 2 );
				//enfin on envoi l'ajout et/ou la suppression dans dest
				if (is_array ( $liste_data_dest ) && count ( $liste_data_dest ) > 0) {
					$liste_champs = $this->_recupererListeChamp ( $donnees ["requete_sortie"] );
					$this->onDebug ( "Liste des champs : ", 1 );
					$this->onDebug ( $liste_champs, 1 );
					if ($donnees ["type"] == "delete")
						$CODE_RETOUR = $this->_removeDestData ( $liste_data_dest, $connexion_sortie, $donnees ["table"], $liste_champs );
					$CODE_RETOUR = $this->_AddDestData ( $liste_data_dest, $connexion_sortie, $donnees ["table"], $liste_champs );
				} else
					$this->onInfo ( "Aucune modification necessaire pour la table : " . $donnees ["table"] );
			}
		} else {
			return $this->onError ( "Aucune table dans la liste des options.", "" );
		}
		
		$this->_gereCumulAccount ( $liste_option, $connexion_entree, $connexion_sortie, $connexion );
		
		return $CODE_RETOUR;
	}

	/**
	 * Affiche le help.<br>
	 * Cette fonction fait un exit.
	 * Arguments reconnus :<br>
	 * --help
	 */
	static public function help() {
		$help = parent::help ();
		
		$help [__CLASS__] ["text"] = array ();
		$help [__CLASS__] ["text"] [] .= "Gere la synchro des bases";
		$help [__CLASS__] ["text"] [] .= " <sql using=\"oui\">";
		$help [__CLASS__] ["text"] [] .= "  <liste_bases>";
		$help [__CLASS__] ["text"] [] .= "   <sql_entree using=\"oui\">";
		$help [__CLASS__] ["text"] [] .= "    <database>database_source</database>";
		$help [__CLASS__] ["text"] [] .= "    <dbhost>serveur1</dbhost>";
		$help [__CLASS__] ["text"] [] .= "    <dbuser>user</dbuser>";
		$help [__CLASS__] ["text"] [] .= "    <dbpasswd>passwd</dbpasswd>";
		$help [__CLASS__] ["text"] [] .= "   </sql_entree>";
		$help [__CLASS__] ["text"] [] .= "   <sql_sortie using=\"oui\" >";
		$help [__CLASS__] ["text"] [] .= "    <database>database_dest</database>";
		$help [__CLASS__] ["text"] [] .= "    <dbhost>serveur2</dbhost>";
		$help [__CLASS__] ["text"] [] .= "    <dbuser>user</dbuser>";
		$help [__CLASS__] ["text"] [] .= "    <dbpasswd>passwd</dbpasswd>";
		$help [__CLASS__] ["text"] [] .= "   </sql_sortie>";
		$help [__CLASS__] ["text"] [] .= "  </liste_bases>";
		$help [__CLASS__] ["text"] [] .= " </sql>";
		$help [__CLASS__] ["text"] [] .= " ";
		$help [__CLASS__] ["text"] [] .= " <tables>";
		$help [__CLASS__] ["text"] [] .= "  <table_name>";
		$help [__CLASS__] ["text"] [] .= "   <requete_entree>select champ_1,champ_2 from table_name where id=1</requete_entree>";
		$help [__CLASS__] ["text"] [] .= "   <requete_entree_secondaire>select champ_1,champ_2 from table_name  </requete_entree_secondaire> Optionnel";
		$help [__CLASS__] ["text"] [] .= "   <requete_sortie>select champ_1,champ_2 from table_name2 where id=1</requete_sortie>";
		$help [__CLASS__] ["text"] [] .= "   <table>table_name2</table>";
		$help [__CLASS__] ["text"] [] .= "  </table_name>";
		$help [__CLASS__] ["text"] [] .= " </tables>";
		$help [__CLASS__] ["text"] [] .= " ";
		$help [__CLASS__] ["text"] [] .= "Dans le select, il ne faut pas mettre des \"integers\" ou il faut les nommer avec \"as\"";
		$help [__CLASS__] ["text"] [] .= " ";
		$help [__CLASS__] ["text"] [] .= "Pour les \"requete_entree_secondaire\", il faut avoir les memes champs que la \"requete_entree\" dans le select";
		
		return $help;
	}
}
?>
