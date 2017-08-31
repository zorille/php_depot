<?php

/**
 * @author dvargas
 * @package Extraction
 */

/**
 * Prend un requete SQL et une connexion db, applique la requete
 * et renvoi le resultat sous forme de tableau.
 *
 * @param string $sql Requete a appliquer.
 * @param db $connexion Connexion au storage ou au sqlite.
 * @return array Tableau de resultat.
 */
function requete($sql, &$connexion) {
	$liste_row = array ();
	try {
		$resultat = $connexion->faire_requete ( $sql );
	} catch ( Exception $e ) {
		abstract_log::onError_standard ( $e->getMessage (), "", $e->getCode () );
	}
	if ($resultat) {
		foreach ( $resultat as $row ) {
			foreach ( $row as $key => $value ) {
				if (! is_int ( $key )) {
					if (! isset ( $liste_row [$key] ))
						$liste_row [$key] = array ();
					$liste_row [$key] [] .= $value;
				}
			}
		}
	}
	return $liste_row;
}

/**
 * Concatene deux tableaux.
 *
 * @param array $tableau1 Tableau quelconque.
 * @param array $tableau2 Tableau quelconque.
 * @return array Renvoi les tableaux concatenes.
 */
function merge_tableau($tableau1, $tableau2) {
	foreach ( $tableau2 as $key => $value ) {
		if (is_array ( $value )) {
			foreach ( $value as $valeur )
				$tableau1 [$key] [] .= $valeur;
		} else
			$tableau1 [$key] [] .= $value;
	}
	
	return $tableau1;
}
//FIN des FONCTIONS sur la base de donnees


/**
 * Prend des donnees calculees format d'enregistrement standard
 * et les met dans un ou plusieurs fichiers.<br>
 * Tableau d'entree :<br>
 * array (<br>
 * [nom_du_fichier](<br>
 * 	0 => entete separer par des ;<br>
 * 	1 => valeur separer par des ;<br>
 * 	. => valeur separer par des ;<br>
 * 	. => valeur separer par des ;<br>
 * 	n => valeur separer par des ;<br>
 * 	)<br>
 * [nom_du_fichier2] ...<br>
 * )<br>
 *
 * @param options &$liste_option Pointeur sur les arguments.
 * @param dates $liste_date Dates traitees.
 * @param array $donnees_resultat Tableau de donnees preparees a l'enregistrement.
 * @return array Tableau de donnees preparees a l'enregistrement.
 */
function enregistre_donnees(&$liste_option, $liste_date, $donnees_resultat) {
	$CODE_RETOUR = array ();
	if (is_array ( $donnees_resultat )) {
		if ($liste_option->verifie_option_existe ( "fichier[@ajouter='oui']", true ) !== false) {
			$ouverture = 'a';
		} else {
			$ouverture = 'w';
		}
		abstract_log::onInfo_standard ( "Enregistrement des donnees." );
		foreach ( $donnees_resultat as $nom_fichier => $liste_donnee ) {
			$uuid = $nom_fichier; //pour la conversion en xls
			

			//Si un nom de fichier est passe en argument, on le prend
			if ($liste_option->verifie_option_existe ( "fichier_sortie", true ) !== false) {
				$nom_fichier = $liste_option->getOption ( "fichier_sortie" );
			} else {
				$nom_fichier = fonctions_standards::creer_nom_fichier ( $liste_option, $liste_date, $nom_fichier );
			}
			
			abstract_log::onDebug_standard ( "Nom du fichier en cours : " . $nom_fichier, 1 );
			abstract_log::onDebug_standard ( "Type d'ouverture : " . $ouverture, 1 );
			if (fichier::tester_fichier_existe ( $nom_fichier ) && $liste_option->verifie_option_existe ( "fichier[@ajouter='oui']", true ) !== false) {
				$flag = false;
			} else {
				$flag = true;
			}
			$fichier = fichier::creer_fichier ( $liste_option, $nom_fichier, "oui" );
			$fichier->ouvrir ( $ouverture );
			foreach ( $liste_donnee as $donnees ) {
				if ($flag)
					$fichier->ecrit ( $donnees . "\n" );
				else
					$flag = true;
			}
			$fichier->close ();
			if ($liste_option->verifie_option_existe ( "convert_csv_to_xls" ) !== false) {
				$nom_fichier = convert_to_xls ( $liste_option, $nom_fichier, $uuid );
			}
			
			if (! in_array ( $nom_fichier, $CODE_RETOUR )) {
				$CODE_RETOUR [] .= $nom_fichier;
			}
		}
		abstract_log::onInfo_standard ( "Donnees enregistrees." );
	} else
		$CODE_RETOUR = false;
	
	if (! $CODE_RETOUR)
		abstract_log::onWarning_standard ( "Il n'y a pas de donnees a sauvegarder." );
	
	return $CODE_RETOUR;
}

/**
 * Cree l'entete des fichiers csv avec la liste des champs de l'ordre de sortie.
 *
 * @param options &$liste_option Pointeur sur les arguments.
 * @return string Entete du csv.
 */
