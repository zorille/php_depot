<?php
/*
 * xinetd.inc
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

define('XINETD_BASE', '/usr/local');
define('XINETD_CONF', '/usr/local/etc/xinetd.conf');
define('XINETD_RCFILE', '/usr/local/etc/rc.d/xinetd_pfsense.sh');
//To have service start/stop/restart menu with pkg_xinetd.php
$shortcut_section = 'xinetd';

function php_install_xinetd() {
$rc_file = <<< EOF
#!/bin/sh
#
# $FreeBSD$
#
# PROVIDE: xinetd
# REQUIRE: DAEMON
#
# pfSense Package
# DO NOT TOUCH
#

. /etc/rc.subr

name=xinetd_pfsense


: \${xinetd_pfsense_enable:="NO"}
: \${xinetd_pfsense_config:="/usr/local/etc/xinetd.conf"}
: \${xinetd_pfsense_logdir:="/var/log/xinetd_pfsense"}
: \${xinetd_pfsense_piddir:="/var/run/xinetd_pfsense"}
: \${xinetd_pfsense_pidfile:="\${xinetd_pfsense_piddir}/xinetd.pid"}


xinetd_pfsense_prestart () {
    if [ ! -e "\${xinetd_pfsense_logdir}" ]; then
        echo "\${xinetd_pfsense_logdir} not found. Creating ..."
        mkdir "\${xinetd_pfsense_logdir}"
        chown "\${xinetd_pfsense_user}:\${xinetd_pfsense_user}" "\${xinetd_pfsense_logdir}"
        chmod 0750 "\${xinetd_pfsense_logdir}"
    fi
    if [ ! -d "\${xinetd_pfsense_piddir}" ]; then
        mkdir -p "\${xinetd_pfsense_piddir}"
        chown "\${xinetd_pfsense_user}:\${xinetd_pfsense_user}" "\${xinetd_pfsense_piddir}"
    fi
}

start_precmd="xinetd_pfsense_prestart"

command=/usr/local/sbin/xinetd
command_args='> /dev/null 2>&1'
xinetd_pfsense_flags=\${xinetd_pfsense_flags:-"-f \${xinetd_pfsense_config} -pidfile \${xinetd_pfsense_pidfile}"}

run_rc_command "$1"

EOF;
        file_put_contents(XINETD_RCFILE,$rc_file);
        @chmod(XINETD_RCFILE, 0755);
        @chown($fpath, "root");
}

function php_deinstall_xinetd() {
	unlink_if_exists(XINETD_RCFILE);
	unlink_if_exists(XINETD_CONF);

	if (is_dir("/var/log/xinetd_pfsense")) {
		mwexec("/bin/rm -rf /var/log/xinetd_pfsense/");
	}
	if (is_dir("/var/run/xinetd_pfsense")) {
		mwexec("/bin/rm -rf /var/run/xinetd_pfsense/");
	}
}

function validate_input_xinetd($post, &$input_errors) {
	if (isset($post['xinetd_enabled'])) {
	}
}

function sync_package_xinetd() {
	global $config, $g;

	conf_mount_rw();

	// Check Xinetd settings
	if (is_array($config['installedpackages']['xinetd'])) {
		$xinetd_config = $config['installedpackages']['xinetd']['config'][0];
		if ($xinetd_config['xinetd_enabled'] == "on") {
			$xinetd_conf_file=XINETD_BASE . "/etc/xinetd.conf";

			$XinetdServices = base64_decode($xinetd_config['xinetdservices']);
			$data = <<< EOF
{$XinetdServices}

EOF;
			file_put_contents($xinetd_conf_file, strtr($data, array("\r" => "")));
		}
	}

	if (is_array($xinetd_config) && $xinetd_config['xinetd_enabled'] == "on") {
		mwexec("/usr/bin/sed -i \"\" 's/xinetd_pfsense_enable:=\"NO\"/xinetd_pfsense_enable:=\"YES\"/' " . XINETD_RCFILE);
		restart_service("xinetd");
	} else {
		mwexec("/usr/bin/sed -i \"\" 's/xinetd_pfsense_enable:=\"YES\"/xinetd_pfsense_enable:=\"NO\"/' " . XINETD_RCFILE);
		if (is_service_running("xinetd")) {
			stop_service("xinetd");
		}
	}

	conf_mount_ro();
}

?>
