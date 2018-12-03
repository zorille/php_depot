<?php
require_once ("Digraph.inc");

/**
 * ***************************** GESTION DES LOGS **********************
 */
function filtre_ip_port(
		$ip) {
	if (preg_match ( '/^(?<ip>\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\.(?<port>\d+)$/', $ip, $donnees ) != false) {
		return array (
				"ip" => $donnees ['ip'],
				"port" => $donnees ['port']
		);
	}
	if (preg_match ( '/^(?<fqdn>(|\w{1,64})\.\w{1,64}.*)\.(?<port>\d+)$/', $ip, $donnees )) {
		return array (
				"ip" => $donnees ['fqdn'],
				"port" => $donnees ['port']
		);
	}
	return false;
}

/**
 * Valide la presence d'IP ou de FQDN et extrait le port de communication
 * @param array $liste_ips
 * @return boolean|array
 */
function retrouve_source_cible_SocksIOPS(
		$liste_ips,
		$sourceInternal) {
	$liste_connexions = array ();
	$ip_source = "";
	foreach ( $liste_ips as $pos => $ip ) {
		$donnees = filtre_ip_port ( $ip );
		if ($donnees) {
			if ($pos === 0) {
				$type = 'source';
				$ip_source = $donnees ['ip'];
			} else {
				$type = 'cible';
				if ($donnees ['ip'] != $ip_source) {
					$sourceInternal = "";
				}
			}
			if ($sourceInternal != "" && $sourceInternal != $donnees ['ip']) {
				$msgSourceInternal = " (" . $sourceInternal . ")";
			} else {
				$msgSourceInternal = "";
			}
			$liste_connexions [$type] ['ip'] = $donnees ['ip'] . $msgSourceInternal;
			$liste_connexions [$type] ['port'] = $donnees ['port'];
		} else {
			return false;
		}
	}
	return $liste_connexions;
}

/**
 * Creer une entree dans le tableau de reference de toutes les adresses IP/FQDN
 * @param array $infos_ip
 * @param array $liste_adresses
 */
function creer_objet_ip_SocksIOPS(
		$infos_ip,
		&$liste_adresses) {
	// Si l'adresse n'esxiste pas
	if (! isset ( $liste_adresses [$infos_ip ['ip']] )) {
		// On l'a cree
		$liste_adresses [$infos_ip ['ip']] ['ip'] = $infos_ip ['ip'];
		$liste_adresses [$infos_ip ['ip']] ['port'] = array (
				$infos_ip ['port']
		);
		$liste_adresses [$infos_ip ['ip']] ['cible'] = array ();
	} else {
		// On valide que le port n'est pas deja present
		if (! in_array ( $infos_ip ['port'], $liste_adresses [$infos_ip ['ip']] ['port'], true )) {
			// On ajoute le port utilise
			$liste_adresses [$infos_ip ['ip']] ['port'] [] = $infos_ip ['port'];
		}
	}
}

