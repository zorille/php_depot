<?php
require_once("guiconfig.inc");
require_once("SockdIOPS/network.inc");

$logfile = '/var/log/SockdIOPS_opnsense/'.build_logfile_list();
$logclog = false;

$service_hook = 'sockdiops';

require_once 'diag_logs_template.inc';
