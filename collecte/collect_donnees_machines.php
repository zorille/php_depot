#!/usr/bin/php
<?php
/**
 *
 * @ignore
 */
/**
 *
 * @author dvargas
 * @package collecte
 */
// Deplacement pour joindre le repertoire lib
$deplacement = "/../..";
$rep_document = dirname ( $argv [0] ) . $deplacement;
/**
 * Permet d'inclure toutes les librairies communes necessaires
 */
require_once $rep_document . "/php_framework/config.php";

/**
 * Librairies specifiques au programme
 */
require_once $liste_option ->getOption ( "rep_scripts" ) . "/lib/parse_collected_datas.class.php";
/**
 * Librairies specifiques au programme
 */
require_once $liste_option ->getOption ( "rep_scripts" ) . "/lib/collected_datas_to_sqlite.class.php";

/**
 *
 * @ignore Affiche le help.<br> Cette fonction fait un exit. Arguments reconnus :<br> --help
 */
function help() {
	$fichier = basename ( __FILE__ );
	$help = array ( 
			"usage" => array ( 
					$fichier . " --conf [fichiers de conf] [OPTIONS]", 
					$fichier . " --help" ), 
			"exemple" => array ( 
					"./" . $fichier . " --conf {Chemin vers conf_clients}/database/prod_CLIENT_sam_sqlite.xml --repertoire_fichiers ./liste_datas/ --verbose" ), 
			$fichier => array () );
	$help [$fichier] ["text"] = array ();
	$help [$fichier] ["text"] [] .= "Permet de charge les donnees recoltees par les commandes shell dans la base gestion_sam";
	$class_utilisees = array ( 
			"requete_complexe_gestion_sam" );
	$help = array_merge ( $help, fonctions_standards::help_fonctions_standard ( false, true, $class_utilisees ) );
	fonctions_standards::affichage_standard_help ( $help );
	echo "[Exit]0\n";
	exit ( 0 );
}
// Cette fonction fait un exit 0
if ($liste_option ->verifie_option_existe ( "help" ))
	help ();
	
	// Le fichier de log est cree
abstract_log::onInfo_standard ( "Heure de depart : " . date ( "d/m/Y H:i:s", time () ) );

function retrouve_commande(&$class_flux, $commande) {
	$row_donnees ["commande"] = $commande;
	abstract_log::onInfo_standard ( "Commande : " . $row_donnees ["commande"] );
	if (strpos ( $row_donnees ["commande"], "sudo" ) !== false) {
		$datas = $class_flux ->getConnexion () 
			->ssh_shell_commande ( $row_donnees ["commande"] );
		if (is_array ( $datas ) && isset ( $datas ["output"] )) {
			$row_donnees ["resultat"] = parse_tty ( $datas ["output"], $row_donnees ["commande"] );
		} else {
			$row_donnees ["resultat"] = "";
		}
	} else {
		$datas = $class_flux ->getConnexion () 
			->ssh_commande ( $row_donnees ["commande"] );
		if (is_array ( $datas ) && isset ( $datas ["output"] )) {
			$row_donnees ["resultat"] = $datas ["output"];
		} else {
			$row_donnees ["resultat"] = "";
		}
	}
	return array ( 
			$row_donnees );
}

function parse_tty($resultat, $commande) {
	$splitted_datas = explode ( "\n", $resultat );
	$flag = false;
	$counter = count ( $splitted_datas );
	for($i = 0; $i < $counter; $i ++) {
		if (strpos ( $splitted_datas [$i], " ~]$ " . substr ( $commande, 0, 15 ) ) !== false) {
			unset ( $splitted_datas [$i] );
			$flag = true;
			continue;
		}
		if ($flag) {
			if (strpos ( $splitted_datas [$i], "~]$" ) !== false) {
				unset ( $splitted_datas [$i] );
				$flag = false;
				continue;
			}
		} else {
			unset ( $splitted_datas [$i] );
		}
	}
	abstract_log::onDebug_standard ( $splitted_datas, 2 );
	return implode ( "\n", $splitted_datas );
}

function manage_table(&$connexion, $table) {
	// connexion->faire_requete ( "DROP TABLE IF EXISTS " . $table . ";" );
	$connexion ->faire_requete ( "CREATE TABLE IF NOT EXISTS " . $table . "(id INTEGER PRIMARY KEY ASC AUTOINCREMENT, serveur, cle, valeur);" );
}

/**
 * Main programme Code retour en 2xxx en cas d'erreur
 *
 * @ignore
 * @param options $liste_option
 * @param logs $fichier_log
 * @return boolean
 */