function creer_entete_csv(&$liste_option) {
	if (check_report_date ( $liste_option ))
		$entete = __CLASS__;
	else
		$entete = __CLASS__;
	$titre = $liste_option->getOption ( array (
			"ordre_de_sortie",
			"champ",
			"titre" 
	) );
	$separateur = $liste_option->getOption ( array (
			"ordre_de_sortie",
			"separateur" 
	) );
	if (is_array ( $titre )) {
		foreach ( $titre as $value ) {
			if (isset ( $entete ) && $entete != "") {
				$entete .= $separateur;
			}
			$entete .= $value;
		}
	} else {
		if (isset ( $entete ) && $entete != "") {
			$entete .= $separateur;
		}
		$entete .= $titre;
	}
	
	return $entete;
}

/**
 * Verifie si la date doit apparaitre dans l'entete du fichier csv.
 *
 * @param options &$liste_option Pointeur sur les arguments.
 * @return Bool TRUE si oui, FALSE sinon.
 */
function check_report_date(&$liste_option) {
	if ($liste_option->verifie_option_existe ( "ordre_de_sortie[@ajoute_date='oui']", true ) !== false) {
		$sortie_date = true;
	} else {
		$sortie_date = false;
	}
	
	return $sortie_date;
}

/**
 * Renvoi les donnees d'un position precise du format d'enregistrement.
 *
 * @param array $donnee Liste des donnees au format d'enregistrement.
 * @param int $case Position a renvoyer.
 * @return Bool TRUE si oui, FALSE sinon.
 */
function renvoi_donnees($donnee, $case = 0) {
	if (is_array ( $donnee ) && isset ( $donnee [$case] ))
		$CODE_RETOUR = $donnee [$case];
	elseif (! is_array ( $donnee ))
		$CODE_RETOUR = $donnee;
	else
		$CODE_RETOUR = FALSE;
	
	return $CODE_RETOUR;
}

/**
 * applique un decodage sur une url.
 *
 * @param string $ligne Ligne a decoder.
 * @return string Renvoi la ligne decodee.
 */
function hash_ligne($ligne) {
	return urldecode ( $ligne );
}

/**
 * Renvoi la profondeur maximum des champs pour la liste des resultats.
 *
 * @param array $tableau Liste des donnees au format d'enregistrement.
 * @param array $liste_champ_utilise Liste des champs utilisees.
 * @return int Nombre max de champ.
 */
function nombre_max_champ($tableau, $liste_champ_utilise) {
	$resultat = 1;
	foreach ( $tableau as $champ => $liste ) {
		if (is_array ( $liste ) && verifie_champ_utiliser ( $champ, $liste_champ_utilise )) {
			$valeur = count ( $liste );
			if ($valeur > $resultat)
				$resultat = $valeur;
		}
	}
	return $resultat;
}

/**
 * Verifie si un champ est utilise lors de l'enregistrement.
 *
 * @param string $champ Nom d'un champ a tester.
 * @param array $liste_champ_utilise Liste des champs utilisees.
 * @return Bool TRUE si oui, FALSE sinon.
 */
function verifie_champ_utiliser($champ, $liste_champ_utilise) {
	if (is_array ( $liste_champ_utilise ))
		$liste_champ = $liste_champ_utilise;
	else
		$liste_champ [0] = $liste_champ_utilise;
	$nb_champ = count ( $liste_champ );
	$CODE_RETOUR = false;
	for($i = 0; $i < $nb_champ; $i ++) {
		if ($champ === $liste_champ [$i]) {
			$CODE_RETOUR = true;
			break;
		}
	}
	return $CODE_RETOUR;
}

/**
 * Envoi le mail avec les extractions en fichier joint.
 *
 * @param options &$liste_option Pointeur sur les arguments.
 * @param array $liste_fichier Liste des fichiers a attacher.
 * @return array|false Tableau de donnees extraitees, FALSE sinon.
 */
function envoi_mail(&$liste_option, $liste_fichier) {
	abstract_log::onInfo_standard ( "Envoi du mail de confirmation." );
	$mail = fonctions_standards_mail::creer_liste_mail ( $liste_option );
	if ($mail !== false && is_array ( $liste_fichier ) && $liste_fichier [0] != false) {
		//Enfin on envoi le(s) mail(s)
		abstract_log::onInfo_standard ( "Liste destinataire : " . $mail->getMailingList() );
		if ($liste_option->verifie_option_existe ( "email_sujet", true ) !== false)
			$mail->setSujet ( $liste_option->getOption ( "email_sujet" ) );
		else
			$mail->setSujet ( "Extraction" );
		if ($liste_option->verifie_option_existe ( "email_corp", true ) !== false)
			$mail->ecrit ( $liste_option->getOption ( "email_corp" ) );
		else
			$mail->ecrit ( "Bonjour, \n\n Ci-joint votre extraction." );
		foreach ( $liste_fichier as $fichier_data )
			$mail->attache_fichier ( $fichier_data, "application/octet-stream" );
		$mail->envoi ();
	} else
		abstract_log::onInfo_standard ( "Pas d'envoi de mail de confirmation." );
	
	return true;
}

