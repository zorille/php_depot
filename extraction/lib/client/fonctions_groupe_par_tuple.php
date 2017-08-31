<?php
/**
 * @author dvargas
 * @package Extraction
 * @subpackage Client
 */

/**
 * @ignore
 * Permet de groupe les donnees extraites sur un champ maitre.
 * il faut ajouter : <champ_maitre>nom_du_champ</champ_maitre> dans la balise requetelist.<br>
 * il faut ajouter : <sous_champ>nom_du_champ</sous_champ> dans la balise requetelist.<br>
 *
 * @param options &$liste_option Pointeur sur les arguments.
 * @param array $liste_serial Liste des serial a traiter.
 * @param dates $liste_dates Date des donnees a extraire.
 * @param array $donnee_extraites Tableau des donnees extraites.
 * @return array|false Tableau de donnees extraitees, FALSE sinon.
 */
function groupe_par_champ(&$liste_option,$liste_serial,$liste_dates,$donnee_extraites)
{
	$champ_maitre=$liste_option->getOption(array("sql","requetelist","champ_maitre"),true);
	$sous_champ=$liste_option->getOption(array("sql","requetelist","sous_champ"),true);
	//Il faut un champ de filtrage
	if($champ_maitre!==false && $sous_champ!==false && $donnee_extraites)
	{

		foreach($liste_serial as $serial)
		{
			foreach($liste_dates->getListeDates() as $date)
			{
				$donnee_travaille=array();
				if(isset($donnee_extraites[$serial][$date][$champ_maitre]) && is_array($donnee_extraites[$serial][$date][$champ_maitre]))
				{
					if(is_array($sous_champ))
					{
						foreach($sous_champ as $champ)
						{
							for($i=0;$i<count($donnee_extraites[$serial][$date][$champ_maitre]);$i++)
							$donnee_travaille[$champ."_".$donnee_extraites[$serial][$date][$champ_maitre][$i]]=$donnee_extraites[$serial][$date][$champ][$i];
						}
					} else {
						for($i=0;$i<count($donnee_extraites[$serial][$date][$champ_maitre]);$i++) {
							$donnee_travaille[$sous_champ."_".$donnee_extraites[$serial][$date][$champ_maitre][$i]]=$donnee_extraites[$serial][$date][$sous_champ][$i];
						}
					}

					$donnee_extraites[$serial][$date]=$donnee_travaille;
				}
			}
		}

	}

	return $donnee_extraites;
}

/**
 * @ignore
 * Verifie si un champ existe.
 *
 * @param array $tableau Tableau d'algorithme.
 * @param string $champ Champ a retrouver.
 * @param array $type Type de champ.
 * @return string|false Nom Algo ou false s'il n'existe pas.
 */
function verifie_champ($tableau,$champ,$type)
{
	$CODE_RETOUR=false;
	for($i=0;$i<count($tableau);$i++)
	{
		if(isset($tableau["algo".$i]) && isset($tableau["algo".$i]["champ"]) && $tableau["algo".$i]["champ"]==$champ && $tableau["algo".$i]["type"]==$type)
		{
			$CODE_RETOUR="algo".$i;
			break;
		}
	}
	return $CODE_RETOUR;
}

/**
 * @ignore
 * Permet de construire l'algorithme en fonction du champ maitre.
 *
 * @param options &$liste_option Pointeur sur les arguments.
 * @param array $donnee_extraites Tableau des donnees extraites.
 * @return true
 */
