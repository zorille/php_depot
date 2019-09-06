{% if helpers.exists('OPNsense.sockdiops.sockdglobal.global.enabled') and OPNsense.sockdiops.sockdglobal.global.enabled|default("0") == "1" %}
sockdiops_var_script="/usr/local/opnsense/scripts/OPNsense/SockdIOPS/setup.sh"
sockdiops_config="/usr/local/etc/SockdIOPS/sockdiops.conf"
sockdiops_enable="YES"
sockdiops_flags=""
{% else %}
sockdiops_enable="NO"
{% endif %}
