#! /usr/bin/perl -w
#
# Copyright © CoServIT 2017 – tous droits réservés.
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
use Scalar::Util qw( looks_like_number );

# Librairy for SNMP
use Net::SNMP;

# Constant used to simulate boolean type in Perl.
use constant false => 0;
use constant true  => 1;

use Switch;

no if $] >= 5.018, warnings => 'experimental';

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
        copyright => "Copyright (c) Coservit 2017",
        subject => "Réalise une ou deux opérations mathématiques entre plusieurs OID puis renvoie un ou deux résultats. Alerte si le résultat du premier calcul dépasse un certain seuil.",
        description => "<Strong>Configuration : </Strong> Communauté SNMP, version SNMP, port SNMP, liste OID, 1ère formule de calcul | 2ème formule de calcul, seuil warning, seuil critique, préfixe des données de performance."
    }
);

# Host, Communaute, version, port sont defini dans la Coservit API
$np->addOption({
        key			=> 'oid',
        label		=> 'Liste des OID',
        type		=> 1,
        description => 'Listes des OID utilisées dans le calcul.
                        Séparées par des points-virgules.',
        required	=> 1,
        sample		=> '1.5.6.3.5;1.2.9.7.3;1.2.5.4.0.7'
    });

$np->addOption({
        key			=> 'calc',
        label		=> 'Formule de calcul',
        type		=> 1,
        description => 'Formule du calcul à appliquer sur les OID.
                        Utilisation de variables OID1,OID2,OID3,OIDn définies dans l\'ordre de déclaration des OID dans l\'option --oid
                        Prise en compte des parenthèses pour priorités de calcul.
                        Possibilité de faire 2 calculs en les séparant par un \' | \'.
                        Dans ce cas, le résultat du 1er calcul sera stocké dans une variable RES1 et sera la seule valeur comparée aux seuils.
                        Le résultat du 2ème calcul sera stocké dans une variable RES2 que l\'on pourra afficher dans la sortie du plugin.',
        required	=> 1,
        sample		=> '(1-((OID3*10^9+OID4)/(OID1*10^9+OID2))°*100|OID2+OID4'
    });

$np->addOption({
        key			=> 'w',
        label		=> 'Seuil d\'alerte',
        type		=> 22,
        description => 'Seuil d\'alerte dans l\'unité choisie dans l\'option -F. Seule la valeur RES1 est comparée à ce seuil.',
        required	=> 1,
        sample		=> '80'
    });

$np->addOption({
        key			=> 'c',
        label		=> 'Seuil Critique',
        type		=> 22,
        description => 'Seuil critique dans l\'unité choisie dans l\'option -F. Seule la valeur RES1 est comparée à ce seuil.',
        required	=> 1,
        sample		=> '90'
    });

$np->addOption({
        key			=> 'F',
        label		=> 'Format de sortie et Perfdata',
        type		=> 1,
        description => 'Adaptation de la sortie texte du plugin.
                        Sortie prédéfinie : F1 : RES1 F2 (RES2 F3) > #seuil_warning# où F1,F2,F3 sont les 3 paramètres modifiables ici.
                        On mettra dans :
                        F1, le nom de la valeur mesurée.
                        F2, l\'unité du résultat du premier calcul
                        F3, l\'unité du résultat du deuxième calcul si il y en a un.',
        required	=> 1,
        sample		=> 'Used Space;%;GB|used_prct;used_GB'
    });

$np->addOption({
        key			=> 'default',
        label		=> 'Valeur par défaut',
        type		=> 11,
        description => 'Valeur retournée par le plugin si un des calculs implique une division par zéro. Si ce paramètre n\'est pas renseigné, la division par zéro ne sera pas possible et lèvera un erreur.',
        required	=> 0,
        sample		=> '100'
    });

###############################################################################
################       Vérification des options     ###########################
###############################################################################
$np->checkOptions();

my $o_oids = $np->getValue('oid');
my $o_calc = $np->getValue('calc');
my $warningThreshold = $np->getValue('w');
my $criticalThreshold = $np->getValue('c');
my $o_format = $np->getValue('F');
my $o_default = $np->getValue('default');

$SIG{'ALRM'} = sub {
    print "Cannot get information. Please check your SNMP configuration.";
    exit $ERRORS{"UNKNOWN"};
};

my ($output_format,$perfdata_format) = split('\|',$o_format);
my @params_format;

