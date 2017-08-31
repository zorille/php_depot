<?php
/**
 * @author dvargas
 */

/**
 * class algorythme<br>
 * Gere les calculs pour les extractions.
 * @package Extraction
*/
class algorythme extends abstract_log {
	//Information de depart
	/**
	 * var privee
	 * @access private
	 * @var array
	*/
	var $def_algorythme;
	/**
	 * var privee
	 * @access private
	 * @var string
	*/
	var $date;
	/**
	 * var privee
	 * @access private
	 * @var string
	*/
	var $serials;
	/**
	 * var privee
	 * @access private
	 * @var array
	*/
	var $donnees;
	/**
	 * var privee
	 * @access private
	 * @var array
	*/
	var $liste_dates;
	
	//variable de travaille
	/**
	 * var privee
	 * @access private
	 * @var array
	*/
	var $algorythme;
	/**
	 * var privee
	 * @access private
	 * @var array
	*/
	var $resultat;
	/**
	 * var privee
	 * @access private
	 * @var array
	*/
	var $tableau_resultat;
	/**
	 * var privee
	 * @access private
	 * @var array
	*/
	var $donnees_calcul;

	/*********************** Creation de l'objet *********************/
	/**
	 * Instancie un objet de type algorythme.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param array $donnees Donnees extraites au format standard.
	 * @param array $liste_algos Tableau d'algorithmes a appliquer.
	 * @param dates $liste_dates Liste des dates a traiter.
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return algorythme
	 */
	static function &creer_algorythme(&$liste_option, $donnees, $liste_algos, &$liste_dates, $sort_en_erreur = false, $entete = __CLASS__) {
		$objet = new algorythme ( $donnees, $liste_algos, $liste_dates, $sort_en_erreur, $entete );
		$objet->_initialise ( array (
				"options" => $liste_option 
		) );
		
		return $objet;
	}

	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return algorythme
	 */
	public function &_initialise($liste_class) {
		parent::_initialise ( $liste_class );
		return $this;
	}

	/*********************** Creation de l'objet *********************/
	/**
	 * Creer l'objet et prepare les donnees de calcul.
	 * 
	 * @param array $donnees Donnees extraites au format standard.
	 * @param array $liste_algos Tableau d'algorithmes a appliquer.
	 * @param dates $liste_dates Liste des dates a traiter.
	*/
	function __construct($donnees, $liste_algos, $liste_dates, $sort_en_erreur = false, $entete = __CLASS__) {
		//Gestion de abstract_log
		parent::__construct ( $sort_en_erreur, $entete );
		
		$this->setDefAlgorythme ( $liste_algos );
		$this->setDonnees( $donnees );
		$this->liste_dates = $liste_dates;
		
		if (isset ( $liste_algos ["algo_dates"] ) && $liste_algos ["algo_dates"] == "oui")
			$this->date = "oui";
		else
			$this->date = "non";
		if (isset ( $liste_algos ["algo_serials"] ) && $liste_algos ["algo_serials"] == "oui")
			$this->serials = "oui";
		else
			$this->serials = "non";
		
		return true;
	}

	/**
	 * Prepare les algorithmes temporaire de calcul (durant le traitement).
	 * 
	 * @param array $algorythme Tableau d'algorithmes a appliquer.
	 * @return true
	*/
	function setAlgorythme($algorythme) {
		if (is_array ( $algorythme ))
			$this->algorythme = $algorythme;
		else
			return $this->onError ( "Il faut des algorithmes" );
		
		return true;
	}

	/**
	 * Prepare les algorithmes complet de calcul.
	 * 
	 * @param array $algorythme Tableau d'algorithmes a appliquer.
	 * @return true
	*/
	function setDefAlgorythme($algorythme) {
		if (is_array ( $algorythme ))
			$this->def_algorythme = $algorythme;
		else
			return $this->onError ( "Il faut des algorithmes" );
		
		return true;
	}

	/**
	 * Prepare les donnees completes de calcul.
	 * 
	 * @param array $donnees Tableau de donnees a traiter.
	 * @return true
	*/
	function setDonnees($donnees) {
		if ($donnees !== "")
			$this->donnees = $donnees;
		else
			return $this->onError ( "Il faut des donnees pour travailler" );
		
		return true;
	}

