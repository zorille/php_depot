<?php
/*
 * beats.inc
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

define('BEATS_BASE', '/usr/local');
define('BEATS_CONF', '/usr/local/etc/filebeat.yml');
define('BEATS_RCFILE', '/usr/local/etc/rc.d/beats.sh');
//To have service start/stop/restart menu with pkg_beats.php
$shortcut_section = 'elkbeats';

function php_install_beats() {
	$rc_file = <<< EOF
#!/bin/sh
#
# \$FreeBSD\$
#

# PROVIDE: filebeat
# REQUIRE: DAEMON
# BEFORE: LOGIN
# KEYWORD: shutdown

#
# pfSense Package
# DO NOT TOUCH
#

. /etc/rc.subr

name=elkbeats_pfsense

: \${elkbeats_pfsense_enable:="NO"}
elkbeats_pfsense_flags=\${elkbeats_pfsense_flags:-"-path.home /var/db/beats/\${name} -path.config /usr/local/etc"}

if [ ! -d /var/log/elkbeats_pfsense ]; then
        /bin/mkdir -p /var/log/elkbeats_pfsense
        /bin/chmod 755 /var/log/elkbeats_pfsense
fi
/usr/sbin/chown -R root:wheel /var/log/elkbeats_pfsense

if [ ! -d /var/run/elkbeats_pfsense ]; then
        /bin/mkdir -p /var/run/elkbeats_pfsense
        /bin/chmod 755 /var/run/elkbeats_pfsense
fi
/usr/sbin/chown -R root:wheel /var/run/elkbeats_pfsense

# daemon
start_precmd=filebeat_pfsense_prestart
command=/usr/sbin/daemon
pidfile="/var/run/elkbeats_pfsense/\${name}"
command_args="-rP \${pidfile} /usr/local/sbin/filebeat \${filebeat_pfsense_config}"

filebeat_pfsense_prestart() {
# Have to empty rc_flags so they don't get passed to daemon(8)
        rc_flags=""
}

run_rc_command "$1"
#/bin/pgrep -fn "\${command} \${elkbeats_pfsense_flags}" > \${pidfile}

EOF;
        file_put_contents(BEATS_RCFILE,$rc_file);
        @chmod(BEATS_RCFILE, 0755);
        @chown($fpath, "root");

}

function php_deinstall_beats() {
	unlink_if_exists(BEATS_RCFILE);
	unlink_if_exists(BEATS_CONF);

	if (is_dir("/var/log/elkbeats_pfsense")) {
		mwexec("/bin/rm -rf /var/log/elkbeatsi_pfsense/");
	}
	if (is_dir("/var/run/elkbeats_pfsense")) {
		mwexec("/bin/rm -rf /var/run/elkbeats_pfsense/");
	}
}

function validate_input_beats($post, &$input_errors) {
	if (isset($post['beats_enabled'])) {
		if (!preg_match("/\w+/", base64_decode($post['prospectors']))) {
                        $input_errors[] = "Prospectors field is required.";
                }
		if (isset($post['elasticsearchenable'])) {
			if (!preg_match("/\w+/", $post['elasticsearchhostname'])) {
                       		$input_errors[] = "ElasticSearch Hostname field is required.";
                	}
		}
		if (isset($post['logstashenable'])) {
			if (!preg_match("/\w+/", $post['logstashhostname'])) {
                       		$input_errors[] = "Logstash Hostname field is required.";
                	}
			//We testing SSL for Logstash
			if (isset($post['logstashsslenable'])) {
				if (!preg_match("/\w+/", $post['logstashsslcertificateauthorities'])) {
                       			$input_errors[] = "Logstash SSL Certificate Authorities field is required.";
                		}
				if (!preg_match("/\w+/", $post['logstashsslcertificate'])) {
                       			$input_errors[] = "Logstash SSL Client Certificate field is required.";
                		}
				if (!preg_match("/\w+/", $post['logstashsslkey'])) {
                       			$input_errors[] = "Logstash SSL Client Key field is required.";
                		}
			}
		}
		if (isset($post['loggingtofile'])) {
			if (!preg_match("/\w+/", $post['logfile'])) {
                       		$input_errors[] = "Logfile Name field is required.";
                	}
		}
		#if ($post['listenport'] != '') {
		#	if (!is_numericint($post['listenport'])) {
		#		$input_errors[] = "'Listen Port' value is not numeric.";
		#	} elseif ($post['listenport'] < 1 || $post['listenport'] > 65535) {
		#		$input_errors[] = "You must enter a valid value for 'Listen Port'.";
		#	}
		#}
	}
}

function valid_params_beats($param,$value,$array=false){
	if(!empty($value)){
		if($array){
			return $param.": [\"".$value."\"]\n";
		}
		return $param.": ".$value."\n";
	}
	return "";
}

function sync_package_beats() {
	global $config, $g;

	conf_mount_rw();

	// Check Beats settings
	if (is_array($config['installedpackages']['elkbeats'])) {
		$beats_config = $config['installedpackages']['elkbeats']['config'][0];
		if ($beats_config['beats_enabled'] == "on") {
			$name=valid_params_beats("name",$beats_config['name']);
			$tags=valid_params_beats("tags",$beats_config['tags'],true);
			$fields = base64_decode($beats_config['fields']);

			$Prospectors = base64_decode($beats_config['prospectors']);

			$output="";
			if($beats_config['elasticsearchenable'] == 'on' ){
				$output="#================= Elasticsearch ==================\n";
				$output.="output.elasticsearch:\n";
				$output.=valid_params_beats("hosts",$beats_config['elasticsearchhostname'],true);
				$output.=valid_params_beats("protocol",$beats_config['elasticsearchprotocol']);
				$output.=valid_params_beats("username",$beats_config['elasticsearchusername']);
				$output.=valid_params_beats("password",$beats_config['elasticsearchpassword']);
				
			} elseif ($beats_config['logstashenable'] == 'on' ){
				$output="#================= Logstash ==================\n";
				$output.="output.logstash:\n";
				$output.=valid_params_beats(" hosts",$beats_config['logstashhostname'],true);
				$output.=valid_params_beats(" protocol",$beats_config['logstashsslcertificateauthorities'],true);
				$output.=valid_params_beats(" username",$beats_config['logstashsslcertificate']);
				$output.=valid_params_beats(" password",$beats_config['logstashsslkey']);

			}

			$loglevel=valid_params_beats("logging.level",$beats_config['loglevel']);
			$logselector=valid_params_beats("logging.selectors",$beats_config['logselector'],true);
			if($beats_config['loggingtosyslog'] == "on" ){
				$syslog="logging.to_syslog: true\n";
			} else {
				$syslog="logging.to_syslog: false\n";
			}
			if($beats_config['loggingtofile'] == "on" ){
				$logtofile="logging.to_file: true\n";
				$logtofile.="logging.files:\n";
				$logtofile.=" path: /var/log/filebeat\n";
				$logtofile.=valid_params_beats(" name",$beats_config['logfile']);
				$logtofile.=valid_params_beats(" rotateeverybytes",$beats_config['rotateeverybytes']);
				$logtofile.=valid_params_beats(" keepfiles",$beats_config['keepfiles']);
			} else {
				$logtofile="logging.to_file: false\n";
			}

			$beats_conf_file = <<< EOF
#================= Prospectors ==================
filebeat.prospectors:
{$Prospectors}

#================= General ==================
{$name}
{$tags}
{$fields}

{$output}

#================= Logs ==================
{$loglevel}
{$logselector}

{$syslog}
{$logtofile}
EOF;
			file_put_contents(BEATS_CONF, strtr($beats_conf_file, array("\r" => "")));
		}
	}

	if (is_array($beats_config) && $beats_config['beats_enabled'] == "on") {
		mwexec("/usr/bin/sed -i \"\" 's/elkbeats_pfsense_enable:=\"NO\"/elkbeats_pfsense_enable:=\"YES\"/' " . BEATS_RCFILE);
		restart_service("elkbeats");
	} else {
		mwexec("/usr/bin/sed -i \"\" 's/elkbeats_pfsense_enable:=\"YES\"/elkbeats_pfsense_enable:=\"NO\"/' " . BEATS_RCFILE);
		if (is_service_running("beats")) {
			stop_service("elkbeats");
		}
	}

	conf_mount_ro();
}

?>