function retrouve_algo(&$liste_option,$donnee_extraites)
{
	$i=0;
	$new_algo=array();
	$liste_algo=$liste_option->getOption("algorythme",true);

	if(is_array($liste_algo))
	{
		foreach($liste_algo as $nom_algo=>$algo)
		{
			if($nom_algo=="algo_serials" || $nom_algo=="algo_dates" )
			{
				$new_algo[$nom_algo]=$algo;
				continue;
			}
				
			$operateur=$algo["operateur"];

			if(isset($type) && $type!=$algo["type"])
			{
				if(!isset($compteur)) $compteur=$i;
				$type=$algo["type"];
				for($j=0;$j<$compteur;$j++)
				{
					$new_algo["algo".$i]["champ"]="algo".$j;
					$new_algo["algo".$i]["type"]=$type;
					$new_algo["algo".$i]["operateur"]=$operateur;
					$i++;
				}
			} else {
				$type=$algo["type"];
				foreach($donnee_extraites as $serial=>$liste_dates)
				{
					foreach($liste_dates as $date=>$donnees)
					{
						if(is_array($donnees)){
							foreach($donnees as $champ=>$data_finale)
							{
								$champ_en_cours=verifie_champ($new_algo,$champ,$type);
								if($champ_en_cours===false)
								{
									$new_algo["algo".$i]["champ"]=$champ;
									$new_algo["algo".$i]["type"]=$type;
									$new_algo["algo".$i]["operateur"]=$operateur;
									$i++;
								}
							}
						}
					}
				}
			}
		}

	}

	//Puis on creer l'ordre de sortie
	$ordre_sortie=array();
	if(isset($compteur))
	{
		for($j=$compteur;$j>0;$j--)
		{
			$tempo=$compteur-$j;
			$ordre_sortie["titre"][].=$new_algo["algo".$tempo]["champ"];
			$tempo=$i-$j;
			$ordre_sortie["nom_algo"][].="algo".$tempo;
		}
	} else {
		foreach($new_algo as $nom_algo=>$algo)
		{
			if($nom_algo=="algo_serials" || $nom_algo=="algo_dates" ) continue;
			$ordre_sortie["titre"][].=$algo["champ"];
			$ordre_sortie["nom_algo"][].=$nom_algo;
		}
	}

	$liste_option->setOption("algorythme",$new_algo);
	$liste_option->setOption(array("ordre_de_sortie_tempo","champ"),$ordre_sortie);

	return true;
}

/**
 * @ignore
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
	} else $donnee_extraites=false;

	if($donnee_extraites)
	{
		$donnee_extraites=groupe_par_champ($liste_option,$liste_serial,$liste_dates,$donnee_extraites);
		retrouve_algo($liste_option,$donnee_extraites);
	}

	$fichier->verbose("Donnees extraitees :",1);
	$fichier->verbose($donnee_extraites,1);

	return $donnee_extraites;
}

/**
 * @ignore
 * Permet de retrouver le nom du champ principal.
 *
 * @param string $champ Nom du champ recherche.
 * @param array $tableau Tableau de resultat.
 * @return int Position du champ.
 */
function retrouve_position_champ_maitre($champ,$tableau)
{
	$position=0;
	if(is_array($tableau))
	{
		foreach($tableau as $pos=>$value)
		{
			if($value==$champ)
			{
				$position=$pos;
				break;
			} else $position++;
		}
	}

	return $position;
}

/**
 * @ignore
 * Permet de retrouver les libelles du resultat.
 *
 * @param string $titre Nom du libelle recherche.
 * @param array $liste_champ Tableau de champ de depart.
 * @return array Tableau de libelle.
 */
function retrouve_libelle($titre,$liste_champ)
{
	$tableau=array();
	foreach($liste_champ as $champ)
	{
		$tempo=explode($champ,$titre);
		if(count($tempo)==2)
		{
			$tableau[0]=substr($tempo[1],1);
			$tableau[1]=$champ;
			break;
		}
	}

	return $tableau;
}

/**
 * @ignore
 * Permet de retrouver les resultats pour chaques libelles.
 *
 * @param options &$liste_option Pointeur sur les arguments.
 * @param array $donnees_algo Tableau des algorithmes.
 * @return array Tableau de champ pour chaque libelle.
 */
