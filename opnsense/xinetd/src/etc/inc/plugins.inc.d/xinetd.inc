<?php

/**
 *    Copyright (C) 2018 Damien Vargas
 *    Copyright (C) 2017 Frank Wall
 *    All rights reserved.
 *
 *    Redistribution and use in source and binary forms, with or without
 *    modification, are permitted provided that the following conditions are met:
 *
 *    1. Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *    2. Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 *    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 *    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 *    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 *    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 *    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 *    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 *    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 *    POSSIBILITY OF SUCH DAMAGE.
 */

function xinetd_enabled()
{
    global $config;

    return isset($config['OPNsense']['xinetd']['global']['enabled']) &&
    $config['OPNsense']['xinetd']['global']['enabled'] == 1;
}

function xinetd_configure()
{
	return array(
			//'earlybootup'=>array(),
			'bootup' => array('xinetd_configure_do'),
			'newwanip'=>array('xinetd_reconfigure_do:2'),
			//'vpn' => array('myplugin_configure_vpn:2')
	);
}

function xinetd_configure_do($verbose = false)
{
	$res = configd_run('xinetd configure');
	
	if ($verbose) {
		
		printf("Xinetd configure: %s\n", trim($res));
		
	}
	return;
}

function xinetd_reconfigure_do($verbose = false, $interface ='')
{
	$res = configd_run('xinetd reconfigure');
	
	if ($verbose) {
		
		printf("Xinetd reconfigure: %s for %s\n", trim($res),$interface);
		
	}
	return;
}

function xinetd_firewall($fw)
{
    if (!xinetd_enabled()) {
        return;
    }
}

/**
 *  register legacy service
 * @return array
 */
function xinetd_services()
{
    $services = array();

    if (!xinetd_enabled()) {
        return $services;
    }

    $services[] = array(
        'description' => gettext('Xinetd'),
        'pidfile' => '/var/run/xinetd.pid',
        'configd' => array(
            'restart' => array('xinetd restart'),
            'start' => array('xinetd start'),
            'stop' => array('xinetd stop'),
        ),
        'name' => 'xinetd',
    );

    return $services;
}

/**
 *  sync configuration via xmlrpc
 * @return array
 */

function xinetd_xmlrpc_sync()
{
    $result = array();
    $result['id'] = 'xinetd';
    $result['section'] = 'OPNsense.xinetd';
    $result['description'] = gettext('Sync Xinetd tool');
    return array($result);
}
