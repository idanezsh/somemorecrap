#!/usr/bin/perl
# PineApp Mail-SeCure version 3.70 Licensing.
# Generate licensing request key.
# 
#
# 20021026 - Initial version of keyonfo.pl.
# 20030402 - Modified keyinfo.pl to this script for licensing validation & installation.
# 20030403 - use ifconfig -a to show all adapters.
# 20030406 - Transmit request key as well.
#          - Start Spam & Web-Access modules if license was renewed and required.
# 20030825 - Added models: U & W
# 20071022 - Removed: U,W,M; Added L,B,P
# 20071125 - Re-added W and shifted L. + V2 Keys support
# 20100505 - Added J and K
#
# Range: 0 - 1155 (Base 34)
#           0 0 0 0 0 0 0 0 0 0 1 1 1 1 1 1 1 1 1 1 2 2 2 2 2 2 2 2 2 2 3 3 3 3
#           0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3
@keys = qw (Q R 4 T X A J 8 K 3 Z N L U S 1 E G 2 Y P I 9 F D 6 H 7 B M V 5 C W);
@hexs = qw (0 1 2 3 4 5 6 7 8 9 A B C D E F);
%nums = (Q=>0,R=>1,4=>2,T=>3,X=>4,A=>5,J=>6,8=>7,K=>8,3=>9,
         Z=>10,N=>11,L=>12,U=>13,S=>14,1=>15,E=>16,G=>17,2=>18,Y=>19,
         P=>20,I=>21,9=>22,F=>23,D=>24,6=>25,H=>26,7=>27,B=>28,M=>29,
         V=>30,5=>31,C=>32,W=>33);
%rnum = (K=>0,3=>1,Z=>2,E=>3,G=>4,2=>5,Y=>6,N=>7,L=>8,Q=>9,
         R=>10,J=>11,U=>12,S=>13,1=>14,H=>15,7=>16,B=>17,M=>18,8=>19,
         T=>20,X=>21,A=>22,P=>23,I=>24,9=>25,F=>26,D=>27,6=>28,V=>29,
         5=>30,C=>31,W=>32,4=>33);

require "/usr/local/pineapp/loadbrands.pl";

$base=$#keys+1;

# decode my base.

sub demybase {
    $key=shift;
    $value=0;
    for $i (1..length($key)) {
	$value+=$nums{substr($key,length($key)-$i,1)}*(34**($i-1));
    }
    return $value;
}

# decode return my base.

sub rdemybase {
    $key=shift;
    $value=0;
    for $i (1..length($key)) {
	$value+=$rnum{substr($key,length($key)-$i,1)}*(34**($i-1));
    }
    return $value;
}


# convert to my hex.

sub hexbase {
    $sn='';
    $number=shift;
    while ($number>-1) {
	$sn=$hexs[$number % 16].$sn;
	if ($number<16) {
	    $number=-1;
	} else {
	    $number=int($number/16);
	}
    }
    return $sn;
}

# Fill string to 6 characters.

sub fillmac {
    $sn=shift;
    if (length($sn)<6) {
	for $i (1..6-length($sn)) {
	    $sn=$hexs[0].$sn;
	}
    }
    $macpart=substr($sn,0,2).":".substr($sn,2,2).":".substr($sn,4,2);
    return $macpart;
}

# Fill reverse mac string to 6 characters.

sub fillrmac {
    $sn=shift;
    if (length($sn)<6) {
	for $i (1..6-length($sn)) {
	    $sn=$hexs[0].$sn;
	}
    }
    $macpart=substr($sn,4,2).":".substr($sn,2,2).":".substr($sn,0,2);
    return $macpart;
}

$givenkey=$ARGV[0];
$retkey=$ARGV[1];

@keyp=split("-",$givenkey);

$recon_randomprod=sqrt(sqrt(&demybase($keyp[2])));
$recon_mac=&fillrmac(&hexbase(int(&demybase($keyp[0])-(($recon_randomprod**4)*6))));
$recon_mac.=":".&fillmac(&hexbase(int(&demybase($keyp[1])-(($recon_randomprod**4)*6))));
$recon_prodsn=&demybase($keyp[3])/$recon_randomprod;
$recon_randomdays=&demybase($keyp[4])/1795;
$recon_expdays=&demybase($keyp[5])/$recon_randomdays;
$recon_model=&demybase($keyp[6])/$recon_randomprod;
if (length($recon_model) eq 8) {
    $recon_modelnum=int($recon_model/10000);
    $recon_modelflags=$recon_model % 10000;
}
if (length($recon_model) eq 7) {
    $recon_modelnum=int($recon_model/1000);
    $recon_modelflags=$recon_model % 1000;
}
if (length($recon_model) eq 6) {
    $recon_modelnum=int($recon_model/100);
    $recon_modelflags=$recon_model % 100;
}

