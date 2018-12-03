<?php
/*
 * SockdIOPS_view_logs.php
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

//pkg install graphviz
##|+PRIV
##|*IDENT=SockdIOPS-view-logs
##|*NAME=Services: SockdIOPS
##|*DESCR=Allow access to the 'Services: SockdIOPS' page.
##|*MATCH=SockdIOPS_view_logs.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("SockdIOPS/SockdIOPS.inc");
require_once("SockdIOPS/network.inc");

$SockdIOPSlogdir = '/var/log/SockdIOPS_pfsense/';

$list=build_logfile_list();
$file=array_pop($list);

$liste_connexions = parse_log_SocksIOPS ($SockdIOPSlogdir.$file,10000);
$graph=create_graph($liste_connexions);


//$savemsg=$graph->render ();
// $tempo='digraph G {
//     subgraph cluster_1 {
//         node [style=filled, fillcolor=blue];
//         "192.168.150.99";
//     }
//     subgraph cluster_2 {
//         node [style=filled, fillcolor=blue];
//         "192.168.50.97";
//         "192.168.50.97";
//         "192.168.200.97";
//     }
//     subgraph cluster_3 {
//         node [style=filled, fillcolor=blue];
//         "192.168.50.100";
//         "192.168.200.97";
//     }
//     subgraph cluster_4 {
//         node [style=filled, fillcolor=blue];
//         "pfsense.outils.prod.infraops";
//     }
//     subgraph cluster_5 {
//         node [style=filled, fillcolor=blue];
//         "wiki.outils.prod.infraops";
//     }
//     subgraph cluster_6 {
//         node [style=filled, fillcolor=blue];
//         "192.168.150.100";
//     }
//     subgraph cluster_7 {
//         node [style=filled, fillcolor=blue];
//         "dolibarr.outils.prod.infraops";
//     }
//     subgraph cluster_8 {
//         node [style=filled, fillcolor=blue];
//         "zabbix.outils.prod.infraops";
//     }
//     subgraph cluster_9 {
//         node [style=filled, fillcolor=blue];
//         "192.168.150.98";
//     }
//     subgraph cluster_10 {
//         node [style=filled, fillcolor=blue];
//         "192.168.50.98";
//         "172.16.83.14";
//     }
//     subgraph cluster_11 {
//         node [style=filled, fillcolor=blue];
//         "vcenter.admin.equal";
//     }
//     "192.168.150.99" -> "192.168.50.97" [label="44443"];
//     "192.168.50.97" -> "192.168.50.97" [label=internal];
//     "192.168.50.97" -> "192.168.50.100" [label="44443"];
//     "192.168.50.97" -> "192.168.200.97" [label=internal];
//     "192.168.50.97" -> "192.168.50.98" [label="44443"];
//     "192.168.50.100" -> "192.168.200.97" [label=internal];
//     "192.168.200.97" -> "pfsense.outils.prod.infraops" [label="443"];
//     "192.168.200.97" -> "wiki.outils.prod.infraops" [label="22"];
//     "192.168.200.97" -> "192.168.200.97" [label="22"];
//     "192.168.200.97" -> "dolibarr.outils.prod.infraops" [label="443"];
//     "192.168.200.97" -> "zabbix.outils.prod.infraops" [label="443"];
//     "192.168.150.100" -> "192.168.50.97" [label="44443"];
//     "192.168.150.98" -> "192.168.50.97" [label="44443"];
//     "192.168.50.98" -> "172.16.83.14" [label=internal];
//     "172.16.83.14" -> "vcenter.admin.equal" [label="443"];
// }
// ';
// file_put_contents ( "/tmp/render.dot", $tempo );
// exec("/usr/local/bin/dot -Tpng /tmp/render.dot > network.png");

$pgtitle = array(gettext("Package"), gettext("SockdIOPS"), gettext("Network View"));
include_once("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($savemsg) {
	print_info_box($savemsg);
}

add_header_menu("View Graph");

?>


<div class="panel panel-default" id="fileOutput">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('Log Contents')?></h2></div>
		<div class="panel-body">
			<img src="network.png" alt="graphe de connexions reseau" />
		</div>
</div>

<?php include("foot.inc"); ?>

