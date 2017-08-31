#!/usr/bin/php
<?php
/**
 * Permet de convertir un csv d'extraction hebdo (tf1 ou europe1) en excel
 * @author dvargas
 * @package Tools
 * @subpackage Excel
*/
$rep_document = dirname ( $argv [0] ) . "/../../..";
/**
 * Permet de charger les librairies necessaire.
*/
require_once $rep_document . "/php_framework/config.php";

/**
 * @ignore
 */
function help() {
	echo "

###################### Partie communes ############################

--conf=fichier de conf
--fichier_entree           Fichier csv a transformer
--fichier_sortie           Fichier cible excel
--separateur=;             Caractere de separation dans le csv

###################### Partie communes ############################

		\n";
	fonctions_standards::help_fonctions_standard ( "oui" );
	echo "[Exit]0\n";
	exit ( 0 );
}

/**
 * @todo
 * @param <type> $liste_option
 * @param <type> $xls_onglet
 * @param <type> $xls_doc
 * @param <type> $uuid
 * @return <type>
 */
function creer_onglet(&$liste_option, &$xls_onglet, &$xls_doc, $uuid) {
	// on va cherher en base le nom du compte correspondant au uuid
	$resultat_requete = fonctions_standards_sgbd::requete_sql ( $liste_option, "SELECT name FROM table_name WHERE uuid='" . $uuid . "';" );
	$xls_onglet [$uuid] = &$xls_doc->addworksheet ( $resultat_requete [0] ["name"] );
	
	return true;
}

/**
 * @todo
 * @param <type> $xls_onglet
 * @param <type> $elements
 * @param <type> $num_ligne
 * @param <type> $uuid
 * @return <type>
 */
function ajoute_ligne(&$xls_onglet, &$elements, $num_ligne, $uuid) {
	$cursor_champ = 0;
	foreach ( $elements as $elem ) {
		if (trim ( $elem ) == $uuid)
			$elem = "=\"" . $uuid . "\"";
		$xls_onglet [$uuid]->write ( $num_ligne, $cursor_champ, trim ( $elem ) );
		$cursor_champ ++;
	}
	
	return true;
}

//Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();
	
	//Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

if ($liste_option->verifie_option_existe ( "separateur", true ) === false)
	$liste_option->setOption ( "separateur", ";" );

if ($liste_option->verifie_option_existe ( "fichier_entree", true ) !== false) {
	// Import/Export du document CSV vers XLS
	$tab_file_csv = array ();
	$erreur = 0;
	$liste_uuid = array ();
	$cursor_ligne = array ();
	$entete = false;
	
	abstract_log::onInfo_standard ( "fichier d'entree : " . $liste_option->getOption ( "fichier_entree" ) );
	
	if ($liste_option->verifie_option_existe ( "fichier_sortie", true ) !== false)
		$filename_excel_report = $liste_option->getOption ( "fichier_sortie" );
	else {
		$fonctions_standards = fonctions_standards::creer_fonctions_standards ( $liste_option );
		$filename_excel_report = $fonctions_standards->creer_nom_fichier ( $liste_option, $liste_dates );
	}
	abstract_log::onInfo_standard ( "fichier de sortie : " . $filename_excel_report );
	
	$xls_file_export = fichier::creer_fichier ( $liste_option, $filename_excel_report, "oui" );
	$xls_file_export->ouvrir ( "ab" );
	// Construction du document XLS
	$xls_doc = new writeexcel_workbook ( $xls_file_export->handler );
	
	abstract_log::onInfo_standard ( "On charge les donnees." );
	//recuperation des donnees csv
	$csv_file_import = file ( $liste_option->getOption ( "fichier_entree" ) );
	
	if ($csv_file_import) {
		abstract_log::onInfo_standard ( "On enregistre les donnees." );
		// Extraction des lignes
		foreach ( $csv_file_import as $ligne ) {
			abstract_log::onDebug_standard ( "ligne traitee : " . $ligne, 2 );
			//On recupere l'entete
			if ($entete === false) {
				$entete = $ligne;
				continue;
			}
			//on split la ligne en cours
			$elements = explode ( $liste_option->getOption ( "separateur" ), $ligne );
			
			//On cree un onglet pour le uuid en cours
			if (! isset ( $liste_uuid [$elements [1]] )) {
				$liste_uuid [$elements [1]] = 1;
				creer_onglet ( $liste_option, $xls_onglet, $xls_doc, $elements [1] );
				$elements_entete = explode ( $liste_option->getOption ( "separateur" ), $entete );
				ajoute_ligne ( $xls_onglet, $elements_entete, 0, $elements [1] );
				$cursor_ligne [$elements [1]] = 1;
			}
			
			abstract_log::onDebug_standard ( $xls_onglet, 2 );
			// Ajout des champs
			ajoute_ligne ( $xls_onglet, $elements, $cursor_ligne [$elements [1]], $elements [1] );
			//on augmente le curseur de ligne
			$cursor_ligne [$elements [1]] ++;
		}
	}
} else
	abstract_log::onError_standard ( "Il faut un fichier csv pour travailler.", "" );

if ($erreur < 1)
	$xls_doc->close ();

$xls_file_export->close ();

abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?> 
