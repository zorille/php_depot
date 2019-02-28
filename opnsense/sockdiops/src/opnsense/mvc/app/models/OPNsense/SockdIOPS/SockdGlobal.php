<?php
/**
 *    Copyright (C) 2018 Damien Vargas
 *    Copyright (C) 2017 Frank Wall
 *    Copyright (C) 2015 Deciso B.V.
 *
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
 *
 */
namespace OPNsense\SockdIOPS;

use OPNsense\Base\BaseModel;
use OPNsense\Core\Backend;


/**
 * Class SockdIOPS
 * @package OPNsense\SockdIOPS
 */
class SockdGlobal extends GlobalModel
{
	private $sockdiops_conf='/usr/local/opnsense/service/templates/OPNsense/SockdIOPS/sockdiops.conf';
	private $sockdiops_log_dir='/var/log/SockdIOPS_opnsense';
	private $sockdiops_rc_conf='/etc/rc.conf.d/sockdiops';
    /**
     * check if module is enabled
     * @return bool is the SockdIOPS service enabled
     */
    public function isEnabled()
    {
        if ((string)$this->global->enabled === "1") {
            return true;
        }
        return false;
    }
    
    private function internalInterfaceLines(){
    	$internallist="";
    	foreach ( $this->global->internalInterfaces->getFlatNodes() as $item ) {
    		foreach($item->getNodeData() as $interface){
    			if($interface['selected']==1){
    				$internallist .= "internal: " . $this->getInterfaceName(array($interface)) . $this->getPortLine($this->global->listenPort,"=") . "\n";
    			}
    		}
    	}
    	if((string)$this->global->listenLocalhost === "1"){
    		$internallist .= "internal: 127.0.0.1 " . $this->getPortLine($this->global->listenPort,"=") . "\n";
    	}
    	return $internallist;
    }
    
    private function externalInterfaceLines(){
    	$internallist="";
    	foreach ( $this->global->externalInterfaces->getFlatNodes() as $item ) {
    		foreach($item->getNodeData() as $interface){
    			if($interface['selected']==1){
    				$internallist .= "external: " . $this->getInterfaceName(array($interface)) . "\n";
    			}
    		}
    	}
    	return $internallist;
    }
    
    private function externalRotationLine(){
    	foreach ( $this->global->externalRotation->getFlatNodes() as $item ) {
    		foreach($item->getNodeData() as $data){
    			if($data['selected']==1){
    				return "external.rotation: " . strtolower($data['value']) . "\n";
    			}
    		}
    	}
    	return 'external.rotation: none'."\n";
    }
    
    private function DnsResolveDstLine()
    {
    	if ((string)$this->global->dnsresolvdst === "1") {
    		return 'dnsresolvdst: yes';
    	}
    	return 'dnsresolvdst: no';
    }
    
    private function srcHostLine(){
    	$srcHost='';
    	foreach ( $this->global->srcHost->getFlatNodes() as $item ) {
    		foreach($item->getNodeData() as $data){
    			if($data['selected']==1){
    				if(empty($srcHost)){
    					$srcHost='srchost: ';
    				}
    				$srcHost.= " ".strtolower($data['value']);
    			}
    		}
    	}
    	return $srcHost;
    }
    
    private function outputMethodLine(){
    	$line="";
    	foreach ( $this->global->outputMethod as $item ) {
    		if($item['selected']==1){
    			$line .= " " . $item['value'];
    		}
    	}
    	return $line;
    }
    
    private function clientListLine(){
    	$mdlCl=new ClientsList();
    	return $mdlCl->createClientsRules();
    }
    
    private function sockListLine(){
    	$mdlCl=new SocksList();
    	return $mdlCl->createSocksRules();
    }
    
    private function routeListLine(){
    	$mdlCl=new RoutesList();
    	return $mdlCl->createRoutesRules();
    }
    
    private function manageStartup(){
    	if($this->isEnabled()){
    		file_put_contents ( $this->sockdiops_rc_conf, 'sockdiops_enable="YES"'."\n" );
    	} else {
    		file_put_contents ( $this->sockdiops_rc_conf, 'sockdiops_enable="NO"'."\n" );
    	}
    	return $this;
    }
    
    public function generateSockdiopsConf() {
    	if(! $this->isEnabled()){
    		return $this;
    	}
    	
    	$internallist = $this->internalInterfaceLines();
    	$externallist = $this->externalInterfaceLines();
    	$externalRotation = $this->externalRotationLine();
    	$socksmethod=(string)$this->global->socksMethod;
    	$clientmethod=(string)$this->global->clientMethod;
    	$userprivileged=(string)$this->global->userPrivileged;
    	$userunprivileged=(string)$this->global->userUnPrivileged;
    	$dnsresolvdst=$this->DnsResolveDstLine();
    	$srcHost = $this->srcHostLine();
    	$clienttimeout=(string)$this->global->clientTimeout;
    	$sockettimeout=(string)$this->global->socketTimeout;
    	$listlogs = $this->outputMethodLine();
    	$logFile = (string)$this->global->logOutput;
    	$ClientPass = $this->clientListLine();
    	$SocksPass = $this->sockListLine();
    	$Routes = $this->routeListLine();
    	$UserParams = (string)$this->global->userParams;
    	
    	$sockdiops_conf_file = <<< EOF
logoutput: {$listlogs} {$this->sockdiops_log_dir}/{$logFile}

{$internallist}
{$externallist}
{$externalRotation}

socksmethod: {$socksmethod}
clientmethod: {$clientmethod}
user.privileged: {$userprivileged}
user.unprivileged: {$userunprivileged}
{$srcHost}
{$dnsresolvdst}

timeout.io: {$clienttimeout}
timeout.negotiate: {$sockettimeout}

{$UserParams}

{$ClientPass}

{$SocksPass}

{$Routes}

EOF;

		exec ( "/bin/cp -f " . $this->sockdiops_conf . " " . $this->sockdiops_conf . "_sav" );
		file_put_contents ( $this->sockdiops_conf, strtr ( $sockdiops_conf_file, array (
				"\r" => ""
		) ) );

    	return $this;
    }
    
}
