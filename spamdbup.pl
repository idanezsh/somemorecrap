#!/usr/bin/perl
# PineApp Spam DB update.
# 
# Copyright (c) 2002-2007, PineApp Ltd. <http://www.PineApp.com>
#
# $Id: spamdbup.pl,v 1.21 2014/04/24 12:08:31 andrey Exp $


use POSIX;

require "/usr/local/pineapp/loadbrands.pl";

$looping=0;
$updateflag=0;

sub reportadmin {
    $notif_from=$OEM_NOTIFY_ADDR.'@'.$ddomain;
    open(SM,"|$qmailinject -h -f '$notif_from'");
    print SM "From: \"".$OEM_COMPANY_NAME." ".$OEM_PRODUCT_NAME." SpamDB automated update.\" <$admin>\n";
    print SM "To: $admin\n";
    print SM "Subject: SpamDB automatic update notification (S/N: $PA_SN, Hostname: $hostname).\n";
    print SM "X-PineApp-Mail-Rcpt-To: $admin\n";
    print SM "Content-type: text/plain\n";
    print SM "
  
Attention Administrator!
  
".$OEM_COMPANY_NAME." ".$OEM_PRODUCT_NAME." has failed downloading new SpamDB signatures.
  
Old database is kept untouched.:\n\n";
}

sub reportlicexp {
    $notif_from=$OEM_NOTIFY_ADDR.'@'.$ddomain;
    open(SM,"|$qmailinject -h -f '$notif_from'");
    print SM "From: \"".$OEM_COMPANY_NAME." ".$OEM_PRODUCT_NAME." SpamDB automated update.\" <$admin>\n";
    print SM "To: $admin\n";
    print SM "Subject: SpamDB automatic update notification (S/N: $PA_SN, Hostname: $hostname).\n";
    print SM "X-PineApp-Mail-Rcpt-To: $admin\n";
    print SM "Content-type: text/plain\n";
    print SM "
  
Attention Administrator!
  
".$OEM_COMPANY_NAME." ".$OEM_PRODUCT_NAME." cannot download SpamDB updates, 
Your license for this product has expired.

Please contact your reseller A.S.A.P.

Or refer to ".$OEM_COMPANY_NAME." site: ".$OEM_COMPANY_URL."\n\n";  
    close (SM);
}

sub update {
    system ("/usr/bin/logger -p local1.notice -t spamdbup.pl Opening encrypted update.");
    system ("rm -rf /var/data/queue/tmp/saUpdate.dir 2>/dev/null");
    mkdir ("/var/data/queue/tmp/saUpdate.dir",755);
    chdir ("/var/data/queue/tmp/saUpdate.dir");
    system("unzip -P Mail-SeCure1038383Update3923810148281Key32848101Encrypted9188281 /tmp/spamdb-update.pineapp 1>/dev/null 2>/dev/null");
    if ( ! -e "/var/data/queue/tmp/saUpdate.dir/update" ) { 
	system ("/usr/bin/logger -p local1.notice -t spamdbup.pl Update failed.");
	print "Update Failed: Stage 1\n";
	exit;
    }

    system("mcrypt -d -k LaChacha 283fsdfkj 238sjaljk 238uadsjh 3u90adkaj d83ue0udakj 89u120ajsdkl Clave -a rc2 update 1>/dev/null 2>/dev/null");
  
    if ( ! -e "/var/data/queue/tmp/saUpdate.dir/update.dc" ) { 
	print "Update Failed: Stage 2 !\n";
	system ("/usr/bin/logger -p local1.notice -t spamdbup.pl Update failed.");
	exit;
    }
  
    system("tar xfz update.dc 2>/dev/null");
  
    if ( ! -f "spamdbupdate.tar.bz2" ) { 
	print "Update Failed: Stage 3 !\n";
	system ("/usr/bin/logger -p local1.notice -t spamdbup.pl Update failed.");
	exit;
    }
    system ("/usr/bin/logger -p local1.notice -t spamdbup.pl Installing update.");
    system ("tar xfj spamdbupdate.tar.bz2 -C /");
 
    chdir ("/tmp");

    system ("rm -rf /var/data/queue/tmp/saUpdate.dir 2>/dev/null");
    system ("/usr/bin/logger -p local1.notice -t spamdbup.pl Update completed.");
    system ("/bin/cat /tmp/spamdb-update.version > /etc/mail/spamassassin/version");
    $spamdrestart=1;
}

