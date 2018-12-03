<?php
/* SockdIOPS.inc part of pfSense (https://www.pfsense.org) 
 * Copyright (c) 2015 Rubicon Communications, LLC (Netgate) 
 * Copyright (c) 2013 Danilo G. Baio 
 * Copyright (c) 2013 Marcello Coutinho 
 * Copyright (c) 2018 Damien Vargas
 * All rights reserved. 
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); 
 * you may not use this file except in compliance with the License. 
 * You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0 
 * Unless required by applicable law or agreed to in writing, software distributed under the 
 * License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, 
 * either express or implied. 
 * 
 * See the License for the specific language governing permissions and limitations under the License. 
 */
require_once ("util.inc");
require_once ("functions.inc");
require_once ("pkg-utils.inc");
require_once ("globals.inc");
define ( 'SOCKDIOPS_BASE', '/usr/local' );
define ( 'SOCKDIOPS_CONF', '/usr/local/etc/SockdIOPS/sockdiops.conf' );
define ( 'SOCKDIOPS_RCFILE', '/usr/local/etc/rc.d/SockdIOPS.sh' );
// To have service start/stop/restart menu with pkg_SockdIOPS.php
$shortcut_section = 'sockdiops';

function php_install_SockdIOPS() {
	$rc_file = <<< EOF
#!/bin/sh
#
# \$FreeBSD\$
#

# PROVIDE: sockd
# REQUIRE: NETWORKING
# KEYWORD: shutdown

#
# pfSense Package
# DO NOT TOUCH
#

. /etc/rc.subr

name=sockdiops_pfsense

command=/usr/local/sbin/sockd
pidfile=/var/run/SockdIOPS_pfsense/sockd.pid
required_files=/usr/local/etc/SockdIOPS/sockdiops.conf

: \${sockdiops_pfsense_enable:="NO"}
sockdiops_pfsense_flags=\${sockdiops_pfsense_flags:-"-D -p /var/run/SockdIOPS_pfsense/sockd.pid -f /usr/local/etc/SockdIOPS/sockdiops.conf"}

if [ ! -d /var/log/SockdIOPS_pfsense ]; then
	/bin/mkdir -p /var/log/SockdIOPS_pfsense
	/bin/chmod 755 /var/log/SockdIOPS_pfsense
fi
/usr/sbin/chown -R proxy:proxy /var/log/SockdIOPS_pfsense

if [ ! -d /var/run/SockdIOPS_pfsense ]; then
	/bin/mkdir -p /var/run/SockdIOPS_pfsense
	/bin/chmod 755 /var/run/SockdIOPS_pfsense
fi
/usr/sbin/chown -R proxy:proxy /var/run/SockdIOPS_pfsense

load_rc_config $name
run_rc_command "$1"
/bin/pgrep -fn "\${command} \${sockdiops_pfsense_flags}" > \${pidfile}

EOF;
	file_put_contents ( SOCKDIOPS_RCFILE, $rc_file );
	@chmod ( SOCKDIOPS_RCFILE, 0755 );
	mwexec ( "/bin/mkdir -p /usr/local/etc/SockdIOPS/" );
	@chmod ( "/usr/local/etc/SockdIOPS/", 0755 );
	@chown ( $fpath, "root" );
}

function php_deinstall_SockdIOPS() {
	unlink_if_exists ( SOCKDIOPS_RCFILE );
	unlink_if_exists ( SOCKDIOPS_CONF );
	if (is_dir ( "/var/log/SockdIOPS_pfsense" )) {
		mwexec ( "/bin/rm -rf /var/log/SockdIOPS_pfsense/" );
	}
	if (is_dir ( "/var/run/SockdIOPS_pfsense" )) {
		mwexec ( "/bin/rm -rf /var/run/SockdIOPS_pfsense/" );
	}
	if (is_dir ( "/usr/local/etc/SockdIOPS" )) {
		mwexec ( "/bin/rm -rf /usr/local/etc/SockdIOPS/" );
	}
}

