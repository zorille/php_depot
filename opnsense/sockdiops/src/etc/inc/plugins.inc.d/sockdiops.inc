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

function sockdiops_enabled()
{
    global $config;

    return isset($config['OPNsense']['sockdiops']['sockdglobal']['global']['enabled']) &&
    $config['OPNsense']['sockdiops']['sockdglobal']['global']['enabled'] == 1;
}

function sockdiops_configure()
{
	return array(
			//'earlybootup'=>array(),
			'bootup' => array('sockdiops_configure_do'),
			'newwanip'=>array('sockdiops_reconfigure_do:2'),
			//'vpn' => array('myplugin_configure_vpn:2')
	);
}

function sockdiops_configure_do($verbose = false)
{
	$res = configd_run('sockdiops configure');
	
	if ($verbose) {
		
		printf("SockdIOPS configure: %s\n", trim($res));
		
	}
	return;
}

function sockdiops_reconfigure_do($verbose = false, $interface ='')
{
	$res = configd_run('sockdiops reconfigure');
	
	if ($verbose) {
		
		printf("SockdIOPS reconfigure: %s for %s\n", trim($res),$interface);
		
	}
	return;
}

function sockdiops_firewall($fw)
{
    if (!sockdiops_enabled()) {
        return;
    }

    //$fw->registerAnchor('sockdiops/*', 'nat');
    //$fw->registerAnchor('sockdiops/*', 'rdr');
    //$fw->registerAnchor('sockdiops/*', 'fw');
}

/**
 *  register legacy service
 * @return array
 */
function sockdiops_services()
{
    $services = array();

    if (!sockdiops_enabled()) {
        return $services;
    }

    $services[] = array(
        'description' => gettext('SockdIOPS Server'),
        'pidfile' => '/var/run/SockdIOPS_opnsense/sockdiops.pid',
        'configd' => array(
            'restart' => array('sockdiops restart'),
            'start' => array('sockdiops start'),
            'stop' => array('sockdiops stop'),
        ),
        'name' => 'sockdiops',
    );

    return $services;
}

/**
 *  sync configuration via xmlrpc
 * @return array
 */

function sockdiops_xmlrpc_sync()
{
    $result = array();
    $result['id'] = 'sockdiops';
    $result['section'] = 'OPNsense.sockdiops';
    $result['description'] = gettext('Sync SockdIOPS tool');
    return array($result);
}
