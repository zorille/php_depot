#!/usr/bin/php
<?php
/**
 * @ignore
 */
/**
 * @author dvargas
 * @package collecte
 */
// Deplacement pour joindre le repertoire lib
$deplacement = "/../..";
$rep_document = dirname ( $argv [0] ) . $deplacement;
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/parse_collected_datas.class.php";
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/collected_datas_to_sqlite.class.php";
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->getOption ( "rep_scripts" ) . "/lib/collect_data_ssh.class.php";

/**
 * @ignore Affiche le help.<br> Cette fonction fait un exit. Arguments reconnus :<br> --help
 */
function help() {
	$fichier = basename ( __FILE__ );
	$help = array (
			"usage" => array (
					$fichier . " --conf [fichiers de conf] [OPTIONS]",
					$fichier . " --help"
			),
			"exemple" => array (
					"./" . $fichier . " --conf {Chemin vers conf_clients}/database/prod_CLIENT_sam_sqlite.xml --repertoire_fichiers ./liste_datas/ --verbose"
			),
			$fichier => array ()
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Permet de charge les donnees recoltees par les commandes shell dans la base gestion_sam";
	$class_utilisees = array (
			"abstract_log",
			"machine"
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
 * Main programme Code retour en 2xxx en cas d'erreur
 *
 * @ignore
 * @param options $liste_option
 * @param logs $fichier_log
 * @return boolean
 */
function principale(
		&$liste_option,
		&$fichier_log) {
	//On creer la liste de machines a collecter
	$liste_machines = machines::creer_machines ( $liste_option )->charge_liste_machines ();
	//On creer les flux 
	// On prepare une class flux par serveur
	$collecte_data=collect_data_ssh::creer_collect_data_ssh($liste_option);
	foreach($liste_machines->getListeMachines() as $machine){
		//A priori : SSH = UX (linux,bsd,UNIX)
		if($machine->getTypeConnexion()=="ssh"){
			$class_flux->creer_connexion_ssh ( $machine->getNom() );
			$data= $class_flux->getConnexion ()
			->ssh_commande ( "/usr/bin/uname -a" );
			if (is_array ( $data ) && isset ( $data ["output"] )) {
				$row_donnees ["resultat"] = $data ["output"];
			} else {
				$row_donnees ["resultat"] = "";
			}
			abstract_log::onInfo_standard($row_donnees);
		}
	}
	return true;
}
principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );
exit ( $fichier_log->renvoiExit () );
?>
