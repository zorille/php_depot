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
namespace OPNsense\Xinetd\Api;

use \OPNsense\Base\ApiMutableModelControllerBase;
use \OPNsense\Base\UIModelGrid;
use \OPNsense\Core\Config;
use \OPNsense\Xinetd\Configfiles;
use \OPNsense\Xinetd\General;

/**
 * Class ConfigfilesController
 * @package OPNsense\Xinetd
 */
class ConfigfilesController extends \OPNsense\Xinetd\MasterController
{
	static protected $internalModelName = 'configfiles';
	static protected $internalModelClass = '\OPNsense\Xinetd\Configfiles';
	
	public function searchConfigfilesAction()
    {
    	return $this->searchBase('configfiles.configfile', array("serviceName", "disable","flags","type","socketType","protocol","wait","user","group","instances","nice","server","serverArgs","onlyFrom","listenAddress","listenLocalhost","listenPort","redirect","logType","logOnSuccess","logOnFailure","cps"),"serviceName");
    }
    
    public function getConfigfilesAction($uuid = NULL)
    {
    	return $this->getBase('configfile', 'configfiles.configfile', $uuid);
    }
    
    public function addConfigfilesAction()
    {
    	$mdlServer = new General();
    	$mdlServer->configDirty();
    	return $this->addBase('configfile', 'configfiles.configfile');
    }
    
    public function delConfigfilesAction($uuid)
    {
    	$mdlServer = new General();
    	$mdlServer->configDirty();
    	return $this->delBase('configfiles.configfile',$uuid);
    }
    
    public function setConfigfilesAction($uuid)
    {
    	$mdlServer = new General();
    	$mdlServer->configDirty();
    	return $this->setBase('configfile', 'configfiles.configfile',$uuid);
    }
    
    public function toggleConfigfilesAction($uuid)
    {
    	$mdlServer = new General();
    	$mdlServer->configDirty();
    	return $this->toggleBase('configfiles.configfile', $uuid);
    }
}
