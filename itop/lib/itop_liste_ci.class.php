<?php
/**
 * Gestion de itop.
 * @author dvargas
 */
use Zorille\framework\abstract_log as abstract_log;
use Zorille\itop as itop;
use \Exception as Exception;
/**
 * class itop_liste_ci
 *
 * @package iTop
 * @subpackage liste_ci
 */
class itop_liste_ci extends abstract_log {
	/**
	 * var privee
	 *
	 * @access private
	 * @var array
	 */
	private $liste_ci = array ();

	/**
	 * ********************* Creation de l'objet ********************
	 */
	/**
	 * Instancie un objet de type itop_liste_ci. @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet gestion_connexion_url
	 * @return itop_liste_ci
	 */
	static function &creer_itop_liste_ci(&$liste_option, $sort_en_erreur = false, $entete = __CLASS__) {
		abstract_log::onDebug_standard ( __METHOD__, 1 );
		$objet = new itop_liste_ci ( $sort_en_erreur, $entete );
		$objet ->_initialise ( array ( 
				"options" => $liste_option ) );
		
		return $objet;
	}

	/**
	 * Initialisation de l'objet @codeCoverageIgnore
	 * @param array $liste_class
	 * @return itop_liste_ci
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
	public function __construct($sort_en_erreur = false, $entete = __CLASS__) {
		// Gestion de serveur_datas
		parent::__construct ( $sort_en_erreur, $entete );
	}

	/************************************ Gestion de la liste CI par objet PHP **********************************/
	/**
	 * Ajoute un ci a la liste
	 * @param itop_ci $ci
	 * @return itop_liste_ci
	 * @throws Exception
	 */
	public function ajoute_ci(&$ci) {
		if (! $ci instanceof itop_ci) {
			return $this ->onError ( '$ci doit etre une instance de itop_ci' );
		}
		
		$liste_ci = $this ->getListeCi ();
		$liste_ci [$ci ->getFormat () . "::" . $ci ->getId ()] = $ci;
		
		if (isset ( $ci ->getDonnees ()["friendlyname"] )) {
			$liste_ci [$ci ->getFormat () . "::" . $ci ->getDonnees ()["friendlyname"]] = &$liste_ci [$ci ->getFormat () . "::" . $ci ->getId ()];
		} elseif (! isset ( $ci ->getDonnees ()["name"] )) {
			return $this ->onError ( "Pas de Name pour le ci de type " . $ci ->getFormat (), $ci ->getOqlCi () );
		} else {
			$liste_ci [$ci ->getFormat () . "::" . $ci ->getDonnees ()["name"]] = &$liste_ci [$ci ->getFormat () . "::" . $ci ->getId ()];
		}
		return $this ->setListeCi ( $liste_ci );
	}

	public function valide_ci_existe($ci) {
		if (! $ci instanceof itop_ci) {
			return $this ->onError ( '$ci doit etre une instance de itop_ci' );
		}
		
		return isset ( $this ->getListeCi ()[$ci ->getFormat () . "::" . $ci ->getId ()] );
	}

	public function renvoie_ci($ci) {
		if ($this ->valide_ci_existe ( $ci )) {
			return $this ->getListeCi ()[$ci ->getFormat () . "::" . $ci ->getId ()];
		}
		
		return null;
	}

	public function retrouve_ci_par_nom($nom_ci, $type_ci) {
		if (isset ( $this ->getListeCi ()[$type_ci . "::" . $nom_ci] )) {
			return $this ->getListeCi ()[$type_ci . "::" . $nom_ci];
		}
		// 		foreach ( $this ->getListeCi () as $type => $ci ) {
		// 			if (strpos ( $type, $type_ci ) === 0) {
		// 				if ($ci ->getDonnees ()['name'] == $nom_ci || (isset ( $ci ->getDonnees ()['friendlyname'] ) && $ci ->getDonnees ()['friendlyname'] == $nom_ci)) {
		// 					return $ci;
		// 				}
		// 			}
		// 		}
		

		return NULL;
	}
	/************************************ Gestion de la liste CI par objet PHP **********************************/

	
	/************************************ Gestion de la liste CI par requete OQL **********************************/
	public function recupere_VirtualMachine(&$itop_webservice, $liste_champ = "name,managementip,business_criticity,move2production,osfamily_name,osversion_name,cpu,ram") {
		$donnees_par_machine = $this ->getListeCi ();
		$this ->onInfo ( "VirtualMachine" );
		$liste_cis = $itop_webservice ->core_get ( 'VirtualMachine', 'SELECT VirtualMachine', $liste_champ );
		foreach ( $liste_cis ['objects'] as $ci ) {
			if (! isset ( $donnees_par_machine [$ci ['fields'] ['name']] )) {
				$donnees_par_machine [$ci ['fields'] ['name']] = array ();
			}
			$ci ['fields'] ['class'] = $ci ['class'];
			$donnees_par_machine [$ci ['fields'] ['name']] [count ( $donnees_par_machine [$ci ['fields'] ['name']] )] = $ci ['fields'];
		}
		
		return $this ->setListeCi ( $donnees_par_machine );
	}

