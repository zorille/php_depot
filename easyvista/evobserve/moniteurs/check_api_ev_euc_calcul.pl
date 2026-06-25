#! /usr/bin/perl -w
#
# Copyright © CoServIT 2020 – tous droits réservés.
# Avertissement : ce logiciel est protégé par le code de la propriété intellectuelle et le droit d'auteur.
# Toute personne ne respectant pas ces dispositions se rendra coupable du délit de contrefaçon et
# sera passible des sanctions pénales prévues par la loi. En particulier, aucune reproduction, même
# partielle, autres que celles prévues à l'article L 122-5 du code de la propriété intellectuelle, ne peut
# être faite de ce logiciel sans l'autorisation expresse de l'auteur.
# Les droits d'utilisation du logiciel sont régis par la relation contractuelle établie entre l'auteur et
# l'utilisateur du logiciel.
# Aucun droit d'utilisation n'est consenti par l'auteur en l'absence de relation contractuelle.
#
use POSIX;
use strict;
use warnings;
use File::Basename;
use JSON;
use LWP;
use DateTime;
use Time::Local;
use Data::Dumper;
use lib dirname($0);

# Constant used to simulate boolean type in Perl.
use constant false => 0;
use constant true  => 1;

# Coservit API
eval {
	require(dirname($0)."/CoservitAPI.pm");
} or die "Missing Coservit module";

# Nagios specific
use utils qw(%ERRORS $TIMEOUT trim);

my $PROGNAME = basename($0);
my $VERSION = '1.1.0';

sub makeHeader {
    my $request = shift;
    $request->header(accept => "application/json");
    $request->header('Content-Type' => "application/x-www-form-urlencoded");
}

sub getToken {
    my ($ua, $subUrl, $subLogin , $subPassword) = @_ or die "Missing (User Agent, Login , Password or Url) Parametre please check ";

    my $req;
    my $data;
    my $resp;
    my $result;
    my $token;
    my $errmsg;

    my $tokenUrl = $subUrl."/servicenav/auth/token";
    $data = "username=$subLogin&password=$subPassword";
    $req = HTTP::Request->new( "POST" => $tokenUrl);
    makeHeader($req);
    $req->content($data);
    $resp = $ua->request($req);

    if(!$resp->is_success) {
        my $codeRetour = $resp->{_rc};

        if ($codeRetour eq '500') {
            $errmsg = "Unable to connect to  $subUrl please check ";
            print $errmsg;
            exit($ERRORS{UNKNOWN});
        }else {
            my $message  = $resp->{_content};
            $errmsg = "Error : $message please check ";
            print $errmsg;
            exit($ERRORS{UNKNOWN});
        }

    }

    $result = JSON::decode_json($resp->decoded_content);
    $token = $result->{'token'};
    verb(" Token : $token");
    return $token;
}

sub getData {
    my ($ua ,$token,$subUrl) = @_ or die "Missing (User Agent,Token or Url) Parametre please check  ";

    my $req;
    my $resp;
    my $result;
    my $errmsg;

    $req = HTTP::Request->new( "GET" => $subUrl);
    makeHeader($req);
    $req->header('Authorization' => "Bearer $token");
    $resp = $ua->request($req);

    if(!$resp->is_success) {
        my $codeRetour = $resp->{_rc};
        if ($codeRetour eq '500') {
            $errmsg = "print GET request false : Please check ";
            print $errmsg;
            exit($ERRORS{UNKNOWN});
        }else {
            my $message  = $resp->{_content}->{message};
            $errmsg = "Error : $message please check ";
            print $errmsg;
            exit($ERRORS{UNKNOWN});
        }
    }
    $result = JSON::decode_json($resp->decoded_content);
    return $result;
}