$modelflags="";

$modelflags=$modelflags."S" if ($recon_modelflags & 1);   # Advanced Spam
$modelflags=$modelflags."L" if ($recon_modelflags & 2);   # Lawful Interception: Sniffer module
$modelflags=$modelflags."V" if ($recon_modelflags & 4);   # VPN + Firewall - being depricated
$modelflags=$modelflags."R" if ($recon_modelflags & 8);   # Vade Retro Module 
$modelflags=$modelflags."C" if ($recon_modelflags & 16);  # Inappropriate Content Control
$modelflags=$modelflags."W" if ($recon_modelflags & 32);  # Web+URL Filtering
$modelflags=$modelflags."B" if ($recon_modelflags & 64);  # Lawful Interception: Load balancing module
$modelflags=$modelflags."P" if ($recon_modelflags & 128); # Lawful Interception: Processing module (was "M")
$modelflags=$modelflags."A" if ($recon_modelflags & 256); # Enhanced Content Filtering
$modelflags=$modelflags."J" if ($recon_modelflags & 512); # ClamAV
$modelflags=$modelflags."K" if ($recon_modelflags & 1024);# Kaspersky AV

if (($recon_randomprod != int($recon_randomprod)) || ($recon_prodsn != int($recon_prodsn)) ||
    ($recon_randomdays != int($recon_randomdays)) || ($recon_expdays != int($recon_expdays)) ||
    ($recon_model != int($recon_model))) {
	print "Wrong Key!";
	exit 1;
}

$mac=`/sbin/ifconfig -a 2>/dev/null| grep HWaddr | grep "^eth0 " | cut -d" " -f11 2>/dev/null`;
$prodsn=`cat /usr/local/etc/PA_SN 2>/dev/null`;

if ($mac != $recon_mac) {
    print "This key is not suitable for this computer (mac)";
    exit 1;
}

if ($prodsn != $recon_prodsn) {
    print "This key is not suitable for this computer (prodsn)";
    exit 1;
}

if (! $retkey) {
    $HTTP_PROXY_PORT=`cat /etc/rc.pineapp/rc.system | grep ^HTTP_PROXY_PORT | cut -d\\\" -f2`;
    chomp ($HTTP_PROXY_PORT);
    $HTTP_PROXY_SERVER=`cat /etc/rc.pineapp/rc.system | grep ^HTTP_PROXY_SERVER | cut -d\\\" -f2`;
    chomp ($HTTP_PROXY_SERVER);
    $HTTP_PROXY_AUTH_USER=`cat /etc/rc.pineapp/rc.system | grep ^HTTP_PROXY_AUTH_USER | cut -d\\\" -f2`;
    chomp ($HTTP_PROXY_AUTH_USER);
    $HTTP_PROXY_AUTH_PW=`cat /etc/rc.pineapp/rc.system | grep ^HTTP_PROXY_AUTH_PW | cut -d\\\" -f2`;
    chomp ($HTTP_PROXY_AUTH_PW);
	if (($HTTP_PROXY_SERVER ne "") && ($HTTP_PROXY_PORT ne "")) {
	if (($HTTP_PROXY_AUTH_USER ne "") && ($HTTP_PROXY_AUTH_PW ne "")) {
	    $HTTPEXPORT="export http_proxy=\"http://$HTTP_PROXY_AUTH_USER:$HTTP_PROXY_AUTH_PW\@$HTTP_PROXY_SERVER:$HTTP_PROXY_PORT/\" ; ";
	} else {
	    $HTTPEXPORT="export http_proxy=\"http://$HTTP_PROXY_SERVER:$HTTP_PROXY_PORT/\" ; ";
	}
	print $HTTPEXPORT."\n";
    } else {
	$HTTPEXPORT="";
    }

    $reqkey=`/usr/local/pineapp/reqkey.pl`;
    chop($reqkey);
    $retkey=`$HTTPEXPORT lynx "http://$OEM_UPDATES_URL/reg/pareg.html?key=$givenkey&reqkey=$reqkey" --dump 2>&1 | grep "RETURN" | awk -F\\" '{print \$2}'`;
    chop ($retkey);
}

if (`grep "$retkey" /usr/local/etc/PA_RETKEYS 2>/dev/null`) {
    print "Key was already installed on this box.";
    exit 1;
}

if ($retkey =~ /ERROR:/) {
    print "$retkey";
    exit 1;
}

