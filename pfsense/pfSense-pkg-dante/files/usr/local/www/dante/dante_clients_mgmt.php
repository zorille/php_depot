<?php
/*
 * dante_clients_mgmt.php
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
// #|*IDENT=dante-clients-mgmt
// #|*NAME=Services: Dante
// #|*DESCR=Allow access to the 'Services: Dante' page.
// #|*MATCH=dante_clients_mgmt.php*
// #|-PRIV
require_once ("guiconfig.inc");
require_once ("dante.inc");
global $config;

// Gestion des PASS
// Grab saved settings from configuration
if (! is_array ( $config ['installedpackages'] ['dante'] ['passclients'] )) {
	$config ['installedpackages'] ['dante'] ['passclients'] = array ();
}
$pass_clients = &$config ['installedpackages'] ['dante'] ['passclients'];
//Changing array order
if (isset ( $_POST ['order-store'] ) && $_POST ['order-store']=="Save") {
	$tmp_order=array();
	$pos=count($_POST);
	//We check new order
	for ($i=0;$i<=$pos;$i++){
		if(isset($_POST["frc".$i]) && isset($pass_clients[$_POST["frc".$i]])){
			$tmp_order[$_POST["frc".$i]]=$pass_clients[$_POST["frc".$i]];
		}
	}
	// Write the new configuration
	$pass_clients=$tmp_order;
	$savemsg="Order changed for Pass Socks entries.";
	write_config ( "Dante pkg: ".$savemsg );
	unset($tmp_order);
}
// Set default to not show Pass Client modification lists editor
$passclientslist_edit_style = "display: none;";
if (isset ( $_POST ['ClientPassList_dup'] ) && isset ( $pass_clients [$_POST ['ClientPassList_id']] )) {
	// Write the new configuration
	$id = 'a' . uniqid ();
	$pass_clients[$id]=$pass_clients[$_POST ['ClientPassList_id']];
	$pass_clients[$id]['id']=$id;
	write_config ( "Dante pkg: Cloned Pass Client entry from list." );
}
if (isset ( $_POST ['ClientPassList_delete'] ) && isset ( $pass_clients [$_POST ['ClientPassList_id']] )) {
	// Write the new configuration
	unset ( $pass_clients [$_POST ['ClientPassList_id']] );
	write_config ( "Dante pkg: deleted pass client entry from list." );
}
// Permet de mettre les variable dans l'objet de modification
if (isset ( $_POST ['ClientPassList_edit'] ) && isset ( $pass_clients [$_POST ['ClientPassList_id']] )) {
	$passclientslist_edit_style = "show";
	$passclientslist_id = $pass_clients [$_POST ['ClientPassList_id']] ['id'];
	if (empty ( $pass_clients [$_POST ['ClientPassList_id']] ['ip'] )) {
		$passclientslist_ip = $pass_clients [$_POST ['ClientPassList_id']] ['fqdn'];
	} else {
		$passclientslist_ip = $pass_clients [$_POST ['ClientPassList_id']] ['ip'];
	}
	$passclientslist_cidr = $pass_clients [$_POST ['ClientPassList_id']] ['cidr'];
	$passclientslist_sport = $pass_clients [$_POST ['ClientPassList_id']] ['sport'];
	$ClientPassList_interface = $pass_clients [$_POST ['ClientPassList_id']] ['interface'];
	$ClientPassList_logstype = $pass_clients [$_POST ['ClientPassList_id']] ['logstype'];
}
if (isset ( $_POST ['PassSave'] ) && isset ( $_POST ['ClientPassList_id'] )) {
	if (! empty ( $_POST ['ClientPassList_id'] ) && isset ( $pass_clients [$_POST ['ClientPassList_id']] )) {
		$id = $_POST ['ClientPassList_id'];
	} else {
		$id = 'a' . uniqid ();
	}
	$tmp = array (
			"id" => $id,
			"ip" => '',
			"cidr" => '',
			'fqdn' => '',
			"sport" => '',
			"interface" => '',
			"logstype" => ''
	);
	if (valide_ip_cidr_fqdn ( $tmp, 'Pass', 'Client','' ) && valide_port ( $tmp, 'Pass', 'Client','s' )) {
		$tmp ['interface'] = $_POST ['ClientPassList_interface'];
		$tmp ['logstype'] = $_POST ['ClientPassList_logstype'];
		$pass_clients [$tmp ['id']] = $tmp;
		$savemsg = "Dante pkg:Pass Client created in list.";
		write_config ( $savemsg );
	}
}
$passclientslists = $pass_clients;
if (! is_array ( $passclientslists )) {
	$passclientslists = array ();
}
// Sync to configured CARP slaves if any are enabled
// dante_sync_on_changes();
// Get all the Active Clients Lists as an array
// Leave this as the last thing before spewing the page HTML
// so we can pick up any changes made in code above.
// Gestion des BLOCK
if (! is_array ( $config ['installedpackages'] ['dante'] ['blockclients'] )) {
	$config ['installedpackages'] ['dante'] ['blockclients'] = array ();
}
$block_clients = &$config ['installedpackages'] ['dante'] ['blockclients'];
$blockclientslist_edit_style = "display: none;";
if (isset ( $_POST ['ClientBlockList_delete'] ) && isset ( $block_clients [$_POST ['ClientBlockList_id']] )) {
	// Write the new configuration
	unset ( $block_clients [$_POST ['ClientBlockList_id']] );
	write_config ( "Dante pkg: deleted block client entry from list." );
}
// Permet de mettre les variable dans l'objet de modification
if (isset ( $_POST ['ClientBlockList_edit'] ) && isset ( $block_clients [$_POST ['ClientBlockList_id']] )) {
	$blockclientslist_edit_style = "show";
	$blockclientslist_id = $block_clients [$_POST ['ClientBlockList_id']] ['id'];
	if (empty ( $block_clients [$_POST ['ClientBlockList_id']] ['ip'] )) {
		$blockclientslist_ip = $block_clients [$_POST ['ClientBlockList_id']] ['fqdn'];
	} else {
		$blockclientslist_ip = $block_clients [$_POST ['ClientBlockList_id']] ['ip'];
	}
	$blockclientslist_cidr = $block_clients [$_POST ['ClientBlockList_id']] ['cidr'];
	$blockclientslist_sport = $block_clients [$_POST ['ClientBlockList_id']] ['sport'];
	$ClientBlockList_interface = $block_clients [$_POST ['ClientBlockList_id']] ['interface'];
	$ClientBlockList_logstype = $block_clients [$_POST ['ClientBlockList_id']] ['logstype'];
}
if (isset ( $_POST ['BlockSave'] ) && isset ( $_POST ['ClientBlockList_id'] )) {
	if (! empty ( $_POST ['ClientBlockList_id'] ) && isset ( $block_clients [$_POST ['ClientBlockList_id']] )) {
		$id = $_POST ['ClientBlockList_id'];
	} else {
		$id = 'a' . uniqid ();
	}
	$tmp = array (
			"id" => $id,
			"ip" => '',
			"cidr" => '',
			'fqdn' => '',
			"sport" => '',
			"interface" => '',
			"logstype" => ''
	);
	if (valide_ip_cidr_fqdn ( $tmp, 'Block', 'Client','' ) && valide_port ( $tmp, 'Block', 'Client','s' )) {
		$tmp ['interface'] = $_POST ['ClientBlockList_interface'];
		$tmp ['logstype'] = $_POST ['ClientBlockList_logstype'];
		$block_clients [$tmp ['id']] = $tmp;
		$savemsg = "Dante pkg:Block Client created in list.";
		write_config ( $savemsg );
	}
}
$blockclientslists = $block_clients;
if (! is_array ( $blockclientslists )) {
	$blockclientslists = array ();
}
$pgtitle = array (
		gettext ( "Services" ),
		gettext ( "Dante" ),
		gettext ( "Clients Mgmt" )
);
include_once ("head.inc");
add_header_menu ( "Clients" );
/* Display Alert message, under form tag or no refresh */
if ($input_errors) {
	print_input_errors ( $input_errors );
}
if ($savemsg) {
	print_info_box ( $savemsg, 'success' );
}
?>

