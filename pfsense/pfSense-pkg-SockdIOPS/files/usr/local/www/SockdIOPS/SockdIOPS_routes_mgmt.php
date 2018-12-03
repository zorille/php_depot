<?php
/*
 * SockdIOPS_routes_mgmt.php
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
// #|*IDENT=SockdIOPS-routes-mgmt
// #|*NAME=Services: SockdIOPS
// #|*DESCR=Allow access to the 'Services: SockdIOPS' page.
// #|*MATCH=SockdIOPS_routes_mgmt.php*
// #|-PRIV
require_once ("guiconfig.inc");
require_once ("SockdIOPS/SockdIOPS.inc");
global $config;

// Gestion des PASS
// Grab saved settings from configuration
if (! is_array ( $config ['installedpackages'] ['sockdiops'] ['passroutes'] )) {
	$config ['installedpackages'] ['sockdiops'] ['passroutes'] = array ();
}
$pass_routes = &$config ['installedpackages'] ['sockdiops'] ['passroutes'];
// Set default to not show Pass Routes modification lists editor
$passrouteslist_edit_style = "display: none;";
//Changing array order
if (isset ( $_POST ['order-store'] ) && $_POST ['order-store']=="Save") {
	$tmp_order=array();
	$pos=count($_POST);
	//We check new order
	for ($i=0;$i<=$pos;$i++){
		if(isset($_POST["frc".$i]) && isset($pass_routes[$_POST["frc".$i]])){
			$tmp_order[$_POST["frc".$i]]=$pass_routes[$_POST["frc".$i]];
		}
	}
	// Write the new configuration
	$pass_routes=$tmp_order;
	$savemsg="Order changed for routes entries.";
	write_config ( "SockdIOPS pkg: ".$savemsg );
	unset($tmp_order);
}
if (isset ( $_POST ['RoutesPassList_dup'] ) && isset ( $pass_routes [$_POST ['RoutesPassList_id']] )) {
	// Write the new configuration
	$id = 'a' . uniqid ();
	$pass_routes[$id]=$pass_routes[$_POST ['RoutesPassList_id']];
	$pass_routes[$id]['id']=$id;
	write_config ( "SockdIOPS pkg: Cloned Pass Route entry from list." );
}
if (isset ( $_POST ['RoutesPassList_delete'] ) && isset ( $pass_routes [$_POST ['RoutesPassList_id']] )) {
	// Write the new configuration
	unset ( $pass_routes [$_POST ['RoutesPassList_id']] );
	write_config ( "SockdIOPS pkg: deleted pass routes entry from list." );
}
// Permet de mettre les variable dans l'objet de modification
if (isset ( $_POST ['RoutesPassList_edit'] ) && isset ( $pass_routes [$_POST ['RoutesPassList_id']] )) {
	$passrouteslist_edit_style = "show";
	$passrouteslist_id = $pass_routes [$_POST ['RoutesPassList_id']] ['id'];
	if (empty ( $pass_routes [$_POST ['RoutesPassList_id']] ['fromip'] )) {
		$passrouteslist_fromip = $pass_routes [$_POST ['RoutesPassList_id']] ['fromfqdn'];
	} else {
		$passrouteslist_fromip = $pass_routes [$_POST ['RoutesPassList_id']] ['fromip'];
	}
	$passrouteslist_fromcidr = $pass_routes [$_POST ['RoutesPassList_id']] ['fromcidr'];
	if (empty ( $pass_routes [$_POST ['RoutesPassList_id']] ['toip'] )) {
		$passrouteslist_toip = $pass_routes [$_POST ['RoutesPassList_id']] ['tofqdn'];
	} else {
		$passrouteslist_toip = $pass_routes [$_POST ['RoutesPassList_id']] ['toip'];
	}
	$passrouteslist_tocidr = $pass_routes [$_POST ['RoutesPassList_id']] ['tocidr'];
	$passrouteslist_tport = $pass_routes [$_POST ['RoutesPassList_id']] ['tport'];
	if (empty ( $pass_routes [$_POST ['RoutesPassList_id']] ['viaip'] )) {
		$passrouteslist_viaip = $pass_routes [$_POST ['RoutesPassList_id']] ['viafqdn'];
	} else {
		$passrouteslist_viaip = $pass_routes [$_POST ['RoutesPassList_id']] ['viaip'];
	}
	$passrouteslist_viacidr = $pass_routes [$_POST ['RoutesPassList_id']] ['viacidr'];
	$passrouteslist_viaport = $pass_routes [$_POST ['RoutesPassList_id']] ['viaport'];
	$RoutesPassList_proxyprotocol = $pass_routes [$_POST ['RoutesPassList_id']] ['proxyprotocol'];
	$RoutesPassList_protocol = $pass_routes [$_POST ['RoutesPassList_id']] ['protocol'];
	$RoutesPassList_command = $pass_routes [$_POST ['RoutesPassList_id']] ['command'];
}
if (isset ( $_POST ['PassSave'] ) && isset ( $_POST ['RoutesPassList_id'] )) {
	if (! empty ( $_POST ['RoutesPassList_id'] ) && isset ( $pass_routes [$_POST ['RoutesPassList_id']] )) {
		$id = $_POST ['RoutesPassList_id'];
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
			"viaip" => '',
			"viacidr" => '',
			'viafqdn' => '',
			"viaport" => '',
			"protocol" => '',
			"proxyprotocol" => '',
			"command" => '',
	);
	if (valide_ip_cidr_fqdn ( $tmp, 'Pass', 'Routes','from' ) 
			&& valide_ip_cidr_fqdn ( $tmp, 'Pass', 'Routes','to' ) 
			&& valide_port ( $tmp, 'Pass', 'Routes', 't' ) 
			&& valide_ip_cidr_fqdn ( $tmp, 'Pass', 'Routes','via' ) 
			&& valide_port ( $tmp, 'Pass', 'Routes', 'via' ) ) {
		$tmp ['protocol'] = $_POST ['RoutesPassList_protocol'];
		$tmp ['proxyprotocol'] = $_POST ['RoutesPassList_proxyprotocol'];
		$tmp ['command'] = $_POST ['RoutesPassList_command'];
		$pass_routes [$tmp ['id']] = $tmp;
		$savemsg = "SockdIOPS pkg:Pass Routes created in list.";
		write_config ( $savemsg );
	}
}
$passrouteslists = $pass_routes;
if (! is_array ( $passrouteslists )) {
	$passrouteslists = array ();
}
// Get all the Active Routes Lists as an array
// Leave this as the last thing before spewing the page HTML
// so we can pick up any changes made in code above.
$pgtitle = array (
		gettext ( "Services" ),
		gettext ( "SockdIOPS" ),
		gettext ( "Routes Mgmt" )
);
include_once ("head.inc");
add_header_menu ( "Routes" );
/* Display Alert message, under form tag or no refresh */
if ($input_errors) {
	print_input_errors ( $input_errors );
}
if ($savemsg) {
	print_info_box ( $savemsg, 'success' );
}
?>