/**
 * Convertie un csv en Excel.
 *
 * @param options $liste_option Pointeur sur les arguments.
 * @param string $filename_csv_report Chemin complet du csv a convertir.
 * @param string $uuid uuid du csv a convertir.
 * @return string Renvoi le nom du fichier Excel.
 */
function convert_to_xls(&$liste_option, $filename_csv_report, $uuid) {
	abstract_log::onInfo_standard ( "Conversion au format excel" );
	// Import/Export du document CSV vers XLS
	$tab_file_csv = array ();
	$num_form = 0;
	$erreur = 0;
	
	// Construction du document XLS
	$filename_excel_report = str_replace ( $liste_option->getOption ( array (
			"fichier",
			"extension" 
	) ), ".xls", $filename_csv_report );
	if ($liste_option->verifie_option_existe ( "supprime_xls_precedent" ) !== false && fichier::tester_fichier_existe ( $filename_excel_report ))
		fichier::supprime_fichier ( $filename_excel_report );
	$xls_file_export = fichier::creer_fichier ( $liste_option, $filename_excel_report, "oui" );
	$xls_file_export->ouvrir ( "ab" );
	$xls_doc = new writeexcel_workbook ( $xls_file_export->handler );
	
	if (is_int ( $uuid )) {
		// on va cherher en base le nom du compte correspondant au uuid
		$resultat_requete = fonctions_standards_sgbd::requete_sql ( $liste_option, "SELECT name FROM database WHERE uuid='" . $uuid . "';" );
		$xls_onglet [$num_form] = &$xls_doc->addworksheet ( substr ( $resultat_requete [0] ["name"], 0, 30 ) );
	} else
		$xls_onglet [$num_form] = &$xls_doc->addworksheet ( $uuid );
	
	$csv_file_import = file ( $filename_csv_report );
	$cursor_ligne = 0;
	
	if ($csv_file_import) {
		// Extraction des lignes
		foreach ( $csv_file_import as $ligne ) {
			$cursor_champ = 0;
			$elements = explode ( $liste_option->getOption ( array (
					"ordre_de_sortie",
					"separateur" 
			) ), $ligne );
			
			// Extraction des champs
			foreach ( $elements as $elem ) {
				$xls_onglet [$num_form]->write ( $cursor_ligne, $cursor_champ, trim ( $elem ) );
				$cursor_champ ++;
			}
			$cursor_ligne ++;
		}
	}
	
	if ($erreur < 1) {
		$xls_doc->close ();
		
		//header("Content-Type: application/x-msexcel; name=\"demo_file.xls\"");
		//header("Content-Disposition: inline; file_csv=\"demo_file.xls\"");
	}
	$xls_file_export->close ();
	
	return $filename_excel_report;
}

/**
 * Convertie un uuid en Nom du client.<br>
 * Necessite une definition de la connexion vers la base.
 *
 * @param options $liste_option Pointeur sur les arguments.
 * @param string $uuid uuid a convertir en nom.
 * @return string Nom du client.
 */
function convert_uuid_to_nom(&$liste_option, $uuid) {
	//connexion a base de donnees
	$connexion = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
	$db_source = fonctions_standards_sgbd::recupere_db_source ( $connexion, true );
	//extraction des noms a partir des uuids
	$requete_sql = $db_source->requete_select_base ( "", $uuid );
	foreach ( $requete_sql as $row ) {
		$nom = $row ['name'];
	}
	$db_source->close ();
	
	return $nom;
}

/**
 * Renvoi la liste des dates a traiter.
 *
 * @param options &$liste_option Pointeur sur les arguments.
 * @param dates &$liste_dates Date des donnees a extraire.
 * @return array liste des dates a traiter
 */
function renvoi_liste_dates(&$liste_option, &$liste_dates) {
	if ($liste_option->getOption ( "cumul_month" ) !== false) {
		$liste_date_tempo = $liste_dates->getListeMonth ();
	} elseif ($liste_option->getOption ( "cumul_week" ) !== false) {
		$liste_date_tempo = $liste_dates->getListeWeek ();
	} else {
		$liste_date_tempo = $liste_dates->getListeDates ();
	}
	
	return $liste_date_tempo;
}

/**
 * @ignore
 * Affiche le help.<br>
 * Cette fonction fait un exit.
 * Arguments reconnus :<br>
 * --help
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
	$help [$fichier] ["text"] [] .= "Extraction de donnees pour faire un Excel";
	$help [$fichier] ["text"] [] .= "\t--cumul_month\t\tutilise les donnees mois";
	$help [$fichier] ["text"] [] .= "\t--cumul_week\t\tutilise les donnees semaine";
	
	$class_utilisees = array ( 
			"" 
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\r\n";
	exit ( 0 );
}

?>
