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
					$fichier . " --conf {Chemin vers conf_clients}/dev_client_zabbix_serveurs.xml --zabbix_serveur zabbix.domain --action ajout --zabbix_valuemapping_nom 'HP Insight System Status' --zabbix_mappings_mappingFile /tmp/HP_Insight_System_Status.txt --verbose",
					$fichier . " --conf {Chemin vers conf_clients}/dev_client_zabbix_serveurs.xml --zabbix_serveur zabbix.domain --action supp --zabbix_valuemapping_nom 'HP Insight System Status' --verbose",
					$fichier . " --conf {Chemin vers conf_clients}/dev_client_zabbix_serveurs.xml --zabbix_serveur zabbix.domain --action export --zabbix_dossier_export /tmp --zabbix_valuemapping_nom '' --verbose" 
			),
			$fichier => array () 
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Permet de gerer les valueMappings dans zabbix (ATENTION necessite d'ajouter les classes de valuemapping trouvees sur https://support.zabbix.com/ au serveur)";
	$help [$fichier] ["text"] [] .= "\t--zabbix_serveur Nom du zabbix a utiliser";
	$help [$fichier] ["text"] [] .= "\t--action ajout|supp|export Action a faire";
	$help [$fichier] ["text"] [] .= "\t--zabbix_dossier_export /tmp Dossier de sortie des exports";
	
	$class_utilisees = array (
			"zabbix_datas",
			"zabbix_wsclient",
			"zabbix_valuemapping"
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
		$zabbix_valuemapping = zabbix_valuemapping::creer_zabbix_valuemapping ( $liste_option, $zabbix_ws );
		
		if (! $zabbix_ws && $zabbix_valuemapping) {
			return abstract_log::onError_standard ( "Erreur dans les classes necessaires" );
		}
		if ($liste_option->verifie_option_existe ( "zabbix_serveur" ) === false) {
			return abstract_log::onError_standard ( "Il faut un zabbix pour travailler." );
		}
		
		//On valide la liste des parametres
		if ($liste_option->verifie_option_existe ( "action" ) === false) {
			return abstract_log::onError_standard ( "Il faut une action a effectuer : ajout|supp|export pour travailler." );
		}
		
		//On se connecte au zabbix
		if ($zabbix_ws->prepare_connexion ( $liste_option->getOption ( "zabbix_serveur" ) ) === false) {
			return false;
		}
		
		switch (strtolower ( $liste_option->getOption ( "action" ) )) {
			case 'ajout' :
				abstract_log::onInfo_standard ( "Ajout d'un valuemapping dans zabbix" );
				$zabbix_valuemapping->retrouve_zabbix_param ( false )
					->creer_valuemapping ();
				break;
			case 'supp' :
				abstract_log::onInfo_standard ( "Suppression d'un valuemapping dans zabbix" );
				$zabbix_valuemapping->retrouve_zabbix_param ( true )
					->supprime_valuemapping ();
				break;
			case 'export' :
				if ($liste_option->verifie_option_existe ( "zabbix_dossier_export", true ) === false) {
					return abstract_log::onError_standard ( "Il faut un --zabbix_dossier_export pour travailler." );
				}
				$liste_vm = $zabbix_valuemapping->retrouve_zabbix_param ( true )
					->recherche_valuemapping ( true, "extend" );
				foreach ( $liste_vm as $valuemapping ) {
					$nom_fichier = $liste_option->getOption ( "zabbix_dossier_export" ) . "/" . str_replace ( " ", "_", $valuemapping ["name"] ) . ".txt";
					abstract_log::onInfo_standard ( "Export : fichier en cours : " . $nom_fichier );
					$fichier_sortie = fichier::creer_fichier ( $liste_option, $nom_fichier, "oui" );
					$fichier_sortie->ouvrir ( "w" );
					foreach ( $valuemapping ["mappings"] as $mapping ) {
						abstract_log::onDebug_standard ( "Mapping en cours : " . $mapping ["value"] . "=>" . $mapping ["newvalue"], 1 );
						$fichier_sortie->ecrit ( $mapping ["value"] . "=>" . $mapping ["newvalue"] . "\n" );
					}
					$fichier_sortie->close ();
				}
				
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