	/**
	 * Cree le tableau de resultat a partir d'un nom d'algorithme 
	 * et met la valeur a zero.
	 * 
	 * @param string $nom_algo Nom de l'algorithme.
	 * @return true
	*/
	function setResultat($nom_algo) {
		if (! isset ( $this->resultat [$nom_algo] ))
			$this->resultat [$nom_algo] = 0;
		elseif ($this->resultat [$nom_algo] == "")
			$this->resultat [$nom_algo] = 0;
		return true;
	}

	/**
	 * Nettoie les variables temporaires de traitement.
	 * 
	 * @return true
	*/
	function nettoie_donnees_resultat() {
		$this->algorythme = "";
		$this->resultat = "";
		$this->tableau_resultat = array ();
		$this->donnees_calcul = "";
		return true;
	}

	/**
	 * Lance les calculs.<br>
	 * Cette fonction selectionne le type de calcul : 
	 * unitaire, somme par dates, somme par serials.
	 * 
	 * @return array Tableau de resultat.
	*/
	function calcul() {
		//on nettoie les donnees de calcul avant toutes chose
		$this->nettoie_donnees_resultat ();
		
		$this->setAlgorythme ( $this->def_algorythme );
		//Si on une gestion des serials
		if ($this->serials == "oui")
			$this->calcul_serials ();
			//Puis si on est par date,
		elseif ($this->date == "oui")
			$this->calcul_dates ();
			//enfin, si on ne somme pas les serials et les dates,
		else
			$this->calcul_unitaire ();
			
			//On renvoie les donnees calculees
		return $this->tableau_resultat;
	}

	/**
	 * Ordonne et lance les calculs dans le cas d'un traitement sur plusieurs serials.<br>
	 * 
	 * @return true
	*/
	function calcul_serials() {
		//Dans l'ordre :
		//on applique les calculs unitaires
		//puis on applique les calculs dates
		//Temps que l'on trouve des algos unitaires on les appliques
		$liste_algo_tempo = array ();
		$liste_algo_restant = array ();
		$flag = true;
		foreach ( $this->algorythme as $nom_algo => $algo ) {
			if (! is_array ( $algo ))
				continue;
			if ($flag && ($algo ["type"] == "unitaire" || $algo ["type"] == "date"))
				$liste_algo_tempo [$nom_algo] = $algo;
			else {
				$flag = false;
				$liste_algo_restant [$nom_algo] = $algo;
			}
		}
		if (count ( $liste_algo_tempo ) > 0) {
			$this->setAlgorythme ( $liste_algo_tempo );
			$this->calcul_dates ();
		}
		//On concatene le tableau de donnees et le tableau de resultat pour continuer
		foreach ( $this->tableau_resultat as $serial => $liste_dates ) {
			if (isset ( $this->tableau_resultat [$serial] ["algo_date"] ) && is_array ( $this->tableau_resultat [$serial] ["algo_date"] ) && count ( $this->tableau_resultat [$serial] ["algo_date"] ) > 0) {
				$this->donnees [$serial] = array ();
				$this->donnees [$serial] ["algo_date"] = $this->tableau_resultat [$serial] ["algo_date"];
			} else
				foreach ( $liste_dates as $date => $donnees_local )
					$this->donnees [$serial] [$date] = $this->concatene_tableau ( $this->donnees [$serial] [$date], $this->tableau_resultat [$serial] [$date] );
		}
		
		//Donnees resultantes :		
		//[serial]
		//	[algo_date]
		//		[algoX]
		//ou
		//[serial]
		//	[date]
		//		[algoX]
		

		$this->setAlgorythme ( $liste_algo_restant );
		$this->donnees_calcul = $this->donnees;
		$this->tableau_resultat = array ();
		
		$liste_dates_hash = array ();
		//Pour chaque serial, on creer les donnees de "donnees_calcul" et 
		foreach ( $this->donnees as $serial => $liste_dates ) {
			//Si on a un algo_date alors on bosse dessus, sinon on bosse sur chaque date
			foreach ( $liste_dates as $date => $donnees )
				$liste_dates_hash [$date] = 1;
		}
		
		//Pour chaque algorithme,
		//on applique les algos dans l'ordre d'apparition des algos
		foreach ( $this->algorythme as $nom_algo => $algo ) {
			//on applique les algos en fonction de leur type
			if ($algo ["type"] != "serial")
				$liste_algo_restant_apres_date [$nom_algo] = $algo;
			else {
				foreach ( $liste_dates_hash as $date => $valeur ) {
					//on passe la liste de serial et on ajoute les champs communs par date ou algo_date
					$this->calcul_plusieurs_serials ( $nom_algo, $algo, $date );
					//le resultat est stocke dans [algo_serials]
					$this->tableau_resultat ["algo_serial"] [$date] [$nom_algo] = $this->resultat [$nom_algo];
					//On ajoute le resultat dans les donnees de calcul si elle sont re-utiliser
					$this->donnees_calcul ["algo_serial"] [$date] [$nom_algo] = $this->resultat [$nom_algo];
					//On re-initialise les variables
					$this->resultat = array ();
				}
			}
		}
		
		//Si il reste des algos unitaires, on les traites en derniers
		if (count ( $liste_algo_restant_apres_date ) > 0) {
			$this->setAlgorythme ( $liste_algo_restant_apres_date );
			$this->donnees = array ();
			$this->donnees ["algo_serial"] = $this->tableau_resultat ["algo_serial"];
			$this->calcul_unitaire ();
			foreach ( $this->tableau_resultat ["algo_serial"] as $date => $resultat ) {
				$this->tableau_resultat ["algo_serial"] [$date] = $this->concatene_tableau ( $this->tableau_resultat ["algo_serial"] [$date], $this->donnees ["algo_serial"] [$date] );
			}
		}
		
		//enfin on applique les sommes sur les serials
		return true;
	}

