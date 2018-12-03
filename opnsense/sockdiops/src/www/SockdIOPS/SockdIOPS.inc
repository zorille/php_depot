<?php
function build_logfile_list() {
	$config = &config_read_array('sockdiops', 'sockdglobal');
	$list = array();
	
	if (is_array($config) && isset($config['global']['logOutput'])) {
		$logs = array( $config['global']['logOutput']);
	} else {
		$logs = array( 'sockd.log');
	}
	foreach ($logs as $log) {
		$list[$log] = $log;
	}
	
	return($list);
}

?>