function creer_arbo_SocksIOPS(
		&$arbo,
		$ip_source,
		$ip_cible,
		&$donnees) {
	if (isset ( $arbo [$ip_source] )) {
		$arbo [$ip_source] [$ip_cible] = $donnees;
		return true;
	}
	foreach ( $arbo as $ip => $cible ) {
		if (is_array ( $cible )) {
			if (creer_arbo_SocksIOPS ( $arbo [$ip], $ip_source, $ip_cible, $donnees ) === true) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Creer la definition du cluster local
 * @param array $tableau
 * @param string $ip
 */
function creer_cluster_SockIOPS(
		&$tableau,
		$ip) {
	if (! isset ( $tableau ['cluster'] )) {
		$tableau ['cluster'] = array (
				$ip
		);
	} elseif (! in_array ( $ip, $tableau ['cluster'], true )) {
		$tableau ['cluster'] [] = $ip;
	}
}

/**
 * Decoupe la ligne en 2 ou 4 adresses IP/FQDN
 * @param array $liste_connect
 * @return array
 */
function valide_nb_champ(
		$liste_connect) {
	$nouvelle_liste = array ();
	$sourceInternal = "";
	foreach ( $liste_connect as $connect ) {
		$liste_ips = explode ( " ", $connect );
		switch (count ( $liste_ips )) {
			case 2 :
				// On retrouve les IP/FQDN source et cible
				$connexion_locale = retrouve_source_cible_SocksIOPS ( array (
						$liste_ips [0],
						$liste_ips [1]
				), $sourceInternal );
				// Si il n'y avait pas d'IP, on boucle
				if ($connexion_locale === false) {
					continue;
				}
				$nouvelle_liste [] = $connexion_locale;
				$sourceInternal = $connexion_locale ['cible'] ['ip'];
				break;
			case 4 :
				// On retrouve les IP/FQDN source et cible
				$connexion_locale = retrouve_source_cible_SocksIOPS ( array (
						$liste_ips [0],
						$liste_ips [1]
				), $sourceInternal );
				// Si il n'y avait pas d'IP, on boucle
				if ($connexion_locale !== false) {
					$nouvelle_liste [] = $connexion_locale;
					$sourceInternal = $connexion_locale ['cible'] ['ip'];
				}
				// On retrouve les IP/FQDN source et cible
				$connexion_locale = retrouve_source_cible_SocksIOPS ( array (
						substr ( $liste_ips [2], 1, strlen ( $liste_ips [2] ) - 2 ),
						$liste_ips [3]
				), $sourceInternal );
				// Si il n'y avait pas d'IP, on boucle
				if ($connexion_locale !== false) {
					$nouvelle_liste [] = $connexion_locale;
				}
				break;
			default :
				continue;
		}
	}
	return $nouvelle_liste;
}

/**
 * Extrait les informations contenues dans la ligne
 * @param string $ligne
 * @param array &$liste_adresses
 * @return boolean
 */
function creer_arborescence_SocksIOPS(
		$ligne,
		&$liste_adresses) {
	$liste_connect = explode ( " -> ", $ligne );
	// On valide si on a 2 ou 4 @IP entre 2 fleches (->)
	$liste_connect = valide_nb_champ ( $liste_connect );
	$previous_ip = "";
	foreach ( $liste_connect as $connect ) {
		// On gere l'adresse source
		creer_objet_ip_SocksIOPS ( $connect ['source'], $liste_adresses );
		// On gere l'adresse cible
		creer_objet_ip_SocksIOPS ( $connect ['cible'], $liste_adresses );
		// Puis on range les adresses par source et cible
		if ($previous_ip != "") {
			$liste_adresses [$previous_ip] ['cible'] [$connect ['source'] ['ip']] = "internal";
			creer_cluster_SockIOPS ( $liste_adresses [$previous_ip], $connect ['source'] ['ip'] );
			creer_cluster_SockIOPS ( $liste_adresses [$connect ['source'] ['ip']], $previous_ip );
		}
		// On gere les ports de communication
		if (isset ( $liste_adresses [$connect ['source'] ['ip']] ['cible'] [$connect ['cible'] ['ip']] )) {
			if (strpos ( $liste_adresses [$connect ['source'] ['ip']] ['cible'] [$connect ['cible'] ['ip']], $connect ['cible'] ['port'] ) === false)
				$liste_adresses [$connect ['source'] ['ip']] ['cible'] [$connect ['cible'] ['ip']] .= "," . $connect ['cible'] ['port'];
		} else {
			$liste_adresses [$connect ['source'] ['ip']] ['cible'] [$connect ['cible'] ['ip']] = $connect ['cible'] ['port'];
		}
		$previous_ip = $connect ['cible'] ['ip'];
	}
	return true;
}

function parse_log_SocksIOPS(
		$log_file,
		$nb_lines = 5000) {
	exec ( "/usr/bin/tail -" . $nb_lines . " " . $log_file, $return );
	$liste_adresses = array ();
	foreach ( $return as $ligne ) {
		if (preg_match ( '/info: pass\([0-9]{1}\): tcp\/connect \[: (?<route>.*)$/', $ligne, $resultat ) != false) {
			if (isset ( $resultat ['route'] )) {
				creer_arborescence_SocksIOPS ( $resultat ['route'], $liste_adresses );
			}
		} // preg_match deconnexion
// 		if (preg_match ( '/info: block\([1-5]{1}\): tcp\/accept \]: (?<route>[0-9. ]+): .*$/', $ligne, $resultat ) != false) {
// 			print_r($resultat);
// 			if (isset ( $resultat ['route'] )) {
// 				creer_arborescence_SocksIOPS ( $resultat ['route'], $liste_adresses );
// 			}
// 		} // preg_match deconnexion
	}
	return $liste_adresses;
}

/**
 * ***************************** GESTION DES LOGS **********************
 */
/**
 * ***************************** GESTION GRAPHIZ ***********************
 */
function donnees_cluster_SocksIOPS(
		&$graph,
		$donnees,
		$pos) {
	if (count ( $donnees ) > 1) {
		$attr = array (
				'style' => 'filled',
				'fillcolor' => 'grey'
		);
	} else {
		$attr = array (
				'style' => 'filled',
				'fillcolor' => 'white'
		);
	}
	$subgraph = $graph->subgraph ( 'cluster_' . $pos )
		->attr ( 'node', $attr );
	foreach ( $donnees as $ip ) {
		$subgraph->node ( $ip );
	}
	$subgraph->end ();
	return true;
}

function creer_cluster_SocksIOPS(
		$liste_connexions,
		&$arbo,
		&$graph,
		$pos = 1) {
	foreach ( $liste_connexions as $ip => $suite ) {
		$liste_ip = array (
				$ip
		);
		// On gere les cible
		if (! empty ( $suite ['cible'] )) {
			$edge = array_keys ( $suite ['cible'] );
			foreach ( $edge as $cible_ip ) {
				$arbo [] = array (
						0 => $ip,
						1 => $cible_ip,
						'label' => $suite ['cible'] [$cible_ip]
				);
			}
		}
		if (! isset ( $liste_connexions [$ip] ['done'] )) {
			// On gere les clusters
			if (isset ( $suite ['cluster'] )) {
				foreach ( $suite ['cluster'] as $ipcluster ) {
					if (! in_array ( $ipcluster, $liste_ip, true )) {
						$liste_ip [] = $ipcluster;
					}
					$liste_connexions [$ip] ['done'] = 1;
					$liste_connexions [$ipcluster] ['done'] = 1;
				}
			}
			donnees_cluster_SocksIOPS ( $graph, $liste_ip, $pos ++ );
		}
	}
	return true;
}

function create_graph(
		&$liste_connexions,
		$image = true) {
	$arbo = array ();
	$graph = new Alom\Graphviz\Digraph ( 'G' );
	creer_cluster_SocksIOPS ( $liste_connexions, $arbo, $graph );
	foreach ( $arbo as $data ) {
		$attr = array (
				'label' => $data ['label']
		);
		unset ( $data ['label'] );
		$graph->edge ( $data, $attr );
	}
	if ($image) {
		file_put_contents ( "/tmp/render.dot", $graph->render () );
		exec ( "/usr/local/bin/dot -Tpng /tmp/render.dot > /usr/local/www/SockdIOPS/network.png" );
		unlink ( "/tmp/render.dot" );
	}
	return $graph;
}
/**
 * ***************************** GESTION GRAPHIZ ***********************
 */

function retrieve_code(){
	$data="";
	$config = OPNsense\Core\Config::getInstance()->object();
	if ($config->interfaces->count() > 0) {
		foreach ($config->interfaces->children() as $node) {
			$data.=(string)$node->if.(string)$node->ipaddr.(string)$node->subnet.(string)$node->ipaddrv6;
		}
	}
	return strtoupper(substr(md5($data), 0,30));
}

function build_logfile_list() {
	$config = &config_read_array('OPNsense','sockdiops','sockdglobal');
	
	if (is_array($config) && isset($config['global']['logOutput'])) {
		return $config['global']['logOutput'];
	}
	return 'sockd.log';
}
?>