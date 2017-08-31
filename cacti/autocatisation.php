#!/usr/bin/php
<?php
/**
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package Steria
 * @subpackage Cacti
 */
// Specifiquement pour cacti, on a des INCLUDE qui permettent de charger les APIs de Cacti
$INCLUDE_CACTI_DEVICE = true;
$INCLUDE_CACTI_ADDTREE = true;
$rep_document = dirname ( $argv [0] ) . "/../../..";
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

/**
 *
 * @ignore Affiche le help.<br>
 *         Cette fonction fait un exit.
 *         Arguments reconnus :<br>
 *         --help
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
	$help [$fichier] ["text"] [] .= "automatiser.php Script 1.0, specifique Steria pour Cacti \n\n";
	$help [$fichier] ["text"] [] .= "\t--xymon_ip IP du xymon de reference";
	$help [$fichier] ["text"] [] .= "\t--port Port du xymon de reference";
	
	$class_utilisees = array (
			"fichier",
			"cacti_addDevice",
			"sgbd" 
	);
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}

// Cette fonction fait un exit 0
if ($liste_option->verifie_option_existe ( "help" ))
	help ();
	
	// Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );
/**
 * ******** VOTRE CODE A PARTIR D'ICI*********
 */
$continue = true;

if ($liste_option->verifie_variable_standard ( "xymon_ip" ) === false) {
	$continue = false;
	abstract_log::onError_standard ( "Il faut l'adresse IP de Xymon", "", 2001 );
} else {
	$xymon = $liste_option->renvoie_variables_standard (  "xymon_ip" );
}

$xymon_port = $liste_option->renvoie_variables_standard ( "xymon_port", "1985" );
$xymon_path = $liste_option->renvoie_variables_standard ( "xymon_path", "/home/xymon" );
$xymon_rrd_path = $liste_option->renvoie_variables_standard ( "xymon_rrd_path", $xymon_path . "/data/rrd" );
$xymon_bin = $liste_option->renvoie_variables_standard ( "xymon_bin", $xymon_path . "/server/bin/xymon" );
$xymon_hosts = $liste_option->renvoie_variables_standard ( "xymon_hosts", $xymon_path . "/server/etc/hosts.cfg" );

if ($liste_option->verifie_variable_standard ( "cacti_mut_ip" ) === false) {
	$continue = false;
	abstract_log::onError_standard ( "Il faut l'adresse IP de Cacti", "", 2002 );
} else {
	$cacti_hostname = $liste_option->renvoie_variables_standard ( "cacti_mut_hostname" );
}

$cacti_device = cacti_addDevice::creer_cacti_addDevice ( $liste_option, false );
$cacti_tree = cacti_addTree::creer_cacti_addTree ( $liste_option, false );

$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
$db_cacti = fonctions_standards_sgbd::recupere_db_cacti ( $connexion_db );

if (! fichier::tester_fichier_existe ( $xymon_path . "/server/bin/steria_xymongrep" )) {
	abstract_log::onError_standard ( "le Fichier " . $xymon_path_home . "/server/bin/steria_xymongrep doit etre present sur la machine. Merci de le rendre disponible", "", 2003 );
	$continue = false;
}
if (! fichier::tester_fichier_existe ( $xymon_path . "/server/etc/cacti_xymonserver.cfg" )) {
	abstract_log::onError_standard ( "le Fichier " . $xymon_path_home . "/server/etc/cacti_xymonserver.cfg doit etre present sur la machine. Merci de le rendre disponible", "", 2004 );
	$continue = false;
}
if (! fichier::tester_fichier_existe ( $xymon_hosts )) {
	abstract_log::onError_standard ( "le Fichier " . $xymon_hosts . " doit etre present sur la machine. Merci de le rendre disponible", "", 2005 );
	$continue = false;
}

