#!/usr/bin/php
<?php
/**
 *
 * @author dvargas
 * @package collecte
 */
$INCLUDE_PHPEXCEL = true;
$rep_document = dirname ( $argv [0] ) . "/../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->renvoie_option ( "rep_scripts" ) . "/lib/parse_collected_datas.class.php";
/**
 * Librairies specifiques au programme
 */
require_once $liste_option->renvoie_option ( "rep_scripts" ) . "/lib/collected_datas_to_excel.class.php";

/**
 *
 * @ignore Affiche le help.<br> Cette fonction fait un exit. Arguments reconnus :<br> --help
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
	$help [$fichier] ["text"] [] .= "Permet d'extraire les Excel a partir des donnees collectees";
	$help [$fichier] ["text"] [] .= "--dossier_sortie";
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
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

function principale(
		&$liste_option, 
		&$fichier_log) {
	try {
		if ($liste_option->verifie_option_existe ( "dossier_sortie" ) === false) {
			$liste_option->setOption ( 'dossier_sortie', '.' );
		}
		// On se connecte a la base cmdb_vodafone
		$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
		$db_cmdb_vodafone = fonctions_standards_sgbd::recupere_db_cmdb_vodafone ( $connexion_db );
		$liste_serveur = $db_cmdb_vodafone->select_serveur ();
		$objPHPExcel_menu = new PHPExcel ();
		$objPHPExcel_menu->getProperties ()
			->setCreator ( "Damien Vargas" )
			->setLastModifiedBy ( "Damien Vargas" )
			->setTitle ( "Master" )
			->setSubject ( "Server configuration" )
			->setDescription ( "An image of current server" );
		$objPHPExcel_menu->removeSheetByIndex ( 0 );
		$sheet = $objPHPExcel_menu->createSheet ();
		$sheet->setTitle ( "Machines_list" );
		$row = 1;
		foreach ( $liste_serveur as $row_serveur ) {
			abstract_log::onInfo_standard ( "Serveur en cours : " . $row_serveur ['serveur'] );
			$sheet->setCellValueByColumnAndRow ( 0, $row, $row_serveur ['serveur'] );
			$sheet->getCellByColumnAndRow ( 0, $row )
				->getHyperlink ()
				->setUrl ( 'machines_list/' . $row_serveur ['serveur'] . '.xlsx' );
			$row ++;
			$last_pos = array ();
			$objPHPExcel = new PHPExcel ();
			$objPHPExcel->getProperties ()
				->setCreator ( "Damien Vargas" )
				->setLastModifiedBy ( "Damien Vargas" )
				->setTitle ( $row_serveur ['serveur'] )
				->setSubject ( "Server configuration" )
				->setDescription ( "An image of current server" );
			$objPHPExcel->removeSheetByIndex ( 0 );
			$parsing_data = collected_datas_to_excel::creer_collected_datas_to_excel ( $liste_option );
			$parsing_data->setObjetExcel ( $objPHPExcel );
			$donnees_machine = $db_cmdb_vodafone->requete_select_standard ( "collected_datas", array (
					"serveur" => $row_serveur ['serveur'] 
			) );
			$parsing_data->setDonneesSource ( $donnees_machine );
			$parsing_data->parse_datas ();
			$objWriter = PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel2007' );
			$objWriter->save ( $liste_option->getOption ( 'dossier_sortie' ) . "/" . $row_serveur ['serveur'] . '.xlsx' );
			unset ( $objPHPExcel );
		}
		$objWriter = PHPExcel_IOFactory::createWriter ( $objPHPExcel_menu, 'Excel2007' );
		$objWriter->save ( $liste_option->getOption ( 'dossier_sortie' ) . "/Machines_list.xlsx" );
	} catch ( Exception $e ) {
		return abstract_log::onError_standard ( $e->getMessage () );
	}
	return true;
}
principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );
exit ( $fichier_log->renvoie_exit () );
?>