# now extract return key and compare!

@keyr=split("-",$retkey);

$ret_randomprod=sqrt(sqrt(&rdemybase($keyr[2])));
$ret_mac=&fillrmac(&hexbase(int(&rdemybase($keyr[0])-(($ret_randomprod**3)*7))));
$ret_mac.=":".&fillmac(&hexbase(int(&rdemybase($keyr[1])-(($ret_randomprod**3)*7))));
$ret_prodsn=&rdemybase($keyr[3])/$ret_randomprod;
$ret_randomdays=&rdemybase($keyr[4])/1792;
$ret_expdays=&rdemybase($keyr[5])/$ret_randomdays;
$ret_model=&rdemybase($keyr[6])/$ret_randomprod;

if (length($ret_model) eq 8) {
    $ret_modelnum=int($ret_model/10000);
    $ret_modelflags=$ret_model % 10000;
}
if (length($ret_model) eq 7) {
    $ret_modelnum=int($ret_model/1000);
    $ret_modelflags=$ret_model % 1000;
}
if (length($ret_model) eq 6) {
    $ret_modelnum=int($ret_model/100);
    $ret_modelflags=$ret_model % 100;
}

$modelflags="";


$modelflags=$modelflags."S" if ($ret_modelflags & 1);
$modelflags=$modelflags."L" if ($ret_modelflags & 2);
$modelflags=$modelflags."V" if ($ret_modelflags & 4);
$modelflags=$modelflags."R" if ($ret_modelflags & 8);
$modelflags=$modelflags."C" if ($ret_modelflags & 16);
$modelflags=$modelflags."W" if ($ret_modelflags & 32);
$modelflags=$modelflags."B" if ($ret_modelflags & 64);
$modelflags=$modelflags."P" if ($ret_modelflags & 128);
$modelflags=$modelflags."A" if ($ret_modelflags & 256);
$modelflags=$modelflags."J" if ($ret_modelflags & 512);
$modelflags=$modelflags."K" if ($ret_modelflags & 1024);

if (($ret_randomprod != int($ret_randomprod)) || ($ret_prodsn != int($ret_prodsn)) ||
    ($ret_randomdays != int($ret_randomdays)) || ($ret_expdays != int($ret_expdays)) ||
    ($ret_model != int($ret_model))) {
	print "Wrong Key!";
	exit 1;
}

if (($recon_mac != $ret_mac) || ($recon_prodsn != $ret_prodsn) || ($recon_expdays != $ret_expdays) ||
    ($recon_model != $ret_model) || ($recon_randomprod != $ret_randomprod) || ($recon_randomdays != $ret_randomdays)) {
	print "Wrong Key - no match!";
	exit 1;
}

print "Key installed";

$PA_EXPIRE=`cat /usr/local/etc/PA_EXPIRE 2>/dev/null || echo 0`;

if ($PA_EXPIRE <= 0) {
    $PA_EXPIRE=$ret_expdays;
} else {
    $PA_EXPIRE=$PA_EXPIRE+$ret_expdays;
}

system ("echo $PA_EXPIRE > /usr/local/etc/PA_EXPIRE");
system ("/usr/local/pineapp/fetchlicense.php installkey");
system ("rm -f /usr/local/etc/PA_EXPIRED 2>/dev/null");
system ("rm -f /usr/local/etc/PA_EXPUP 2>/dev/null");
system ("date +\"\%B \%e, \%Y\" > /usr/local/etc/PA_DATE");
system ("echo $givenkey > /usr/local/etc/PA_KEY");
system ("chmod 755 /etc/rc.pineapp/rc.version 2>/dev/null");
system ("chown qmailq.qmail /etc/rc.pineapp/rc.version 2>/dev/null");
system ("echo $retkey >> /usr/local/etc/PA_RETKEYS");
system ("/usr/local/pineapp/mngs_check.php $ret_modelnum &");
system ("/usr/local/pineapp/PineAppConfig 1>/dev/null 2>/dev/null");
$WEBACC=`cat /etc/rc.pineapp/rc.qmail | grep "^WEBACCESS_STATUS=" | cut -d'"' -f2`;
$SPAM=`cat /etc/rc.pineapp/rc.spam | grep "^SPAM_MODULE=" | cut -d'"' -f2`;
$WEBACC_RUN=`netstat -a -n | grep LISTEN | grep :443`;

if ($SPAM=="yes") {
    system ("touch /var/run/spamd 2>/dev/null");
}

if (($WEBACC=="yes") && (!$WEBACC_RUN)) {
    system ("touch /var/run/apache.restart");
}