sub getDataPost {
    my ($ua ,$token,$subUrl,$data) = @_ or die "Missing (User Agent,Token or Url) Parametre please check  ";

    my $req;
    my $resp;
    my $result;
    my $errmsg;

    $req = HTTP::Request->new( "POST" => $subUrl);
    makeHeader($req);
    $req->header('Authorization' => "Bearer $token");
	 $req->header('Content-Type' => "application/json");

	$req->content(JSON::encode_json($data));
    $resp = $ua->request($req);

    if(!$resp->is_success) {
        my $codeRetour = $resp->{_rc};
        if ($codeRetour eq '500') {
            $errmsg = "Post response false : Please check ";
            print $errmsg;
            exit($ERRORS{UNKNOWN});
        }else {
            my $message  = $resp->{_content}->{message};
            $errmsg = "Error : $message please check ";
            print $errmsg;
            exit($ERRORS{UNKNOWN});
        }
    }
    $result = JSON::decode_json($resp->decoded_content);
    return $result;
}

sub collectValue {
	verb("collectValue");
	my ($ua ,$token,$url,$host,$service) = @_ or die "Missing (User Agent,Token or Url) Parametre please check  ";
	my $result;

	#collect service A from company
	my $url_service = $service;
	$url_service =~ s/ /+/g;
    $url_service =~ s/([^A-Za-z0-9\+-])/sprintf("%%%02X", ord($1))/seg;
	verb("host : ".$host);
	verb("service : ".$service);
	my $apiUrl = $url ."/servicenav/fr_FR/services?fields[]=name&fields[]=id&display_name=".$url_service;
	$result = getData($ua,$token,$apiUrl);


	my $service_id=0;
	foreach my $row (@{$result->{_embedded}->{items}}) {
		$service_id =  "service_".$row->{id};
        	verb("Service : ".$service_id);
		verb("Service check : ".$row->{name});
	}

	#collect group metric from service A from company
	$apiUrl = $url ."/bigdata/groups/$service_id/metrics";
	$result = getData($ua,$token,$apiUrl);

	my $metric_id=0;
	foreach my $row (@{$result->{_embedded}->{items}}) {
		$metric_id = $row->{id};
		verb("Metric : ".$metric_id);
	}

	#collect group metric from service A from company
	#$apiUrl = $url ."/bigdata/groups/$service_id/metrics/$metric_id/measurements?date_start=$datestringstart&date_stop=$datestringstop&page=1&limit=1&sort=-timestamp";
	verb("Get Neasurement last with metric ".$metric_id);
	$apiUrl = $url ."/bigdata/groups/$service_id/metrics/$metric_id/measurements/last";
	$result = getData($ua,$token,$apiUrl);


# Format of /measurement/last
#{
#    "uuid": "07051690-140d-439b-b7af-41105823f530",
#    "bucket": "2024-03-01T00:00:00+0000",
#    "timestamp": 1710844143,
#    "metric_name": "load_1_min",
#    "critical_threshold": "2",
#    "max": null,
#    "metric_unit": null,
#    "min": null,
#    "value": 0.17,
#    "warning_threshold": "1"
#}


#TODO GET the data from measurements/last

	my $result_id=0;
	$result_id = abs($result->{value});
	verb("Value : ".$result_id);
	return $result_id;
}

$SIG{__DIE__} = sub  {
    my $msg;
    if($_[0] =~ /Can't locate LWP\/Authen\/Bearer.pm/){
       $msg = "Unable to connect, please check your credentials";
    } else{
        $msg = "Unable to get the information: @_";
        $msg =~ s/ at \S+ line \d+.//;
    }
    print $msg;
    exit $ERRORS{'UNKNOWN'};
};

# New Coservit Plug
my $np = Coservit::PluginBox->new(
    {
        name => $PROGNAME,
        version => $VERSION,
        copyright => "Copyright (c) Coservit 2020",
        subject => "Collect value from ",
        description => "Collects ."
    }
);

$np->addOption({
        key			=> 'url',
        label		=> 'Target platform URL',
        type		=> 26,
        context		=> 19,
        description => 'URL of the ServiceNav source platform hosting the dashboard',
        required	=> 1,
        sample		=> 'https://servicenav.io'
    });