	/**
	 * Ordonne et lance les calculs dans le cas d'un traitement sur plusieurs dates.<br>
	 * 
	 * @return true
	*/
	function calcul_dates() {
		//Temps que l'on trouve des algos unitaires on les appliques
		$liste_algo_tempo = array ();
		$liste_algo_restant = array ();
		$flag = true;
		foreach ( $this->algorythme as $nom_algo => $algo ) {
			if (! is_array ( $algo ))
				continue;
			if ($flag && $algo ["type"] == "unitaire")
				$liste_algo_tempo [$nom_algo] = $algo;
			else {
				//puis des qu'on trouve un algo type "date"
				$flag = false;
				$liste_algo_restant [$nom_algo] = $algo;
			}
		}
		if (count ( $liste_algo_tempo ) > 0) {
			$this->setAlgorythme ( $liste_algo_tempo );
			$this->calcul_unitaire ();
		}
		//Pour chaque serial, on prend les donnees de "donnees_calcul" et 
		foreach ( $this->donnees as $serial => $liste_dates ) {
			$this->donnees_calcul = $liste_dates;
			$this->setAlgorythme ( $liste_algo_restant );
			//on ajoute les donnees resutat s'il y en a 
			if (count ( $this->tableau_resultat ) > 0 && isset ( $this->tableau_resultat [$serial] )) {
				foreach ( $liste_dates as $date => $valeur ) {
					$this->donnees_calcul [$date] = $this->concatene_tableau ( $this->donnees_calcul [$date], $this->tableau_resultat [$serial] [$date] );
				}
			}
			
			//puis on applique les algos dans l'ordre d'apparition des algos
			//
			//on applique les algos en fonction de leur type
			foreach ( $this->algorythme as $nom_algo => $algo ) {
				if ($algo ["type"] != "date")
					$liste_algo_restant_apres_date [$nom_algo] = $algo;
				else {
					$this->calcul_plusieurs_dates ( $nom_algo, $algo );
					$this->tableau_resultat [$serial] ["algo_date"] [$nom_algo] = $this->resultat [$nom_algo];
					//On re-initialise les variables
					$this->resultat = array ();
					//On ajoute le resultat dans les donnees de calcul si elle sont re-utiliser
					$this->donnees_calcul ["algo_date"] [$nom_algo] = $this->tableau_resultat [$serial] ["algo_date"] [$nom_algo];
				}
			}
			
			if (count ( $liste_algo_restant_apres_date ) > 0) {
				$this->setAlgorythme ( $liste_algo_restant_apres_date );
				$resultat_en_cours = $this->tableau_resultat;
				$this->donnees = array ();
				$this->donnees [$serial] ["algo_date"] = $this->tableau_resultat [$serial] ["algo_date"];
				$this->calcul_unitaire ();
				$resultat_en_cours [$serial] ["algo_date"] = $this->concatene_tableau ( $resultat_en_cours [$serial] ["algo_date"], $this->tableau_resultat [$serial] ["algo_date"] );
				$this->tableau_resultat = $resultat_en_cours;
			}
			//et on stocke le resultat dans un liste de type :
			//[serial]
			//	[algo_dates]
			//		[algoX]
		}
		
		return true;
	}

