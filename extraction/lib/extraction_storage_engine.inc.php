<?php
/**
 * @author dvargas
 * @package Extraction
*/
/**
 * Extrait des donnees via le storage engine.
 *
 * @param options &$liste_option Pointeur sur les arguments.
 * @param string $sql Requete sql a appliquer sur le storage.
 * @param string $serial Serial a extraire.
 * @param string $date Date a extraire.
 * @return array|false Tableau de donnees extraitees, FALSE sinon.
*/
function recupere_donnees(&$liste_option,$sql,$serial,$date)
{
	$donnees_resultat=array();
	$type="day";
	//Pour chaque dates on recupere les donnees
	if($liste_option->getOption("cumul_month")!==false) $type="month";
	if($liste_option->getOption("cumul_week")!==false) $type="week";
        $connexion_databases=fonctions_standards_sgbd::creer_connexion_liste_option($liste_option);
        $connexion=fonctions_standards_sgbd::recupere_db_database($connexion_databases,true);
	if($connexion)
	{
		abstract_log::onDebug_standard("La connexion est creer, on applique la(es) requete(s).",1);
		abstract_log::onDebug_standard($connexion,2);
		if(is_array($sql))
		{
			foreach($sql as $requete)
			{
				$connexion->ajouter_requete($requete);
				$connexion->storage_engine_parse_sql($serial,$date,$type);
				abstract_log::onDebug_standard("Requete sur la base :",1);
				abstract_log::onDebug_standard($connexion->requete,1);
				$donnees_tempo=requete($connexion->requete,$connexion);
				$donnees_resultat=merge_tableau($donnees_resultat,$donnees_tempo);
			}
		} else {
			$connexion->ajouter_requete($sql);
			$connexion->storage_engine_parse_sql($serial,$date,$type);
			abstract_log::onDebug_standard("Requete sur la base :",1);
			abstract_log::onDebug_standard($connexion->requete,1);
			$donnees_resultat[0]=requete($connexion->requete,$connexion);
		}
		$connexion->close_storage();
	} else {
		abstract_log::onWarning_standard("Le connexion au storage engine n'existe pas.");
		$donnees_resultat=false;
	}
	abstract_log::onDebug_standard("Resultat des requetes sur la base :",1);
	abstract_log::onDebug_standard($donnees_resultat,1);

	return $donnees_resultat;

}

?>
