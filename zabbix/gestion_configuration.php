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
					abstract_log::colorize("TEMPLATES", "Yellow"),
					"./gestion_configuration.php --conf {Chemin vers conf_clients}/prod_client_zabbix_serveurs.xml --zabbix_serveur zabbix.dev.client.fr.ghc.local --action import --zabbix_configuration_format xml --zabbix_configuration_fichier /tmp/template_import.xml --zabbix_configuration_rules templates --templates_createMissing true --templates_updateExisting false --verbose",
					"./gestion_configuration.php --conf {Chemin vers conf_clients}/prod_client_zabbix_serveurs.xml --zabbix_serveur zabbix.dev.client.fr.ghc.local --action export --zabbix_configuration_format xml --zabbix_configuration_fichier /tmp/template_export.xml --zabbix_configuration_options templates --verbose"
			),
			$fichier => array () 
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Permet de gerer les configurations dans zabbix";
	$help [$fichier] ["text"] [] .= "\t--zabbix_serveur Nom du zabbix a utiliser";
	$help [$fichier] ["text"] [] .= "\t--action import|export Action a faire";
	
	$class_utilisees = array (
			"zabbix_datas",
			"zabbix_wsclient",
			"zabbix_configuration"
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
		$zabbix_configuration = zabbix_configuration::creer_zabbix_configuration ( $liste_option, $zabbix_ws );
		
		if (! $zabbix_ws && $zabbix_configuration) {
			return abstract_log::onError_standard ( "Erreur dans les classes necessaires" );
		}
		if ($liste_option->verifie_option_existe ( "zabbix_serveur" ) === false) {
			return abstract_log::onError_standard ( "Il faut un zabbix pour travailler." );
		}
		
		//On valide la liste des parametres
		if ($liste_option->verifie_option_existe ( "action" ) === false) {
			return abstract_log::onError_standard ( "Il faut une action a effectuer : import|export pour travailler." );
		}
		
		//On se connecte au zabbix
		if ($zabbix_ws->prepare_connexion ( $liste_option->getOption ( "zabbix_serveur" ) ) === false) {
			return false;
		}
		
		switch (strtolower ( $liste_option->getOption ( "action" ) )) {
			case 'import' :
				$retour = $zabbix_configuration->retrouve_zabbix_param ( true )
					->importer ();
				break;
			case 'export' :
				$zabbix_configuration->retrouve_zabbix_param ( false, true );
				foreach ( $zabbix_configuration->getOptions () as $option ) {
					switch (strtolower ( $option )) {
						case "templates" :
							$zabbix_templates = zabbix_templates::creer_zabbix_templates ( $liste_option, $zabbix_ws );
							$zabbix_templates->retrouve_zabbix_param ()
								->valide_liste_templates ();
							$zabbix_configuration->setObjetTemplates ( $zabbix_templates );
							break;
						default :
							abstract_log::onError_standard ( "Cette option " . $option . " n'existe pas." );
					}
					$retour = $zabbix_configuration->exporter ();
				}
				break;
			default :
				abstract_log::onError_standard ( "Action inconnue " . $liste_option->getOption ( "action" ) );
		}
		
		if ($retour === false) {
			abstract_log::onError_standard ( "L'" . $liste_option->getOption ( "action" ) . " n'a pas fonctionne." );
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
