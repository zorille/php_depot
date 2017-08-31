<?php
/*
 * privoxy.inc
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

define('PRIVOXY_BASE', '/usr/local');
define('PRIVOXY_CONF_DIR', '/usr/local/etc/privoxy');
define('PRIVOXY_RCFILE', '/usr/local/etc/rc.d/privoxy_pfsense.sh');
//To have service start/stop/restart menu with pkg_privoxy.php
$shortcut_section = 'privoxy';

function php_install_privoxy() {
	$rc_file = <<< EOF
#!/bin/sh
#
# \$FreeBSD\$
#

#
#
# PROVIDE: privoxy
# REQUIRE: DAEMON
# BEFORE: LOGIN
#
#
# pfSense Package
# DO NOT TOUCH
#

. /etc/rc.subr

name="privoxy_pfsense"

: \${privoxy_pfsense_enable:="NO"}
: \${privoxy_pfsense_config:="/usr/local/etc/privoxy/config"}
: \${privoxy_pfsense_logdir:="/var/log/privoxy_pfsense"}
: \${privoxy_pfsense_user:="privoxy"}
: \${privoxy_pfsense_piddir:="/var/run/privoxy_pfsense"}
: \${privoxy_pfsense_pidfile:="\${privoxy_pfsense_piddir}/privoxy.pid"}

privoxy_pfsense_prestart () {
    if [ ! -e "\${privoxy_pfsense_config}" ]; then
        echo "config file not found. Copying the example file to \${privoxy_pfsense_config}."
        cp /usr/local/share/examples/privoxy/config "\${privoxy_pfsense_config}"
        chown "\${privoxy_pfsense_user}:\${privoxy_pfsense_user}" "\${privoxy_pfsense_config}";
    fi
    actionfile="/usr/local/etc/privoxy/match-all.action"
    if [ ! -e "\${actionfile}" ]; then
        echo "\${actionfile} not found. Copying the example file."
        cp /usr/local/share/examples/privoxy/match-all.action "\${actionfile}"
        chown "\${privoxy_pfsense_user}:\${privoxy_pfsense_user}" "\${actionfile}"
    fi
    if [ ! -e "\${privoxy_pfsense_logdir}" ]; then
        echo "\${privoxy_pfsense_logdir} not found. Creating ..."
        mkdir "\${privoxy_pfsense_logdir}"
        chown "\${privoxy_pfsense_user}:\${privoxy_pfsense_user}" "\${privoxy_pfsense_logdir}"
        chmod 0750 "\${privoxy_pfsense_logdir}"
    fi
    if [ ! -d "\${privoxy_pfsense_piddir}" ]; then
        mkdir -p "\${privoxy_pfsense_piddir}"
        chown "\${privoxy_pfsense_user}:\${privoxy_pfsense_user}" "\${privoxy_pfsense_piddir}"
    fi
}

start_precmd="privoxy_pfsense_prestart"

command="/usr/local/sbin/privoxy"
command_args="\${privoxy_pfsense_flags} --pidfile \${privoxy_pfsense_pidfile} \${privoxy_pfsense_config}"

run_rc_command "$1"

EOF;
        file_put_contents(PRIVOXY_RCFILE,$rc_file);
        @chmod(PRIVOXY_RCFILE, 0755);
        @chown($fpath, "root");
}

function php_deinstall_privoxy() {
        unlink_if_exists(PRIVOXY_RCFILE);
        unlink_if_exists(PRIVOXY_CONF_DIR . "/config" );

	if (is_dir("/var/log/privoxy_pfsense")) {
		mwexec("/bin/rm -rf /var/log/privoxy_pfsense/");
	}
	if (is_dir("/var/run/privoxy_pfsense")) {
		mwexec("/bin/rm -rf /var/run/privoxy_pfsense/");
	}
}

function validate_input_privoxy($post, &$input_errors) {
	if (isset($post['privoxy_enabled'])) {
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
	}
}

function sync_package_privoxy() {
	global $config, $g;

	conf_mount_rw();

	// Check Privoxy settings
	if (is_array($config['installedpackages']['privoxy'])) {
		$privoxy_config = $config['installedpackages']['privoxy']['config'][0];
		if ($privoxy_config['privoxy_enabled'] == "on") {
			$confdir = PRIVOXY_CONF_DIR;

			$UserParams = base64_decode($privoxy_config['userparams']);
			$listitems = explode (",",$privoxy_config['debug_level']);
			$debuglist = "";
			foreach( $listitems as $debug_item){
				$debuglist .= $debug_item ."\n";
			}
			$ForwardList = base64_decode($privoxy_config['forwardlist']);

			$privoxy_conf_file = <<< EOF
confdir {$confdir}
logdir /var/log/privoxy_pfsense
actionsfile match-all.action # Actions that are applied to all sites and maybe overruled later on.
actionsfile default.action   # Main actions file
#actionsfile user.action      # User customizations
filterfile default.filter
#filterfile user.filter      # User customizations
logfile logfile

{$debuglist}

hostname {$privoxy_config['hostname']}
listen-address  {$privoxy_config['listeaddress']}:{$privoxy_config['listenport']}
toggle  1
enable-remote-toggle  0
enable-remote-http-toggle  0
enable-edit-actions 0
enforce-blocks 0
buffer-limit 4096
enable-proxy-authentication-forwarding 0

{$ForwardList}

forwarded-connect-retries  0
accept-intercepted-requests 0
allow-cgi-request-crunching 0
split-large-forms 0
keep-alive-timeout 5
tolerate-pipelining 1
socket-timeout {$privoxy_config['sockettimeout']}
{$UserParams}


EOF;
			file_put_contents(PRIVOXY_CONF_DIR . "/config", strtr($privoxy_conf_file, array("\r" => "")));
		}
	}

	if (is_array($privoxy_config) && $privoxy_config['privoxy_enabled'] == "on") {
		mwexec("/usr/bin/sed -i \"\" 's/privoxy_pfsense_enable:=\"NO\"/privoxy_pfsense_enable:=\"YES\"/' " . PRIVOXY_RCFILE);
		restart_service("privoxy");
	} else {
		mwexec("/usr/bin/sed -i \"\" 's/privoxy_pfsense_enable:=\"YES\"/privoxy_pfsense_enable:=\"NO\"/' " . PRIVOXY_RCFILE);
		if (is_service_running("privoxy")) {
			stop_service("privoxy");
		}
	}

	conf_mount_ro();
}

?>
