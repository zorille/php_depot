#!/usr/bin/php
<?php
/**
 *
 * @ignore
 *
 *
 */
/**
 *
 * @author dvargas
 * @package HP
 * @subpackage sitescope
 */
$INCLUDE_SITESCOPE = true;
$rep_document = dirname ( $argv [0] ) . "/../../..";
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
	$help [$fichier] ["text"] [] .= "Permet d'extraire la liste des devices d'un ou plusieurs sitescope";
	$help [$fichier] ["text"] [] .= "\t--fichier_sortie /tmp/fichier.out Chemin et nom du fichier d'extraction";
	
	$class_utilisees = array (
			"fichier",
			"sitescope_fonctions_standards",
			"options" 
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
 * ******** VOTRE CODE A PARTIR D'ICI*********
 */
$continue = true;

$sitescope_liste = sitescope_datas::creer_sitescope_datas ( $liste_option );

if ($liste_option->verifie_option_existe ( "fichier_sortie" ) === false) {
	$liste_option->setOption ( "fichier_sortie", "/tmp/liste_serveurs_sitescope.csv" );
}

$soapClient = soap::creer_soap ( $liste_option );
$soapClient->setCacheWsdl ( WSDL_CACHE_NONE );

if ($continue && $sitescope_liste && $soapClient) {
	abstract_log::onInfo_standard ( "Fichier de sortie : " . $liste_option->getOption ( "fichier_sortie" ) );
	$fichier_out = fichier::creer_fichier ( $liste_option, $liste_option->getOption ( "fichier_sortie" ), "oui" );
	$fichier_out->ouvrir ( "w" );
	$fichier_out->ecrit ( "client;nom;Nom du CI;Adresse du CI\n" );
	
	foreach ( $sitescope_liste->getServeurDatas() as $serveur_data ) {
		abstract_log::onInfo_standard ( "Sitescope : " . $serveur_data ["nom"] );
		abstract_log::onDebug_standard ( $serveur_data, 2 );
		try {
			$serveur_data["wsdl"]= $sitescope_liste->getWsdlData ( "APISiteScope" ) . "?wsdl";
			$soapClient->retrouve_variables_tableau ( $serveur_data );
			if ($soapClient->connect ( )) {
				
				$liste_servers = $soapClient->getSoapClient ()
					->getAllUnixNTConfiguredOnlyServerList ();
				abstract_log::onDebug_standard ( $liste_servers, 2 );
				
				if (is_object ( $liste_servers )) {
					abstract_log::onDebug_standard ( "Ajout des serveurs du SiS : " . $serveur_data ["nom"], 1 );
					
					foreach ( $liste_servers->item as $server ) {
						if ($server [1] != "Serveur SiteScope") {
							$fichier_out->ecrit ( $serveur_data ["client"] . ";" . $serveur_data ["nom"] . ";" . $server [1] . ";" . $server [0] . "\n" );
						}
					}
				}
			}
		} catch ( Exception $e ) {
			abstract_log::onError_standard ( $e->getMessage () );
		}
	}
	
	$fichier_out->close ();
} else {
	abstract_log::onError_standard ( "Erreur dans les variables necessaires" );
}

/**
 * ********* FIN DE VOTRE CODE ***************
 */
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
