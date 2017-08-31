#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package Zabbix
 * @subpackage Zabbix
 */
$rep_document = dirname ( $argv [0] ) . "/../..";
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
			"exemples" => array (
					abstract_log::colorize("En COURS DE DEV", "Red"),
			),
			$fichier => array () 
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Permet de gerer les utilisateurs dans zabbix";
	$help [$fichier] ["text"] [] .= "\t--zabbix_serveur Nom du zabbix a utiliser";
	$help [$fichier] ["text"] [] .= "\t--action ajout|supp Action a faire";
	
	$class_utilisees = array (
			"zabbix_user",
 			"zabbix_usermedia",
			"zabbix_mediatype",
 			"zabbix_usergroups"
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();

abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

function principale(&$liste_option, &$fichier_log) {
	try {
		$zabbix_ws = zabbix_wsclient::creer_zabbix_wsclient ( $liste_option, zabbix_datas::creer_zabbix_datas ( $liste_option ) );
		$zabbix_user = zabbix_user::creer_zabbix_user ( $liste_option, $zabbix_ws );
		
		if (! $zabbix_ws && $zabbix_user) {
			return abstract_log::onError_standard ( "Erreur dans les classes necessaires" );
		}
		if ($liste_option->verifie_option_existe ( "zabbix_serveur" ) === false) {
			return abstract_log::onError_standard ( "Il faut un zabbix pour travailler." );
		}
		
		//On valide la liste des parametres
		if ($liste_option->verifie_option_existe ( "action" ) === false) {
			return abstract_log::onError_standard ( "Il faut une action a effectuer : ajout|supp pour travailler." );
		}
		
		//On se connecte au zabbix
		$zabbix_ws->prepare_connexion ( $liste_option->getOption ( "zabbix_serveur" ) );
		//On recupere les parametres user
		$zabbix_user->retrouve_zabbix_param ();
		
		switch (strtolower ( $liste_option->getOption ( "action" ) )) {
			case 'ajout' :
				//Retrouve la liste des HostGroup
				$zabbix_usergroups = zabbix_usergroups::creer_zabbix_usergroups ( $liste_option, $zabbix_ws );
				$zabbix_usergroups->retrouve_zabbix_param ()
					->valide_liste_groups ();
				
				///Retrouve la liste des mediatypes
				$zabbix_mediatypes = zabbix_mediatype::creer_zabbix_mediatype ( $liste_option, $zabbix_ws );
				$zabbix_mediatypes->retrouve_zabbix_param ( true )
					->recherche_mediatypeid_by_Name ();
				
				//Retrouve la liste des usermedia
				$zabbix_usermedia = zabbix_usermedia::creer_zabbix_usermedia ( $liste_option, $zabbix_ws );
				$zabbix_usermedia->retrouve_zabbix_param ()
					->setMediaTypeId ( $zabbix_mediatypes->getMediatypeId () );
				
				abstract_log::onInfo_standard ( "Ajout de l'utilisateur : " . $zabbix_user->getAlias () );
				$zabbix_user->setObjetListeUserGroups ( $zabbix_usergroups )
					->setObjetListeMedia ( $zabbix_usermedia )
					->creer_user ();
				break;
			case 'supp' :
				$zabbix_user->recherche_userid_by_Alias ()
					->supprime_user ();
				break;
			default :
				abstract_log::onError_standard ( "Action inconnue " . $liste_option->getOption ( "action" ) );
		}
	} catch ( Exception $e ) {
		// Exception in ZabbixApi catched
		return abstract_log::onError_standard ( $e->getMessage (), "", $e->getCode () );
	}
	
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