	/**
	 * Ordonne et lance les calculs dans le cas d'un traitement champ par champ.<br>
	 * 
	 * @return true
	*/
	function calcul_unitaire() {
		//Pour chaque serial et chaque date, on prend les donnees de "donnees_calcul" et on applique les algos
		//Pour chaque algorythme du type "unitaire"
		//on stock le resultat comme suit :
		//[serial]
		//	[date]
		//		[algoX] = xx
		$this->tableau_resultat = array ();
		foreach ( $this->donnees as $serial => $liste_dates ) {
			foreach ( $liste_dates as $date => $donnee_calcul ) {
				if (sizeof ( $donnee_calcul ) > 0) {
					$this->donnees_calcul = $donnee_calcul;
					foreach ( $this->algorythme as $nom_algo => $algo_en_cours ) {
						if (is_array ( $algo_en_cours ) && $algo_en_cours ["type"] == "unitaire") {
							$this->choix_calcul ( $nom_algo, $algo_en_cours );
							$this->tableau_resultat [$serial] [$date] [$nom_algo] = $this->resultat [$nom_algo];
							//On re-initialise les variables
							$this->resultat = array ();
							//On ajoute le resultat dans les donnees de calcul si elle sont re-utiliser
							$this->donnees_calcul [$nom_algo] = $this->tableau_resultat [$serial] [$date] [$nom_algo];
						}
					}
				}
			}
		}
		
		return true;
	}

	/**
	 * Applique un algorithme sur plusieurs dates.
	 * 
	 * @param string $nom_algo Nom de l'algorithme.
	 * @param string $algo Algorithme a appliquer.
	 * @return array Resultat de l'Algorithme.
	*/
	function calcul_plusieurs_dates($nom_algo, $algo) {
		//pour chaque date on applique les algos
		$liste_dates = $this->recupere_date ();
		$donnees_calcul_complete = $this->donnees_calcul;
		foreach ( $donnees_calcul_complete as $date => $liste_donnees ) {
			if (is_array ( $liste_donnees ) && count ( $liste_donnees ) > 0) {
				$this->donnees_calcul = $liste_donnees;
				$this->choix_calcul ( $nom_algo, $algo );
			}
		}
		$this->donnees_calcul = $donnees_calcul_complete;
		
		return $this->tableau_resultat;
	}

	/**
	 * Applique un algorithme sur plusieurs serials.
	 * 
	 * @param string $nom_algo Nom de l'algorithme.
	 * @param string $algo Algorithme a appliquer.
	 * @return array Resultat de l'Algorithme.
	*/
	function calcul_plusieurs_serials($nom_algo, $algo, $date) {
		$donnees_totales = $this->donnees_calcul;
		
		//Pour chaque serial
		foreach ( $donnees_totales as $serial => $donnees ) {
			if (isset ( $donnees [$date] ) && $serial != "algo_serial") {
				$this->donnees_calcul = $donnees [$date];
				$this->choix_calcul ( $nom_algo, $algo );
			}
		}
		$this->donnees_calcul = $donnees_totales;
		
		return $this->tableau_resultat;
	}

