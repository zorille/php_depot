{#
 # Copyright (C) 2018 Damien Vargas
 # Copyright (C) 2017 Frank Wall
 # Copyright (C) 2014-2015 Deciso B.V.
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without modification,
 # are permitted provided that the following conditions are met:
 #
 # 1.  Redistributions of source code must retain the above copyright notice,
 #     this list of conditions and the following disclaimer.
 #
 # 2.  Redistributions in binary form must reproduce the above copyright notice,
 #     this list of conditions and the following disclaimer in the documentation
 #     and/or other materials provided with the distribution.
 #
 # THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 # INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 # AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 # AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 # OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 # SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 # INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 # CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 # ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 # POSSIBILITY OF SUCH DAMAGE.
 #}

<script>
    $( document ).ready(function () {
        var data_get_map = {'frm_forward_settings':"/api/privoxy/forward/get"};
        mapDataToFormUI(data_get_map).done(function (data) {
            formatTokenizersUI();
            $('.selectpicker').selectpicker('refresh');
            updateServiceControlUI('privoxy');
        });

        ajaxCall(url="/api/privoxy/service/status", sendData={}, callback=function (data, status) {
            updateServiceStatusUI(data['status']);
        });

        /*************************************************************************************************************
         * link grid actions
         *************************************************************************************************************/

        $("#grid-forwards").UIBootgrid(
            {   'search':'/api/privoxy/forward/searchForward',
                'get':'/api/privoxy/forward/getForward/',
                'set':'/api/privoxy/forward/setForward/',
                'add':'/api/privoxy/forward/addForward/',
                'del':'/api/privoxy/forward/delForward/',
                'toggle':'/api/privoxy/forward/toggleForward/'
            }
        );

        /*************************************************************************************************************
         * Commands
         *************************************************************************************************************/

        // update history on tab state and implement navigation
    	if(window.location.hash != "") {
        	$('a[href="' + window.location.hash + '"]').click()
    	}
    	$('.nav-tabs a').on('shown.bs.tab', function (e) {
        	history.pushState(null, null, e.target.hash);
    	});
    });
</script>

<div class="tab-content content-box tab-content">
    <div class="content-box" style="padding-bottom: 1.5em;">
    	<!-- tab page "forwards" -->
        <table id="grid-forwards" class="table table-condensed table-hover table-striped table-responsive" data-editDialog="Forward">
            <thead>
            <tr>
                <th data-column-id="rulePosition" data-type="string" data-visible="true" data-sortable="true">{{ lang._('Rule Order') }}</th>
                <th data-column-id="ruleType" data-type="string" data-visible="true" data-sortable="false">{{ lang._('Rule Type') }}</th>
                <th data-column-id="targetPattern" data-type="string" data-visible="true" data-sortable="false">{{ lang._('Target Pattern') }}</th>
                <th data-column-id="httpParentProxy" data-type="string" data-visible="true" data-sortable="false">{{ lang._('HTTP Parent IP/FQDN') }}</th>
                <th data-column-id="httpParentPort" data-type="string" data-visible="true" data-sortable="false">{{ lang._('HTTP Parent Port') }}</th>
                <th data-column-id="socksProxy" data-type="string" data-visible="true" data-sortable="false">{{ lang._('Socks IP/FQDN') }}</th>
                <th data-column-id="socksPort" data-type="string" data-visible="true" data-sortable="false">{{ lang._('Socks Port') }}</th>
                <th data-column-id="uuid" data-type="string" data-identifier="true" data-visible="false" data-sortable="false">{{ lang._('ID') }}</th>
                <th data-column-id="commands" data-formatter="commands" data-sortable="false">{{ lang._('Commands') }}</th>            
            </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
            <tr>
                <td></td>
                <td>
                    <button data-action="add" type="button" class="btn btn-xs btn-default"><span class="fa fa-plus"></span></button>
                </td>
            </tr>
            </tfoot>
        </table>
        </div>
        {{ partial("layout_partials/base_dialog",['fields':formForward,'id':'Forward','label':lang._('Add Forward Rule')])}}
</div>