function retrouve_champs(&$liste_option,$donnees_algo)
{
	$tableau=array();
	$champ_maitre=$liste_option->getOption(array("sql","requetelist","champ_maitre"),true);
	$sous_champ=$liste_option->getOption(array("sql","requetelist","sous_champ"),true);
	$liste_titre=$liste_option->getOption(array("ordre_de_sortie_tempo","champ","titre"),true);

	if(is_array($sous_champ)) {
		$sous_champ=$sous_champ;
	} else {
		$sous_champ[0]=$sous_champ;
	}

	$compteur=count($liste_titre);

	//Pour chaque champ de ordre_de_sortie
	for($i=0;$i<$compteur;$i++)
	{
		$titre=$liste_titre[$i];
		$algo=$liste_option->getOption(array("ordre_de_sortie_tempo","champ","nom_algo",$i));
		$libelle=retrouve_libelle($titre,$sous_champ);
		$valeur_algo=$donnees_algo[$algo];

		if(count($libelle)==2)
		{
			$position=retrouve_position_champ_maitre($libelle[0],$tableau[$champ_maitre]);
			$tableau[$champ_maitre][$position]=$libelle[0];
			$tableau[$libelle[1]][$position]=$valeur_algo;
		}

	}
	return $tableau;
}

/**
 * @ignore
 * Permet de retrouver le tableau au format standard.
 *
 * @param options &$liste_option Pointeur sur les arguments.
 * @param array $donnees_resultat Tableau des resultats.
 * @return array Tableau re-organise.
 */
function retrouve_tableau_depart(&$liste_option,$donnees_resultat)
{
	$donnee_finale=array();

	if($liste_option->verifie_option_existe(array("sql","requetelist","champ_maitre"),true)!==false && 
	$liste_option->verifie_option_existe(array("sql","requetelist","sous_champ"),true))
	{
		foreach($donnees_resultat as $serial => $donnees_par_date)
		{
			$donnee_finale[$serial]=array();
			foreach($donnees_par_date as $date => $donnees_algo)
			{
				$donnee_finale[$serial][$date]=array();
				//On doit se servir de l'ordre de sortie pour recreer le tableau final
				$sous_champ=retrouve_champs($liste_option,$donnees_algo);
				$donnee_finale[$serial][$date]=$sous_champ;
			}
		}
	}


	return $donnee_finale;
}

/**
 * @ignore
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
 * @param array &$donnees_resultat Liste des resultats calcules par la class algorithme.
 * @return array Tableau de donnees preparees a l'enregistrement.
 */
function prepare_donnees(&$liste_option,&$donnees_resultat)
{
	$tableau_valeur=array();
	$entete=creer_entete_csv($liste_option);
	$donnees_resultat=retrouve_tableau_depart($liste_option,$donnees_resultat);
	$liste_nom_algo=$liste_option->getOption(array("ordre_de_sortie","champ","nom_algo"));
	$separateur=$liste_option->getOption(array("ordre_de_sortie","separateur"));

	foreach($donnees_resultat as $serial => $donnees_par_date)
	{
		$donnee_preparees[$serial][0]=$entete;
		foreach($donnees_par_date as $date => $donnees_algo)
		{
			$nb_max_champ=nombre_max_champ($donnees_algo,$liste_nom_algo);
			for($i=0;$i<$nb_max_champ;$i++)
			{
				$ligne="";
				//Ajout de la date dans la ligne
				if(check_report_date($liste_option)) $ligne=$date;
				//on creer une table de hash avec les valeurs a la case $i
				foreach($donnees_algo as $libelle => $valeur)
				{
					if(is_array($valeur))
					{
						if(isset($valeur[$i]))	$tableau_valeur[$libelle]=$valeur[$i];
						else $tableau_valeur[$libelle]=" ";
						//else $tableau_valeur[$libelle]=$valeur[sizeof($valeur)-1];
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
						if($nom_algo!="") $ligne.=hash_ligne($tableau_valeur[$nom_algo]);
					}
				} else {
					if($ligne!="") $ligne.=$separateur;
					$ligne.=hash_ligne($tableau_valeur[$liste_nom_algo]);
				}
				$donnee_preparees[$serial][].=$ligne;
			}
		}
	}
	return $donnee_preparees;
}

?>