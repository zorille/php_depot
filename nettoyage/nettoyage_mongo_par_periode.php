#!/usr/bin/php
<?php
/**
 * Permet de faire un nettoyage dans la mongodb.
 *
 * @author dvargas
 * @package nettoyage
 */

$rep_document=dirname($argv[0])."/../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document."/php_framework/config.php";

//Cette fonction fait un exit 0
if($liste_option->verifie_option_existe("help")) {
	fonctions_standards::help_fonctions_standard("oui",true,true);
	exit(0);
}

//Le fichier de log est cree
abstract_log::onInfo_standard("Heure de depart : ".date("d/m/Y H:i:s",time()));

$continue=true;

//On creer la liste des dates
$liste_dates=dates::creer_dates($liste_option);

//gestion de l'environnement
if($liste_option->verifie_option_existe("env")===false){
	$liste_option->setOption("env","preprod");
}

//gestion de la collection
if($liste_option->verifie_option_existe("collection")===false){
	abstract_log::onError_standard("Il faut une collection (--collection)");
	$fichier_log->setExit(1);
	$continue=false;
}

//gestion de la collection
if($liste_option->verifie_option_existe("champ_date")===false){
	abstract_log::onError_standard("Il faut un champ de la collection qui contient la date (--champ_date)");
	$fichier_log->setExit(1);
	$continue=false;
}

/******* Gestion des BASES de DONNEES ********/
$connexion=array();
$connexion=fonctions_standards_sgbd::creer_connexion_liste_option($liste_option);
$mongo=fonctions_standards_sgbd::recupere_db_mongodbAbstract($connexion,true);
/******* FIN de la Gestion des BASES de DONNEES ********/

if($continue && $liste_dates && $mongo){
	if(!method_exists($mongo,"get".$liste_option->getOption("collection")."Collection")){
		abstract_log::onError_standard("La collection ".$liste_option->getOption("collection")." est introuvable.");
		$fichier_log->setExit(1);
	} else {
		try {
			$collection=call_user_func_array(array($mongo, "get".$liste_option->getOption("collection")."Collection"), array());
			abstract_log::onInfo_standard("Collection : ".$collection. " |Date : ".$liste_dates->recupere_premier_jour()
					." |Champ date : ".$liste_option->getOption("champ_date"));
			$where=array();
			$mongo->fabrique_where($where, $liste_option->getOption("champ_date"),$liste_dates->recupere_premier_jour(),"date","<");
			abstract_log::onDebug_standard($where,1);
			$select=array();
			$resultat=$mongo->selectionner($select, $collection,$where);
			abstract_log::onInfo_standard("On s'apprete a nettoyer ".$resultat->count()." entrees dans la mongo.");
			
			//Si on valide la suppression, on nettoie les entrees dans la collection
			if($liste_option->verifie_option_existe("valide_suppression")!==false){
				abstract_log::onInfo_standard("On nettoie la collection : ".$collection);
				
				//On ne supprime pas les entrees
				$mongo->supprimer($collection, $where,false,false,false,90000);
			}
		} catch (MongoCursorException $e){
			abstract_log::onError_standard($e->getMessage());
		}
	}
}


abstract_log::onInfo_standard("Heure de fin : ".date("d/m/Y H:i:s",time()));

exit($fichier_log->renvoiExit());
?>
