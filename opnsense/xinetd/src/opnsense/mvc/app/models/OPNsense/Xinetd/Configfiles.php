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
class Configfiles extends MasterModel {

	private function disableLine($value)
	{
		switch ($value){
			case '0':
				return '	disable = no'."\n";
			case '1':
				return '	disable = yes'."\n";
		}
		return '';
	}
	
	private function flagsLine(
			$listflags) {
				if(empty($listflags)){
					return '';
				}
				$flagsLine = "	flags = ";
				foreach ( $listflags as $flag => $data ) {
					if ($data ['selected'] == 1) {
						$flagsLine .= $data ['value']." ";
					}
				}
				return $flagsLine."\n";
	}
	
	private function typeLine(
			$listtype) {
				if(empty($listtype)){
					return '';
				}
				$typeLine = "	type = ";
				foreach ( $listtype as $flag => $data ) {
					if ($data ['selected'] == 1) {
						$typeLine .= $data ['value']." ";
					}
				}
				return $typeLine."\n";
	}
	
	private function socketTypeLine(
			$listsocketType) {
				if(empty($listsocketType)){
					return '';
				}
				$socketTypeLine = "	socket_type = ";
				foreach ( $listsocketType as $flag => $data ) {
					if ($data ['selected'] == 1) {
						$socketTypeLine .= $data ['value'];
					}
				}
				return $socketTypeLine."\n";
	}
	
	private function protocolLine($value)
	{
		if (!empty($value)) {
			return '	protocol = '.$value."\n";
		}
		return '';
	}
	
	private function waitLine($value)
	{
		switch ($value){
			case '0':
				return '	wait = no'."\n";
			case '1':
				return '	wait = yes'."\n";
		}
		return '';
	}
	
	private function userLine($value)
	{
		if (!empty($value)) {
			return '	user = '.$value."\n";
		}
		return '';
	}
	
	private function groupLine($value)
	{
		if (!empty($value)) {
			return '	group = '.$value."\n";
		}
		return '';
	}
	
	private function instancesLine($value)
	{
		if (!empty($value)) {
			return '	instances = '.$value."\n";
		}
		return '';
	}
	
	private function niceLine($value)
	{
		if (!empty($value)) {
			return '	nice = '.$value."\n";
		}
		return '';
	}
	
	private function serverLine($value,$args)
	{
		if (!empty($value)) {
			$line='	server = '.$value."\n";
			if(!empty($args)){
				$line.='	server_args = '.$args."\n";
			}
			return $line;
		}
		return '';
	}
	
	private function onlyFromLine($value)
	{
		if (!empty($value)) {
			return '	only_from = '.$value."\n";
		}
		return '';
	}
	
	private function listenAddressLines($node){
		$this->getInterfaceData();
		$liste_ip=$this->getSystemInterfaces();
		foreach ( $node['listenAddress'] as $interface ) {
			if($interface['selected']==1){
				$name=strtolower($interface['value']);
				if(isset($liste_ip[$name])){
					return "	bind = " . (string)$liste_ip[$name]->ipaddr."\n";
				}
			}
		}
		
		if((string)$this->listenLocalhost === "1"){
			return "	bind = 127.0.0.1\n";
		}
		throw new \Exception("You need to select a listen interface (including localhost)");
	}
	
	private function listenPortLine($value)
	{
		if (!empty($value)) {
			return '	port = '.$value."\n";
		}
		return '';
	}
	
	private function logTypeLine($value)
	{
		if (!empty($value)) {
			return '	log_type = '.$value."\n";
		}
		return '';
	}
	
	private function logOnSuccessLine($value){
		$logOnSuccess='';
		foreach($value as $data){
				if($data['selected']==1){
					if(empty($logOnSuccess)){
						$logOnSuccess='		log_on_success = ';
					}
					$logOnSuccess.= " ".strtoupper($data['value']);
				}
		}
		return $logOnSuccess."\n";
	}
	
	private function logOnFailureLine($value){
		$logOnFailure='';
		foreach($value as $data){
				if($data['selected']==1){
					if(empty($logOnFailure)){
						$logOnFailure='		log_on_failure = ';
					}
					$logOnFailure.= " ".strtoupper($data['value']);
				}
			}
		return $logOnFailure."\n";
	}
	
	private function cpsLine($value)
	{
		if (!empty($value)) {
			return '	cps = '.$value."\n";
		}
		return '';
	}
	
	private function createService($node,$includeDir){
		$service=$node ['serviceName'];
		$serviceLine='service '.$node ['serviceName']."\n{\n";
		
		$serviceLine.=$this->disableLine($node ['disable']);
		$serviceLine.=$this->flagsLine($node ['flags']);
		$serviceLine.=$this->typeLine($node ['type']);
		$serviceLine.=$this->socketTypeLine($node ['socketType']);
		$serviceLine.=$this->protocolLine($node ['protocol']);
		$serviceLine.=$this->waitLine($node ['wait']);
		$serviceLine.=$this->userLine($node ['user']);
		$serviceLine.=$this->groupLine($node ['group']);
		
		$serviceLine.=$this->listenAddressLines($node);
		$serviceLine.=$this->listenPortLine($node['listenPort']);
		$serviceLine.=$this->onlyFromLine($node ['onlyFrom']);
		
		$serviceLine.=$this->serverLine($node['server'],$node['serverArgs']);
		$serviceLine.=$this->logTypeLine($node ['logType']);
		
		$serviceLine.=$this->instancesLine($node ['instances']);
		$serviceLine.=$this->niceLine($node ['nice']);
		$serviceLine.=$this->logOnSuccessLine($node ['logOnSuccess']);
		$serviceLine.=$this->logOnFailureLine($node ['logOnFailure']);
		$serviceLine.=$this->cpsLine($node ['cps']);
		
		$serviceLine.="\n}\n";
		
		
		file_put_contents ( $includeDir."/".$service , strtr ( $serviceLine, array (
				"\r" => ""
		) ) );
	}

	public function createConfigFiles($includeDir) {
		exec( "/bin/rm -f " .$includeDir. "/*" );
		foreach ( $this->configfiles->configfile->getChildren () as $alias ) {
			$node = $alias->getNodes ();
			//throw new \Exception ( print_r ( $node, true ) );
			if (! is_null ( $node )) {
				$this->createService($node,$includeDir);
				
			}
		}
		return $this;
	}
}