print "\n";
print "PineApp SeCure SpamDB update engine.\n";
print "\nCopyright (c) 2003-2008, PineApp Ltd. <http://www.PineApp.com>\n";
print "$Revision: 1.21 $, $Date: 2014/04/24 12:08:31 $.\n\n";

if ( -f "/var/qmail-sys/bin/qmail-inject" ) { 
    $qmailinject="/var/qmail-sys/bin/qmail-inject";
} else {
    $qmailinject="/var/qmail/bin/qmail-inject";
}

if (defined ($ARGV[0]) && $ARGV[0] eq "-v") {
    print "usage: spamdbup.pl\n\n";
    exit;
}

system ("/usr/bin/logger -p local1.notice -t spamdbup.pl Initiating SpamDB Auto-update.");

@rcqmail=split("\n",`cat /etc/rc.pineapp/rc.qmail`);

foreach $rcqmail (@rcqmail) {
    @line=split("=",$rcqmail);
    if ($line[0] eq "DEFAULT_DOMAIN") {
	$ddomain=$line[1];
	$ddomain=~ s/\=//g;
	$ddomain=~ s/\"//g;
    }
}

@rcsystem=split("\n",`cat /etc/rc.pineapp/rc.system`);

foreach $rcsys (@rcsystem) {
    @line=split("=",$rcsys);
    if ($line[0] eq "HOSTNAME") {
	$hostname=$line[1];
	$hostname=~ s/\=//g;
	$hostname=~ s/\"//g;
    }
}

$PA_SN=`cat /usr/local/etc/PA_SN`;
chomp($PA_SN);
$HTTP_PROXY_PORT=`cat /etc/rc.pineapp/rc.system | grep ^HTTP_PROXY_PORT | cut -d\\\" -f2`;
chomp ($HTTP_PROXY_PORT);
$HTTP_PROXY_SERVER=`cat /etc/rc.pineapp/rc.system | grep ^HTTP_PROXY_SERVER | cut -d\\\" -f2`;
chomp ($HTTP_PROXY_SERVER);
$HTTP_PROXY_AUTH_USER=`cat /etc/rc.pineapp/rc.system | grep ^HTTP_PROXY_AUTH_USER | cut -d\\\" -f2`;
chomp ($HTTP_PROXY_AUTH_USER);
$HTTP_PROXY_AUTH_PW=`cat /etc/rc.pineapp/rc.system | grep ^HTTP_PROXY_AUTH_PW | cut -d\\\" -f2`;
chomp ($HTTP_PROXY_AUTH_PW);
if (($HTTP_PROXY_SERVER !="") &&  ($HTTP_PROXY_PORT != "")) {
	$HTTPEXPORT="http_proxy=http://$HTTP_PROXY_SERVER:$HTTP_PROXY_PORT ";
	if (($HTTP_PROXY_AUTH_USER ne "") && ($HTTP_PROXY_AUTH_PW ne "")) {
		$HTTP_PROXY_AUTH_STR="--proxy-user=".$HTTP_PROXY_AUTH_USER." --proxy-passwd=".$HTTP_PROXY_AUTH_PW;
	}
	else {
		$HTTP_PROXY_AUTH_STR="";
	}
	print $HTTPEXPORT."\n";
} else {
	$HTTPEXPORT="";
}

$mac=`/sbin/ifconfig -a | /bin/grep eth0 | /bin/sed 's/.*HWaddr //'`;
chomp($mac);

$spamdrestart=0;

