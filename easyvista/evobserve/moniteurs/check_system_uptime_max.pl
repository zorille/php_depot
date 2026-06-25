#! /usr/bin/perl -w
#
# Copyright © CoServIT 2015 – tous droits réservés.
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
use Storable;
use Scalar::Util qw(looks_like_number);


# Librairy for SNMP
use Net::SNMP;

# Constant used to simulate boolean type in Perl.
use constant false => 0;
use constant true  => 1;

# Coservit API
eval {
        require(dirname($0)."/CoservitSNMPAPI.pm");
} or die "Missing Coservit module";

# Nagios specific
use lib "/usr/local/nagios/libexec";
use utils qw(%ERRORS $TIMEOUT trim);

my $PROGNAME = basename($0);
my $VERSION = '1.0.0';

# New Coservit Plug
my $np = Coservit::SNMPBox->new(
        {
                name => $PROGNAME,
                version => $VERSION,
                copyright => "Copyright (c) Coservit 2015",
                subject => "Vérifie que l’équipement n’est pas allumé depuis plus d’un certain temps.",
                description => "Vérifie que l’équipement n’est pas allumé depuis plus d’un certain temps. Notifie lors d’un dépassement de seuil. <strong>Configuration :</strong> communauté SNMP, seuil d’alerte et seuil critique."
        }
);

# Host, Communaute, version, port sont defini dans la Coservit API

$np->addOption({
        key                     => 'w',
        label           => 'Seuil alerte',
        type            => 14, #Periode
        description => 'Seuil d\'alerte si l\'équipement n\'a pas été redémarré depuis plus n jours. Format du seuil : 07d00h00m',
        required        => 1,
        sample          => '07d00h00m'
});
$np->addOption({
        key                     => 'c',
        label           => 'Seuil critique',
        type            => 14, #Periode
        description => 'Seuil critique si l\'équipement n\'a pas été redémarré depuis plus m jours. Format du seuil : 08d00h00m',
        required        => 0,
        sample          => '08d00h00m'
});

###############################################################################
################       Vérification des options     ###########################
###############################################################################
$np->checkOptions();

# Seuils warning et critique
my $warning_period = $np->getValue('w');
my $critical_period = $np->getValue('c');

# Conversion de la pťriode
my @jour = split(/[dj]/i, lc($warning_period));
my @heure = split(/h/i, $jour[1]);
my @minute = split(/m/i, $heure[1]);

unless (looks_like_number($jour[0]) && looks_like_number($heure[0]) && looks_like_number($minute[0]) ) {
        print "Unable to get the information: Incorrect warning period\n";
        exit $ERRORS{"UNKNOWN"}
}

my $warning_threshold = $jour[0] * (24.0 * 60.0) + $heure[0] * 60.0 + $minute[0];

# Conversion de la pťriode
@jour = split(/[dj]/i, lc($critical_period));
@heure = split(/h/i, $jour[1]);
@minute = split(/m/i, $heure[1]);

unless (looks_like_number($jour[0]) && looks_like_number($heure[0]) && looks_like_number($minute[0]) ) {
        print "Unable to get the information: Incorrect critical period\n";
        exit $ERRORS{"UNKNOWN"}
}

my $critical_threshold = $jour[0] * (24.0 * 60.0) + $heure[0] * 60.0 + $minute[0];

# SNMPv2 Login
$np->openSNMPSession;

# SNMP initialisation failed
if (!$np->sessionOpen) {
   printf("ERROR: %s.\n", $np->lastError);
   exit($ERRORS{'UNKNOWN'});
}


###############################################################################
###############       Lancement de la requête SNMP     #########################
###############################################################################

my $UPTIMEOID_DESC='1.3.6.1.2.1.1.3.0';

my $session = $np->session;

my $uptime = $session->get_request( -varbindlist => [$UPTIMEOID_DESC] );
  if (!$uptime) {
      printf("Unable to get the information : %s.\n", $session->error);
      exit $ERRORS{'UNKNOWN'};
 }
 
$uptime = $uptime->{$UPTIMEOID_DESC};

my $uptime_min = int($uptime / 100.0 / 60.0);

###############################################################################
##################           ###########################
###############################################################################

sub formatUptime
{
        my $in_uptime = shift;

        my $days = int($in_uptime / (100.0 * 60.0 * 60.0 * 24.0));

        my $hours = int($in_uptime / (100.0 * 60.0 * 60.0)) % 24;

        my $minutes = int($in_uptime / (100.0 * 60.0)) % 60;

        my $seconds = int($in_uptime / (100.0)) % 60;

        my $cents = int($in_uptime % 100);

        my $outstr = '';

        if ($days > 0)
        {
                $outstr .= $days . ($days != 1 ? ' days' : ' day') . ', ';
        }

        $outstr .= $hours . ':' . sprintf("%02d", $minutes) . ':' . sprintf("%02d", $seconds) . '.' . sprintf("%02d", $cents);

        return $outstr; 
}

my $returnCode = $np->getStatus( $uptime_min , $warning_threshold , $critical_threshold );
my $output = '';

if ($returnCode == $ERRORS{'CRITICAL'})
{
        $output = 'CRITICAL: ';
}
elsif ($returnCode == $ERRORS{'WARNING'})
{
        $output = 'WARNING: ';
}

$output .= 'System up for ' . formatUptime($uptime);

my $perfData ="Uptime=" . $uptime_min . "minutes" . $np->getThresholdPerfString($warning_threshold, $critical_threshold);
print $output."|".$perfData;
exit $returnCode;
