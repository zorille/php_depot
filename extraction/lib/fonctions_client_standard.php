<?php
/**
 * @author dvargas
 * @package Extraction
*/

/**
 * Permet d'extraire les donnees clientes de maniere standard.<br>
 * tableau renvoye :<br>
 * on charge les donnees clientes au format :<br>
 * array (<br>
 * [serial] (<br>
 * 	[date] (<br>
 * 		[champ](<br>
 * 			valeur1<br>
 * 			valeur2<br>
 * 			.<br>
 * 			.<br>
 * 			valeurn<br>
 * 			)<br>
 * 		)<br>
 * 		[champ2] ....<br>
 * 	)<br>
 * 	[date2] ...<br>
 * [serial2] ...<br>
 * )<br>
 * conditions : <br>
 * 	-le nombre de valeurs par champ doit etre identique.<br>
 * 	-le nombre de champs par date doit etre identique.
 *
 * @param options &$liste_option Pointeur sur les arguments.
 * @param array $liste_serial Liste des serial a traiter.
 * @param dates $liste_dates Date des donnees a extraire.
 * @return array|false Tableau de donnees extraitees, FALSE sinon.
*/
function extraire_donnees_sqlite(&$liste_option,$liste_serial,$liste_dates)
{
	$liste_requete=$liste_option->getOption(array("sql","requetelist"));
	if(is_array($liste_requete))
	{
		$requete=$liste_option->getOption(array("sql","requetelist","requete"));
		//Si le fichiers a extraire sont passes en argument, on les traitent directement
		if($liste_option->verifie_option_existe("fichier_entree",true)!==false)
		{
			$liste_fichier=explode(" ",$liste_option->getOption("fichier_entree"));
			foreach($liste_fichier as $sqlite)
			{
				if(is_array($requete)){
						$donnee_extraites[$sqlite]["today"]=recupere_donnees($liste_option,$requete,"","",$sqlite);
				} else {
					$donnee_extraites[$sqlite]["today"]=recupere_donnees($liste_option,$liste_requete,"","",$sqlite);
				}
			}
		} else {
			$liste_date_tempo=renvoi_liste_dates($liste_option,$liste_dates);
			foreach($liste_serial as $serial)
			{
				foreach($liste_date_tempo as $date)
				{
					if(is_array($requete)){
						$donnee_extraites[$serial][$date]=recupere_donnees($liste_option,$requete,$serial,$date);
					} else {
						$donnee_extraites[$serial][$date]=recupere_donnees($liste_option,$liste_requete,$serial,$date);
					}
				}
			}
		}
	} else $donnee_extraites=false;
	abstract_log::onDebug_standard("Donnees extraitees :",1);
	abstract_log::onDebug_standard($donnee_extraites,1);
	
	return $donnee_extraites;
}

/**
 * Prend des donnees calculees par la class algorithme et les 
 * met au format d'enregistrement standard.<br>
 * Tableau d'entree :<br>
 * array(<br>
 * [serial ou algo_serial]<br>
 * 	[date ou algo_date]<br>
 * 		[nom_algo]<br>
 *   .<br>
 *   .<br>
 * )<br>
 *<br>
 * tableau renvoye :<br>
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
 * @param array $donnees_resultat Liste des resultats calcules par la class algorithme.
 * @return array Tableau de donnees preparees a l'enregistrement.
*/
function prepare_donnees(&$liste_option,$donnees_resultat)
{
	$tableau_valeur=array();
	$donnee_preparees=array();
	$entete=creer_entete_csv($liste_option);

	if($liste_option->getOption("fichier[@ajouter_serial='oui']",true)!==false)
	{
		$entete="Serial".$liste_option->getOption(array("ordre_de_sortie","separateur")).$entete;
	}

	foreach($donnees_resultat as $serial => $donnees_par_date)
	{
		//On prepare le nom du fichier
		if($liste_option->getOption("fichier[@ajouter_serial='oui']",true)!==false) {
			$nom_fichier="unique";
		} else {
			$nom_fichier=$serial;
		}

		//On prepare le tableau
		//Si on ajoute dans la fichier, on ne met pas d'entete
		$donnee_preparees[$nom_fichier][0]=$entete;

		foreach($donnees_par_date as $date => $donnees_algo)
		{
			$liste_nom_algo=$liste_option->getOption(array("ordre_de_sortie","champ","nom_algo"));
			$separateur=$liste_option->getOption(array("ordre_de_sortie","separateur"));
			$nb_max_champ=nombre_max_champ($donnees_algo,$liste_nom_algo);
			for($i=0;$i<$nb_max_champ;$i++)
			{
				if($liste_option->getOption("fichier[@ajouter_serial='oui']",true)!==false) {
					$ligne=$serial;
				} else {
					$ligne="";
				}
				//Ajout de la date dans la ligne
				if(check_report_date($liste_option)) {
					if($ligne!="") {
						$ligne.=$separateur;
					}
					$ligne.=$date;
				}
				//on creer une table de hash avec les valeurs a la case $i
				foreach($donnees_algo as $libelle => $valeur)
				{
					if(is_array($valeur)) 
					{
						if(isset($valeur[$i]))	$tableau_valeur[$libelle]=$valeur[$i];
						else $tableau_valeur[$libelle]=" ";
					} elseif($i==0) $tableau_valeur[$libelle]=$valeur;
					else $tableau_valeur[$libelle]=" ";
				}

				//Pour chaque champ de ordre_de_sortie
				//on recupere le nom_algo et on le cherche dans les donnees algo pour l'afficher
				if(is_array($liste_nom_algo))
				{
					foreach($liste_nom_algo as $nom_algo)
					{
						if($ligne!="") $ligne.=$separateur;
						if($liste_option->verifie_option_existe("convertir_serial_en_nom")!==false && $nom_algo=="serial") {
							$tableau_valeur[$nom_algo]=convert_serial_to_nom($liste_option,$tableau_valeur[$nom_algo]);
						}
						if($nom_algo!="" && isset($tableau_valeur[$nom_algo])) {
							$ligne.=hash_ligne($tableau_valeur[$nom_algo]);
						} elseif($liste_option->verifie_option_existe(array("ordre_de_sortie","champ",$nom_algo))!==false)
							$ligne.=$liste_option->getOption(array("ordre_de_sortie","champ",$nom_algo));
					}

				} else {
					if($ligne!="") $ligne.=$separateur;
					$ligne.=hash_ligne($tableau_valeur[$liste_nom_algo]);
				}
				$donnee_preparees[$nom_fichier][].=$ligne;
			}
		}
	}
	return $donnee_preparees;
}

?>
