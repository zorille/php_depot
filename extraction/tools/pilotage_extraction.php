#!/usr/bin/php
<?php
/**
 * @author dvargas
 * @package Extraction
 */
$rep_document = dirname ( $argv [0] ) . "/../..";
/**
 * Permet de charger les librairies necessaire.
 */
require_once $rep_document . "/php_framework/config.php";

$output = array ();

//D'abord on creer la liste de dates
$liste_dates = dates::creer_dates ( $liste_option );

//Puis la liste des serials a traiter
$liste_serial1 = array (
		"00011",
		"212012197768" 
);
$liste_serial2 = array (
		"208008188505",
		"257057197764" 
);

$flag = true;

foreach ( $liste_dates->getListeDates () as $date ) {
	try {
		$hash_day = $liste_dates->parse_date ( $date );
	} catch ( Exception $e ) {
		continue;
	}
	if ($date === "20070504")
		$flag = false;
	if (strftime ( "%A", mktime ( 0, 0, 0, $hash_day ['month'], $hash_day ['day'], $hash_day ['year'] ) ) == "Friday")
		$date1 = $date;
	if (strftime ( "%A", mktime ( 0, 0, 0, $hash_day ['month'], $hash_day ['day'], $hash_day ['year'] ) ) == "Thursday") {
		echo "Periode du " . $date1 . " au " . $date . " en cours.\n";
		if ($flag) {
			$CMD = "./extraction.php --conf=./conf/extraction_" . $liste_serial1 [0] . ".xml --date_debut=" . $date1 . " --date_fin=" . $date;
			exec ( $CMD, $output, $var_return );
			if ($var_return !== 0)
				print_r ( $output );
			$CMD = "./extraction.php --conf=./conf/extraction_" . $liste_serial2 [0] . ".xml --date_debut=" . $date1 . " --date_fin=" . $date;
			exec ( $CMD, $output, $var_return );
			if ($var_return !== 0)
				print_r ( $output );
		} else {
			$CMD = "./extraction.php --conf=./conf/extraction_" . $liste_serial1 [1] . ".xml --date_debut=" . $date1 . " --date_fin=" . $date;
			exec ( $CMD, $output, $var_return );
			if ($var_return !== 0)
				print_r ( $output );
			$CMD = "./extraction.php --conf=./conf/extraction_" . $liste_serial2 [1] . ".xml --date_debut=" . $date1 . " --date_fin=" . $date;
			exec ( $CMD, $output, $var_return );
			if ($var_return !== 0)
				print_r ( $output );
		}
		echo "Periode du " . $date1 . " au " . $date . " fini.\n";
	}
}
?> 
