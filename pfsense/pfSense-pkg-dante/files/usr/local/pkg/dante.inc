<?php
/*
 * dante.inc
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2015 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2013 Danilo G. Baio
 * Copyright (c) 2013 Marcello Coutinho
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once("util.inc");
require_once("functions.inc");
require_once("pkg-utils.inc");
require_once("globals.inc");

define('DANTE_BASE', '/usr/local');
define('DANTE_CONF', '/usr/local/etc/sockd.conf');
define('DANTE_RCFILE', '/usr/local/etc/rc.d/dante.sh');
//To have service start/stop/restart menu with pkg_dante.php
$shortcut_section = 'dante';

function php_install_dante() {
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

name=dante_pfsense

command=/usr/local/sbin/sockd
pidfile=/var/run/dante_pfsense/sockd.pid
required_files=/usr/local/etc/sockd.conf

: \${dante_pfsense_enable:="NO"}
dante_pfsense_flags=\${dante_pfsense_flags:-"-D"}

if [ ! -d /var/log/dante_pfsense ]; then
	/bin/mkdir -p /var/log/dante_pfsense
	/bin/chmod 755 /var/log/dante_pfsense
fi
/usr/sbin/chown -R proxy:proxy /var/log/dante_pfsense

if [ ! -d /var/run/dante_pfsense ]; then
	/bin/mkdir -p /var/run/dante_pfsense
	/bin/chmod 755 /var/run/dante_pfsense
fi
/usr/sbin/chown -R proxy:proxy /var/run/dante_pfsense

load_rc_config $name
run_rc_command "$1"
/bin/pgrep -fn "\${command} \${dante_pfsense_flags}" > \${pidfile}

EOF;
	file_put_contents(DANTE_RCFILE,$rc_file);
        @chmod(DANTE_RCFILE, 0755);
        @chown($fpath, "root");
}

function php_deinstall_dante() {
	unlink_if_exists(DANTE_RCFILE);
	unlink_if_exists(DANTE_CONF);

	if (is_dir("/var/log/dante_pfsense")) {
		mwexec("/bin/rm -rf /var/log/dante_pfsense/");
	}
	if (is_dir("/var/run/dante_pfsense")) {
		mwexec("/bin/rm -rf /var/run/dante_pfsense/");
	}
}

function validate_input_dante($post, &$input_errors) {
	if (isset($post['dante_enabled'])) {
		#if (!preg_match("/\w+/", $post['hostname'])) {
                #        $input_errors[] = "Hostname field is required.";
                #}
		if ($post['listenport'] != '') {
			if (!is_numericint($post['listenport'])) {
				$input_errors[] = "'Listen Port' value is not numeric.";
			} elseif ($post['listenport'] < 1 || $post['listenport'] > 65535) {
				$input_errors[] = "You must enter a valid value for 'Listen Port'.";
			}
		}
		if ($post['sockettimeout'] != '') {
			if (!is_numericint($post['sockettimeout'])) {
				$input_errors[] = "'Socket Timeout' value is not numeric.";
			} elseif ($post['sockettimeout'] < 1 || $post['sockettimeout'] > 10000) {
				$input_errors[] = "You must enter a valid value for 'Socket Timeout'.";
			}
		}
		if (preg_match("/\w+/", $post['danteoutputmethod']) && !preg_match("/\w+/", $post['dantelogoutput'])) {
                       	$input_errors[] = "Output Log File is required.";
                }
		if (!preg_match("/\w+/", $post['clientmethod'])) {
                       	$post['clientmethod']='none';
                }
		if (!preg_match("/\w+/", $post['socksmethod'])) {
                       	$post['socksmethod']='none';
                }
	}
}

function get_connected_interface() {
	static $interfaces=array();
	if (!count($interfaces)) {
		$curif="";
		// launch ifconfig and parse its result (inet/inet6)
		// but only at first function call
		exec("ifconfig",$out);
		foreach($out as $line) {
			if (preg_match("#^([a-z\.]*)([0-9]*): #",$line,$mat)) {
				$curif=count($interfaces);
				$interfaces[$curif]['name']=$mat[1].$mat[2];
			}
			if (preg_match("#inet ([0-9\.]*) #",$line,$mat)) {
				$interfaces[$curif][]=$mat[1];
			}
			if (preg_match("#inet6 ([0-9a-fA-F:]*) #",$line,$mat)) {
				$interfaces[$curif][]=$mat[1];
			}
		}
	}
	return $interfaces;
}

function find_interface($pfsense_name,$interfaces){
	switch($pfsense_name) {
		case "wan":
			if(isset($interfaces[0]['name'])){
				return $interfaces[0]['name'];
			}
			return "vmx0";
		case "lan":
			if(isset($interfaces[1]['name'])){
				return $interfaces[1]['name'];
			}
			return "vmx1";
		case "opt1":
			if(isset($interfaces[2]['name'])){
				return $interfaces[2]['name'];
			}
			return "vmx2";
		case "opt2":
			if(isset($interfaces[3]['name'])){
				return $interfaces[3]['name'];
			}
			return "vmx3";
		case "lo0":
		case "loopback":
		case "localhost":
			return "127.0.0.1";
	}
	return $pfsense_name;
}

function sync_package_dante() {
	global $config, $g;

	conf_mount_rw();

	// Check Dante settings
	if (is_array($config['installedpackages']['dante'])) {
		$dante_config = $config['installedpackages']['dante']['config'][0];
		if ($dante_config['dante_enabled'] == "on") {
			$interfaces=get_connected_interface();
			$listitems = explode (",",$dante_config['internalinterfaces']);
			$internallist = "";
			foreach( $listitems as $item){
				$internallist .= "internal: ".find_interface($item,$interfaces)." port = ".$dante_config['listenport'] . "\n";
			}
			$listitems = explode (",",$dante_config['externalinterfaces']);
			$externallist = "";
			foreach( $listitems as $item){
				$externallist .= "external: ".find_interface($item,$interfaces) . "\n";
			}
			if($dante_config['dantesrchost'] == "on") {
				$dantesrchost="srchost: nodnsunknown nodnsmismatch";
			} else {
				$dantesrchost="";
			}
			
			//LogFile
			$listlogs = str_replace (","," ",$dante_config['danteoutputmethod']);

			$ClientPass = base64_decode($dante_config['clientpass']);
			$ClientBlock = base64_decode($dante_config['clientblock']);

			$SocksPass = base64_decode($dante_config['sockspass']);
			$SocksBlock = base64_decode($dante_config['socksblock']);

			$Routes = base64_decode($dante_config['socksroute']);

			$UserParams = base64_decode($dante_config['userparams']);

			$dante_conf_file = <<< EOF
logoutput: {$listlogs} /var/log/dante_pfsense/{$dante_config['dantelogoutput']}

{$internallist}

{$externallist}
external.rotation: {$dante_config['externalrotation']}

socksmethod: {$dante_config['socksmethod']} 
clientmethod: {$dante_config['clientmethod']}
user.privileged: {$dante_config['userprivileged']}
user.unprivileged: {$dante_config['userunprivileged']}
{$dantesrchost}

timeout.io: {$dante_config['clienttimeout']}
timeout.negotiate: {$dante_config['sockettimeout']}

{$UserParams}

#Client Rules
{$ClientPass}

#Block Client Rules
{$ClientBlock}

#Socks Rules
{$SocksPass}

#Block Socks Rules
{$SocksBlock}

#Route Rules
{$Routes}

EOF;
			file_put_contents(DANTE_CONF, strtr($dante_conf_file, array("\r" => "")));
		}
	}

	if (is_array($dante_config) && $dante_config['dante_enabled'] == "on") {
		 mwexec("/usr/bin/sed -i \"\" 's/dante_pfsense_enable:=\"NO\"/dante_pfsense_enable:=\"YES\"/' " . DANTE_RCFILE);
		restart_service("dante");
	} else {
		 mwexec("/usr/bin/sed -i \"\" 's/dante_pfsense_enable:=\"YES\"/dante_pfsense_enable:=\"NO\"/' " . DANTE_RCFILE);
		if (is_service_running("dante")) {
			stop_service("dante");
		}
	}

	conf_mount_ro();
}

?>
