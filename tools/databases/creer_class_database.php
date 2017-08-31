#!/usr/bin/php
<?php
/**
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package Tools
 * @subpackage databases
 */

// Deplacement pour joindre le repertoire lib
$deplacement = "/../../..";

if (isset ( $_SERVER ) && isset ( $_SERVER ["SCRIPT_FILENAME"] )) {
	$rep_document = dirname ( $_SERVER ["SCRIPT_FILENAME"] ) . $deplacement;
	$liste_variables_systeme = array (
			"conf" => array (
					$rep_document . "/conf_clients/database/xxxx.xml" 
			) 
	);
} else {
	$rep_document = dirname ( $argv [0] ) . $deplacement;
}

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
	$help [$fichier] ["text"] [] .= "Permet de creer les class necessaire pour une database.";
	$help [$fichier] ["text"] [] .= "\t--nom_db Nom de la base de donnees";
	$help [$fichier] ["text"] [] .= "\t--desc_db oui/non creer le fichier desc_db.class.php Par defaut : oui";
	$help [$fichier] ["text"] [] .= "\t--complexe_db oui/non creer le fichier requetes_complexe_db.class.php Par defaut : oui";
	$help [$fichier] ["text"] [] .= "\t--dossier_class Dossier de rangement des nouvelles class";
	
	$class_utilisees = array (
			"fichier" 
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
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
 * @param options &$liste_option
 * @param logs &$fichier_log
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	if ($liste_option->verifie_option_existe ( "nom_db", true ) === false) {
		return abstract_log::onError_standard ( "Il faut un --nom_db" );
	}
	if ($liste_option->verifie_option_existe ( "desc_db", true ) === false) {
		$liste_option->setOption ( "desc_db", "oui" );
	}
	if ($liste_option->verifie_option_existe ( "complexe_db", true ) === false) {
		$liste_option->setOption ( "complexe_db", "oui" );
	}
	if ($liste_option->verifie_option_existe ( "dossier_class", true ) === false) {
		$liste_option->setOption ( "dossier_class", "/tmp" );
	}
	$setTable = '		$this->setTable("TABLE","TABLE");';
	$set__chargeChamps = '		$this->_chargeChamps_TABLE();';
	
	// Les connexions aux bases de donnees
	try {
		$connexion = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
	} catch ( Exception $e ) {
		return abstract_log::onError_standard ( "Connexion en erreur : ", $e->getMessage (), $e->getCode () );
	}
	foreach ( $connexion as $nom_db => $data ) {
		switch ($nom_db) {
			case $liste_option->getOption ( "nom_db" ) . "_prod" :
			case $liste_option->getOption ( "nom_db" ) . "_preprod" :
			case $liste_option->getOption ( "nom_db" ) . "_dev" :
				$db = $data;
				break;
			default :
				return abstract_log::onError_standard ( "pas de connexion db valide" );
		}
	}
	
	if ($liste_option->getOption ( "desc_db" ) == "oui") {
		// affichage de l'ajout dans la class creer_connexion_liste_option
		$nom_fichier_desc = $liste_option->getOption ( "dossier_class" ) . "/desc_bd_" . $liste_option->getOption ( "nom_db" ) . ".class.php";
		abstract_log::onInfo_standard ( "Nom du fichier desc : " . $nom_fichier_desc );
		fichier::supprime_fichier ( $nom_fichier_desc );
		$fichier_desc = fichier::creer_fichier ( $liste_option, $nom_fichier_desc, "oui" );
		$fichier_desc->ouvrir ( "w" );
		
		$entete = '<?php
/**
 * @author dvargas
 * @package Lib
 *
 */
/**
 * class gestion_bd_<Nom de la base>
 *
 * Gere la connexion a une base.
 * @package Lib
 * @subpackage SQL-dbconnue
 */
class desc_bd_<Nom de la base> extends gestion_definition_table
{
	/**
	 * Cree objet, prepare la valeur du sort_en_erreur et entete des logs.
	 *
	 * @param string $entete Entete a afficher dans les logs.
	 * @param string|bool $sort_en_erreur Prend les valeurs oui/non ou true/false.
	 */
	public function __construct($sort_en_erreur="oui",$entete="BD <Nom de la base>")
	{
		//Gestion de abstract_log
		parent::__construct($sort_en_erreur,$entete);

		$this->_chargeTable();
		$this->_chargeChamps();
	}

	private function _chargeTable(){
<Liste tables>

	}

	private function _chargeChamps(){
<Liste charge champs tables>

		return true;
	}

	';
		
		$entete = str_replace ( "<Nom de la base>", $liste_option->getOption ( "nom_db" ), $entete );
		$liste_tables = $db->faire_requete ( "show tables;" );
		if ($liste_tables === false) {
			abstract_log::onError_standard ( "Requete en erreur" );
			return false;
		}
		$liste_table = "";
		$liste__chargeTable = "";
		$tableau_fonctions__chargeTable = array ();
		foreach ( $liste_tables as $row ) {
			$tableau_fonctions__chargeTable [] .= str_replace ( " ", "_", $row [0] );
			
			$setTable_local = str_replace ( "TABLE", $row [0], $setTable );
			$liste_table .= $setTable_local . "\n";
			
			$_chargeChamps_local = str_replace ( "TABLE", str_replace ( " ", "_", $row [0] ), $set__chargeChamps );
			$liste__chargeTable .= $_chargeChamps_local . "\n";
		}
		$entete = str_replace ( "<Liste tables>", $liste_table, $entete );
		$entete = str_replace ( "<Liste charge champs tables>", $liste__chargeTable, $entete );
		
		$fichier_desc->ecrit ( $entete );
		// On gere toutes les fonctions de definition des champs
		foreach ( $tableau_fonctions__chargeTable as $table ) {
			$function = '
	private function _chargeChamps_' . $table . '(){
';
			$liste_champs = $db->faire_requete ( "desc " . $table . ";" );
			if ($liste_champs === false) {
				abstract_log::onError_standard ( "Requete en erreur" );
				return false;
			}
			foreach ( $liste_champs as $row ) {
				abstract_log::onDebug_standard ( $row, 2 );
				$pos = strpos ( $row ['Type'], "(" );
				if ($pos !== false) {
					$type_local = substr ( $row ['Type'], 0, $pos );
				} else {
					$type_local = $row ['Type'];
				}
				switch ($type_local) {
					case "int" :
					case "tinyint" :
					case "smallint" :
					case "bigint" :
					case "mediumint" :
					case "timestamp" :
					case "decimal" :
					case "float" :
					case "double" :
						$type_perso = "numeric";
						break;
					case "datetime" :
					case "date" :
					case "time" :
						$type_perso = "date";
						break;
					default :
						$type_perso = "text";
				}
				
				$function .= '		$this->setChamp ( "' . $row ['Field'] . '", "' . $row ['Field'] . '", "' . $table . '", "' . $type_perso . '" );' . "\n";
			}
			$function .= '		
		return true;
	}

';
			$fichier_desc->ecrit ( $function );
		}
		
		$pieddepage = '
			
	/**
	 * @static
	 *
	 * @param string $echo Affiche le help
	 * @return string Renvoi le help
	 */
	static function help()
	{
		$help = parent::help ();
		
		$help [__CLASS__] ["text"] = array ();
		$help [__CLASS__] ["text"][].="Descripion de la base <Nom de la base>";

		return $help;
	}
}
?>';
		$pieddepage = str_replace ( "<Nom de la base>", $liste_option->getOption ( "nom_db" ), $pieddepage );
		
		$fichier_desc->ecrit ( $pieddepage );
		$fichier_desc->close ();
	}
	
	if ($liste_option->getOption ( "complexe_db" ) == "oui") {
		$nom_fichier_comp = $liste_option->getOption ( "dossier_class" ) . "/requete_complexe_" . $liste_option->getOption ( "nom_db" ) . ".class.php";
		abstract_log::onInfo_standard ( "Nom du fichier requete compexe : " . $nom_fichier_comp );
		fichier::supprime_fichier ( $nom_fichier_comp );
		$fichier_comp = fichier::creer_fichier ( $liste_option, $nom_fichier_comp, "oui" );
		$fichier_comp->ouvrir ( "w" );
		$code_complexe = '<?php
/**
 * @author dvargas
 * @package Lib
 *
 */
/**
 * class requete_complexe_' . $liste_option->getOption ( "nom_db" ) . '<br>
 * Gere la connexion a une base ' . $liste_option->getOption ( "nom_db" ) . '.
 * @package Lib
 * @subpackage SQL-dbconnue
 */

class requete_complexe_' . $liste_option->getOption ( "nom_db" ) . ' extends desc_bd_' . $liste_option->getOption ( "nom_db" ) . ' {
    /*********************** Creation de l\'objet *********************/
	/**
	 * Instancie un objet de type requete_complexe_' . $liste_option->getOption ( "nom_db" ) . '.
	 * @codeCoverageIgnore
	 * @param options $liste_option Reference sur un objet options
	 * @param string|Boolean $sort_en_erreur Prend les valeurs oui/non ou true/false
	 * @param string $entete Entete des logs de l\'objet
	 * @return requete_complexe_' . $liste_option->getOption ( "nom_db" ) . '
	 */
	static function &creer_requete_complexe_' . $liste_option->getOption ( "nom_db" ) . '(&$liste_option, $sort_en_erreur = false, $entete = __CLASS__) {
		$objet = new requete_complexe_' . $liste_option->getOption ( "nom_db" ) . ' ( $sort_en_erreur, $entete );
		$objet->_initialise ( array (
				"options" => $liste_option
		) );
	
		return $objet;
	}
	
	/**
	 * Initialisation de l\'objet
	 * @codeCoverageIgnore
	 * @param array $liste_class
	 * @return requete_complexe_' . $liste_option->getOption ( "nom_db" ) . '
	 */
	public function &_initialise($liste_class) {
		parent::_initialise ( $liste_class );
		return $this;
	}
	
	/*********************** Creation de l\'objet *********************/
}
?>';
		$fichier_comp->ecrit ( $code_complexe );
		$fichier_comp->close ();
	}
	
	abstract_log::onInfo_standard ( "Ajouter les lignes suivantes dans le fichier fonctions_standards_sgbd.class.php (fonction creer_connexion_liste_option):" );
	echo '
		case "' . $liste_option->getOption ( "nom_db" ) . '_prod" :
		case "' . $liste_option->getOption ( "nom_db" ) . '_preprod" :
		case "' . $liste_option->getOption ( "nom_db" ) . '_dev" :
		$CODE_RETOUR [$nom_base] = requete_complexe_' . $liste_option->getOption ( "nom_db" ) . '::creer_requete_complexe_' . $liste_option->getOption ( "nom_db" ) . ' ( $this->getListeOptions, $liste_variables ["sort_en_erreur"] );
		break;' . "\n";
	
	abstract_log::onInfo_standard ( "Ajouter la fonction suivante dans la class fonctions_standards_sgbd :" );
	echo '
	/**
	 * Set le $db_' . $liste_option->getOption ( "nom_db" ) . ' avec la base ' . $liste_option->getOption ( "nom_db" ) . ' standard.
	 *
	 * @param array $connexion        	
	 * @param bool $sort_en_erreur        	
	 * @return requete_complexe_' . $liste_option->getOption ( "nom_db" ) . ' false renvoi l\'objet requete_complexe_' . $liste_option->getOption ( "nom_db" ) . ', false en cas d\'erreur.
	 */
	static public function recupere_db_' . $liste_option->getOption ( "nom_db" ) . '(&$connexion, $sort_en_erreur = false) {
		if ($connexion && isset ( $connexion ["' . $liste_option->getOption ( "nom_db" ) . '_prod"] )) {
			$db_' . $liste_option->getOption ( "nom_db" ) . ' = $connexion ["' . $liste_option->getOption ( "nom_db" ) . '_prod"];
		} elseif ($connexion && isset ( $connexion ["' . $liste_option->getOption ( "nom_db" ) . '_preprod"] )) {
			$db_' . $liste_option->getOption ( "nom_db" ) . ' = $connexion ["' . $liste_option->getOption ( "nom_db" ) . '_preprod"];
		} elseif ($connexion && isset ( $connexion ["' . $liste_option->getOption ( "nom_db" ) . '_dev"] )) {
			$db_' . $liste_option->getOption ( "nom_db" ) . ' = $connexion ["' . $liste_option->getOption ( "nom_db" ) . '_dev"];
		} elseif ($sort_en_erreur) {
			abstract_log::onError_standard ( "Il n\'y a pas de connexion a la base ' . $liste_option->getOption ( "nom_db" ) . '.", "" ,3004);
			$db_' . $liste_option->getOption ( "nom_db" ) . ' = false;
		} else {
			$db_' . $liste_option->getOption ( "nom_db" ) . ' = false;
		}
		
		return $db_' . $liste_option->getOption ( "nom_db" ) . ';
	}' . "\n";
	
	abstract_log::onInfo_standard ( "Enfin ajouter les lignes suivantes dans config_sgbd :" );
	echo '
	require_once "desc_' . $liste_option->getOption ( "nom_db" ) . '.class.php";
	require_once "requete_complexe_' . $liste_option->getOption ( "nom_db" ) . '.class.php";
			' . "\n";
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
