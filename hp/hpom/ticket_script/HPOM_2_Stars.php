<?php
/**
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage HPOM
 */

// Deplacement pour joindre le repertoire lib
$deplacement = "/../../../..";
$rep_document = dirname ( $argv [0] ) . $deplacement;

//On reconstruit la liste des arguments au format "Framework PHP"
//$msgID=$ARGV[0];


if (count ( $argv ) < 5) {
	$new_argv = array (
			$argv [0] 
	);
	$new_argv [] .= '--msgID';
	$new_argv [] .= $argv [1];
	
	if (isset ( $argv [2] ) && $argv [2] == "\t--verbose") {
		$new_argv [] .= '--verbose';
		if (isset ( $argv [3] )) {
			$new_argv [] .= $argv [3];
		}
	}
	
	$new_argv [] .= '--conf';
	$new_argv [] .= $rep_document . '/conf_clients/hpom/prod_hpom_windows.xml';
	$new_argv [] .= $rep_document . '/conf_clients/hpom/hpom_manager_mail.xml';
	$new_argv [] .= $rep_document . '/conf_clients/hpom/prod_fiche_categorie.xml';
	$new_argv [] .= $rep_document . '/conf_clients/stars/CLIENT_stars_serveurs_rest_prod.xml';
	$new_argv [] .= $rep_document . '/conf_clients/database/prod_tools.xml';
	$new_argv [] .= '--create_log_file';
	$new_argv [] .= '--dossier_log';
	$new_argv [] .= 'H:/TOOLS/log';
	$new_argv [] .= '--fichier_log';
	$new_argv [] .= 'HPOM_opcmsg_test.log';
	$new_argv [] .= '--fichier_log_unique=non';
	//$new_argv [] .= '--fichier_log_append';
	$new_argv [] .= '--dry-run';
	
	$argv = $new_argv;
	$argc = count ( $argv );
}

$INCLUDE_HPOM = true;
$INCLUDE_STARS = true;
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/hpom_to_stars.class.php";

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
	$help [$fichier] ["text"] [] .= "Cree un ticket Stars a partir des donnees HPOM et fiches categories";
	
	$class_utilisees = array (
			"hpom"
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
 *
 * @ignore
 * @param options $liste_option        	
 * @param logs $fichier_log        	
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	//valeur de test : 891fe520-4260-71e4-0c6d-0a9901100000
	if ($liste_option->verifie_option_existe ( "msgID", true ) === false) {
		return abstract_log::onError_standard ( "Il faut un msgID", "", 2000 );
	}
	$hpom = hpom::creer_hpom ( $liste_option );
	$hpom->setMsgId ( $liste_option->getOption ( "msgID" ) )
		->connecte_COM ()
		->retrouve_hpom_param ();
	
	//Si le message est interne a HPOM (ne vient pas d'un client specifique)
	if ($hpom->traite_message_interne_hpom ()) {
		return fonctions_standards_mail::envoieMail_standard ( $liste_option, $hpom->getMsgText (), array (
				"text" => $hpom->getDescription () 
		) );
	}
	//Fin du message interne a HPOM (ne vient pas d'un client spécifique)
	

	//On gere la fiche categorie
	$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
	$db_tools = fonctions_standards_sgbd::recupere_db_tools ( $connexion_db );
	//On prepare le traitement de la fiche categorie
	$fiche_cat = fiche_categorie::creer_fiche_categorie ( $liste_option, false );
	//On connecte Stars
	$hpom_to_stars = hpom_to_stars::creer_hpom_to_stars ( $liste_option, false );
	$soapClient_incidents = stars_soap_IncidentManagement::creer_stars_soap_IncidentManagement ( $liste_option );
	if (! $fiche_cat || ! $db_tools || ! $soapClient_incidents || ! $hpom_to_stars) {
		return abstract_log::onError_standard ( "Il manque des variables necessaires", "", 2000 );
	}
	
	// On connecte le stars
	if ($soapClient_incidents->connect ( "CLIENT_Stars3" ) === false) {
		return abstract_log::onError_standard ( "Pas de connexion au stars : CLIENT_Stars3", "", 5110 );
	}
	
	//On traite la fiche categorie
	$fiche_cat->setDbTools ( $db_tools )
		->setHpomObject ( $hpom )
		->gestion_fiche_categorie ()
		->retrouve_priorite ();
	
	$hpom_to_stars->setHpomObject ( $hpom )
		->setFicheCategorieObject ( $fiche_cat )
		->setSoapStarsObject ( $soapClient_incidents )
		->creer_ticket_stars ();
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>  