if ($continue) {
	$lstHosts = array ();
	//On va chercher la liste de machine a afficher dans le cacti desire
	$CMD = $xymon_path . '/server/bin/steria_xymongrep --env=' . $xymon_path . '/server/etc/cacti_xymonserver.cfg --loadhostsfromxymond CACTI:' . $cacti_hostname;
	$liste_donnees = fonctions_standards::applique_commande_systeme ( $CMD );
	if ($liste_donnees [0] == 0) {
		array_shift ( $liste_donnees );
		
		// On creer le fichier Xymon local
		$fichier_hosts_xymon = fichier::creer_fichier ( $liste_option, $xymon_hosts );
		$fichier_hosts_xymon->ouvrir ( "w" );
		
		// Entete du fichier (constante)
		$fichier_hosts_xymon->ecrit ( '0.0.0.0         .default.               # notrends badconn:1:2:3\n#\n# Master configuration file for Xymon\n#\n# This file defines several things:\n#\n# 1) By adding hosts to this file, you define hosts that are monitored by Xymon\n# 2) By adding \"page\", \"subpage\", \"group\" definitions, you define the layout\n#    of the Xymon webpages, and how hosts are divided among the various webpages\n#    that Xymon generates.\n# 3) Several other definitions can be done for each host, see the hosts.cfg(5)\n#    man-page.\n#\n# You need to define at least the Xymon server itself here.\n\n' );
		
		// On ajoute la liste de machines renvoye par Xymon
		foreach ( $liste_donnees as $ligne ) {
			// "1.2.3.4 toto #CACTI:lol","9.8.7.6 tata #CACTI:lol"
			$liste = explode ( " ", $ligne );
			if ($liste === false) {
				abstract_log::onError_standard ( "Erreur durant l'explode", "", 2006 );
				abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );
				exit ( $fichier_log->renvoie_exit () );
			}
			
			$fichier_hosts_xymon->ecrit ( $ligne . "\n" );
			$lstHosts [] .= $liste [1];
		}
		
		$fichier_hosts_xymon->close ();
	}
	
	// Liste des hosts
	abstract_log::onDebug_standard ( $lstHosts, 2 );
	foreach ( $lstHosts as $hostname ) {
		$cacti_device->setDescription ( $hostname );
		$cacti_device->setIp ( $hostname );
		// On ajoute la definition des machines dans cacti
		

		if (! $cacti_device->valide_host_description ()) {
			// Si le host description n'existe pas, on l'ajoute
			$cacti_device->setAvailability ( 'none' );
			$cacti_device->setSnmpVersion ( 1 );
			
			abstract_log::onDebug_standard ( "Ajout de " . $cacti_device->getDescription (), 1 );
			if ($cacti_device->executeCacti_AddDevice ()) {
				abstract_log::onInfo_standard ( "Machine ajoutee : " . $cacti_device->getDescription () );
			} else {
				abstract_log::onError_standard ( "Erreur durant l'ajout de " . $cacti_device->getDescription (), "", 2007 );
			}
		}
		abstract_log::onDebug_standard ( "Machine en cours : " . $cacti_device->getDescription () . " Device-Id : " . $cacti_device->getHostId (), 1 );
		
		// #######################################
		// Creer l'ARBORESCENCE dans cacti #
		// #######################################
		

		$CMD = $xymon_bin . " " . $xymon_ip . ":" . $xymon_port . " 'xymondboard host=" . $hostname . " test=^info fields=XMH_ALLPAGEPATHS'";
		$liste_donnees = fonctions_standards::applique_commande_systeme ( $CMD );
		if ($liste_donnees [0] == 0) {
			// Decouper par ","
			$lstPaths = explode ( ",", $liste_donnees [1] );
			if ($lstPaths) {
				foreach ( $lstPaths as $path ) {
					
					abstract_log::onDebug_standard ( "Path en cours : " . $path, 1 );
					
					// Decouper par "/"
					$lstArbos = explode ( "/", $path );
					// Prendre le 1er element ($tree) qui est le "tree"
					$cacti_tree->setName ( $lstArbos [0] );
					
					if (! $cacti_tree->valide_tree_name ()) {
						// #######################################
						// Creer le TREE dans cacti #
						// #######################################
						

						$cacti_tree->setType ( 'tree' );
						$tree_id = $cacti_tree->executeCacti_addTree ();
					}
					
					array_shift ( $lstArbos );
					
					$cacti_tree->setType ( 'node' );
					$cacti_tree->setNodeType ( 'header' );
					$cacti_tree->setParentNode ( 0 );
					foreach ( $lstArbos as $node ) {
						
						// #######################################
						// Creer le NODE dans cacti #
						// #######################################
						$cacti_tree->setName ( $node );
						$parent_node_id = $cacti_tree->executeCacti_addTree ();
						$cacti_tree->setParentNode ( $parent_node_id );
					}
					
					// #######################################
					// Affecter le DEVICE dans le node #
					// #######################################
					$cacti_tree->setType ( 'node' );
					$cacti_tree->setNodeType ( 'host' );
					$cacti_tree->setHostId ( $cacti_device->getHostId () );
					
					// #######################################
					// Lister les TESTS executes sur le host #
					// #######################################
					

					$lstTests = array ();
					// xec("cd $rrd_path/$hostname");
					exec ( "ls -1 $rrd_path/$hostname/*.rrd", $lstTests );
					
					if ($debug) {
						// echo "exec('ls -1 *.rrd' , $lstTests)";
						echo "\t Repertoire du host : $rrd_path/$hostname \n";
						echo "\t liste des RRDs : ";
						var_dump ( $lstTests );
					}
					// Pour chaques TESTS
					foreach ( $lstTests as $test ) {
						if ($debug) {
							echo "test = $test \n";
						}
						
						// retirer le path
						$test_sans_path = explode ( "$rrd_path/$hostname/", $test );
						
						// Retirer le ".rrd"
						$test = substr ( $test_sans_path [1], 0, - 4 );
						
						if ($debug) {
							echo "\t test sans path = $test_sans_path[1] \n";
							echo "\t test sans rrd = $test \n";
						}
						
						// Connaitre le nom du test pour affecter au graph son graph template
						$testname1 = explode ( ",", $test );
						$testname2 = $testname1 [0];
						$testname3 = explode ( ".", $testname2 );
						$testname = $testname3 [0];
						
						$suite_nom = '';
						if (count ( $testname1 ) > 1) {
							$suite_nom = str_replace ( "${testname2},", "", $test );
						}
						if (count ( $testname3 ) > 1) {
							$suite_nom = str_replace ( "${testname}.", "", $test );
						}
						
						if ($debug) {
							echo "\t testname = $testname \n";
						}
						
						// #######################################
						// Creer les GRAPH_TEMPLATE dans cacti # a faire dans la V2
						// #######################################
						

						$lst_ret_graph_template = null;
						// Recuperation de l'ID du graph_template
						exec ( "php /cacti/cacti/cli/add_graphs.php --list-graph-templates | grep -i 'xymon - $testname'", $lst_ret_graph_template );
						
						if ($debug) {
							echo "\t lst_ret_graph_template : ";
							var_dump ( $lst_ret_graph_template );
						}
						
						if (count ( $lst_ret_graph_template ) === 0) {
							exec ( "echo 'il n y a pas de template pour le test : $test - pour le device : $hostname (id : $host_id)' >> $erreur_log" );
							continue;
						}
						
						foreach ( $lst_ret_graph_template as $ret_graph_template ) {
							if (trim ( $ret_graph_template ) == '') {
								exec ( "echo 'il n y a pas de template pour le test : $test - pour le device : $hostname (id : $host_id)' >> $erreur_log" );
								continue;
							}
							$ret_graph_template = explode ( "\t", $ret_graph_template );
							
							$graph_template_id = $ret_graph_template [0];
							$graph_template_name = $ret_graph_template [1];
							
							if (strpos ( $graph_template_name, "Xymon - " ) !== false) {
								$graph_name = str_replace ( "Xymon - ", "", $graph_template_name );
							} elseif (strpos ( $graph_template_name, "xymon - " ) !== false) {
								$graph_name = str_replace ( "xymon - ", "", $graph_template_name );
							} else {
								exec ( "echo 'Probleme de nommage du graph template : $graph_template_name (id : $graph_template_id) - pour le device : $hostname (id : $host_id) qui doit etre prefixe par \"Xymon - <mon_test>\"' >> $erreur_log" );
								continue;
							}
							
							if ($debug) {
								echo "\t graph_template_id : $graph_template_id \n \t graph_name : $graph_name \n";
							}
							
							// Verification que le graphe existe
							// mysql_connect($database_hostname,$database_username,$database_password);
							// mysql_select_db($database_default) or die("Unable to select database");
							$query = "select id from graph_templates_graph where title = '$hostname - $graph_name $suite_nom';";
							$graph_exist = mysql_query ( $query );
							// mysql_close();
							

							if (! mysql_num_rows ( $graph_exist )) {
								echo "\t GRAPH EXISTE PAS !!!! \t\t HOST : $hostname \t GRAPHE : $graph_name \n";
							} else {
								echo "\t GRAPH EXISTE \t\t HOST : $hostname \t GRAPHE : $graph_name \n";
								continue;
							}
							
							// Creation du graphe
							if ($debug) {
								echo "\t php /cacti/cacti/cli/add_graphs.php --graph-type=cg --graph-template-id=$graph_template_id --host-id=$host_id --graph-title='$hostname - $graph_name $suite_nom' --force \n";
							}
							$ret_create_graph = exec ( "php /cacti/cacti/cli/add_graphs.php --graph-type=cg --graph-template-id=$graph_template_id --host-id=$host_id --graph-title='$hostname - $graph_name $suite_nom' --force" );
							
							// code retourne : "Graph Added - graph-id: (36) - data-source-id: (43)"
							// ou code retourne : "Graph Added - graph-id: (36) - data-source-ids: (43,44)
							

							if ($debug) {
								echo "\t host name : " . $hostname . "\n";
								echo "\t ret_create_graph : $ret_create_graph\n";
							}
							
							// recuperation de l'ID du datasource cree
							if (strpos ( $ret_create_graph, " - data-source-ids: (" ) === false) {
								$ret_create_graph1 = explode ( " - data-source-id: (", $ret_create_graph );
							} else {
								$ret_create_graph1 = explode ( " - data-source-ids: (", $ret_create_graph );
							}
							$data_source_id = substr ( $ret_create_graph1 [1], 0, - 1 );
							
							if ($debug) {
								echo "\t ret_create_graph1 : ";
								var_dump ( $ret_create_graph1 );
								echo "\t data_source_id : " . $data_source_id . "\n\n";
							}
							
							$ret_graph_id = explode ( "graph-id: (", $ret_create_graph1 [0] );
							$graph_id = substr ( $ret_graph_id [1], 0, - 1 );
							
							if ($debug) {
								echo "\t graph_id : " . $graph_id . "\n";
							}
							
							// equete sql d'update pour y mettre le path du rrd dans le datasource
							// nclude(dirname(__FILE__) . "/cacti/include/global.php");
							

							// ysql_connect($database_hostname,$database_username,$database_password);
							// mysql_select_db($database_default) or die("Unable to select database");
							$query = "UPDATE data_template_data set name = '$hostname - $graph_name $suite_nom', name_cache = '$hostname - $graph_name $suite_nom', data_source_path = '$rrd_path/$hostname/$test.rrd' WHERE local_data_id in ($data_source_id)";
							mysql_query ( $query );
							// ysql_close();
						}
					}
				}
			}
		}
	} // Liste des hosts
}

/**
 * ********* FIN DE VOTRE CODE ***************
 */
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoie_exit () );
?>