function validate_input_SockdIOPS(
		$post,
		&$input_errors) {
	if (isset ( $post ['sockdiops_enabled'] )) {
		// if (!preg_match("/\w+/", $post['hostname'])) {
		// $input_errors[] = "Hostname field is required.";
		// }
		if ($post ['listenport'] != '') {
			if (! is_numericint ( $post ['listenport'] )) {
				$input_errors [] = "'Listen Port' value is not numeric.";
			} elseif ($post ['listenport'] < 1 || $post ['listenport'] > 65535) {
				$input_errors [] = "You must enter a valid value for 'Listen Port'.";
			}
		}
		if ($post ['sockettimeout'] != '') {
			if (! is_numericint ( $post ['sockettimeout'] )) {
				$input_errors [] = "'Socket Timeout' value is not numeric.";
			} elseif ($post ['sockettimeout'] < 1 || $post ['sockettimeout'] > 10000) {
				$input_errors [] = "You must enter a valid value for 'Socket Timeout'.";
			}
		}
		if (preg_match ( "/\w+/", $post ['sockdiopsoutputmethod'] ) && ! preg_match ( "/\w+/", $post ['sockdiopslogoutput'] )) {
			$input_errors [] = "Output Log File is required.";
		}
	}
}

function get_connected_interface() {
	static $interfaces = array ();
	if (! count ( $interfaces )) {
		$curif = "";
		// launch ifconfig and parse its result (inet/inet6)
		// but only at first function call
		exec ( "ifconfig", $out );
		foreach ( $out as $line ) {
			if (preg_match ( "#^([a-z\.]*)([0-9]*): #", $line, $mat )) {
				$curif = count ( $interfaces );
				$interfaces [$curif] ['name'] = $mat [1] . $mat [2];
			}
			if (preg_match ( "#inet ([0-9\.]*) #", $line, $mat )) {
				$interfaces [$curif] [] = $mat [1];
			}
			if (preg_match ( "#inet6 ([0-9a-fA-F:]*) #", $line, $mat )) {
				$interfaces [$curif] [] = $mat [1];
			}
		}
	}
	return $interfaces;
}

function find_interface(
		$pfsense_name,
		$interfaces) {
	switch ($pfsense_name) {
		case "wan" :
			if (isset ( $interfaces [0] ['name'] )) {
				return $interfaces [0] ['name'];
			}
			return "vmx0";
		case "lan" :
			if (isset ( $interfaces [1] ['name'] )) {
				return $interfaces [1] ['name'];
			}
			return "vmx1";
		case "opt1" :
			if (isset ( $interfaces [2] ['name'] )) {
				return $interfaces [2] ['name'];
			}
			return "vmx2";
		case "opt2" :
			if (isset ( $interfaces [3] ['name'] )) {
				return $interfaces [3] ['name'];
			}
			return "vmx3";
		case "lo0" :
		case "loopback" :
		case "localhost" :
			return "127.0.0.1";
	}
	return $pfsense_name;
}

/**
 * 
 * @param array $client
 * @param string $type_ip select between '' or 'from' or 'to'
 * @return string
 */
function get_host(
		$client,$type_ip) {
			if (! empty ( $client [$type_ip.'ip'] )) {
				return " " . $client [$type_ip.'ip'] . '/' . $client [$type_ip.'cidr'] . " ";
	}
	return " " . $client [$type_ip.'fqdn'] . " ";
}

function get_port(
		$client,$type_port,$separator="=") {
			if (! empty ( $client [$type_port.'port'] )) {
				return " port " . $separator." ". $client [$type_port.'port'] . " ";
	}
	return '';
}

function get_logstype(
		$client) {
	if (! empty ( $client ['logstype'] )) {
		return " log: " . $client ['logstype'] . "\n";
	}
	return '';
}

function get_protocol(
		$client) {
			if (! empty ( $client ['protocol'] )) {
				return " protocol: " . $client ['protocol'] . "\n";
			}
			return '';
}

function get_clientmethod(
		$client) {
			if (! empty ( $client ['clientmethod'] )) {
				return " clientmethod: " . $client ['clientmethod'] . "\n";
			}
			return '';
}

function get_proxyprotocol(
		$client) {
			if (! empty ( $client ['proxyprotocol'] )) {
				return " proxyprotocol: " . $client ['proxyprotocol'] . "\n";
			}
			return '';
}

function get_command(
		$client) {
			if (! empty ( $client ['command'] )) {
				return " command: " . $client ['command'] . "\n";
			}
			return '';
}

