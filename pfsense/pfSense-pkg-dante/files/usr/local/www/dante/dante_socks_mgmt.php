<?php
/*
 * dante_socks_mgmt.php
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
// #|+PRIV
// #|*IDENT=dante-socks-mgmt
// #|*NAME=Services: Dante
// #|*DESCR=Allow access to the 'Services: Dante' page.
// #|*MATCH=dante_socks_mgmt.php*
// #|-PRIV
require_once ("guiconfig.inc");
require_once ("dante.inc");
global $config;

// Gestion des PASS
// Grab saved settings from configuration
if (! is_array ( $config ['installedpackages'] ['dante'] ['passsocks'] )) {
	$config ['installedpackages'] ['dante'] ['passsocks'] = array ();
}
$pass_socks = &$config ['installedpackages'] ['dante'] ['passsocks'];
//Changing array order
if (isset ( $_POST ['order-store'] ) && $_POST ['order-store']=="Save") {
	$tmp_order=array();
	$pos=count($_POST);
	//We check new order
	for ($i=0;$i<=$pos;$i++){
		if(isset($_POST["frc".$i]) && isset($pass_socks[$_POST["frc".$i]])){
			$tmp_order[$_POST["frc".$i]]=$pass_socks[$_POST["frc".$i]];
		}
	}
	// Write the new configuration
	$pass_socks=$tmp_order;
	$savemsg="Order changed for Pass Socks entries.";
	write_config ( "Dante pkg: ".$savemsg );
	unset($tmp_order);
}
// Set default to not show Pass Socks modification lists editor
$passsockslist_edit_style = "display: none;";
if (isset ( $_POST ['SocksPassList_dup'] ) && isset ( $pass_socks [$_POST ['SocksPassList_id']] )) {
	// Write the new configuration
	$id = 'a' . uniqid ();
	$pass_socks[$id]=$pass_socks[$_POST ['SocksPassList_id']];
	$pass_socks[$id]['id']=$id;
	write_config ( "Dante pkg: Cloned Pass Socks entry from list." );
}
if (isset ( $_POST ['SocksPassList_delete'] ) && isset ( $pass_socks [$_POST ['SocksPassList_id']] )) {
	// Write the new configuration
	unset ( $pass_socks [$_POST ['SocksPassList_id']] );
	write_config ( "Dante pkg: deleted pass socks entry from list." );
}
// Permet de mettre les variable dans l'objet de modification
if (isset ( $_POST ['SocksPassList_edit'] ) && isset ( $pass_socks [$_POST ['SocksPassList_id']] )) {
	$passsockslist_edit_style = "show";
	$passsockslist_id = $pass_socks [$_POST ['SocksPassList_id']] ['id'];
	if (empty ( $pass_socks [$_POST ['SocksPassList_id']] ['fromip'] )) {
		$passsockslist_fromip = $pass_socks [$_POST ['SocksPassList_id']] ['fromfqdn'];
	} else {
		$passsockslist_fromip = $pass_socks [$_POST ['SocksPassList_id']] ['fromip'];
	}
	$passsockslist_fromcidr = $pass_socks [$_POST ['SocksPassList_id']] ['fromcidr'];
	if (empty ( $pass_socks [$_POST ['SocksPassList_id']] ['toip'] )) {
		$passsockslist_toip = $pass_socks [$_POST ['SocksPassList_id']] ['tofqdn'];
	} else {
		$passsockslist_toip = $pass_socks [$_POST ['SocksPassList_id']] ['toip'];
	}
	$passsockslist_tocidr = $pass_socks [$_POST ['SocksPassList_id']] ['tocidr'];
	$passsockslist_tport = $pass_socks [$_POST ['SocksPassList_id']] ['tport'];
	$SocksPassList_logstype = $pass_socks [$_POST ['SocksPassList_id']] ['logstype'];
	$SocksPassList_protocol = $pass_socks [$_POST ['SocksPassList_id']] ['protocol'];
	$SocksPassList_clientmethod = $pass_socks [$_POST ['SocksPassList_id']] ['clientmethod'];
}
if (isset ( $_POST ['PassSave'] ) && isset ( $_POST ['SocksPassList_id'] )) {
	if (! empty ( $_POST ['SocksPassList_id'] ) && isset ( $pass_socks [$_POST ['SocksPassList_id']] )) {
		$id = $_POST ['SocksPassList_id'];
	} else {
		$id = 'a' . uniqid ();
	}
	$tmp = array (
			"id" => $id,
			"fromip" => '',
			"fromcidr" => '',
			'fromfqdn' => '',
			"toip" => '',
			"tocidr" => '',
			'tofqdn' => '',
			"tport" => '',
			"protocol" => '',
			"logstype" => '',
			"clientmethod" => '',
	);
	if (valide_ip_cidr_fqdn ( $tmp, 'Pass', 'Socks','from' ) && valide_ip_cidr_fqdn ( $tmp, 'Pass', 'Socks','to' ) && valide_port ( $tmp, 'Pass', 'Socks','t' )) {
		$tmp ['protocol'] = $_POST ['SocksPassList_protocol'];
		$tmp ['logstype'] = $_POST ['SocksPassList_logstype'];
		$tmp ['clientmethod'] = $_POST ['SocksPassList_clientmethod'];
		$pass_socks [$tmp ['id']] = $tmp;
		$savemsg = "Dante pkg:Pass Socks created in list.";
		write_config ( $savemsg );
	}
}
$passsockslists = $pass_socks;
if (! is_array ( $passsockslists )) {
	$passsockslists = array ();
}
// Sync to configured CARP slaves if any are enabled
// dante_sync_on_changes();
// Get all the Active Socks Lists as an array
// Leave this as the last thing before spewing the page HTML
// so we can pick up any changes made in code above.
// Gestion des BLOCK
if (! is_array ( $config ['installedpackages'] ['dante'] ['blocksocks'] )) {
	$config ['installedpackages'] ['dante'] ['blocksocks'] = array ();
}
$block_socks = &$config ['installedpackages'] ['dante'] ['blocksocks'];
$blocksockslist_edit_style = "display: none;";
if (isset ( $_POST ['SocksBlockList_delete'] ) && isset ( $block_socks [$_POST ['SocksBlockList_id']] )) {
	// Write the new configuration
	unset ( $block_socks [$_POST ['SocksBlockList_id']] );
	write_config ( "Dante pkg: deleted block socks entry from list." );
}
// Permet de mettre les variable dans l'objet de modification
if (isset ( $_POST ['SocksBlockList_edit'] ) && isset ( $block_socks [$_POST ['SocksBlockList_id']] )) {
	$blocksockslist_edit_style = "show";
	$blocksockslist_id = $block_socks [$_POST ['SocksBlockList_id']] ['id'];
	if (empty ( $block_socks [$_POST ['SocksBlockList_id']] ['fromip'] )) {
		$blocksockslist_fromip = $block_socks [$_POST ['SocksBlockList_id']] ['fromfqdn'];
	} else {
		$blocksockslist_fromip = $block_socks [$_POST ['SocksBlockList_id']] ['fromip'];
	}
	$blocksockslist_fromcidr = $block_socks [$_POST ['SocksBlockList_id']] ['fromcidr'];
	if (empty ( $block_socks [$_POST ['SocksBlockList_id']] ['toip'] )) {
		$blocksockslist_toip = $block_socks [$_POST ['SocksBlockList_id']] ['tofqdn'];
	} else {
		$blocksockslist_toip = $block_socks [$_POST ['SocksBlockList_id']] ['toip'];
	}
	$blocksockslist_tocidr = $block_socks [$_POST ['SocksBlockList_id']] ['tocidr'];
	$blocksockslist_tport = $block_socks [$_POST ['SocksBlockList_id']] ['tport'];
	$blocksockslist_logstype = $block_socks [$_POST ['SocksBlockList_id']] ['logstype'];
}
if (isset ( $_POST ['BlockSave'] ) && isset ( $_POST ['SocksBlockList_id'] )) {
	if (! empty ( $_POST ['SocksBlockList_id'] ) && isset ( $block_socks [$_POST ['SocksBlockList_id']] )) {
		$id = $_POST ['SocksBlockList_id'];
	} else {
		$id = 'a' . uniqid ();
	}
	$tmp = array (
			"id" => $id,
			"fromip" => '',
			"fromcidr" => '',
			'fromfqdn' => '',
			"toip" => '',
			"tocidr" => '',
			'tofqdn' => '',
			"tport" => '',
			"logstype" => ''
	);
	if (valide_ip_cidr_fqdn ( $tmp, 'Block', 'Socks','from' ) && valide_ip_cidr_fqdn ( $tmp, 'Block', 'Socks','to' ) && valide_port ( $tmp, 'Block', 'Socks','t' )) {
		$tmp ['logstype'] = $_POST ['SocksBlockList_logstype'];
		$block_socks [$tmp ['id']] = $tmp;
		$savemsg = "Dante pkg:Block Socks created in list.";
		write_config ( $savemsg );
	}
}
$blocksockslists = $block_socks;
if (! is_array ( $blocksockslists )) {
	$blocksockslists = array ();
}
$pgtitle = array (
		gettext ( "Services" ),
		gettext ( "Dante" ),
		gettext ( "Socks Mgmt" )
);
include_once ("head.inc");
add_header_menu ( "Socks" );
/* Display Alert message, under form tag or no refresh */
if ($input_errors) {
	print_input_errors ( $input_errors );
}
if ($savemsg) {
	print_info_box ( $savemsg, 'success' );
}
?>