	public function recupere_Server(&$itop_webservice, $liste_champ = "name,managementip,business_criticity,move2production,osfamily_name,osversion_name,cpu,ram") {
		$donnees_par_machine = $this ->getListeCi ();
		$this ->onInfo ( "Server" );
		$liste_cis = $itop_webservice ->core_get ( 'Server', 'SELECT Server', $liste_champ );
		foreach ( $liste_cis ['objects'] as $ci ) {
			if (! isset ( $donnees_par_machine [$ci ['fields'] ['name']] )) {
				$donnees_par_machine [$ci ['fields'] ['name']] = array ();
			}
			$ci ['fields'] ['class'] = $ci ['class'];
			$donnees_par_machine [$ci ['fields'] ['name']] [count ( $donnees_par_machine [$ci ['fields'] ['name']] )] = $ci ['fields'];
		}
		
		return $this ->setListeCi ( $donnees_par_machine );
	}

	public function recupere_Middleware(&$itop_webservice, $liste_champ = "name,friendlyname,software_id_friendlyname,system_name") {
		$donnees_par_machine = $this ->getListeCi ();
		$this ->onInfo ( "Middleware" );
		$liste_cis = $itop_webservice ->core_get ( 'Middleware', 'SELECT Middleware', $liste_champ );
		foreach ( $liste_cis ['objects'] as $ci ) {
			if (! isset ( $donnees_par_machine [$ci ['fields'] ['system_name']] )) {
				$donnees_par_machine [$ci ['fields'] ['system_name']] = array ();
			}
			$ci ['fields'] ['class'] = $ci ['class'];
			$donnees_par_machine [$ci ['fields'] ['system_name']] [count ( $donnees_par_machine [$ci ['fields'] ['system_name']] )] = $ci ['fields'];
		}
		
		return $this ->setListeCi ( $donnees_par_machine );
	}

	public function recupere_MiddlewareInstance(&$itop_webservice, $liste_champ = "name,friendlyname,middleware_id_friendlyname") {
		$donnees_par_machine = $this ->getListeCi ();
		$this ->onInfo ( "MiddlewareInstance" );
		$liste_cis = $itop_webservice ->core_get ( 'MiddlewareInstance', 'SELECT MiddlewareInstance', $liste_champ );
		foreach ( $liste_cis ['objects'] as $ci ) {
			$donnees = explode ( " ", $ci ['fields'] ['middleware_id_friendlyname'] );
			$system_name = array_pop ( $donnees );
			if (! isset ( $donnees_par_machine [$system_name] )) {
				$donnees_par_machine [$system_name] = array ();
			}
			$ci ['fields'] ['class'] = $ci ['class'];
			$donnees_par_machine [$system_name] [count ( $donnees_par_machine [$system_name] )] = $ci ['fields'];
		}
		
		return $this ->setListeCi ( $donnees_par_machine );
	}

	public function recupere_PCSoftware(&$itop_webservice, $liste_champ = "name,friendlyname,software_id_friendlyname,system_name") {
		$donnees_par_machine = $this ->getListeCi ();
		$this ->onInfo ( "PCSoftware" );
		$liste_cis = $itop_webservice ->core_get ( 'PCSoftware', 'SELECT PCSoftware', $liste_champ );
		if (isset ( $liste_cis ['objects'] )) {
			foreach ( $liste_cis ['objects'] as $ci ) {
				if (! isset ( $donnees_par_machine [$ci ['fields'] ['system_name']] )) {
					$donnees_par_machine [$ci ['fields'] ['system_name']] = array ();
				}
				$ci ['fields'] ['class'] = $ci ['class'];
				$donnees_par_machine [$ci ['fields'] ['system_name']] [count ( $donnees_par_machine [$ci ['fields'] ['system_name']] )] = $ci ['fields'];
			}
		}
		
		return $this ->setListeCi ( $donnees_par_machine );
	}