$np->addOption({
        key			=> 'login',
        label		=> 'login',
        type		=> 2,
        context		=> 19, #HTTP
        description => 'Login to connect to the ServiceNav platform hosting the dashboard',
        required	=> 1,
        sample		=> 'login'
    });

$np->addOption({
        key			=> 'password',
        label		=> 'password',
        type		=> 3,
        context		=> 19, #HTTP
        description => 'Password to connect to the ServiceNav platform hosting the dashboard',
        required	=> 1,
        sample		=> 'password'
    });

$np->addOption({
        key			=> 'company',
        label		=> 'Company name',
        type		=> 1,
        description => 'Name of host/Equipment company.',
        required	=> 1,
        sample		=> 'evobserve'
    });

$np->addOption({
        key			=> 'hostA',
        label		=> 'First Host',
        type		=> 1,
        description => 'Name of the first host/equipment to collect.',
        required	=> 1,
        sample		=> 'epdpm22120001'
    });

$np->addOption({
        key			=> 'serviceA',
        label		=> 'First Service',
        type		=> 1,
        description => 'Name of the first service to collect.',
        required	=> 1,
        sample		=> 'Service A to collect'
    });

$np->addOption({
        key			=> 'hostB',
        label		=> 'Second Host',
        type		=> 1,
        description => 'Name of the Second host/equipment to collect.',
        required	=> 1,
        sample		=> 'epdpm22120002'
    });

$np->addOption({
        key			=> 'serviceB',
        label		=> 'Second Service',
        type		=> 1,
        description => 'Name of the Second service to collect.',
        required	=> 1,
        sample		=> 'Service B to collect'
    });
	
$np->addOption({
        key			=> 'services',
        label		=> 'Liste des services',
        type		=> 1,
        description => 'Liste des services utilisés dans le calcul.
                        Séparés par des points-virgules.',
        required	=> 1,
        sample		=> 'service1;service2;service3'
    });

$np->addOption({
        key             => 'calc',
        label           => 'Formule de calcul',
        type            => 1,
        description 	=> 'Formule du calcul à appliquer sur les Service.
                        Utilisation de variables SRV1,SRV2 définies dans l\'ordre de déclaration des Services dans l\'option --serviceA et --serviceB
                        Prise en compte des parenthèses pour priorités de calcul.
                        Possibilité de faire 2 calculs en les séparant par un \' | \'.
                        Dans ce cas, le résultat du 1er calcul sera stocké dans une variable RES1 et sera la seule valeur comparée aux seuils.
                        Le résultat du 2ème calcul sera stocké dans une variable RES2 que l\'on pourra afficher dans la sortie du plugin.',
        required        => 1,
        sample          => '(SRV1+SRV2)/2|SRV1+SRV2'
    });

$np->addOption({
        key             => 'w',
        label           => 'Seuil d\'alerte',
        type            => 22,
        description => 'Seuil d\'alerte dans l\'unité choisie dans l\'option -F. Seule la valeur RES1 est comparée à ce seuil.',
        required        => 1,
        sample          => '80'
    });

$np->addOption({
        key             => 'c',
        label           => 'Seuil Critique',
        type            => 22,
        description => 'Seuil critique dans l\'unité choisie dans l\'option -F. Seule la valeur RES1 est comparée à ce seuil.',
        required        => 1,
        sample          => '90'
    });


$np->addOption({
        key             => 'F',
        label           => 'Format de sortie et Perfdata',
        type            => 1,
        description => 'Adaptation de la sortie texte du plugin.
                        Sortie prédéfinie : F1 : RES1 F2 (RES2 F3) > #seuil_warning# où F1,F2,F3 sont les 3 paramètres modifiables ici.
                        On mettra dans :
                        F1, le nom de la valeur mesurée.
                        F2, l\'unité du résultat du premier calcul
                        F3, l\'unité du résultat du deuxième calcul si il y en a un.',
        required        => 1,
        sample          => 'Used Space;%;GB|used_prct;used_GB'
    });