<form action="dante_socks_mgmt.php" method="post"
	enctype="multipart/form-data" name="iform" id="iform">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title"><?=gettext("Socks Pass List")?></h2>
		</div>
		<div class="panel-body table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<tbody>
					<tr>
						<td>
							<table class="table table-striped table-hover table-condensed">
								<thead>
									<tr>
										<th><?=gettext("Source FQDN/IP with CIDR")?></th>
										<th><?=gettext("Target FQDN/IP with CIDR")?></th>
										<th><?=gettext("Target Port")?></th>
										<th><?=gettext("Logs Type")?></th>
										<th><?=gettext("Protocol")?></th>
										<th><?=gettext("Client Method")?></th>
										<th><?=gettext("Actions")?></th>
									</tr>
								</thead>
								<tbody class="user-entries ui-sortable" style="display: table-row-group;">
									<?php $pos=0;?>
									<?php foreach ($passsockslists as $i => $list): ?>
										<tr id="fr<?php echo $pos ?>" class="ui-sortable-handle">
										<input id="frc<?php echo $pos ?>" name="frc<?php echo $pos ?>" value="<?php echo $list['id'] ?>" type="hidden"/>
										<?php if(!empty($list['fromip'])) { ?>
										<td><? echo $list['fromip']; ?>/<? echo $list['fromcidr']; ?></td>
										<?php } else { ?>
										<td><? echo $list['fromfqdn']; ?></td>
										<?php } ?>
										<?php if(!empty($list['toip'])) { ?>
										<td><? echo $list['toip']; ?>/<? echo $list['tocidr']; ?></td>
										<?php } else { ?>
										<td><? echo $list['tofqdn']; ?></td>
										<?php } ?>
										<td><? echo $list['tport']; ?></td>
										<td><? echo $list['logstype']; ?></td>
										<td><? echo $list['protocol']; ?></td>
										<td><? echo $list['clientmethod']; ?></td>
										<td><a name="SocksPassList_editX[]"
											id="SocksPassList_editX[]" type="button"
											title="<?=gettext('Edit this Active Socks Entry');?>"
											onClick='SocksPassListid="<?=$i;?>"'
											style="cursor: pointer;"> <i class="fa fa-pencil"></i>
										</a>
										<a name="SocksPassList_dupX[]"
											id="SocksPassList_dupX[]" type="button"
											title="<?=gettext('Duplicate this Active Socks Entry');?>"
											onClick='SocksPassListid="<?=$i;?>"'
											style="cursor: pointer;"> <i class="fa fa-clone"></i>
										</a>
										<a name="SocksPassList_deleteX[]"
											id="SocksPassList_deleteX[]" type="button"
											title="<?=gettext('Delete this Active Socks Entry');?>"
											onClick='SocksPassListid="<?=$i;?>"'
											style="cursor: pointer;"> <i class="fa fa-trash"
												title="<?=gettext('Delete this Active Socks Entry');?>"></i>
										</a></td>
									</tr>
									<?php $pos++; ?>
						<?php endforeach; ?>
							
							
							
							
							
							
							</table>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Modal SID editor window -->
		<div class="modal fade" role="dialog" id="SocksPassList_editor">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"
							aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>

						<h3 class="modal-title" id="myModalLabel"><?=gettext("Socks PASS List Editor")?></h3>
					</div>

					<div class="modal-body">
						<input type="hidden" name="SocksPassList_id"
							id="SocksPassList_id" value="<?=$passsockslist_id;?>" /> <input
							type="hidden" name="SocksPassList_ruleType"
							id="SocksPassList_ruleType" value="Pass" />
						<?=gettext("FROM: ");?>
						<?=gettext(" FQDN or IP: ");?>
						<input type="text" size="40" class="form-control file"
							id="SocksPassList_fromip" name="SocksPassList_fromip"
							value="<?=$passsockslist_fromip;?>" /><br />
						<?=gettext(" CIDR (Only in case of IP): ");?>
						<input type="text" size="2" class="form-control number"
							style="width: 40px;" id="SocksPassList_fromcidr"
							name="SocksPassList_fromcidr" value="<?=$passsockslist_fromcidr;?>" /><br />
						<?=gettext("TO: ");?>
						<?=gettext(" FQDN or IP: ");?>
						<input type="text" size="40" class="form-control file"
							id="SocksPassList_toip" name="SocksPassList_toip"
							value="<?=$passsockslist_toip;?>" /><br />
						<?=gettext(" CIDR (Only in case of IP): ");?>
						<input type="text" size="2" class="form-control number"
							style="width: 40px;" id="SocksPassList_tocidr"
							name="SocksPassList_tocidr" value="<?=$passsockslist_tocidr;?>" /><br />
						<?=gettext("target Port(s): ");?>
						<input type="text" size="8" class="form-control file"
							style="width: 200px;" id="SocksPassList_tport"
							name="SocksPassList_tport" value="<?=$passsockslist_tport;?>" /><br />
						<?=gettext("Log Type (error connect disconnect): ");?>
						<input type="text" size="40" class="form-control file"
							id="SocksPassList_logstype" name="SocksPassList_logstype"
							value="<?=$SocksPassList_logstype;?>" /><br />
						<?=gettext("Protocol (tcp udp): ");?>
						<input type="text" size="8" class="form-control file"
							id="SocksPassList_protocol"
							name="SocksPassList_protocol"
							value="<?=$SocksPassList_protocol;?>" /><br />
						<?=gettext("Clientmethod (username rfc931 pam): ");?>
						<input type="text" size="8" class="form-control file"
							id="SocksPassList_clientmethod"
							name="SocksPassList_clientmethod"
							value="<?=$SocksPassList_clientmethod;?>" /><br />
						<button type="submit" class="btn btn-sm btn-primary" id="PassSave"
							name="PassSave" value="<?=gettext("Save");?>"
							title="<?=gettext("Save changes and close editor");?>">
							<i class="fa fa-save icon-embed-btn"></i>
							<?=gettext("Save");?>
						</button>
						<button type="button" class="btn btn-sm btn-warning" id="cancel"
							name="cancel" value="<?=gettext("Cancel");?>"
							data-dismiss="modal"
							title="<?=gettext("Abandon changes and quit editor");?>">
							<?=gettext("Cancel");?>
						</button>
						<br />
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="infoblock"> 
<?php
print_info_box ( '<p>' . 'The rules controlling what socks are allowed what requests<br />
Pass any http connects to the example.com domain if they authenticate with username.<br />
socks pass {<br />
        from: 10.0.0.0/8 to: 0.0.0.0/0 port = http<br />
        log: connect error<br />
        clientmethod: username<br />
}<br />
Everyone from our internal network, 10.0.0.0/8 is allowed to use tcp and udp for everything else.<br />
socks pass {<br />
        from: 10.0.0.0/8 to: 0.0.0.0/0<br />
        protocol: tcp udp<br />
}<br /></p>', 'info', false );
?>
	</div>
	<nav class="action-buttons">

		<button data-toggle="modal" data-target="#SocksPassList_editor"
			role="button" aria-expanded="false" type="button"
			name="SocksPassList_new" id="SocksPassList_new"
			class="btn btn-success btn-sm"
			title="<?=gettext('Create a new Active Socks List');?>"
			onClick="document.getElementById('SocksPassList_clientmethod').value=''; document.getElementById('SocksPassList_tport').value='';document.getElementById('SocksPassList_protocol').value='';document.getElementById('SocksPassList_logstype').value=''; document.getElementById('SocksPassList_fromip').value=''; document.getElementById('SocksPassList_fromcidr').value=''; document.getElementById('SocksPassList_toip').value=''; document.getElementById('SocksPassList_tocidr').value=''; document.getElementById('SocksPassList_editor').style.display='table-row-group'; document.getElementById('SocksPassList_fromip').focus();
			document.getElementById('SocksPassListid').value='<?=count($pass_socks);?>';">


			<i class="fa fa-plus icon-embed-btn"></i><?=gettext("Add")?>
		</button>
		<button type="submit" class="btn btn-sm btn-primary" id="order-store"
				name="order-store" value="<?=gettext("Save");?>"
				title="<?=gettext("Save order's changes");?>">
				<i class="fa fa-save icon-embed-btn"></i>
					<?=gettext("Save");?>
		</button>

	</nav>


	<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title"><?=gettext("Socks Block List")?></h2>
		</div>
		<div class="panel-body table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<tbody>
					<tr>
						<td>
							<table class="table table-striped table-hover table-condensed">
								<thead>
									<tr>
										<th><?=gettext("Source FQDN/IP with CIDR")?></th>
										<th><?=gettext("Target FQDN/IP with CIDR")?></th>
										<th><?=gettext("Target Port")?></th>
										<th><?=gettext("Logs Type")?></th>
										<th><?=gettext("Actions")?></th>
									</tr>
								</thead>
								<tbody>
						<?php foreach ($blocksockslists as $i => $list): ?>
							<tr>
										<?php if(!empty($list['fromip'])) { ?>
										<td><? echo $list['fromip']; ?>/<? echo $list['fromcidr']; ?></td>
										<?php } else { ?>
										<td><? echo $list['fromfqdn']; ?></td>
										<?php } ?>
										<?php if(!empty($list['toip'])) { ?>
										<td><? echo $list['toip']; ?>/<? echo $list['tocidr']; ?></td>
										<?php } else { ?>
										<td><? echo $list['tofqdn']; ?></td>
										<?php } ?>
										<td><? echo $list['tport']; ?></td>
										<td><? echo $list['logstype']; ?></td>
										<td><a name="SocksBlockList_editX[]"
											id="SocksBlockList_editX[]" type="button"
											title="<?=gettext('Edit this Active Socks List');?>"
											onClick='SocksBlockListid="<?=$i;?>"'
											style="cursor: pointer;"> <i class="fa fa-pencil"></i>
										</a> <a name="SocksBlockList_deleteX[]"
											id="SocksBlockList_deleteX[]" type="button"
											title="<?=gettext('Delete this Active Socks List');?>"
											onClick='SocksBlockListid="<?=$i;?>"'
											style="cursor: pointer;"> <i class="fa fa-trash"
												title="<?=gettext('Delete this Active Socks List');?>"></i>
										</a></td>
									</tr>
						<?php endforeach; ?>
							
							
							
							
							
							
							</table>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Modal SID editor window -->
		<div class="modal fade" role="dialog" id="SocksBlockList_editor">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"
							aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>

						<h3 class="modal-title" id="myModalLabel"><?=gettext("Socks BLOCK List Editor")?></h3>
					</div>

					<div class="modal-body">
						<input type="hidden" name="SocksBlockList_id"
							id="SocksBlockList_id" value="<?=$blocksockslist_id;?>" /> <input
							type="hidden" name="SocksBlockList_ruleType"
							id="SocksBlockList_ruleType" value="Block" />
						<?=gettext("FROM: ");?>
						<?=gettext(" FQDN or IP: ");?>
						<input type="text" size="40" class="form-control file"
							id="SocksBlockList_fromip" name="SocksBlockList_fromip"
							value="<?=$blocksockslist_fromip;?>" /><br />
						<?=gettext(" CIDR (Only in case of IP): ");?>
						<input type="text" size="2" class="form-control number"
							style="width: 40px;" id="SocksBlockList_fromcidr"
							name="SocksBlockList_fromcidr" value="<?=$blocksockslist_fromcidr;?>" /><br />
						<?=gettext("TO: ");?>
						<?=gettext(" FQDN or IP: ");?>
						<input type="text" size="40" class="form-control file"
							id="SocksBlockList_toip" name="SocksBlockList_toip"
							value="<?=$blocksockslist_toip;?>" /><br />
						<?=gettext(" CIDR (Only in case of IP): ");?>
						<input type="text" size="2" class="form-control number"
							style="width: 40px;" id="SocksBlockList_tocidr"
							name="SocksBlockList_tocidr" value="<?=$blocksockslist_tocidr;?>" /><br />
						<?=gettext("target Port(s): ");?>
						<input type="text" size="8" class="form-control file"
							style="width: 200px;" id="SocksBlockList_tport"
							name="SocksBlockList_tport" value="<?=$blocksockslist_tport;?>" /><br />
						<?=gettext("Log Type (error connect disconnect): ");?>
						<input type="text" size="40" class="form-control file"
							id="SocksBlockList_logstype" name="SocksBlockList_logstype"
							value="<?=$blocksockslist_logstype;?>" /><br />
						<button type="submit" class="btn btn-sm btn-primary"
							id="BlockSave" name="BlockSave" value="<?=gettext("Save");?>"
							title="<?=gettext("Save changes and close editor");?>">
							<i class="fa fa-save icon-embed-btn"></i>
							<?=gettext("Save");?>
						</button>
						<button type="button" class="btn btn-sm btn-warning" id="cancel"
							name="cancel" value="<?=gettext("Cancel");?>"
							data-dismiss="modal"
							title="<?=gettext("Abandon changes and quit editor");?>">
							<?=gettext("Cancel");?>
						</button>
						<br />
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="infoblock"> 
<?php
print_info_box ( '<p>' . 'Last line, block everyone else.  This is the default but if you provide one  yourself you can specify your own logging/actions<br />
socks block {<br />
        from: 0.0.0.0/0 to: 0.0.0.0/0<br />
        log: connect error<br />
}<br />
You probably don\'t want people connecting to loopback addresses, who knows what could happen then.<br />
socks block {<br />
        from: 0.0.0.0/0 to: lo0<br />
        log: connect error<br />
}<br /></p>', 'info', false );
?>
	</div>
	<nav class="action-buttons">

		<button data-toggle="modal" data-target="#SocksBlockList_editor"
			role="button" aria-expanded="false" type="button"
			name="SocksBlockList_new" id="SocksBlockList_new"
			class="btn btn-success btn-sm"
			title="<?=gettext('Create a new Active Socks List');?>"
			onClick="document.getElementById('SocksBlockList_tport').value='';document.getElementById('SocksBlockList_toip').value=''; document.getElementById('SocksBlockList_tocidr').value=''; document.getElementById('SocksBlockList_logstype').value='';document.getElementById('SocksBlockList_fromip').value=''; document.getElementById('SocksBlockList_fromcidr').value=''; document.getElementById('SocksBlockList_editor').style.display='table-row-group'; document.getElementById('SocksBlockList_fromip').focus();
			document.getElementById('SocksBlockListid').value='<?=count($block_socks);?>';">


			<i class="fa fa-plus icon-embed-btn"></i><?=gettext("Add")?>
		</button>

	</nav>

</form>


<script type="text/javascript">
//<![CDATA[
events.push(function() {

	$('[id^=SocksPassList_editX]').click(function () {
		$('#SocksPassList_edit').remove();
		$('#SocksPassList_dup').remove();
		$('#SocksPassList_delete').remove();
		$('#SocksBlockList_edit').remove();
		$('#SocksBlockList_delete').remove();
		$('#SocksPassList_id').val(SocksPassListid);
		$('<input type="hidden" name="SocksPassList_edit" id="SocksPassList_edit" value="0"/>').appendTo($(form));
		$(form).submit();
	});

	$('[id^=SocksPassList_dupX]').click(function () {
		$('#SocksPassList_edit').remove();
		$('#SocksPassList_dup').remove();
		$('#SocksPassList_delete').remove();
		$('#SocksBlockList_edit').remove();
		$('#SocksBlockList_delete').remove();
		$('#SocksPassList_id').val(SocksPassListid);
		$('<input type="hidden" name="SocksPassList_dup" id="SocksPassList_dup" value="0"/>').appendTo($(form));
		$(form).submit();
	});

	$('[id^=SocksPassList_deleteX]').click(function () {
		$('#SocksPassList_edit').remove();
		$('#SocksPassList_dup').remove();
		$('#SocksPassList_delete').remove();
		$('#SocksBlockList_edit').remove();
		$('#SocksBlockList_delete').remove();
		$('#SocksPassList_id').val(SocksPassListid);
		$('<input type="hidden" name="SocksPassList_delete" id="SocksPassList_delete" value="0"/>').appendTo($(form));
		$(form).submit();
	});

	$('[id^=SocksBlockList_editX]').click(function () {
		$('#SocksPassList_edit').remove();
		$('#SocksPassList_dup').remove();
		$('#SocksPassList_delete').remove();
		$('#SocksBlockList_edit').remove();
		$('#SocksBlockList_delete').remove();
		$('#SocksBlockList_id').val(SocksBlockListid);
		$('<input type="hidden" name="SocksBlockList_edit" id="SocksBlockList_edit" value="0"/>').appendTo($(form));
		$(form).submit();
	});

	$('[id^=SocksBlockList_deleteX]').click(function () {
		$('#SocksPassList_edit').remove();
		$('#SocksPassList_dup').remove();
		$('#SocksPassList_delete').remove();
		$('#SocksBlockList_edit').remove();
		$('#SocksBlockList_delete').remove();
		$('#SocksBlockList_id').val(SocksBlockListid);
		$('<input type="hidden" name="SocksBlockList_delete" id="SocksBlockList_delete" value="0"/>').appendTo($(form));
		$(form).submit();
	});
	// Make rules sortable. Hiding the table before applying sortable, then showing it again is
    // a work-around for very slow sorting on FireFox
    $('table tbody.user-entries').hide();

    $('table tbody.user-entries').sortable({
            cursor: 'grabbing',
            scroll: true,
            overflow: 'scroll',
            scrollSensitivity: 100,
            update: function(event, ui) {
                    reindex_rules(ui.item.parent('tbody'));
                    dirty = true;
                    ui.item.parent('tbody').find('tr').each(function() {
                        if (this.id) {
                            ruleid = this.id.slice(2);
                            $(this).find('input:hidden:first').each(function() {
                            	$(this).attr("id", "frc" + ruleid);
                            	$(this).attr("name", "frc" + ruleid);
                            });
                		}
            		});
            }
    });

    $('table tbody.user-entries').show();

	// If the user is editing a file, open the modal on page load
<?php if ($passsockslist_edit_style == "show") : ?>
	$("#SocksPassList_editor").modal('show');
<?php endif ?>
<?php if ($blocksockslist_edit_style == "show") : ?>
	$("#SocksBlockList_editor").modal('show');
<?php endif ?>
});
//]]>
</script>

<?php
include ("foot.inc");
?>