	/**
	 * Choisi le calcul en fonction de l'operateur de l'algorithme.
	 * 
	 * @param string $nom_algo Nom de l'algorithme.
	 * @param string $algo Algorithme a appliquer.
	*/
	function choix_calcul($nom_algo, $algo) {
		if (isset ( $algo ["operateur"] ) && $algo ["operateur"] !== "") {
			switch ($algo ["operateur"]) {
				case "+" :
					$this->addition ( $nom_algo, $algo ["champ"] );
					break;
				case "-" :
					$this->soustraction ( $nom_algo, $algo ["champ"] );
					break;
				case "/" :
					$this->division ( $nom_algo, $algo ["champ"] );
					break;
				case "*" :
					$this->multiplication ( $nom_algo, $algo ["champ"] );
					break;
				case "%" :
					$this->pourcentage ( $nom_algo, $algo ["champ"] );
					break;
				case "=" :
					$this->resultat [$nom_algo] = $this->recuperer_valeur ( $algo ["champ"] );
					break;
				default :
					return $this->onError ( "operateur inconnu " . $algo ["operateur"] );
			}
		} else {
			$liste_valeurs = $this->recuperer_valeur ( $algo ["champ"] );
			foreach ( $liste_valeurs as $valeur )
				$this->resultat [$nom_algo] [] .= $valeur;
		}
	}

	/**
	 * Recupere la valeur du champ sur lequel sera applique l'algorithme.
	 * 
	 * @param string $champ_algo Champ defini dans l'algorithme.
	 * @return array Valeur du ou des champs.
	*/
	function recuperer_valeur($champ_algo) {
		$tableau = array ();
		//si le champ est un chiffre, alors on renvoi le chiffre
		if (ereg ( "^[0-9]+$", $champ_algo )) {
			$tableau [0] = $champ_algo;
		} else {
			if ($this->test_champ ( $champ_algo )) {
				if (is_array ( $this->donnees_calcul [$champ_algo] )) {
					foreach ( $this->donnees_calcul [$champ_algo] as $key => $valeur ) {
						$tableau [$key] = $valeur;
					}
				} else
					$tableau [0] = $this->donnees_calcul [$champ_algo];
			} else
				$tableau [0] = 0;
		}
		
		return $tableau;
	}

	/**
	 * Accesseur en lecture<br>
	 * Renvoi la liste des dates.
	 * 
	 * @return dates Liste des dates.
	*/
	function recupere_date() {
		//pour des donnees de type $this->donnees_calcul[DATES]
		return $this->liste_dates;
	}

	/**
	 * Fait une addition.
	 * 
	 * @param string $nom_algo Nom de l'algorithme.
	 * @param string $liste_champ_algo Liste des champs a additionner.
	 * @return true
	*/
	function addition($nom_algo, $liste_champ_algo) {
		$this->setResultat ( $nom_algo );
		if (is_array ( $liste_champ_algo )) {
			foreach ( $liste_champ_algo as $champ_algo ) {
				$liste_valeurs = $this->recuperer_valeur ( $champ_algo );
				foreach ( $liste_valeurs as $valeur )
					$this->resultat [$nom_algo] += $valeur;
			}
		} else {
			$liste_valeurs = $this->recuperer_valeur ( $liste_champ_algo );
			foreach ( $liste_valeurs as $valeur )
				$this->resultat [$nom_algo] += $valeur;
		}
		return true;
	}

	/**
	 * Fait une soustraction.
	 * 
	 * @param string $nom_algo Nom de l'algorithme.
	 * @param string $liste_champ_algo Liste des champs a soustraire.
	 * @return array Valeur du ou des champs.
	*/
	function soustraction($nom_algo, $liste_champ_algo) {
		$this->setResultat ( $nom_algo );
		if (is_array ( $liste_champ_algo )) {
			foreach ( $liste_champ_algo as $champ_algo ) {
				$liste_valeurs = $this->recuperer_valeur ( $champ_algo );
				foreach ( $liste_valeurs as $valeur )
					if ($this->resultat [$nom_algo] == 0)
						$this->resultat [$nom_algo] = $valeur;
					else
						$this->resultat [$nom_algo] -= $valeur;
			}
		} else {
			$liste_valeurs = $this->recuperer_valeur ( $liste_champ_algo );
			foreach ( $liste_valeurs as $valeur )
				if ($this->resultat [$nom_algo] == 0)
					$this->resultat [$nom_algo] = $valeur;
				else
					$this->resultat [$nom_algo] -= $valeur;
		}
		return true;
	}

