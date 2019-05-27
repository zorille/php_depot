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
use \OPNsense\Core\Config;

/**
 * Class Xinetd
 * @package OPNsense\Xinetd
 */
abstract class MasterModel extends BaseModel
{
	private $system_interfaces = array();
	
	/**
	 * collect interface names
	 * @return array interface mapping (raw interface to description)
	 */
	protected function getInterfaceData() {
		$intfmap = array();
		$config = Config::getInstance()->object();
		if ($config->interfaces->count() > 0) {
			foreach ($config->interfaces->children() as $key => $node) {
				$nom = !empty((string)$node->descr) ? (string)$node->descr : $key;
				$intfmap[strtolower($nom)] = $node;
			}
		}
		$this->setSystemInterfaces($intfmap);
		return $this->getSystemInterfaces();
	}
	
	public function getSystemInterfaces(){
		return $this->system_interfaces;
	}
	
	public function setSystemInterfaces($interfaces){
		$this->system_interfaces=$interfaces;
		return $this;
	}
	
	/**
	 * get configuration state
	 * @return bool
	 */
	public function configChanged()
	{
		return file_exists("/tmp/xinetd.dirty");
	}
	
	/**
	 * mark configuration as changed
	 * @return bool
	 */
	public function configDirty()
	{
		return @touch("/tmp/xinetd.dirty");
	}
	
	/**
	 * mark configuration as consistent with the running config
	 * @return bool
	 */
	public function configClean()
	{
		return @unlink("/tmp/xinetd.dirty");
	}
}