function create_client_rules() {
	global $config, $g;
	$clientrules = "\n#Pass Client Rules\n";
	// Check SockdIOPS settings
	if (is_array ( $config ['installedpackages'] ['sockdiops'] ) && isset ( $config ['installedpackages'] ['sockdiops'] ['passclients'] ) && is_array ( $config ['installedpackages'] ['sockdiops'] ['passclients'] )) {
		foreach ( $config ['installedpackages'] ['sockdiops'] ['passclients'] as $client ) {
			$clientrules .= "client pass {\n from: " . get_host ( $client,'' ) . get_port ( $client,'s','' ) . " to: " . $client ['interface'] . "\n" . get_logstype ( $client ) . "}\n";
		}
		/* client pass { from: 192.168.200.103/32 port 1-65535 to: em1 log: error connect disconnect } */
	}
	if (is_array ( $config ['installedpackages'] ['sockdiops'] ) && isset ( $config ['installedpackages'] ['sockdiops'] ['blockclients'] ) && is_array ( $config ['installedpackages'] ['sockdiops'] ['blockclients'] )) {
		$clientrules .= "\n#Block Client Rules\n";
		foreach ( $config ['installedpackages'] ['sockdiops'] ['blockclients'] as $client ) {
			$clientrules .= "client block {\n from: " . get_host ( $client,'' ) . get_port ( $client,'s','' ) . " to: " . $client ['interface'] . "\n" . get_logstype ( $client ) . " }\n";
		}
		/* client block { from: 192.168.200.103/32 port 1-65535 to: em1 log: error connect disconnect } */
	}
	return $clientrules;
}

function create_sock_rules() {
	global $config, $g;
	$sockrules = "\n#Pass Sock Rules\n";
	// Check SockdIOPS settings
	if (is_array ( $config ['installedpackages'] ['sockdiops'] ) && isset ( $config ['installedpackages'] ['sockdiops'] ['passsocks'] ) && is_array ( $config ['installedpackages'] ['sockdiops'] ['passsocks'] )) {
		foreach ( $config ['installedpackages'] ['sockdiops'] ['passsocks'] as $sock ) {
			$sockrules .= "socks pass {\n from: " . get_host ( $sock,'from' ) ." to: " . get_host ( $sock,'to' ) . get_port ( $sock,'t' ) . "\n" . get_protocol($sock) .  get_logstype ( $sock ). get_clientmethod($sock) . "}\n";
		}
		/* sock pass { from: 192.168.200.103/32 port 1-65535 to: em1 log: error connect disconnect } */
	}
	if (is_array ( $config ['installedpackages'] ['sockdiops'] ) && isset ( $config ['installedpackages'] ['sockdiops'] ['blocksocks'] ) && is_array ( $config ['installedpackages'] ['sockdiops'] ['blocksocks'] )) {
		$sockrules .= "\n#Block Sock Rules\n";
		foreach ( $config ['installedpackages'] ['sockdiops'] ['blocksocks'] as $sock ) {
			$sockrules .= "socks block {\n from: " . get_host ( $sock,'from' ) ." to: " . get_host ( $sock,'to' ) . get_port ( $sock,'t' ) . "\n" . get_logstype ( $sock ) . "}\n";
		}
		/* sock block { from: 192.168.200.103/32 port 1-65535 to: em1 log: error connect disconnect } */
	}
	return $sockrules;
}

function create_route_rules() {
	global $config, $g;
	$routerules = "\n#Route Rules\n";
	// Check SockdIOPS settings
	if (is_array ( $config ['installedpackages'] ['sockdiops'] ) && isset ( $config ['installedpackages'] ['sockdiops'] ['passroutes'] ) && is_array ( $config ['installedpackages'] ['sockdiops'] ['passroutes'] )) {
		foreach ( $config ['installedpackages'] ['sockdiops'] ['passroutes'] as $route ) {
			$routerules .= "route {\n from: " . get_host ( $route,'from' ) ." to: " . get_host ( $route,'to' ) . get_port ( $route,'t' ) ." via: " . get_host ( $route,'via' ) . get_port ( $route,'via' ) . "\n" . get_protocol($route) .  get_proxyprotocol ( $route ). get_command($route) . "}\n";
		}
		/* route pass { from: 192.168.200.103/32 port 1-65535 to: em1 log: error connect disconnect } */
	}
	return $routerules;
}