	/**
	 * Fait une division.
	 * 
	 * @param string $nom_algo Nom de l'algorithme.
	 * @param string $liste_champ_algo Liste des champs a diviser.
	 * @return true
	*/
	function division($nom_algo, $liste_champ_algo) {
		$CODE_RETOUR = true;
		$this->setResultat ( $nom_algo );
		if (is_array ( $liste_champ_algo ) && count ( $liste_champ_algo ) == 2) {
			$calcul = 0;
			$resultat_tampon = 0;
			foreach ( $liste_champ_algo as $champ_algo ) {
				$liste_valeurs [$champ_algo] = $this->recuperer_valeur ( $champ_algo );
				foreach ( $liste_valeurs [$champ_algo] as $key => $valeur )
					$resultat_tampon += $valeur;
				if ($calcul == 0)
					$numerateur = $resultat_tampon;
				else
					$denominateur = $resultat_tampon;
				$calcul ++;
				$resultat_tampon = 0;
			}
			if ($calcul == 2 && $denominateur > 0)
				$this->resultat [$nom_algo] = $numerateur / $denominateur;
			elseif ($denominateur == 0)
				$CODE_RETOUR = FALSE;
		} else
			return $this->onError ( "erreur dans la liste des champs de la division" );
		return $CODE_RETOUR;
	}

	/**
	 * Fait une multiplication.
	 * 
	 * @param string $nom_algo Nom de l'algorithme.
	 * @param string $liste_champ_algo Liste des champs a multiplier.
	 * @return true
	*/
	function multiplication($nom_algo, $liste_champ_algo) {
		$this->setResultat ( $nom_algo );
		$valeurs = array ();
		if (is_array ( $liste_champ_algo )) {
			foreach ( $liste_champ_algo as $champ_algo ) {
				$liste_valeurs [$champ_algo] = $this->recuperer_valeur ( $champ_algo );
				foreach ( $liste_valeurs [$champ_algo] as $key => $valeur )
					$resultat_tampon += $valeur;
				$valeurs [] .= $resultat_tampon;
				$resultat_tampon = 0;
			}
			$this->resultat [$nom_algo] = 1;
			foreach ( $valeurs as $valeur )
				$this->resultat [$nom_algo] *= $valeur;
		} else {
			$liste_valeurs = $this->recuperer_valeur ( $liste_champ_algo );
			foreach ( $liste_valeurs as $valeur )
				$valeur_tempo += $valeur;
			$this->resultat [$nom_algo] = $valeur_tempo * $valeur_tempo;
		}
		return true;
	}

	/**
	 * Fait un pourcentage.
	 * 
	 * @param string $nom_algo Nom de l'algorithme.
	 * @param string $liste_champ_algo Liste des champs a traiter.
	 * @return true
	*/
	function pourcentage($nom_algo, $liste_champ_algo) {
		if ($this->division ( $nom_algo, $liste_champ_algo ))
			$this->resultat [$nom_algo] = $this->resultat [$nom_algo] * 100;
		else
			$this->resultat [$nom_algo] = 0;
		return true;
	}

	/**
	 * Teste la presence d'un champ dans les donnees a culculer.
	 * 
	 * @param string $champ Champ a tester.
	 * @return Bool TREU si existe, FALSE sinon.
	*/
	function test_champ($champ) {
		if (! isset ( $this->donnees_calcul [$champ] ))
			$CODE_RETOUR = false;
		elseif ($this->donnees_calcul [$champ] == "")
			$CODE_RETOUR = false;
		else
			$CODE_RETOUR = true;
		return $CODE_RETOUR;
	}

	/**
	 * Concatene deux tableaux.
	 *
	 * @param array $tableau1 Tableau quelconque.
	 * @param array $tableau2 Tableau quelconque.
	 * @return array Renvoi les tableaux concatenes.
	*/
	function concatene_tableau($tableau1, $tableau2) {
		foreach ( $tableau2 as $key => $valeur ) {
			$tableau1 [$key] = $valeur;
		}
		return $tableau1;
	}

	/**
	 * Accesseur en lecture<br>
	 * Renvoi les donnees contenues dans l'objet.
	 * 
	 * @return array Donnees standard extraites.
	*/
	function renvoyer_donnees() {
		return $this->donnees;
	}
}
?>