<form action="SockdIOPS_routes_mgmt.php" method="post"
	enctype="multipart/form-data" name="iform" id="iform">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title"><?=gettext("Routes Pass List")?></h2>
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
										<th><?=gettext("Via FQDN/IP with CIDR")?></th>
										<th><?=gettext("Via Port")?></th>
										<th><?=gettext("Proxy Protocol")?></th>
										<th><?=gettext("Protocol")?></th>
										<th><?=gettext("Command")?></th>
										<th><?=gettext("Actions")?></th>
									</tr>
								</thead>
								<tbody class="user-entries ui-sortable" style="display: table-row-group;">
									<?php $pos=0;?>
									<?php foreach ($passrouteslists as $i => $list): ?>
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
										<?php if(!empty($list['viaip'])) { ?>
										<td><? echo $list['viaip']; ?>/<? echo $list['viacidr']; ?></td>
										<?php } else { ?>
										<td><? echo $list['viafqdn']; ?></td>
										<?php } ?>
										<td><? echo $list['viaport']; ?></td>
										<td><? echo $list['proxyprotocol']; ?></td>
										<td><? echo $list['protocol']; ?></td>
										<td><? echo $list['command']; ?></td>
										<td>
										<!-- <a name="RoutesPassList_XMove_"
											id="RoutesPassList_XMove_" type="button"
											title="Move checked rules above this one. Shift+Click to move checked rules below."
											style="cursor: pointer;"> <i class="fa fa-anchor"></i>
										</a> -->
										<a name="RoutesPassList_editX[]"
											id="RoutesPassList_editX[]" type="button"
											title="<?=gettext('Edit this Active Routes List');?>"
											onClick='RoutesPassListid="<?=$i;?>"'
											style="cursor: pointer;"> <i class="fa fa-pencil"></i>
										</a>
										<a name="RoutesPassList_dupX[]"
											id="RoutesPassList_dupX[]" type="button"
											title="<?=gettext('Duplicate this Active Route Entry');?>"
											onClick='RoutesPassListid="<?=$i;?>"'
											style="cursor: pointer;"> <i class="fa fa-clone"></i>
										</a>
										<a name="RoutesPassList_deleteX[]"
											id="RoutesPassList_deleteX[]" type="button"
											title="<?=gettext('Delete this Active Routes List');?>"
											onClick='RoutesPassListid="<?=$i;?>"'
											style="cursor: pointer;"> <i class="fa fa-trash"
												title="<?=gettext('Delete this Active Routes List');?>"></i>
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
		<div class="modal fade" role="dialog" id="RoutesPassList_editor">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"
							aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>

						<h3 class="modal-title" id="myModalLabel"><?=gettext("Routes PASS List Editor")?></h3>
					</div>

					<div class="modal-body">
						<input type="hidden" name="RoutesPassList_id"
							id="RoutesPassList_id" value="<?=$passrouteslist_id;?>" /> <input
							type="hidden" name="RoutesPassList_ruleType"
							id="RoutesPassList_ruleType" value="Pass" />
						<?=gettext("FROM: ");?>
						<?=gettext(" FQDN or IP: ");?>
						<input type="text" size="40" class="form-control file"
							id="RoutesPassList_fromip" name="RoutesPassList_fromip"
							value="<?=$passrouteslist_fromip;?>" /><br />
						<?=gettext(" CIDR (Only in case of IP): ");?>
						<input type="text" size="2" class="form-control number"
							style="width: 40px;" id="RoutesPassList_fromcidr"
							name="RoutesPassList_fromcidr" value="<?=$passrouteslist_fromcidr;?>" /><br />
						<?=gettext("TO: ");?>
						<?=gettext(" FQDN or IP: ");?>
						<input type="text" size="40" class="form-control file"
							id="RoutesPassList_toip" name="RoutesPassList_toip"
							value="<?=$passrouteslist_toip;?>" /><br />
						<?=gettext(" CIDR (Only in case of IP): ");?>
						<input type="text" size="2" class="form-control number"
							style="width: 40px;" id="RoutesPassList_tocidr"
							name="RoutesPassList_tocidr" value="<?=$passrouteslist_tocidr;?>" /><br />
						<?=gettext(" target Port(s): ");?>
						<input type="text" size="8" class="form-control file"
							style="width: 200px;" id="RoutesPassList_tport"
							name="RoutesPassList_tport" value="<?=$passrouteslist_tport;?>" /><br />
						<?=gettext("VIA: ");?>
						<?=gettext(" FQDN or IP: ");?>
						<input type="text" size="40" class="form-control file"
							id="RoutesPassList_viaip" name="RoutesPassList_viaip"
							value="<?=$passrouteslist_viaip;?>" /><br />
						<?=gettext(" CIDR (Only in case of IP): ");?>
						<input type="text" size="2" class="form-control number"
							style="width: 40px;" id="RoutesPassList_viacidr"
							name="RoutesPassList_viacidr" value="<?=$passrouteslist_viacidr;?>" /><br />
						<?=gettext(" Via Port: ");?>
						<input type="text" size="8" class="form-control file"
							style="width: 200px;" id="RoutesPassList_viaport"
							name="RoutesPassList_viaport" value="<?=$passrouteslist_viaport;?>" /><br />
						<?=gettext("Proxy Protocol (http socks_v4 socks_v5): ");?>
						<input type="text" size="40" class="form-control file"
							id="RoutesPassList_proxyprotocol" name="RoutesPassList_proxyprotocol"
							value="<?=$RoutesPassList_proxyprotocol;?>" /><br />
						<?=gettext("Protocol (tcp udp): ");?>
						<input type="text" size="8" class="form-control file"
							id="RoutesPassList_protocol"
							name="RoutesPassList_protocol"
							value="<?=$RoutesPassList_protocol;?>" /><br />
						<?=gettext("Command (connect): ");?>
						<input type="text" size="8" class="form-control file"
							id="RoutesPassList_command"
							name="RoutesPassList_command"
							value="<?=$RoutesPassList_command;?>" /><br />
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
print_info_box ( '<p>' . 'Route all http connects via an upstream socks server, aka "server-chaining"<br />
route {<br />
 from: 10.0.0.0/8 to: 0.0.0.0/0 port = http via: socks.example.net port = socks<br />
}<br /></p>', 'info', false );
?>
	</div>
	<nav class="action-buttons">

		<button data-toggle="modal" data-target="#RoutesPassList_editor"
			role="button" aria-expanded="false" type="button"
			name="RoutesPassList_new" id="RoutesPassList_new"
			class="btn btn-success btn-sm"
			title="<?=gettext('Create a new Active Routes List');?>"
			onClick="document.getElementById('RoutesPassList_command').value=''; document.getElementById('RoutesPassList_tport').value='';document.getElementById('RoutesPassList_protocol').value='';document.getElementById('RoutesPassList_proxyprotocol').value=''; document.getElementById('RoutesPassList_fromip').value=''; document.getElementById('RoutesPassList_fromcidr').value=''; document.getElementById('RoutesPassList_toip').value=''; document.getElementById('RoutesPassList_tocidr').value='';  document.getElementById('RoutesPassList_viaip').value=''; document.getElementById('RoutesPassList_viacidr').value='';  document.getElementById('RoutesPassList_viaport').value=''; document.getElementById('RoutesPassList_editor').style.display='table-row-group'; document.getElementById('RoutesPassList_fromip').focus();
			document.getElementById('RoutesPassListid').value='<?=count($pass_routes);?>';">


			<i class="fa fa-plus icon-embed-btn"></i><?=gettext("Add")?>
			<button type="submit" class="btn btn-sm btn-primary" id="order-store"
				name="order-store" value="<?=gettext("Save");?>"
				title="<?=gettext("Save order's changes");?>">
				<i class="fa fa-save icon-embed-btn"></i>
					<?=gettext("Save");?>
			</button>
		</button>

	</nav>


</form>


<script type="text/javascript">
//<![CDATA[
events.push(function() {

	$('[id^=RoutesPassList_editX]').click(function () {
		$('#RoutesPassList_edit').remove();
		$('#RoutesPassList_dup').remove();
		$('#RoutesPassList_delete').remove();
		$('#RoutesPassList_id').val(RoutesPassListid);
		$('<input type="hidden" name="RoutesPassList_edit" id="RoutesPassList_edit" value="0"/>').appendTo($(form));
		$(form).submit();
	});

	$('[id^=RoutesPassList_dupX]').click(function () {
		$('#RoutesPassList_edit').remove();
		$('#RoutesPassList_dup').remove();
		$('#RoutesPassList_delete').remove();
		$('#RoutesPassList_id').val(RoutesPassListid);
		$('<input type="hidden" name="RoutesPassList_dup" id="RoutesPassList_dup" value="0"/>').appendTo($(form));
		$(form).submit();
	});

	$('[id^=RoutesPassList_deleteX]').click(function () {
		$('#RoutesPassList_edit').remove();
		$('#RoutesPassList_dup').remove();
		$('#RoutesPassList_delete').remove();
		$('#RoutesPassList_id').val(RoutesPassListid);
		$('<input type="hidden" name="RoutesPassList_delete" id="RoutesPassList_delete" value="0"/>').appendTo($(form));
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
<?php if ($passrouteslist_edit_style == "show") : ?>
	$("#RoutesPassList_editor").modal('show');
<?php endif ?>
});
//]]>
</script>

<?php
include ("foot.inc");
?>
