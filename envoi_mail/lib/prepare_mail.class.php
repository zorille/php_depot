<?php
/**
 * @author dvargas
 * @package Envoi_mail
 */

/**
 * class prepare_mail
 * @author davargas
 *
 */
class prepare_mail extends abstract_log {
	/**
	 * Retrouve la liste des fichiers a joindre dans le mail.
	 *
	 * @param
	 *        	options &$liste_option Pointeur sur les arguments.
	 * @return array false de fichier, FALSE s'il n'y en a pas.
	 */
	static public function prepare_liste_fichier(&$liste_option) {
		abstract_log::onInfo_standard ( "Preparation des fichiers a envoyer." );
		$liste_fichier = array ();
		
		$liste_fichier_brut = $liste_option->getOption ( "liste_fichier", true );
		if ($liste_fichier_brut) {
			if (is_array ( $liste_fichier_brut ))
				$liste_fichier = $liste_fichier_brut;
			else
				$liste_fichier = explode ( " ", $liste_fichier_brut );
			
			foreach ( $liste_fichier as $fichier_a_tester ) {
				$retour = fichier::tester_fichier_existe ( $fichier_a_tester );
				if ($retour === FALSE)
					abstract_log::onError_standard ( "Le fichier " . $fichier_a_tester . " n'existe pas", "" );
			}
		} else
			$liste_fichier = false;
		
		return $liste_fichier;
	}
	
	/**
	 * Prepare le corp au format texte du mail.
	 *
	 * @param
	 *        	options &$liste_option Pointeur sur les arguments.
	 * @return String le texte, "" sinon.
	 */
	static public function prepare_texte(&$liste_option) {
		$flag_texte = "";
		if ($liste_option->verifie_option_existe ( "email_corp_text", true )) {
			$texte = $liste_option->getOption ( "email_corp_text" );
			abstract_log::onDebug_standard ( $texte, 1 );
			if (trim ( $texte ) != "") {
				$flag_texte = $texte;
			}
		} elseif ($liste_option->verifie_option_existe ( "fichier_corp_text", true )) {
			$nom_fichier = $liste_option->getOption ( "fichier_corp_text" );
			if (fichier::tester_fichier_existe ( $nom_fichier )) {
				$data = file_get_contents ( $nom_fichier, FILE_TEXT );
				abstract_log::onDebug_standard ( $data, 1 );
				$flag_texte = $data;
			} else
				abstract_log::onError_standard ( "Le fichier " . $nom_fichier . " n'existe pas", "" );
		}
		
		return $flag_texte;
	}
	
	/**
	 * Prepare le corp au format HTML du mail.
	 *
	 * @param options &$liste_option Pointeur sur les arguments.
	 * @return String le texte, "" sinon.
	 */
	static public function prepare_html(&$liste_option) {
		$flag_texte = "";
		if ($liste_option->verifie_option_existe ( "email_corp_html", true )) {
			$flag_texte = $liste_option->getOption ( "email_corp_html" );
		} elseif ($liste_option->verifie_option_existe ( "fichier_corp_html", true )) {
			$nom_fichier = $liste_option->getOption ( "fichier_corp_html" );
			if (fichier::tester_fichier_existe ( $nom_fichier )) {
				$data = file_get_contents ( $nom_fichier, FILE_TEXT );
				abstract_log::onDebug_standard ( $data, 1 );
				$flag_texte = $data;
			} else
				abstract_log::onError_standard ( "Le fichier " . $nom_fichier . " n'existe pas", "" );
		}
		
		return $flag_texte;
	}
}
?>
