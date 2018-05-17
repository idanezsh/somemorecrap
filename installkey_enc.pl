#!/usr/bin/perl
# PineApp Mail-SeCure version 3.60 Licensing.
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
if (substr($givenkey,0,3) != 'ENC')
{
 print "\nWrong Encription Key!\n\n";
 exit 1;
}

@keyp=split("-",substr($givenkey,3));

$recon_randomprod=sqrt(sqrt(&demybase($keyp[2])));
$recon_mac=&fillrmac(&hexbase(int(&demybase($keyp[0])-(($recon_randomprod**4)*6))));
$recon_mac.=":".&fillmac(&hexbase(int(&demybase($keyp[1])-(($recon_randomprod**4)*6))));
$recon_prodsn=&demybase($keyp[3])/$recon_randomprod;
$recon_randomdays=&demybase($keyp[4])/1795;
$recon_expdays=&demybase($keyp[5])/$recon_randomdays;


if (($recon_randomprod != int($recon_randomprod)) || ($recon_prodsn != int($recon_prodsn)) ||
    ($recon_randomdays != int($recon_randomdays)) || ($recon_expdays != int($recon_expdays)) )
{
 print "\nWrong Key!\n\n";
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
    if (($HTTP_PROXY_SERVER !="") &&  ($HTTP_PROXY_PORT != "")) {
	if (($HTTP_PROXY_AUTH_USER ne "") && ($HTTP_PROXY_AUTH_PW ne "")) {
	    $HTTPEXPORT="export http_proxy=\"http://$HTTP_PROXY_AUTH_USER:$HTTP_PROXY_AUTH_PW@$HTTP_PROXY_SERVER:$HTTP_PROXY_PORT/\" ; ";
	} else {
	    $HTTPEXPORT="export http_proxy=\"http://$HTTP_PROXY_SERVER:$HTTP_PROXY_PORT/\" ; ";
	}
	print $HTTPEXPORT."\n";
    } else {
	$HTTPEXPORT="";
    }

    $reqkey=`/usr/local/pineapp/reqkey.pl`;
    chop($reqkey);
    #print " $HTTPEXPORT lynx \"http://$OEM_UPDATES_URL/reg/paregenc.html?key=$givenkey&reqkey=$reqkey\" --dump 2>&1 | grep \"RETURN\" ";
    $retkey=`$HTTPEXPORT lynx "http://$OEM_UPDATES_URL/reg/paregenc.html?key=$givenkey&reqkey=$reqkey" --dump 2>&1 | grep "RETURN" | awk -F\\" '{print \$2}'`;
    #$retkey=`$HTTPEXPORT lynx "http://$OEM_UPDATES_URL/reg/paregenc.html?key=$givenkey&reqkey=$reqkey" --dump 2>&1 | grep "RETURN" | cut -d\\:   -f2 `;
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

@keyr=split("-",substr($retkey,3));

@keyp=split("-",substr($retkey,3));

$ret_randomprod=sqrt(sqrt(&demybase($keyp[2])));
$ret_prodsn=&demybase($keyp[3])/$ret_randomprod;
$ret_randomdays=&demybase($keyp[4])/1792;
 my $ret_expdays=&demybase($keyp[5])/$ret_randomdays;



if ( ($recon_prodsn != $ret_prodsn) || ($recon_expdays != $ret_expdays) ) {
	print "Wrong Key - no match!";
	exit 1;
}

print "Key installed";

use DBI;
use strict;

my $dbh;
my $sth;
my @vetor;
my $field;


$dbh = DBI->connect('DBI:Pg:dbname=secure', 'postgres', '');
if ($dbh) {
	   #print "\nconnected\n";
   }else {
	   #print "ERROR\n";
	   }
$sth = $dbh->prepare("INSERT INTO config.encryption_license(license_key, tickets) VALUES ( '$ARGV[0]', $ret_expdays);");
   if($sth->execute)
   {
      $sth = $dbh->prepare("UPDATE config.encryption_tickets_remain SET tickets=tickets+$ret_expdays");
      $sth->execute;
   }