<form action="dante_clients_mgmt.php" method="post"
	enctype="multipart/form-data" name="iform" id="iform">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title"><?=gettext("Clients Pass List")?></h2>
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
										<th><?=gettext("Source Port")?></th>
										<th><?=gettext("Connected IP/Interface")?></th>
										<th><?=gettext("Logs Type")?></th>
										<th><?=gettext("Actions")?></th>
									</tr>
								</thead>
								<tbody class="user-entries ui-sortable" style="display: table-row-group;">
									<?php $pos=0;?>
									<?php foreach ($passclientslists as $i => $list): ?>
										<tr id="fr<?php echo $pos ?>" class="ui-sortable-handle">
										<input id="frc<?php echo $pos ?>" name="frc<?php echo $pos ?>" value="<?php echo $list['id'] ?>" type="hidden"/>
										<?php if(!empty($list['ip'])) { ?>
										<td><? echo $list['ip']; ?>/<? echo $list['cidr']; ?></td>
										<?php } else { ?>
										<td><? echo $list['fqdn']; ?></td>
										<?php } ?>
										<td><? echo $list['sport']; ?></td>
										<td><? echo $list['interface']; ?></td>
										<td><? echo $list['logstype']; ?></td>
										<td><a name="ClientPassList_editX[]"
											id="ClientPassList_editX[]" type="button"
											title="<?=gettext('Edit this Active Clients List');?>"
											onClick='ClientPassListid="<?=$i;?>"'
											style="cursor: pointer;"> <i class="fa fa-pencil"></i>
										</a>
										<a name="ClientPassList_dupX[]"
											id="ClientPassList_dupX[]" type="button"
											title="<?=gettext('Duplicate this Active Clients List');?>"
											onClick='ClientPassListid="<?=$i;?>"'
											style="cursor: pointer;"> <i class="fa fa-clone"></i>
										</a>
										<a name="ClientPassList_deleteX[]"
											id="ClientPassList_deleteX[]" type="button"
											title="<?=gettext('Delete this Active Clients List');?>"
											onClick='ClientPassListid="<?=$i;?>"'
											style="cursor: pointer;"> <i class="fa fa-trash"
												title="<?=gettext('Delete this Active Clients List');?>"></i>
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
		<div class="modal fade" role="dialog" id="ClientPassList_editor">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"
							aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>

						<h3 class="modal-title" id="myModalLabel"><?=gettext("Client PASS List Editor")?></h3>
					</div>

					<div class="modal-body">
						<input type="hidden" name="ClientPassList_id"
							id="ClientPassList_id" value="<?=$passclientslist_id;?>" /> <input
							type="hidden" name="ClientPassList_ruleType"
							id="ClientPassList_ruleType" value="Pass" />
						<?=gettext("FQDN or IP: ");?>
						<input type="text" size="40" class="form-control file"
							id="ClientPassList_ip" name="ClientPassList_ip"
							value="<?=$passclientslist_ip;?>" /><br />
						<?=gettext("CIDR (Only in case of IP): ");?>
						<input type="text" size="2" class="form-control number"
							style="width: 40px;" id="ClientPassList_cidr"
							name="ClientPassList_cidr" value="<?=$passclientslist_cidr;?>" /><br />
						<?=gettext("Source Port(s): ");?>
						<input type="text" size="8" class="form-control file"
							style="width: 200px;" id="ClientPassList_sport"
							name="ClientPassList_sport" value="<?=$passclientslist_sport;?>" /><br />
						<?=gettext("Connected IP / Interface: ");?>
						<input type="text" size="8" class="form-control file"
							style="width: 400px;" id="ClientPassList_interface"
							name="ClientPassList_interface"
							value="<?=$ClientPassList_interface;?>" /><br />
						<?=gettext("Log Type (error connect disconnect): ");?>
						<input type="text" size="8" class="form-control file"
							id="ClientPassList_logstype" name="ClientPassList_logstype"
							value="<?=$ClientPassList_logstype;?>" /><br />
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
print_info_box ( '<p>' . 'The "client" rules.  All our clients come from the net 10.0.0.0/8.<br />
<br />
Allow our clients, also provides an example of the port range command.<br />
client pass {<br />
from: 10.0.0.0/8 port 1-65535 to: 0.0.0.0/0<br />
clientmethod: rfc931 # match all idented users that also are in passwordfile<br />
}<br />
<br />
This is identical to above, but allows clients without a rfc931 (ident) too.  In practice this means the socks server will try to get a rfc931 reply first (the above rule), if that fails, it tries this rule.<br />
client pass {<br />
from: 10.0.0.0/8 port 1-65535 to: 0.0.0.0/0<br />
}<br /></p>', 'info', false );
?>
	</div>
	<nav class="action-buttons">

		<button data-toggle="modal" data-target="#ClientPassList_editor"
			role="button" aria-expanded="false" type="button"
			name="ClientPassList_new" id="ClientPassList_new"
			class="btn btn-success btn-sm"
			title="<?=gettext('Create a new Active Clients List');?>"
			onClick="document.getElementById('ClientPassList_sport').value='';document.getElementById('ClientPassList_interface').value='';document.getElementById('ClientPassList_logstype').value='';document.getElementById('ClientPassList_ip').value=''; document.getElementById('ClientPassList_cidr').value=''; document.getElementById('ClientPassList_editor').style.display='table-row-group'; document.getElementById('ClientPassList_ip').focus();
			document.getElementById('ClientPassListid').value='<?=count($pass_clients);?>';">


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
			<h2 class="panel-title"><?=gettext("Clients Block List")?></h2>
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
										<th><?=gettext("Source Port")?></th>
										<th><?=gettext("Connected IP/Interface")?></th>
										<th><?=gettext("Logs Type")?></th>
										<th><?=gettext("Actions")?></th>
									</tr>
								</thead>
								<tbody>
						<?php foreach ($blockclientslists as $i => $list): ?>
							<tr>
										<?php if(!empty($list['ip'])) { ?>
										<td><? echo $list['ip']; ?>/<? echo $list['cidr']; ?></td>
										<?php } else { ?>
										<td><? echo $list['fqdn']; ?></td>
										<?php } ?>
										<td><? echo $list['sport']; ?></td>
										<td><? echo $list['interface']; ?></td>
										<td><? echo $list['logstype']; ?></td>
										<td><a name="ClientBlockList_editX[]"
											id="ClientBlockList_editX[]" type="button"
											title="<?=gettext('Edit this Active Clients List');?>"
											onClick='ClientBlockListid="<?=$i;?>"'
											style="cursor: pointer;"> <i class="fa fa-pencil"></i>
										</a>
										<a name="ClientBlockList_deleteX[]"
											id="ClientBlockList_deleteX[]" type="button"
											title="<?=gettext('Delete this Active Clients List');?>"
											onClick='ClientBlockListid="<?=$i;?>"'
											style="cursor: pointer;"> <i class="fa fa-trash"
												title="<?=gettext('Delete this Active Clients List');?>"></i>
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
		<div class="modal fade" role="dialog" id="ClientBlockList_editor">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"
							aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>

						<h3 class="modal-title" id="myModalLabel"><?=gettext("Client BLOCK List Editor")?></h3>
					</div>

					<div class="modal-body">
						<input type="hidden" name="ClientBlockList_id"
							id="ClientBlockList_id" value="<?=$blockclientslist_id;?>" /> <input
							type="hidden" name="ClientBlockList_ruleType"
							id="ClientBlockList_ruleType" value="Block" />
						<?=gettext("FQDN or IP: ");?>
						<input type="text" size="40" class="form-control file"
							id="ClientBlockList_ip" name="ClientBlockList_ip"
							value="<?=$blockclientslist_ip;?>" /><br />
						<?=gettext("CIDR (Only in case of IP): ");?>
						<input type="text" size="2" class="form-control number"
							style="width: 40px;" id="ClientBlockList_cidr"
							name="ClientBlockList_cidr" value="<?=$blockclientslist_cidr;?>" /><br />
						<?=gettext("Source Port(s): ");?>
						<input type="text" size="8" class="form-control file"
							style="width: 200px;" id="ClientBlockList_sport"
							name="ClientBlockList_sport"
							value="<?=$blockclientslist_sport;?>" /><br />
						<?=gettext("Connected IP / Interface: ");?>
						<input type="text" size="8" class="form-control file"
							style="width: 400px;" id="ClientBlockList_interface"
							name="ClientBlockList_interface"
							value="<?=$ClientBlockList_interface;?>" /><br />
						<?=gettext("Log Type (error connect disconnect): ");?>
						<input type="text" size="8" class="form-control file"
							id="ClientBlockList_logstype" name="ClientBlockList_logstype"
							value="<?=$ClientBlockList_logstype;?>" /><br />
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
print_info_box ( '<p>' . 'Drop everyone else as soon as we can and log the connect, they are not on our net and have no business connecting to us.  This is the default but if you give the rule yourself, you can specify details.<br />
client block {<br />
        from: 0.0.0.0/0 to: 0.0.0.0/0<br />
        log: connect error<br />
}<br />
The rules controlling what clients are allowed what requests<br />
<br />
you probably don\'t want people connecting to loopback addresses, who knows what could happen then.<br />
socks block {<br />
        from: 0.0.0.0/0 to: lo0<br />
        log: connect error<br />
}<br /></p>', 'info', false );
?>
	</div>
	<nav class="action-buttons">

		<button data-toggle="modal" data-target="#ClientBlockList_editor"
			role="button" aria-expanded="false" type="button"
			name="ClientBlockList_new" id="ClientBlockList_new"
			class="btn btn-success btn-sm"
			title="<?=gettext('Create a new Active Clients List');?>"
			onClick="document.getElementById('ClientBlockList_sport').value='';document.getElementById('ClientBlockList_interface').value='';document.getElementById('ClientBlockList_logstype').value='';document.getElementById('ClientBlockList_ip').value=''; document.getElementById('ClientBlockList_cidr').value=''; document.getElementById('ClientBlockList_editor').style.display='table-row-group'; document.getElementById('ClientBlockList_ip').focus();
			document.getElementById('ClientBlockListid').value='<?=count($block_clients);?>';">


			<i class="fa fa-plus icon-embed-btn"></i><?=gettext("Add")?>
		</button>

	</nav>

</form>


<script type="text/javascript">
//<![CDATA[
events.push(function() {

	$('[id^=ClientPassList_editX]').click(function () {
		$('#ClientPassList_edit').remove();
		$('#ClientPassList_dup').remove();
		$('#ClientPassList_delete').remove();
		$('#ClientBlockList_edit').remove();
		$('#ClientBlockList_delete').remove();
		$('#ClientPassList_id').val(ClientPassListid);
		$('<input type="hidden" name="ClientPassList_edit" id="ClientPassList_edit" value="0"/>').appendTo($(form));
		$(form).submit();
	});

	$('[id^=ClientPassList_dupX]').click(function () {
		$('#ClientPassList_edit').remove();
		$('#ClientPassList_dup').remove();
		$('#ClientPassList_delete').remove();
		$('#ClientBlockList_edit').remove();
		$('#ClientBlockList_delete').remove();
		$('#ClientPassList_id').val(ClientPassListid);
		$('<input type="hidden" name="ClientPassList_dup" id="ClientPassList_dup" value="0"/>').appendTo($(form));
		$(form).submit();
	});

	$('[id^=ClientPassList_deleteX]').click(function () {
		$('#ClientPassList_edit').remove();
		$('#ClientPassList_dup').remove();
		$('#ClientPassList_delete').remove();
		$('#ClientBlockList_edit').remove();
		$('#ClientBlockList_delete').remove();
		$('#ClientPassList_id').val(ClientPassListid);
		$('<input type="hidden" name="ClientPassList_delete" id="ClientPassList_delete" value="0"/>').appendTo($(form));
		$(form).submit();
	});

	$('[id^=ClientBlockList_editX]').click(function () {
		$('#ClientPassList_edit').remove();
		$('#ClientPassList_dup').remove();
		$('#ClientPassList_delete').remove();
		$('#ClientBlockList_edit').remove();
		$('#ClientBlockList_delete').remove();
		$('#ClientBlockList_id').val(ClientBlockListid);
		$('<input type="hidden" name="ClientBlockList_edit" id="ClientBlockList_edit" value="0"/>').appendTo($(form));
		$(form).submit();
	});

	$('[id^=ClientBlockList_deleteX]').click(function () {
		$('#ClientPassList_edit').remove();
		$('#ClientPassList_dup').remove();
		$('#ClientPassList_delete').remove();
		$('#ClientBlockList_edit').remove();
		$('#ClientBlockList_delete').remove();
		$('#ClientBlockList_id').val(ClientBlockListid);
		$('<input type="hidden" name="ClientBlockList_delete" id="ClientBlockList_delete" value="0"/>').appendTo($(form));
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
<?php if ($passclientslist_edit_style == "show") : ?>
	$("#ClientPassList_editor").modal('show');
<?php endif ?>
<?php if ($blockclientslist_edit_style == "show") : ?>
	$("#ClientBlockList_editor").modal('show');
<?php endif ?>
});
//]]>
</script>

<?php
include ("foot.inc");
?>