do {
    system ("rm -f /tmp/spamdb-update.*");

    $version=`cat /etc/mail/spamassassin/version`;
    chomp($version);
 
    @rcadmin=split("\n",`cat /etc/rc.pineapp/rc.scanner`);

    foreach $rcadm (@rcadmin) {
	@line=split("=",$rcadm);
	if ($line[0] eq "SCANNER_ADMIN") {
	    $admin=$line[1];
	    $admin=~ s/\=//g;
	    $admin=~ s/\"//g;
	}
    }

    if ( -f "/usr/local/etc/PA_EXPUP" ) {
	&reportlicexp;
	print "License expired\n\n";
	exit;
    }

    system ("/usr/bin/logger -p local1.notice -t spamdbup.pl Downloading control file.");

    if( $HTTPEXPORT ne ""){
    	system ("$HTTPEXPORT /usr/bin/wget $HTTP_PROXY_AUTH_STR -T 60 \"".$OEM_UPDATES_URL."/updates/spamdbupdate.html?ver=SPAMDBv2&domain=$ddomain&mac=$mac&rev=$version&type=md5\" -O /tmp/spamdb-update.md5 -proxy=on");
    } else {
    	system ("/usr/bin/wget -T 60 \'http://".$OEM_UPDATES_URL."/updates/spamdbupdate.html?ver=SPAMDBv2&domain=$ddomain&mac=$mac&rev=$version&type=md5\' -O /tmp/spamdb-update.md5");
    }

    $updatemd5=`cat /tmp/spamdb-update.md5`;

    chomp($updatemd5);

    @upmd5=split(" ",$updatemd5);

    if (!$updatemd5) {
	system ("/usr/bin/logger -p local1.notice -t spamdbup.pl You are running with the latest update.");
	print "You are running with the latest update...\n";
	if ($spamdrestart) {
	    system ("/usr/local/pineapp/PineAppConfig spamdb 1>/dev/null 2>/dev/null");
	    system ("/usr/local/pineapp/spam_fields.php 1>/dev/null 2>/dev/null");
	    system ("/bin/killall -HUP spamd");
	}
	exit;
    }
    system ("/usr/bin/logger -p local1.notice -t spamdbup.pl Downloading SpamDB update...");

    if( $HTTPEXPORT ne ""){
   	 system ("$HTTPEXPORT /usr/bin/wget $HTTP_PROXY_AUTH_STR -T 60 \"".$OEM_UPDATES_URL."/updates/spamdbupdate.html?ver=SPAMDBv2&domain=$ddomain&mac=$mac&rev=$version&type=version\" -O /tmp/spamdb-update.version -proxy=on");
   	 system ("$HTTPEXPORT /usr/bin/wget $HTTP_PROXY_AUTH_STR -T 60 \"".$OEM_UPDATES_URL."/updates/spamdbupdate.html?ver=SPAMDBv2&domain=$ddomain&mac=$mac&rev=$version&type=pineapp\" -O /tmp/spamdb-update.pineapp -proxy=on");
    } else {
   	 system ("/usr/bin/wget -T 60 \'http://".$OEM_UPDATES_URL."/updates/spamdbupdate.html?ver=SPAMDBv2&domain=$ddomain&mac=$mac&rev=$version&type=version\' -O /tmp/spamdb-update.version");
   	 system ("/usr/bin/wget -T 60 \'http://".$OEM_UPDATES_URL."/updates/spamdbupdate.html?ver=SPAMDBv2&domain=$ddomain&mac=$mac&rev=$version&type=pineapp\' -O /tmp/spamdb-update.pineapp");
    }
    $newmd5=`md5sum /tmp/spamdb-update.pineapp`;

    chomp($newmd5);

    @nwmd5=split (" ",$newmd5);

    if ($nwmd5[0] eq $upmd5[0]) {
	system ("/usr/bin/logger -p local1.notice -t spamdbup.pl Downloaded file is OK, updating...");
	print "MD5 check successfull, updating...\n";
	$updateflag=0;
	&update;
    } else {
	system ("/usr/bin/logger -p local1.notice -t spamdbup.pl Download failed!");
	print "Download failed...\n";
	$looping=51;
	$updateflag=1;
    }
    $looping++;
} until (!$updatemd5 || $looping>50);

if ($spamdrestart) {
    system ("/usr/local/pineapp/PineAppConfig spamdb 1>/dev/null 2>/dev/null");
    system ("/usr/local/pineapp/spam_fields.php 1>/dev/null 2>/dev/null");
    system ("/bin/killall -HUP spamd");
}

if ($updateflag) {
    &reportadmin;
}

system ("/usr/bin/logger -p local1.notice -t spamdbup.pl SpamDB automatic update terminated.");
