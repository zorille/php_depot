#! /usr/bin/perl -w
#
# Copyright © CoServIT 2018 – tous droits réservés.
# Avertissement : ce logiciel est protégé par le code de la propriété intellectuelle et le droit d’auteur.
# Toute personne ne respectant pas ces dispositions se rendra coupable du délit de contrefaçon et 
# sera passible des sanctions pénales prévues par la loi. En particulier, aucune reproduction, même 
# partielle, autres que celles prévues à l'article L 122-5 du code de la propriété intellectuelle, ne peut
# être faite de ce logiciel sans l'autorisation expresse de l'auteur.
# Les droits d’utilisation du logiciel sont régis par la relation contractuelle établie entre l’auteur et 
# l’utilisateur du logiciel.
# Aucun droit d’utilisation n’est consenti par l’auteur en l’absence de relation contractuelle.
#

use strict;
use warnings;
use File::Basename;
use Data::Dumper;
use Time::Local;
use Compress::Zlib;
use MIME::Base64;

# Coservit API
eval {
        require(dirname($0)."/CoservitAPI.pm");
} or die "Missing Coservit module";

# Nagios specific
use lib "/usr/local/nagios/libexec";
use utils qw(%ERRORS $TIMEOUT);

# Constant used to simulate boolean type in Perl.
use constant false => 0;
use constant true  => 1;

my $PROGNAME = basename($0);
my $VERSION = '1.0.0';

my $plugin = '/usr/local/nagios/libexec/check_linux_command';

# New Coservit Plug
my $np = Coservit::PluginBox->new(
        {
                name => $PROGNAME,
                version => $VERSION,
                copyright => "Copyright (c) Coservit 2018",
                subject => "Se connecte avec le protocole SSH sur le système Linux associé au contrôle et collecte le temps de réponse sur un port de l'équipement Linux indiqué en paramètre. Passe en état critique si le port n'est pas joignable, ou au dessus des seuils.",
                description => "Se connecte avec le protocole SSH sur le système Linux associé au contrôle et collecte le temps de réponse sur un port de l'équipement Linux indiqué en paramètre. Passe en état critique si le port n'est pas joignable, ou au dessus des seuils. <Strong>Configuration:</Strong> compte, mot de passe, port de connexion SSH, adresse IP ou nom DNS de l'équipement, port à interroger, seuils d'alerte et critique, délai d'exécution."
        }
);

$np->addOption({
    key                 => 'l',
    label               => 'Nom d\'utilisateur',
    description => 'Nom d\'utilisateur de connexion au serveur',
    sample              => 'user',
    type                => 2,
    context             => 1,
    required    => 1
});

$np->addOption({
    key                 => 'x',
    label               => 'Mot de passe',
    description => 'Mot de passe de connexion au serveur',
    sample              => 'password',
    type                => 3,
    context             => 1,
    required    => 1
});

$np->addOption({
    key                 => 'port',
    label               => 'Port SSH',
    description => 'Port de connexion, 22 par défaut.',
    type                => 10,
    context             => 1,
    required    => 0,
    sample              => '822',
    default             => 22
});

$np->addOption({
    key                 => 'ip',
    label               => 'DNS / IP',
    description => 'Nom DNS ou adresse IP du système cible',
    type                => 1,
    required    => 1,
    sample              => '1.2.3.4'
});

$np->addOption({
    key                 => 'p',
    label               => 'Port',
    description => 'Port interrogé sur le système cible',
    type                => 11,
    required    => 1,
    sample              => '3306'
});

$np->addOption({
    key                 => 'o2',
    label               => 'Seuil d\'alerte (ms)',
    type                => 22,
    description => 'Seuil en millisecondes au delà duquel le contrôle passe en alerte. Syntaxe : seuil Nagios. La valeur comparée au seuil est la moyenne des temps de réponse obtenus.',
    sample              => '15',
    required    => 1
});

$np->addOption({
    key                 => 'o1',
    label               => 'Seuil critique (ms)',
    type                => 22,
    description => 'Seuil en millisecondes au delà duquel le contrôle passe en critique. Syntaxe : seuil Nagios. La valeur comparée au seuil est la moyenne des temps de réponse obtenus.',
    sample              => '20',
    required    => 1
});

$np->addOption({
    key                 => 'm2',
    label               => 'Seuil d\'alerte (%)',
    type                => 11,
    description => 'Pourcentage d\'essais ayant échoué',
    sample              => '10',
    required    => 1
});

$np->addOption({
    key                 => 'm1',
    label               => 'Seuil critique (%)',
    type                => 11,
    description => 'Pourcentage d\'essais ayant échoué',
    sample              => '20',
    required    => 1
});

$np->addOption({
    key                 => 'n',
    label               => 'Nombre',
    type                => 11,
    description => 'Nombre d\'essais à effectuer lors d\'un contrôle. Par défaut 4.',
    sample              => '10',
    required    => 0,
    default     => 4
});

