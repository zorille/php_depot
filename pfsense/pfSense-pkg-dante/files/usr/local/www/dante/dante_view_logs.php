<?php
/*
 * dante_view_logs.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2006-2016 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2003-2004 Manuel Kasper
 * Copyright (c) 2005 Bill Marquette
 * Copyright (c) 2009 Robert Zelaya Sr. Developer
 * Copyright (c) 2016 Bill Meeks
 * Copyright (c) 2018 Damien Vargas
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

##|+PRIV
##|*IDENT=dante-view-logs
##|*NAME=Services: Dante
##|*DESCR=Allow access to the 'Services: Dante' page.
##|*MATCH=dante_view_logs.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("dante.inc");

$dantelogdir = '/var/log/dante_pfsense/';

// Limit all file access to just the currently selected interface's logging subdirectory
$logfile = htmlspecialchars($dantelogdir . basename($_POST['file']));

if ($_POST['action'] == 'load') {
	if(!is_file($logfile)) {
		echo "|3|" . gettext("Log file does not exist or that logging feature is not enabled") . ".|";
	} else {
		//$data = file_get_contents($logfile);
		exec("/usr/bin/tail -50 ".$logfile,$return);
		$return=array_reverse($return);
		$data = implode("\n",$return);
		if($data === false) {
			echo "|1|" . gettext("Failed to read log file") . ".|";
		} else {
			$data = base64_encode($data);
			echo "|0|{$logfile}|{$data}|";
		}
	}

	exit;
}

if ($_POST['action'] == 'clear') {
	file_put_contents($logfile, "");

	exit;
}

$pgtitle = array(gettext("Package"), gettext("Dante"), gettext("Logs View"));
include_once("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

function build_logfile_list() {

	$list = array();

	if (is_array($config['installedpackages']['dante']) && isset($config['installedpackages']['dante']['config'][0]['dantelogoutput'])) {
		$logs = array( $config['installedpackages']['dante']['config'][0]['dantelogoutput']);
	} else {
		$logs = array( 'sockd.log');
	}
	foreach ($logs as $log) {
		$list[$log] = $log;
	}

	return($list);
}

if ($savemsg) {
	print_info_box($savemsg);
}

add_header_menu("View Logs");

$form = new Form(false);

$section = new Form_Section('Logs Browser Selections');

$section->addInput(new Form_Select(
	'logFile',
	'Log File to View',
	basename($logfile),
	build_logfile_list()
))->setHelp('Choose which log you want to view..');

// Build the HTML text to display in the StaticText control
$staticContent = '<span style="display:none; " id="fileStatusBox">' .
		'<strong id="fileStatus"></strong>' .
		'</span>' .
		'<p style="display:none;" id="filePathBox">' .
		'<strong>' . gettext("Log File Path: ") . '</strong>' . '<span style="display:inline;" id="fbTarget"></span>' . '</p>' . 
		'<p style="padding-right:15px; display:none;" id="fileRefreshBtn">' . 
		'<button type="button" class="btn btn-sm btn-info" name="refresh" id="refresh" onclick="loadFile();" title="' . 
		gettext("Refresh current display") . '"><i class="fa fa-repeat icon-embed-btn"></i>' . gettext("Refresh") . '</button>&nbsp;&nbsp;' . 
		'<button type="button" class="btn btn-sm btn-danger hidden no-confirm" name="fileClearBtn" id="fileClearBtn" ' . 
		'onclick="clearFile();" title="' . gettext("Clear selected log file contents") . '"><i class="fa fa-trash icon-embed-btn"></i>' . 
		gettext("Clear") . '</button></p>';

$section->addInput(new Form_StaticText(
	'Status/Result',
	$staticContent
));

$form->add($section);

print($form);
?>

<script>
//<![CDATA[
	function loadFile() {
		$("#fileStatus").html("<?=gettext("Loading file"); ?> ...");
		$("#fileStatusBox").show(250);
		$("#filePathBox").show(250);
		$("#fbTarget").html("");

		$.ajax(
				"<?=$_SERVER['SCRIPT_NAME'];?>",
				{
					type: 'post',
					data: {
						action:    'load',
						file: $("#logFile").val()
					},
					complete: loadComplete
				}
		);
	}

	function loadComplete(req) {
		$("#fileContent").show(250);
		var values = req.responseText.split("|");
		values.shift(); values.pop();

		if(values.shift() == "0") {
			var file = values.shift();
			var fileContent = atob(values.join("|"));
			$("#fileStatus").removeClass("text-danger");
			$("#fileStatus").addClass("text-success");
			$("#fileStatus").html("<?=gettext("File successfully loaded"); ?>.");
			$("#fbTarget").removeClass("text-danger");
			$("#fbTarget").html(basename(file));
			$("#fileRefreshBtn").show();
			$("#fileClearBtn").removeClass("hidden");
			$("#fileContent").prop("disabled", false);
			$("#fileContent").val(fileContent);
		}
		else {
			$("#fileStatus").addClass("text-danger");
			$("#fileStatus").html(values[0]);
			$("#fbTarget").addClass("text-danger");
			$("#fbTarget").html("<?=gettext("Not Available"); ?>");
			$("#fileRefreshBtn").hide();
			$("#fileContent").val("");
			$("#fileContent").prop("disabled", true);
		}
	}

	function clearFile() {
		if (confirm("<?=gettext('Are you sure want to erase the log contents?'); ?>")) {
			$.ajax(
				"<?=$_SERVER['SCRIPT_NAME'];?>",
				{
					type: 'post',
					data: {
						action:    'clear',
						file: $("#logFile").val()
					},
				}
			);
			$("#fileContent").val("");
		}
	}

	function basename(path) {
		return path.replace( /\\/g, '/' ).replace( /.*\//, '' );
	}

events.push(function() {

    //-- Click handlers -----------------------------
    $('#logFile').on('change', function() {
	$("#fbTarget").html("");
        loadFile();
    });

    $('#refresh').on('click', function() {
        loadFile();
    });

    //-- Show nothing on initial page load -----------
<?php if(empty($_POST['file'])): ?>
	document.getElementById("logFile").selectedIndex=-1;
<?php endif; ?>

});
//]]>
</script>

<div class="panel panel-default" id="fileOutput">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('Log Contents')?></h2></div>
		<div class="panel-body">
			<textarea id="fileContent" name="fileContent" style="width:100%;" rows="20" wrap="off" disabled></textarea>
		</div>
</div>

<?php include("foot.inc"); ?>

