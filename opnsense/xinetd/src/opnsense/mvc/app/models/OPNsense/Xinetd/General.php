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
namespace OPNsense\Xinetd;

use OPNsense\Base\BaseModel;
use OPNsense\Core\Backend;


/**
 * Class Xinetd
 * @package OPNsense\Xinetd
 */
class General extends MasterModel
{
	private $xinetd_conf='/usr/local/opnsense/service/templates/OPNsense/Xinetd/xinetd.conf';
	
	private $xinetd_includeDir='/usr/local/etc/xinetd.d';

	/**
     * check if module is enabled
     * @return bool is the Xinetd service enabled
     */
    public function isEnabled()
    {
        if ((string)$this->enabled === "1") {
            return true;
        }
        return false;
    }
    
    private function logTypeLine()
    {
    	if (!empty((string)$this->logType)) {
    		return 'log_type = '.(string)$this->logType;
    	}
    	return '';
    }
    
    private function instancesLine()
    {
    	if (!empty((string)$this->instances)) {
    		return 'instances = '.(string)$this->instances;
    	}
    	return 'instances = UNLIMITED';
    }
    
    private function logOnSuccessLine(){
    	$logOnSuccess='';
    	foreach ( $this->logOnSuccess->getFlatNodes() as $item ) {
    		foreach($item->getNodeData() as $data){
    			if($data['selected']==1){
    				if(empty($logOnSuccess)){
    					$logOnSuccess='log_on_success = ';
    				}
    				$logOnSuccess.= " ".strtoupper($data['value']);
    			}
    		}
    	}
    	return $logOnSuccess;
    }
    
    private function logOnFailureLine(){
    	$logOnFailure='';
    	foreach ( $this->logOnFailure->getFlatNodes() as $item ) {
    		foreach($item->getNodeData() as $data){
    			if($data['selected']==1){
    				if(empty($logOnFailure)){
    					$logOnFailure='log_on_failure = ';
    				}
    				$logOnFailure.= " ".strtoupper($data['value']);
    			}
    		}
    	}
    	return $logOnFailure;
    }
    
    private function cpsLine()
    {
    	if (!empty((string)$this->cps)) {
    		return 'cps = '.(string)$this->cps;
    	}
    	return '';
    }
    
    private function createIncludeDir(){
    	if (!empty((string)$this->xinetd_includeDir)) {
    		if(!is_dir((string)$this->xinetd_includeDir)){
    			mkdir((string)$this->xinetd_includeDir,"0755",true);
    		}
    	}
    	return;
    }
    
    private function includedirLine()
    {
    	if (!empty((string)$this->xinetd_includeDir)) {
    		return 'includedir '.(string)$this->xinetd_includeDir;
    	}
    	return '';
    }
    
    private function clientListLine(){
    	$mdlCl=new Configfiles();
    	return $mdlCl->createConfigFiles((string)$this->xinetd_includeDir);
    }
        
    public function generateXinetdConf() {
    	if(! $this->isEnabled()){
    		return $this;
    	}
    	
    	$this->createIncludeDir();
    	$this->clientListLine();
    	
    	$xinetd_conf_file = <<< EOF
defaults
{
	{$this->instancesLine()}
	{$this->logTypeLine()}
	{$this->logOnSuccessLine()}
	{$this->logOnFailureLine()}
	{$this->cpsLine()}
}
{$this->includedirLine()}

EOF;

		exec ( "/bin/cp -f " . $this->xinetd_conf . " " . $this->xinetd_conf . "_sav" );
		file_put_contents ( $this->xinetd_conf, strtr ( $xinetd_conf_file, array (
				"\r" => ""
		) ) );

    	return $this;
    }
    
}
