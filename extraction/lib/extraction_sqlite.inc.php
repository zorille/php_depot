<?php

/**
 * @author dvargas
 * @package Extraction
*/
/**
 * @ignore
 * Extrait des donnees via les fichiers sqlite..
 *
 * @param options &$liste_option Pointeur sur les arguments.
 * @param string $sql Requete sql a appliquer sur le sqlite.
 * @param string $serial Serial a extraire.
 * @param string $date Date a extraire.
 * @param string $sqlite Fichier sqlite different du standard contenant les donnees a extraire.
 * @return array|false Tableau de donnees extraitees, FALSE sinon.
*/
function recupere_donnees(&$liste_option, $sql, $serial, $date, $sqlite = "") {
	$donnees_resultat = array ();
	$type = "";
	$fonctions_standards = fonctions_standards::creer_fonctions_standards ( $liste_option );
	//Pour chaque dates on recupere les donnees
	//Si les donnees sont passees en argument, on utilise les fichiers en arguments
	if ($sqlite == "") {
		$report_path = $fonctions_standards->creer_report_path ( $liste_option );
		if ($liste_option->getOption ( "cumul_month" ) !== false)
			$type = "_m";
		if ($liste_option->getOption ( "cumul_week" ) !== false)
			$type = "_w";
		$sqlite = $report_path . "/report_" . $serial . "_" . $date . $type;
	}
	
	abstract_log::onInfo_standard ( "Traitement du fichier " . $sqlite );
	if (is_file ( $sqlite )) {
		$connexion = requete::creer_requete ( $liste_option, $sqlite, "", "", "sqlite:" );
		$connexion->setDbServeur ( $sqlite )
		->setDbType ( "sqlite" )
		->setDbMaj ( "oui" )
		->prepare_connexion ();
		abstract_log::onDebug_standard ( "La connexion est creer, on applique la(es) requete(s).", 1 );
		if (is_array ( $sql )) {
			abstract_log::onDebug_standard ( "Requete sur la base :", 1 );
			abstract_log::onDebug_standard ( $sql, 1 );
			foreach ( $sql as $requete ) {
				$donnees_tempo = requete ( $requete, $connexion );
				$donnees_resultat = merge_tableau ( $donnees_resultat, $donnees_tempo );
			}
		} else
			$donnees_resultat [0] = requete ( $sql, $connexion );
		$connexion->close ();
	} else {
		abstract_log::onWarning_standard ( "Le fichier " . $sqlite . " n'existe pas." );
		$donnees_resultat = false;
	}
	abstract_log::onDebug_standard ( "Resultat des requetes sur la base :", 1 );
	abstract_log::onDebug_standard ( $donnees_resultat, 1 );
	
	return $donnees_resultat;
}

?>