function sockdiops_clients_mgmt_gere_erreur(
		$type,
		$message) {
	global $input_errors;
	$input_errors [] = gettext ( $message );
	return false;
}

function is_valid_domain_name($domain_name)
{
	$options = array (
			'flags' => FILTER_FLAG_HOSTNAME
	);
	if(strpos ( $domain_name, '.' ) === 0 ){
		$domain_name='www'.$domain_name;
	}
	if(filter_var ( $domain_name, FILTER_VALIDATE_DOMAIN, $options ) != false){
		return true;
	}
	return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
			&& preg_match("/^.{1,253}$/", $domain_name) //overall length check
			&& preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   ); //length of each label
}


/**
 * Validate IP/CIDR or FQDN
 * @param array $tmp
 * @param string $type Pass or Block
 * @param string $type_filter select between 'Client' or 'Socks' or 'Route'
 * @param string $type_ip select between '' or 'from' or 'to'
 * @return boolean
 */
function valide_ip_cidr_fqdn(
		&$tmp,
		$type,
		$type_filter,
		$type_ip) {
	$champ_ip_to_check=$_POST [$type_filter . $type . 'List_' . $type_ip . 'ip'];
	$champ_cidr_to_check=$_POST [$type_filter . $type . 'List_' . $type_ip . 'cidr'];
	if ($champ_ip_to_check == "0.0.0.0" || (filter_var ( $champ_ip_to_check, FILTER_VALIDATE_IP ) != false)) {
		$tmp [$type_ip . 'ip'] = $champ_ip_to_check;
		if ((is_numeric ( $champ_cidr_to_check ) && $champ_cidr_to_check <= 32) || $champ_ip_to_check == "lo0") {
			$tmp [$type_ip . 'cidr'] = $champ_cidr_to_check;
		} else {
			return sockdiops_clients_mgmt_gere_erreur ( $type, "You must provide a valid " . $type_filter . "" . $type . " CIDR (".$champ_cidr_to_check .") with an IP ".$champ_ip_to_check  );
		}
	} elseif (is_valid_domain_name($champ_ip_to_check) ) {
		$tmp [$type_ip . 'fqdn'] = $champ_ip_to_check;
	} else {
		return sockdiops_clients_mgmt_gere_erreur ( $type, "You must provide a valid IP/CIDR or FQDN for the new " . $type_filter . " " . $type . " List." );
	}
	return true;
}

/**
 * @param array $tmp
 * @param string $type $type Pass or Block
 * @param string $type_filter elect between 'Client' or 'Socks' or 'Route'
 * @param string $type_port elect between 's' or 't' or 'via'
 * @return boolean
 */
function valide_port(
		&$tmp,
		$type,
		$type_filter,$type_port) {
			if (! empty ( $_POST [$type_filter . $type . 'List_'.$type_port.'port'] )) {
				$tmp [$type_port.'port'] = $_POST [$type_filter . $type . 'List_'.$type_port.'port'];
	}
	return true;
}