if(defined $output_format && $output_format ne ''){

    @params_format = split(':',$output_format);
    verb('Nb params output: ' . scalar(@params_format));
    if(scalar(@params_format) > 3){
        print 'Too many parameters in -F option';
        exit $ERRORS{'UNKNOWN'};
    }
    if(scalar(@params_format) < 2){
        print 'Too less parameters in -F option';
        verb();
        exit $ERRORS{'UNKNOWN'};
    }

}else{
    print 'Invalid output format.';
    exit $ERRORS{'UNKNOWN'};
}

my @arrayCalc = split('\|', $o_calc);

if(scalar(@arrayCalc) > 2){
    print 'Too many calculation operations';
    exit $ERRORS{'UNKNOWN'};
}

###### SNMP Login
$np->openSNMPSession;

###### SNMP initialisation failed
if (!$np->sessionOpen) {
    print "Cannot get information. Please check your SNMP configuration.";
    exit $ERRORS{'UNKNOWN'};
}
my $session = $np->session();

my @arrayOID = split(':',$o_oids);
my @arrayResultatOID;

my $j = 1;
foreach my $oid (@arrayOID) {
    my $tempRes = $session->get_request(-varbindlist => [$oid]);
    $tempRes = $tempRes->{$oid};
    verb("OID$j=$tempRes");$j++;

    if (!defined($tempRes) ||$tempRes eq ''  ) {
        print "$oid: not found or empty OID";
        $np->closeSNMPSession;
        exit $ERRORS{'UNKNOWN'};

    } elsif (! ($tempRes =~ /^[+-]?\d+$/)){
        print "$tempRes: not an integer, operation not possible.";
        $np->closeSNMPSession;
        exit $ERRORS{'UNKNOWN'};

    } else {
        push @arrayResultatOID, $tempRes;
    }
}
$np->closeSNMPSession;

my @resCalculation;
my $tempResult;
foreach my $calcOp (@arrayCalc)
{
    for(my $i=0;$i < scalar(@arrayResultatOID);$i++)
    {
        my $oid_to_replace = "OID" . ($i+1);
        $calcOp =~ s/$oid_to_replace/$arrayResultatOID[$i]/g;
    }
    verb("Calculation operation: $calcOp");
    if($calcOp =~ /\/0/){
        if(defined($o_default) && $o_default ne ""){
            @resCalculation = ();
            push @resCalculation, $o_default;
            last;
        }else{
            print 'Division by 0, operation not permitted';
            exit $ERRORS{'UNKNOWN'};
        }
    }else{
        $tempResult = eval $calcOp;         # Evaluate that line
        if ($@) {                              # Check for compile or run-time errors.
            print "Invalid calculation operation: $calcOp";
            exit $ERRORS{'UNKNOWN'};

        } else {
            if($tempResult < 0){
                print "Result is a negative value";
                exit $ERRORS{'UNKNOWN'};
            }else{
                push @resCalculation, $tempResult;
            }
        }
    }
}

my $status = $np->getStatus($resCalculation[0],$warningThreshold,$criticalThreshold);
my $output = sprintf("%s: %.2f %s",$params_format[0],$resCalculation[0],$params_format[1]);

if(scalar(@params_format) > 2 && scalar(@resCalculation) > 1){
    $output .= sprintf("(%.2f %s)",$resCalculation[1],$params_format[2]);
}

if($status == $ERRORS{'CRITICAL'}){
    $output .= ': CRITICAL';

}elsif($status == $ERRORS{'WARNING'}){
    $output .= ': WARNING';

}else{
    $output .= ': OK';

}

my $perfData = '';
if(defined $perfdata_format && $perfdata_format ne '') {
    my @arrayPerfData = split(':',$perfdata_format);

    for(my $i = 0; $i < scalar(@arrayPerfData); $i++){
        #We add to the first perf thresholdes
        if($i == 0){
            $perfData .= "'$arrayPerfData[0]'=".sprintf("%.2f%s",$resCalculation[0],$params_format[1]) . $np->getThresholdPerfString($warningThreshold,$criticalThreshold);
        }else{
            if(defined $resCalculation[$i] && $resCalculation[$i] ne ''){
                $perfData .= " '$arrayPerfData[$i]'=".sprintf("%.2f",$resCalculation[$i]);
                if(scalar(@params_format) > 2 ){
                    $perfData .= $params_format[2];
                }
            }
        }
    }
    print $perfData;
    $output .= '|'.$perfData;
}

print $output;
exit $status;
