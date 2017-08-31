#!/usr/bin/php
<?php
/**
 * @author dvargas
 * @package Tools
 * @subpackage Wrapper
 */





$rep_document=dirname($argv[0])."/../../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document."/php_framework/config.php";

//Le fichier de log est cree
abstract_log::onInfo_standard("Heure de depart : ".date("d/m/Y H:i:s",time()));
/********** VOTRE CODE A PARTIR D'ICI**********/
if($liste_option->verifie_option_existe("command_workspace",true)!==false){
	$workspace = "cd ".$liste_option->getOption("command_workspace").";";
} else {
	$workspace="";
}

if($liste_option->verifie_option_existe("command_cmd",true)!==false){
	$command = $liste_option->getOption("command_cmd");
} else {
	abstract_log::onError_standard("Il faut un commande shell/C.");
	$command=false;
}

$base64=fonctions_standard_strings::creer_base64($liste_option);
if($base64===false){
	abstract_log::onError_standard("La class base64 n'est pas cree.");
}

if($base64 && $command){
	$commandLine=$workspace.$command;

	$liste_option_entree=$liste_option->getListeOption();
	foreach($liste_option_entree as $param=>$value){
		if(strpos($param,"_base64")!==false){
			abstract_log::onDebug_standard("Param : ".$param,2);
			abstract_log::onDebug_standard("value : ".$value,2);
			$commandLine=$commandLine." --".str_replace("_base64","",$param)." \"".str_replace("\"","",$base64->decode($value))."\"";
		}
	}
	abstract_log::onDebug_standard($commandLine,1);
	passthru($commandLine, $retval);
	$fichier_log->setExit($retval);
} else {

	$fichier_log->setExit(1);
}

/*********** FIN DE VOTRE CODE ****************/
abstract_log::onInfo_standard("Heure de fin : ".date("d/m/Y H:i:s",time()));


exit($fichier_log->renvoiExit());
?>
