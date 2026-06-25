<?php
/**
 * @author dvargas
 */
use Zorille\framework\abstract_log;
use Zorille\framework\options;
use Zorille\evobserve;

/**
 * class evobserve_standard
 *
 * @package Euclyde
 * @subpackage evobserve_standard
 */
class evobserve_standard extends abstract_log {
	/**
	 * @access private
	 * @var manage_modeles_evobserve
	 */
	private $modeles_evobserve = null;
	/**
	 * var privee
	 *
	 * @access private
	 * @var evobserve\wsclient
	 */
	private $wsclient = null;

	/**
	 * @access private
	 * @var string
	 */
	private $category = "";
	/**
	 * @access private
	 * @var evobserve\Boxes
	 */
	private $objboxe = null;

	/**
	 * ********************* Creation de l'objet ********************
	 */
	/**
	 * Instancie un objet de type VMware\liste_ci.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param gestion_client $gestion_client
	 * @param evobserve\wsclient $evobserve_webservice
	 * @param Boolean|string $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l'objet
	 * @return evobserve_standard
	 * @throws Exception
	 */
	static function &creer_evobserve_standard(
		options           &$liste_option,
		evobserve\wsclient &$evobserve_webservice,
		bool|string       $sort_en_erreur = false,
		string            $entete = __CLASS__): evobserve_standard
	{
		abstract_log::onDebug_standard ( __METHOD__, 1 );
		$objet = new evobserve_standard ( $sort_en_erreur, $entete );
		return $objet->_initialise ( array (
				"options" => $liste_option,
				"evobserve:wsclient" => $evobserve_webservice
		) );
	}

	/**
	 * Initialisation de l'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return self
	 * @throws Exception
	 */
	public function &_initialise(
        array $liste_class): static {
		parent::_initialise ( $liste_class );
		$this->setObjetEvobserveWsclient ( $liste_class ['evobserve:wsclient'] )
			->setObjModelesEvobserve ( manage_modeles_evobserve::creer_manage_modeles_evobserve ( $liste_class ['options'], $liste_class ['evobserve:wsclient'] ) )
			->setObjBoxes ( evobserve\Boxes::creer_Boxes ( $liste_class ['options'], $liste_class ['evobserve:wsclient'] ) );
		return $this;
	}

	/**
	 * ********************* Creation de l'objet ********************
	 */
	/**
	 * Constructeur
	 * @codeCoverageIgnore
	 * @param string $sort_en_erreur Sort en erreur.
	 * @param string $nom_module Nom du module.
	 */
	public function __construct(
		$sort_en_erreur = "non",
		string $nom_module = __CLASS__) {
		parent::__construct ( $sort_en_erreur, $nom_module );
	}

	/**
	 * Extrait des parametres d'un liste d'option
	 * @codeCoverageIgnore
	 * @param array|string $chemin_option
	 * @return boolean string array
	 * @throws Exception
	 */
	protected function _valideOption(
		array|string $chemin_option,
		             $valeur_defaut = false): mixed
	{
		$this->onDebug ( __METHOD__, 1 );
		// Si je n'ai pas de valeur par defaut, je verifie la presence de la variable
		if ($valeur_defaut === false && $this->getListeOptions ()
			->verifie_variable_standard ( $chemin_option ) === false) {
			if (is_array ( $chemin_option )) {
				$chemin_option = implode ( "_", $chemin_option );
			}
			return $this->onError ( "Il manque le parametre : " . $chemin_option );
		}
		// On revoi la valeur de la variable
		$datas = $this->getListeOptions ()
			->renvoi_variables_standard ( $chemin_option, $valeur_defaut );
		if (is_array ( $datas ) && isset ( $datas ["#comment"] )) {
			unset ( $datas ["#comment"] );
		}
		return $datas;
	}

	/**
	 * Retrouve le nom du collector/boxe
	 * @param array $ci
	 * @return string
	 * @throws Exception
	 */
	public function recupere_collector(
		array  $ci): string {
			$collector='My collector';
		/* Function to return Collector name */
		return trim ( $collector );
	}

	/**
	 * @param array $ci
	 * @param array $tag_ids
	 * @param string $codeclient
	 * @return string
	 * @exception
	 * @throws Exception
	 */
	public function recupere_site_evobserve(
		array $ci,
		array  &$tag_ids): string {
		
		$site=''; //creer une fonction pour retrouver le site
		$tags = evobserve\Tags::creer_Tags ( $this->getListeOptions (), $this->getObjetEvobserveWsclient () );
		$tag_site = $tags->retrouve_id_tag ( $ci['name'] );
		if (! in_array ( $tag_site, $tag_ids )) {
			$tag_ids [] = $tag_site;
		}
		$this->onDebug ( "Site : " . $site, 1 );
		return $site;
	}

    /**
     * @param evobserve\Company|array $companies
     * @param string $nom
     * @return int|false
     * @throws Exception
     */
	public function recupere_site_evobserve_par_nom(
			evobserve\Company|array &$companies,
			string                 $nom): bool|int {
		$this->onDebug ( __METHOD__, 1 );
		$site = "";
		$companies->recupere_company_tree ( array (
				'id' => $companies->getId ()
		) );
		$liste_tags = []; //Retrouve le nom du site grace au differents tags
		foreach ( $liste_tags as $tag ) {
			//$site=nom du site
		}
		foreach ( $companies->getCompanies () as $company ) {
			if ($company->name == $site) {
				return $company->id;
			}
		}
		return $this->onError ( "Pas de site trouve pour " . $site, "", 1 );
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */

	/**
	 * @codeCoverageIgnore
	 * @return manage_modeles_evobserve|null
	 */
	public function &getObjModelesEvobserve(): ?manage_modeles_evobserve {
		return $this->modeles_evobserve;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setObjModelesEvobserve(
			$ObjModelesEvobserve): static {
		$this->modeles_evobserve = $ObjModelesEvobserve;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 * @return evobserve\Boxes|null
	 */
	public function &getObjBoxes(): ?evobserve\Boxes {
		return $this->objboxe;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setObjBoxes(
			$objboxe): static {
		$this->objboxe = $objboxe;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 * @return evobserve\wsclient|null
	 */
	public function &getObjetEvobserveWsclient(): ?evobserve\wsclient {
		return $this->wsclient;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setObjetEvobserveWsclient(
			&$wsclient): static {
		$this->wsclient = $wsclient;
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getCategory(): string {
		return $this->category;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function &setCategory(
			$category): static {
		$this->category = $category;
		return $this;
	}

	/**
	 * ***************************** ACCESSEURS *******************************
	 */
	/**
	 * Affiche le help.<br> @codeCoverageIgnore
	 */
	static public function help(): array|string
	{
		$help = parent::help ();
		$help [__CLASS__] ["text"] = array ();
		$help [__CLASS__] ["text"] [] .= "evobserve_standard :";
		return $help;
	}
}