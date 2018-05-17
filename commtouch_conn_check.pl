#!/usr/bin/perl
# PineApp test connection to commtouch servers
# 
# Copyright (c) 2002-2007, PineApp Ltd. <http://www.PineApp.com>
#
# $Id: commtouch_conn_check.pl,v 1.6 2014/03/20 08:23:45 andrey Exp $


use POSIX;
use MIME::Base64;
#use strict;
use LWP::UserAgent;
use HTTP::Request::Common;
use File::Find;
use Time::HiRes;
import string
require "/usr/local/pineapp/loadbrands.pl";

sub rcparm {

    my ($reqparm,$file) = @_;
    
    if ($file && $lastfile ne $file) {
	@rc=split("\n",`/bin/cat $file`);
	$lastfile=$file;
    }

    foreach $rcl (@rc)  {
	@line=split("=",$rcl);
	if ($line[0] eq $reqparm) {
	    $parm=$line[1];
	    $parm=~ s/\=//g;
	    $parm=~ s/\"//g;
	}
    }
    return $parm;
}

$warnings = "\n";
$send_mail_flag = 0;
$connectivity_alert = rcparm("COMMTOUCH_CONNECTIVITY_NOTIFY","/etc/rc.pineapp/rc.system");
print "connectivity_alert [" . $connectivity_alert ."] \n";
if (length($connectivity_alert) > 1 &&   $connectivity_alert eq "yes"){
	print "Commtouch connectivity alerts are enabled, Running tests (this may take a minute...)\n";
} else {
	print "Commtouch connectivity alerts are disabled. exiting\n";
	exit 0;
}
#****************Ctasd Check***************************
my $opt_host = "localhost";
my $ctasd_port = "8088";
my $ctipd_port = "7070";
$SPAM_ENGINE = rcparm("SPAM_ENGINE","/etc/rc.pineapp/rc.spam");
if ($SPAM_ENGINE eq "rpd") {
	my $ua = new LWP::UserAgent;
	my $request =   "X-CTCH-PVer: 0000001\r\n";
	my $classify_method = "http://$opt_host:$ctasd_port/ctasd/GetServices";
	my $ctipd_error = "";
	my $ctasd_error = "";
	my $response = $ua->request(
	                        POST $classify_method,
	                        Content => $request);
	printf "ctasd Response:\n";
	printf $response->code. "\n";
	printf $response->content. "\n";
	printf $response->status_line. "\n";
	if( $response->code != 200) {
		$send_mail_flag = 1;
		$ctasd_error = "Code: " . $response->code . "\nDetails: " . $response->content . ".\nStatus: " .$response->status_line ;
		
	}
}

#****************Ctipd Check******************************
$CT_IPREP_STATUS = rcparm("CT_IPREP","/etc/rc.pineapp/rc.spam");
if ($CT_IPREP_STATUS eq "yes") {
	$request = "x-ctch-request-id: 12345678"
			. "\r\nx-ctch-request-type: classifyip"
			. "\r\nx-ctch-pver: 1.0"
			. "\r\n\r\nx-ctch-ip: 192.168.0.1";
	$classify_method = "http://$opt_host:$ctipd_port/ctipd/iprep";
	$response = $ua->request(
	                        POST $classify_method,
	                        Content => $request);
	printf "ctipd Response:\n";
	printf $response->code. "\n";
	printf $response->content. "\n";
	printf $response->status_line. "\n";
	#split response content to lines
	@res_content = split("\n",$response->content );
	foreach $res_cont_line (@res_content)  {
		@line=split(":",$res_cont_line);
		if ($line[0] eq "x-ctch-request-status") {#look for this line and check the status code
			$parm=$line[1];
			$parm=~ s/\=//g;
			$parm=~ s/\"//g;
			if( $line[1] > 300){
				$send_mail_flag = 1;
				#create notification error
				$ctipd_error = "Code: ". $line[1] . "\nDescription:" . substr($response->content,index($response->content,"\r\n\r\n")+4)."\n"  ;
			}
		}
	}
}

#****************SendEmail Check***************************
if ( $send_mail_flag == 0) {
	print "No need to send mail exiting.\n\n " ;
	exit 0;
}

if ( -f "/var/qmail-sys/bin/qmail-inject" ) {
    $qmailinject="/var/qmail-sys/bin/qmail-inject";
} else {
    $qmailinject="/var/qmail/bin/qmail-inject";
}

$recipient = rcparm("QMAIL_ADMIN","/etc/rc.pineapp/rc.qmail");
print "Sending notification to: ". $recipient . "\n";
$ddomain=rcparm("DEFAULT_DOMAIN","/etc/rc.pineapp/rc.qmail");
$sender= "notification@" +  $ddomain;

$notif_subj = "$OEM_PRODUCT_NAME Notification, Commtouch connectivity Alert";
$notif_body = "Warning!\nSystem is unable to connect to Commtouch servers.\nPlease check outbound port 80 access, or verify your current proxy settings.\nError details:\nCtasd:\n$ctasd_error\nCtipd:\n$ctipd_error\n\nIf you keep recieving this error please contact $OEM_COMPANY_NAME Support center.\n";

$bsender=encode_base64($sender);
chomp($bsender);

$notif_body=encode_base64($notif_body);

$notif_from=$OEM_NOTIFY_ADDR.'@'.$ddomain;
open(SM,"|$qmailinject -h -f '$notif_from'");
print SM "From: ".$OEM_COMPANY_NAME." ".$OEM_PRODUCT_NAME." <$notif_from>\n";
print SM "To: <$recipient>\n";
print SM "X-PineApp-Mail-Rcpt-To: <$recipient>\n";
print SM "Subject: $notif_subj\n";
print SM "Content-Transfer-Encoding: base64\n";
print SM "Content-type: text/plain; charset=\"UTF-8\"\n";
print SM "MIME-Version: 1.0

";
@bits = ( $notif_body =~ /.{1,62}/gs );

for $bit (@bits) {
    print SM $bit."\n";
}

print SM "\n";

close (SM);
