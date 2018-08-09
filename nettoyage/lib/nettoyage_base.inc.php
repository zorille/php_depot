<?php
/**
 * Permet de Nettoyer des Bases de donnees.
 * @author dvargas
 * @package Nettoyage
*/

/**
 * @ignore
 * Affiche le help.<br>
 * Cette fonction fait un exit.
 * Arguments reconnus :<br>
 * --help
*/
function help()
{
	echo "

###################### Partie communes SYNCHRO BASE ############################

--conf=fichier de conf

Par fichier XML :
<sql using=\"oui\">
  <database>database</database>
  <dbhost>sophia-db1</dbhost>
  <dbuser>nobody</dbuser>
  <dbpasswd></dbpasswd>
</sql>

<tables>
 <table>
  <requete_entree>
   delete from table where id=1
  </requete_entree>
 </estaReport>
</tables>

###################### Partie communes SYNCHRO BASE ############################

		\n";
	fonctions_standards::help_fonctions_standard("oui");
	echo "[Exit]0\n";
	exit(0);
}

/**
 * Recupere la liste de requete a appliquer et l'applique.
 *
 * @param logs &$fichier Pointeur sur un objet logs pour l'affichage.
 * @param options &$liste_option Pointeur sur les arguments.
 * @param requete $connexion Connexion vers la base de donnee.
 * @return Bool TRUE si OK, FALSE sinon.
*/
function nettoie_table(&$fichier,&$liste_option,$connexion)
{
	$CODE_RETOUR=false;
	abstract_log::onInfo_standard("Le nettoyage demarre.");
	$tables=$liste_option->getOption("tables");
        if(is_array($connexion)){
            foreach($connexion as $local_db){
                $db=$local_db;
            }
        } else {
           $db=$connexion;
        }
	if($tables && is_array($tables))
	{
		foreach($tables as $table=>$donnees)
		{
			if(!isset($donnees["requete_entree"])) continue;
			abstract_log::onInfo_standard("On nettoie la table : ".$table);
			abstract_log::onDebug_standard("La requete a appliquer : ".$donnees["requete_entree"],1);
                        if(method_exists($db,"faire_requete")){
                        	try {
                        		$db->faire_requete($donnees["requete_entree"]);
                        	} catch ( Exception $e ) {
                        		abstract_log::onError_standard ( $e->getMessage (), "", $e->getCode () );
                        	}
                        } else {
                            abstract_log::onError_standard("Il n'y a pas de methode 'faire_requete'.",$db);
                        }

                        if(method_exists($db,"getDatabase")){
                            $CODE_RETOUR=$db->getDatabase();
                        }
		}
	} else abstract_log::onError_standard("Aucune table dans la liste des options.","");

	return $CODE_RETOUR;
}

?>
