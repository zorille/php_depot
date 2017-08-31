#!/usr/bin/php
<?php
/**
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package Package
 * @subpackage SubPackage
 */

//Deplacement pour joindre le repertoire lib
$deplacement = "/../..";
$rep_document = dirname ( $argv [0] ) . $deplacement;

/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require $rep_document . "/php_outils/aws/aws-autoloader.php";
require_once $rep_document . "/php_framework/config.php";

use Aws\CloudWatch\CloudWatchClient;
use Aws\Ec2\Ec2Client;


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
			$fichier => array () 
	);
	$help [$fichier] ["text"] = array ();
	$help [$fichier]["text"] [] .= "Connecte le CloudWatch de AWS avec Zabbix (EN COURS DE DEV)";
	$help [$fichier]["text"] [] .= "\t--AWS_nom Nom de AWS a connecter dans le fichier de conf";
	$help [$fichier]["text"] [] .= "\t--AWS_Access_Key Access key for your AWS account";
	$help [$fichier]["text"] [] .= "\t--AWS_Secret_Access_Key Secret key to your AWS account's access key";
	$help [$fichier]["text"] [] .= "\t--fichier_data nom du fichier data de Zabbix";
	$help [$fichier]["text"] [] .= "\t--zabbix_CI nom du CI Zabbix";
	
	$class_utilisees = array ( 
			"aws_datas",
			"aws_wsclient"
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
 * Main programme
 * Code retour en 2xxx en cas d'erreur
 * @ignore
 *
 * @param options $liste_option
 * @param logs $fichier_log
 * @return boolean
*/
function principale(&$liste_option, &$fichier_log) {
	if ($liste_option->verifie_option_existe ( "AWS_nom" ) === false) {
		return abstract_log::onError_standard ( "Il faut un --AWS_nom" );
	}
	if ($liste_option->verifie_option_existe ( "zabbix_CI" ) === false) {
		return abstract_log::onError_standard ( "Il faut un --zabbix_CI" );
	}
	if ($liste_option->verifie_option_existe ( "fichier_data" ) === false) {
		$liste_option->setOption ( "fichier_data", "/tmp/" . $liste_option->getOption ( "zabbix_CI" ) . "_AWS.data" );
	}
	if ($liste_option->verifie_option_existe ( "AWS_Access_Key" ) === false) {
		return abstract_log::onError_standard ( "Il faut un --AWS_Access_Key Access key" );
	}
	if ($liste_option->verifie_option_existe ( "AWS_Secret_Access_Key" ) === false) {
		return abstract_log::onError_standard ( "Il faut un --AWS_Secret_Access_Key" );
	}
	
	/*$cw =  Ec2Client::factory ( array (
			'key' => $liste_option->getOption ( "AWS_Access_Key" ),
			'secret' => $liste_option->getOption ( "AWS_Secret_Access_Key" ),
			'region' => 'us-west-1' 
	) );
	
	$resultat = $cw->describeInstances ( array () );*/
	
	$cw = CloudWatchClient::factory ( array (
			'key' => $liste_option->getOption ( "AWS_Access_Key" ),
			'secret' => $liste_option->getOption ( "AWS_Secret_Access_Key" ),
			'region' => 'us-west-1' 
	) );
	/*$resultat = $cw->listMetrics ( array (
			'Namespace' => 'AWS/Billing' 
	) );*/
	//$resultat = $cw->DescribeAlarms ( array () );
	$resultat = $cw->getMetricStatistics ( array (
			'Namespace' => 'AWS/EC2',
			'MetricName' => 'CPUUtilization',
			'StartTime' => '1 January 2015',
			'EndTime' => '31 January 2015',
			'Period' => 1800,
			'Statistics' => array (
					'Average',
					'SampleCount'
			)
	) );
	abstract_log::onDebug_standard ( $resultat->toArray(), 1 );
	abstract_log::onDebug_standard ( $resultat->getKeys(), 1 );
	abstract_log::onInfo_standard ( "Fichier de sortie : " . $liste_option->getOption ( "fichier_data" ) );
	/*$fichier_data = fichier::creer_fichier ( $liste_option, $liste_option->getOption ( "fichier_data" ), "oui" );
	$fichier_data->ouvrir ( "w" );
	
	foreach ( $resultat as $ligne ) {
		$fichier_data->ecrit ( $liste_option->getOption ( "zabbix_CI" ) . " " . $ligne . "\n" );
	}
	$command_version = "1.3";
	// Get data collection end time (we will use this to compute the total data collection time)
	$end_time = time ();
	$data_collection_time = $end_time - $start_time;
	$fichier_data->ecrit ( $liste_option->getOption ( "zabbix_CI" ) . " EC2_Plugin_Data_collection_time " . $data_collection_time . "\n" );
	$fichier_data->ecrit ( $liste_option->getOption ( "zabbix_CI" ) . " EC2_Plugin_Version " . $command_version . "\n" );
	$fichier_data->ecrit ( $liste_option->getOption ( "zabbix_CI" ) . " EC2_Plugin_Checksum " . $md5_checksum_string . "\n" );
	$fichier_data->close ();
	
	fonctions_standards::applique_commande_systeme ( "/usr/bin/zabbix_sender -vv -z 127.0.0.1 -i " . $liste_option->getOption ( "fichier_data" ) . " 2>&1", false );
	*/
	return true;
}

principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );

exit ( $fichier_log->renvoiExit () );
?>