function sync_package_SockdIOPS() {
	global $config, $g, $input_errors;
	conf_mount_rw ();
	// Check SockdIOPS settings
	if (is_array ( $config ['installedpackages'] ['sockdiops'] )) {
		$sockdiops_config = $config ['installedpackages'] ['sockdiops'] ['config'] [0];
		if ($sockdiops_config ['sockdiops_enabled'] == "on" && $sockdiops_config ['sockdiops_interface_conf'] == "on") {
			$interfaces = get_connected_interface ();
			$listitems = explode ( ",", $sockdiops_config ['internalinterfaces'] );
			$internallist = "";
			foreach ( $listitems as $item ) {
				$internallist .= "internal: " . find_interface ( $item, $interfaces ) . " port = " . $sockdiops_config ['listenport'] . "\n";
			}
			$listitems = explode ( ",", $sockdiops_config ['externalinterfaces'] );
			$externallist = "";
			foreach ( $listitems as $item ) {
				$externallist .= "external: " . find_interface ( $item, $interfaces ) . "\n";
			}
			if ($sockdiops_config ['sockdiopssrchost'] == "on") {
				$SockdIOPSsrchost = "srchost: nodnsunknown nodnsmismatch";
			} else {
				$SockdIOPSsrchost = "";
			}
			// LogFile
			$listlogs = str_replace ( ",", " ", $sockdiops_config ['sockdiopsoutputmethod'] );
			$ClientPass = create_client_rules ();
			$SocksPass = create_sock_rules();
			$Routes = create_route_rules();
			$UserParams = base64_decode ( $sockdiops_config ['userparams'] );
			$sockdiops_conf_file = <<< EOF
logoutput: {$listlogs} /var/log/SockdIOPS_pfsense/{$sockdiops_config['sockdiopslogoutput']}

{$internallist}

{$externallist}
external.rotation: {$sockdiops_config['externalrotation']}

socksmethod: {$sockdiops_config['socksmethod']} 
clientmethod: {$sockdiops_config['clientmethod']}
user.privileged: {$sockdiops_config['userprivileged']}
user.unprivileged: {$sockdiops_config['userunprivileged']}
{$SockdIOPSsrchost}

timeout.io: {$sockdiops_config['clienttimeout']}
timeout.negotiate: {$sockdiops_config['sockettimeout']}

{$UserParams}

{$ClientPass}

{$SocksPass}

{$Routes}

EOF;
			mwexec ( "/bin/cp -f " . SOCKDIOPS_CONF . " " . SOCKDIOPS_CONF . "_sav" );
			file_put_contents ( SOCKDIOPS_CONF, strtr ( $sockdiops_conf_file, array (
					"\r" => ""
			) ) );
		}
	}
	if (is_array ( $sockdiops_config ) && $sockdiops_config ['sockdiops_enabled'] == "on") {
		mwexec ( "/usr/bin/sed -i \"\" 's/sockdiops_pfsense_enable:=\"NO\"/sockdiops_pfsense_enable:=\"YES\"/' " . SOCKDIOPS_RCFILE );
		restart_service ( "SockdIOPS" );
	} else {
		mwexec ( "/usr/bin/sed -i \"\" 's/sockdiops_pfsense_enable:=\"YES\"/sockdiops_pfsense_enable:=\"NO\"/' " . SOCKDIOPS_RCFILE );
		if (is_service_running ( "SockdIOPS" )) {
			stop_service ( "SockdIOPS" );
		}
	}
	conf_mount_ro ();
}

function add_header_menu(
		$active) {
	$tab_array = array ();
	$tab_array [] = array (
			gettext ( "Main Config" ),
			($active == "Main Config") ? true : false,
			"/pkg_edit.php?xml=SockdIOPS.xml&id=0"
	);
	$tab_array [] = array (
			gettext ( "Clients" ),
			($active == "Clients") ? true : false,
			"/SockdIOPS/SockdIOPS_clients_mgmt.php"
	);
	$tab_array [] = array (
			gettext ( "Socks" ),
			($active == "Socks") ? true : false,
			"/SockdIOPS/SockdIOPS_socks_mgmt.php"
	);
	$tab_array [] = array (
			gettext ( "Routes" ),
			($active == "Routes") ? true : false,
			"/SockdIOPS/SockdIOPS_routes_mgmt.php"
	);
	$tab_array [] = array (
			gettext ( "View Logs" ),
			($active == "View Logs") ? true : false,
			"/SockdIOPS/SockdIOPS_view_logs.php"
	);
	$tab_array [] = array (
			gettext ( "View Graph" ),
			($active == "View Graph") ? true : false,
			"/SockdIOPS/SockdIOPS_view_network.php"
	);
	display_top_tabs ( $tab_array, true );
}

function build_logfile_list() {
	
	$list = array();
	
	if (is_array($config['installedpackages']['sockdiops']) && isset($config['installedpackages']['sockdiops']['config'][0]['sockdiopslogoutput'])) {
		$logs = array( $config['installedpackages']['sockdiops']['config'][0]['sockdiopslogoutput']);
	} else {
		$logs = array( 'sockd.log');
	}
	foreach ($logs as $log) {
		$list[$log] = $log;
	}
	
	return($list);
}

?>
