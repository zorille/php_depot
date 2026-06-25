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
my $VERSION = '1.0.0';

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

	verb("getData URL : ".$subUrl);
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
	my ($ua ,$token,$url,$company,$host,$service,$position) = @_ or die "Missing (User Agent,Token or Url) Parametre please check  ";
	my $result;
	
	#collect service A from company
	my $url_service = $service;
	$url_service =~ s/ /+/g;
    $url_service =~ s/([^A-Za-z0-9\+-])/sprintf("%%%02X", ord($1))/seg;
	verb("company : ".$company);
	verb("host : ".$host);
	verb("service : ".$service);
	#my $apiUrl = $url ."/servicenav/fr_FR/companies/".$company."/services?comptesub=false&host_name=".$host."&service_name=".$url_service;
	my $apiUrl = $url ."/servicenav/fr_FR/companies/".$company."/services?comptesub=false&service_name=".$url_service;
	$result = getData($ua,$token,$apiUrl);

	my $service_id=0;
	foreach my $row (@{$result}) {
		verb("Service : ".$service);
        verb("Service check : ".$row->{name});
		if($row->{name} eq $service) {
			$service_id = "service_".$row->{id};
			verb("Service : ".$service_id);
		}
	}

	#collect group metric from service A from company
	$apiUrl = $url ."/bigdata/groups/$service_id/metrics";
	$result = getData($ua,$token,$apiUrl);

	my $metric_id=0;
	foreach my $row (@{$result->{_embedded}->{items}}) {
		$metric_id = $row->{id};
		verb("Metric : ".$metric_id);
	}
	
	my $datestringstart = "";
	my $datestringstop = "";
	my $sort = "%2Btimestamp";
	
	
	if( $position eq "debut" ) {
		#On recupere la moyenne entre -2 heure et -1 heure
		my $t_start = DateTime->now(time_zone => "local")->subtract(hours => 24);
		$datestringstart = $t_start->strftime("%F %H:%M:%S");
		my $t_end = DateTime->now(time_zone => "local")->subtract(hours => 12);
		$datestringstop = $t_end->strftime("%F %H:%M:%S");
	} else {
		#On recupere la moyenne entre -0 heure et maintenant
		my $t_start = DateTime->now(time_zone => "local")->subtract(hours => 12);
		$datestringstart = $t_start->strftime("%F %H:%M:%S");
		$datestringstop = strftime "%F %H:%M:%S", localtime;
	}
	verb("date and time - $datestringstart\n");
	verb("date and time - $datestringstop\n");

	#collect group metric from service A from company
	$apiUrl = $url ."/bigdata/groups/$service_id/metrics/$metric_id/measurements?date_start=$datestringstart&date_stop=$datestringstop&page=1&limit=10&sort=$sort";
	$result = getData($ua,$token,$apiUrl);

	my $size = @{$result};
	verb("getData Value : Result found ". $size);
	my $sum = 0;
	foreach my $row (@{$result}) {
		$sum += $row->{value};
		verb("Value : ".$row->{value}." => ".$sum);
		#last;
	}
	my $value = $sum / $size;
	my $result_id = sprintf("%.0f", $value);

	verb("Result : ".$result_id);
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
        key			=> 'host',
        label		=> 'First Host',
        type		=> 1,
        description => 'Name of the first host/equipment to collect.',
        required	=> 1,
        sample		=> 'epdpm22120001'
    });
	
$np->addOption({
        key			=> 'service',
        label		=> 'First Service',
        type		=> 1,
        description => 'Name of the first service to collect.',
        required	=> 1,
        sample		=> 'Service A to collect'
    });
	
$np->addOption({
        key             => 'seuilVariation',
        label           => 'Seuil de variation max en litre',
        type            => 22,
        description => 'Definit le seuil max de variation avant de considerer qu\'il y eu une consommation.',
        required        => 0,
        sample          => '80'
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



###############################################################################
################       Vérification des options     ###########################
###############################################################################

$np->checkOptions();

my $url         = $np->getValue('url');
my $login       = $np->getValue('login');
my $password    = $np->getValue('password');
my $company		= $np->getValue('company');
my $host		= $np->getValue('host');
my $service		= $np->getValue('service');
my $seuil		= $np->getValue('seuilVariation');
my $warningThreshold = $np->getValue('w');
my $criticalThreshold = $np->getValue('c');
my $o_format 	= $np->getValue('F');


if ($url !~ /^https?:\/\/(?:[A-Z0-9_-](?:[A-Z0-9_-]{0,62}[A-Z0-9_-])?\.){1,8}[A-Z0-9_-]{1,63}\/?$/i ){
    print "URL Incorrect - Please check";
    exit $ERRORS{'UNKNOWN'};
}

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
my $companies = "";


$token = getToken($ua,$url,$login,$password);

# Collect company ID
my $apiUrl = $url . "/servicenav/fr_FR/companies/list?name=$company";
$result = getData($ua,$token,$apiUrl);

foreach my $row (@{$result->{_embedded}->{items}}) {
	$companies = $row->{id};
}

my $position = "debut";
my $resultatA = collectValue($ua,$token,$url,$companies,$host,$service,$position);
if (!defined($resultatA) ||$resultatA eq ''  ) {
	print "$service: not found or empty service";
	exit $ERRORS{'UNKNOWN'};

} elsif (! ($resultatA =~ /^-?\d+\.?\d*$/)){
	print "$resultatA: not an integer, operation not possible.";
	exit $ERRORS{'UNKNOWN'};

}
$position="fin";
my $resultatB = collectValue($ua,$token,$url,$companies,$host,$service,$position);
if (!defined($resultatB) ||$resultatB eq ''  ) {
	print "$service: not found or empty service";
	exit $ERRORS{'UNKNOWN'};

} elsif (! ($resultatB =~ /^-?\d+\.?\d*$/)){
	print "$resultatB: not an integer, operation not possible.";
	exit $ERRORS{'UNKNOWN'};

} 

my $resCalculation=$resultatB-$resultatA;
if ( $resCalculation > $seuil ) {
	$resCalculation = 0;
} else {
	$resCalculation = abs($resCalculation);
}
my $status = $np->getStatus($resCalculation,$warningThreshold,$criticalThreshold);
my $output = sprintf("%s: %.2f %s",$params_format[0],$resCalculation,$params_format[1]);

if($status == $ERRORS{'CRITICAL'}){
    $output .= ': CRITICAL';

}elsif($status == $ERRORS{'WARNING'}){
    $output .= ': WARNING';

}else{
    $output .= ': OK';

}

my $perfData = '';
if(defined $perfdata_format && $perfdata_format ne '') {
    $perfData .= "'$params_format[0]'=".sprintf("%.2f%s",$resCalculation,$perfdata_format) . $np->getThresholdPerfString($warningThreshold,$criticalThreshold);
    $output .= '|'.$perfData;
}

print $output;
exit $status;