	public function recupere_OtherSoftware(&$itop_webservice, $liste_champ = "name,friendlyname,software_id_friendlyname,system_name") {
		$donnees_par_machine = $this ->getListeCi ();
		$this ->onInfo ( "OtherSoftware" );
		$liste_cis = $itop_webservice ->core_get ( 'OtherSoftware', 'SELECT OtherSoftware', $liste_champ );
		foreach ( $liste_cis ['objects'] as $ci ) {
			if (! isset ( $donnees_par_machine [$ci ['fields'] ['system_name']] )) {
				$donnees_par_machine [$ci ['fields'] ['system_name']] = array ();
			}
			$ci ['fields'] ['class'] = $ci ['class'];
			$donnees_par_machine [$ci ['fields'] ['system_name']] [count ( $donnees_par_machine [$ci ['fields'] ['system_name']] )] = $ci ['fields'];
		}
		
		return $this ->setListeCi ( $donnees_par_machine );
	}

	public function recupere_WebServer(&$itop_webservice, $liste_champ = "name,friendlyname,software_id_friendlyname,system_name") {
		$donnees_par_machine = $this ->getListeCi ();
		$this ->onInfo ( "WebServer" );
		$liste_cis = $itop_webservice ->core_get ( 'WebServer', 'SELECT WebServer', $liste_champ );
		foreach ( $liste_cis ['objects'] as $ci ) {
			if (! isset ( $donnees_par_machine [$ci ['fields'] ['system_name']] )) {
				$donnees_par_machine [$ci ['fields'] ['system_name']] = array ();
			}
			$ci ['fields'] ['class'] = $ci ['class'];
			$donnees_par_machine [$ci ['fields'] ['system_name']] [count ( $donnees_par_machine [$ci ['fields'] ['system_name']] )] = $ci ['fields'];
		}
		
		return $this ->setListeCi ( $donnees_par_machine );
	}
	
	public function recupere_WebApplication(&$itop_webservice, $liste_champ = "name") {
		$donnees_par_machine = $this ->getListeCi ();
		$this ->onInfo ( "WebApplication" );
		$liste_cis = $itop_webservice ->core_get ( 'WebApplication', 'SELECT WebApplication', $liste_champ );
		foreach ( $liste_cis ['objects'] as $ci ) {
			$datas=explode(" ",$ci['fields']["name"]);
			$last=array_pop($datas);
			if (! isset ( $donnees_par_machine [$last] )) {
				$donnees_par_machine [$last] = array ();
			}
			$ci ['fields'] ['class'] = $ci ['class'];
			$donnees_par_machine [$last] [count ( $donnees_par_machine [$last] )] = $ci ['fields'];
		}
	
		return $this ->setListeCi ( $donnees_par_machine );
	}

	public function recupere_IPInterface(&$itop_webservice, $liste_champ = "name,friendlyname,ipaddress") {
		$donnees_par_machine = $this ->getListeCi ();
		$this ->onInfo ( "IPInterface" );
		$liste_cis = $itop_webservice ->core_get ( 'IPInterface', 'SELECT IPInterface', $liste_champ );
		foreach ( $liste_cis ['objects'] as $ci ) {
			$donnees = explode ( " ", $ci ['fields'] ['friendlyname'] );
			$system_name = array_pop ( $donnees );
			if (! isset ( $donnees_par_machine [$system_name] )) {
				$donnees_par_machine [$system_name] = array ();
			}
			$ci ['fields'] ['class'] = $ci ['class'];
			$donnees_par_machine [$system_name] [count ( $donnees_par_machine [$system_name] )] = $ci ['fields'];
		}
		
		return $this ->setListeCi ( $donnees_par_machine );
	}
	/************************************ Gestion de la liste CI par requete OQL **********************************/

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * @codeCoverageIgnore
	 */
	public function getListeCi() {
		return $this->liste_ci;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getCiParNom($ci_name, $type_ci) {
		return $this ->retrouve_ci_par_nom ( $ci_name, $type_ci );
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setListeCi($liste_ci) {
		$this->liste_ci = $liste_ci;
		
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
		$help [__CLASS__] ["text"] [] .= "itop_liste_ci :";
		
		return $help;
	}
}
?>
