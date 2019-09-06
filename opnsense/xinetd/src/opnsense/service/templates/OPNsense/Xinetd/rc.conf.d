{% if helpers.exists('OPNsense.xinetd.general.enabled') and OPNsense.xinetd.general.enabled|default("0") == "1" %}
xinetd_var_script="/usr/local/opnsense/scripts/OPNsense/Xinetd/setup.sh"
xinetd_config="/usr/local/etc/xinetd.conf"
xinetd_enable="YES"
xinetd_flags=""
{% else %}
xinetd_enable="NO"
{% endif %}