$np->addOption({
        key             => 'default',
        label           => 'Valeur par défaut',
        type            => 11,
        description => 'Valeur retournée par le plugin si un des calculs implique une division par zéro. Si ce paramètre n\'est pas renseigné, la division par zéro ne sera pas possible et lèvera un erreur.',
        required        => 0,
        sample          => '100'
    });



###############################################################################
################       Vérification des options     ###########################
###############################################################################

$np->checkOptions();

my $url         = $np->getValue('url');
my $login       = $np->getValue('login');
my $password    = $np->getValue('password');
my $company		= $np->getValue('company');
my $hostA		= $np->getValue('hostA');
my $serviceA	= $np->getValue('serviceA');
my $hostB		= $np->getValue('hostB');
my $serviceB	= $np->getValue('serviceB');
my $o_services	= $np->getValue('services');
my $o_calc 		= $np->getValue('calc');
my $warningThreshold = $np->getValue('w');
my $criticalThreshold = $np->getValue('c');
my $o_format 	= $np->getValue('F');
my $o_default 	= $np->getValue('default');


if ($url !~ /^https?:\/\/(?:[A-Z0-9_-](?:[A-Z0-9_-]{0,62}[A-Z0-9_-])?\.){1,8}[A-Z0-9_-]{1,63}\/?$/i ){
    print "URL Incorrect - Please check";
    exit $ERRORS{'UNKNOWN'};
}

if($o_services eq 'NotUsed'){
	$o_services = $serviceA .';'.$serviceB;
}

my @arrayCalc = split('\|', $o_calc);

#if(scalar(@arrayCalc) > 2){
#    print 'Too many calculation operations';
#    exit $ERRORS{'UNKNOWN'};
#}

my ($output_format,$perfdata_format) = split('\|',$o_format);
my @params_format;

if(defined $output_format && $output_format ne ''){

    @params_format = split(';',$output_format);
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

my $ua = LWP::UserAgent->new;
$ua->ssl_opts(verify_hostname => 0);

my $token;
my $result;

my $criticCounter = 0;
my $warningCounter = 0;
my $unknownCounter = 0;

my $totalHits = 0;
my @hits = ();
my $nbOfCall = 0;
my $state = 0;
my $nameOfSoc = "";
my $company_id = 0;
my $filter = "";
my $text = "";
my @companies = ();


$token = getToken($ua,$url,$login,$password);

# Collect company ID
#my $apiUrl = $url . "/servicenav/fr_FR/companies/list?name=$company";
#$result = getData($ua,$token,$apiUrl);
#
#foreach my $row (@{$result->{_embedded}->{items}}) {
#	$companies[0] = $row->{id};
#}

my @arrayServices = split(';',$o_services);
my @arrayResultatOID;
my $j = 1;
foreach my $service ( @arrayServices ) {
	my $resultatA = collectValue($ua,$token,$url,$hostA,$service);
	if (!defined($resultatA) ||$resultatA eq ''  ) {
		print "$serviceA: not found or empty serviceA";
		exit $ERRORS{'UNKNOWN'};

	} elsif (! ($resultatA =~ /^-?\d+\.?\d*$/)){
		print "$resultatA: not an integer, operation not possible.";
		exit $ERRORS{'UNKNOWN'};

	} else {
		verb("SRV$j=$resultatA");$j++;
		push @arrayResultatOID, $resultatA;
	}
}

my @resCalculation;
my $tempResult;
foreach my $calcOp (@arrayCalc)
{
    for(my $i=0;$i < scalar(@arrayResultatOID);$i++)
    {
        my $oid_to_replace = "SRV" . ($i+1);
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
    my @arrayPerfData = split(';',$perfdata_format);

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
    $output .= '|'.$perfData;
}

print $output;
exit $status;