$np->checkOptions();

# Récupération des arguments
my $o_host = $np->getValue('H');
my $o_login = $np->getValue('l');
my $o_password = $np->getValue('x');
my $o_targetIP = $np->getValue('ip');
my $o_criticalThresholdTime = $np->getValue('o1');
my $o_warningThresholdTime = $np->getValue('o2');
my $o_criticalThresholdLoss = $np->getValue('m1');
my $o_warningThresholdLoss = $np->getValue('m2');
my $o_nbTries = $np->getValue('n');
my $o_port = $np->getValue('port');
my $o_portTargetHost = $np->getValue('p');
my $o_timeout = $np->getValue('timeout');

if (!defined($o_timeout))
{
    $o_timeout = 60;
}

if (defined($o_timeout) && $o_timeout > 1)
{
    #pour avoir le temps de partir en timeout avant tout le script expire, il faut prendre une seconde en moins.
    $o_timeout--;
}

my $timeout_cmd = "timeout $o_timeout ";

# Path to ssh pass
my $sshpassPath = '/usr/bin/sshpass';

# script to execute remotely
#r[0] : ret code  ;  r[1] : time ; r[2] : ip
my $prg = sprintf 'use MIME::Base64;use Net::Ping;$p=new Net::Ping("tcp");$p->hires(1);$p->port_number(%s);$n=0;$t=0;$s=0;$min=0;$max=0;', $o_portTargetHost ;
$prg   .= sprintf 'for(1..%s){@r=$p->ping("%s");@r or last;$s++ if $r[0];$n++;$r[1]=$r[1]*1000;$min = $r[1] if $n == 1;$max = $r[1] if $n == 1;$max = $r[1] if($max < $r[1]);$min = $r[1] if($min > $r[1]);$t+=$r[1];};',$o_nbTries, $o_targetIP ;
$prg   .= 'if($n){printf"pl=%.2f;rta=%f;rtmin=%f;rtmax=%f\n",100 * ($n - $s)/$n,$t/$n,$min,$max}else{print"resolving_error"}' ;

verb('script: '.$prg);

my $encoded = encode_base64(compress($prg), "");
my $pingCmd = sprintf "perl -e 'use MIME::Base64;use Compress::Zlib;eval(uncompress(decode_base64(\"%s\")))'\n", $encoded ;

# Get informations
$pingCmd =~ s/"/"'"'"/g;
my $cmd = "$timeout_cmd$sshpassPath -p '$o_password' ssh -o StrictHostKeyChecking=no -l $o_login $o_host -p $o_port \"$pingCmd\" 2>&1";
my $result = `$cmd`;
my $error = $?;

verb ("Command : $cmd");
verb ("Result : $result");

if (($error >> 8) == 124 || ($error >> 8) != 0) 
{
    printf "Could not connect to the host";
    exit $ERRORS{'CRITICAL'};
}

$result = trim($result);
if($result eq ''){
    printf "Unable to get the information: empty result.";
    exit $ERRORS{'UNKNOWN'};
}

if($result eq 'resolving_error'){
    printf "Unable to resolve target hostname";
    exit $ERRORS{'UNKNOWN'};
}

my @resultArray = split(';', $result);
my @pl = split('=',$resultArray[0]);
my @rta = split('=',$resultArray[1]);
my @rtmin = split('=',$resultArray[2]);
my @rtmax = split('=',$resultArray[3]);

my $statusRTA = $np->getStatus($rta[1],$o_warningThresholdTime,$o_criticalThresholdTime);

my $statusLossPacket;
if($pl[1] >= $o_criticalThresholdLoss){
    $statusLossPacket = $ERRORS{'CRITICAL'};
}elsif($pl[1] >= $o_warningThresholdLoss){
    $statusLossPacket = $ERRORS{'WARNING'};
}else{
    $statusLossPacket = $ERRORS{'OK'};
}

my $status = $ERRORS{'OK'};
my $output = "Host: $o_targetIP - Port: $o_portTargetHost rta $rta[1]ms, lost $pl[1]%";
# PerfData
$output .= "|pl=$pl[1]%;$o_warningThresholdLoss;$o_criticalThresholdLoss rta=$rta[1]ms".$np->getThresholdPerfString($o_warningThresholdTime,$o_criticalThresholdTime)." rtmin=$rtmin[1]ms rtmax=$rtmax[1]ms";

if($statusRTA == $ERRORS{'CRITICAL'} || $statusLossPacket == $ERRORS{'CRITICAL'}){
    $status = $ERRORS{'CRITICAL'};
    
}elsif($statusRTA == $ERRORS{'WARNING'} || $statusLossPacket == $ERRORS{'WARNING'}){
    $status = $ERRORS{'WARNING'};

}

print $output;
exit $status;
