<?php

/*
 Copyright (C) 2018 Damien Vargas
 Copyright (C) 2014 Deciso B.V.
 Copyright (C) 2010 Jim Pingle <jimp@pfsense.org>
 Copyright (C) 2006 Eric Friesen
 All rights reserved.
 
 Redistribution and use in source and binary forms, with or without
 modification, are permitted provided that the following conditions are met:
 
 1. Redistributions of source code must retain the above copyright notice,
 this list of conditions and the following disclaimer.
 
 2. Redistributions in binary form must reproduce the above copyright
 notice, this list of conditions and the following disclaimer in the
 documentation and/or other materials provided with the distribution.
 
 THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 POSSIBILITY OF SUCH DAMAGE.
 */

require_once("guiconfig.inc");
require_once("SockdIOPS/network.inc");

$service_hook = 'sockdiops';

$SockdIOPSlogdir = '/var/log/SockdIOPS_opnsense/';

$file=build_logfile_list();

$liste_connexions = parse_log_SocksIOPS ($SockdIOPSlogdir.$file,10000);
$graph=create_graph($liste_connexions);

include("head.inc");
?>

<body>

<?php
include("fbegin.inc");

?>

<section class="page-content-main">
  <div class="container-fluid">
    <div class="row">

      <section class="col-xs-12">
		<div class="panel panel-default" id="fileOutput">
			<div class="panel-heading"><h2 class="panel-title"><?=gettext('Connected Graph')?></h2></div>
			<div class="panel-body">
				<img src="/SockdIOPS/network.png" alt="graphe de connexions reseau" />
			</div>
		</div>
	  </section>
	  <section class="col-xs-12">
	  	<div class="content-box" style="padding-bottom: 1.5em;">
            <div class="col-md-12">
                SockdIOPS Serial Number : <?php echo retrieve_code();?>
            </div>
        </div>
	  </section>
	</div>
  </div>
</section>

<?php include("foot.inc"); ?>