function principale(&$liste_option, &$fichier_log) {
	if ($liste_option ->verifie_option_existe ( "machines" ) === false) {
		return abstract_log::onError_standard ( "Il faut une liste de machine --machines" );
	} elseif (! is_array ( $liste_option ->getOption ( "machines" ) )) {
		$liste_machines = array ( 
				$liste_option ->getOption ( "machines" ) );
	} else {
		$liste_machines = $liste_option ->getOption ( "machines" );
	}
	
	// On se connecte a la base gestion_sam
	$connexion_db = fonctions_standards_sgbd::creer_connexion_liste_option ( $liste_option );
	$db_gestion_sam = $connexion_db ["gestion_sam_sqlite"];
	
	// On prepare la liste des tables
	$liste_tables = array ( 
			"crontabs", 
			"logs", 
			"nagios", 
			"network", 
			"os", 
			"process", 
			"rpm" );
	
	// On creer la liste des tables, si elles n'existent pas
	foreach ( $liste_tables as $table ) {
		manage_table ( $db_gestion_sam, $table );
	}
	
	foreach ( $liste_machines as $serveur ) {
		// On prepare une class flux par serveur
		$class_flux = fonctions_standards_flux::creer_fonctions_standards_flux ( $liste_option );
		if (! is_object ( $class_flux )) {
			return abstract_log::onError_standard ( "La class fonctions_standards_flux est introuvable." );
		}
		try {
			abstract_log::onInfo_standard ( "Connexion ssh sur " . $serveur );
			$connexion = $class_flux ->creer_connexion_ssh ( $serveur );
			
			$donnees = retrouve_commande ( $class_flux, "hostname" );
			$donnees [0] ["resultat"] = trim ( $donnees [0] ["resultat"] );
			
			$parsing_data = collected_datas_to_sqlite::creer_collected_datas_to_sqlite ( $liste_option ) ->setObjetDbGestionSam ( $db_gestion_sam ) 
				->setCiId ( strtok ( $donnees [0] ["resultat"], "." ) );
			// On nettoie toutes les tables
			foreach ( $liste_tables as $table ) {
				$parsing_data ->nettoyer_serveur_en_base ( $table );
			}
			
			// OS
			$parsing_data ->setDonneesSource ( $donnees ) 
				->parse_os () 
				->enregistrer_en_base ( "os" );
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "cat /etc/redhat-release" ) ) 
				->parse_os () 
				->enregistrer_en_base ( "os" );
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "uname -a" ) ) 
				->parse_os () 
				->enregistrer_en_base ( "os" );
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "cat /proc/cpuinfo |grep processor |wc -l" ) ) 
				->parse_os () 
				->enregistrer_en_base ( "os" );
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "cat /proc/meminfo |grep MemTotal" ) ) 
				->parse_os () 
				->enregistrer_en_base ( "os" );
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "cat /etc/hosts" ) ) 
				->parse_hosts () 
				->enregistrer_en_base ( "os" );
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "cat /etc/passwd" ) ) 
				->parse_users () 
				->enregistrer_en_base ( "os" );
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "cat /etc/group" ) ) 
				->parse_group () 
				->enregistrer_en_base ( "os" );
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "sudo /bin/cat /etc/sudoers" ) ) 
				->parse_sudo () 
				->enregistrer_en_base ( "os" );
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "sudo /sbin/chkconfig --list" ) ) 
				->parse_chkconfig () 
				->enregistrer_en_base ( "os" );
			
			// DISKs
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "df -PT" ) ) 
				->parse_filesystem () 
				->enregistrer_en_base ( "os" );
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "cat /proc/partitions" ) ) 
				->parse_disk () 
				->enregistrer_en_base ( "os" );
			
			// NETWORK
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "/sbin/ifconfig -a" ) ) 
				->parse_network () 
				->enregistrer_en_base ( "network" );
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, 'sudo /usr/sbin/ss -lptnu |awk \'{print $1" "$2" "$5" "$7}\'' ) ) 
				->parse_sockets () 
				->enregistrer_en_base ( "network" );
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "sudo /usr/sbin/lsof -i -P -n|grep ESTABLISHED" ) ) 
				->parse_network () 
				->enregistrer_en_base ( "network" );
			
			// PROCESSUS
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "/bin/ps -efawww" ) ) 
				->parse_process () 
				->enregistrer_en_base ( "process" );
			
			// CRONTABS
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "for user in `sudo /bin/ls /var/spool/cron/`; do echo cat /var/spool/cron/\$user; sudo /bin/cat /var/spool/cron/\$user; done" ) ) 
				->parse_cron () 
				->enregistrer_en_base ( "crontabs" );
			
			// LOGS
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "sudo /usr/sbin/lsof |egrep \"_log|\.log|\.txt|\.out\"" ) ) 
				->parse_logs () 
				->enregistrer_en_base ( "logs" );
			/* $parsing_data->setDonneesSource ( retrouve_commande ( $class_flux, "sudo /usr/sbin/lsof |egrep \"_log|\.log|\.txt|\.out\" |awk '{print \$NF}' |sort|uniq" ) ) ->parse_logs () ->enregistrer_en_base ( "logs" ); */
			
			// RPMs
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "/bin/rpm -qa |sort" ) ) 
				->parse_rpm () 
				->enregistrer_en_base ( "rpm" );
			
			// NAGIOS
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "sudo /bin/cat /usr/local/nagios/etc/nrpe.cfg" ) ) 
				->parse_nagios () 
				->enregistrer_en_base ( "nagios" );
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "cat /etc/xinetd.d/nrpe" ) ) 
				->parse_nrpe_nagios () 
				->enregistrer_en_base ( "nagios" );
			$parsing_data ->setDonneesSource ( retrouve_commande ( $class_flux, "ls /usr/local/nagios/libexec/" ) ) 
				->parse_plugins_nagios () 
				->enregistrer_en_base ( "nagios" );
			
			$class_flux ->getConnexion () 
				->ssh_close ();
		} catch ( Exception $e ) {
		}
	}
	return true;
}
principale ( $liste_option, $fichier_log );
abstract_log::onInfo_standard ( "Heure de fin : " . date ( "d/m/Y H:i:s", time () ) );
exit ( $fichier_log ->renvoiExit () );
?>
