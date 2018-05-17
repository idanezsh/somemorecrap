#!/usr/bin/php
<?
//ini_set("display_errors",1);
//create array from rc.qmail
$temp = `grep ^LOCAL_USER /etc/rc.pineapp/rc.qmail`;
$temp = explode("LOCAL_USER",trim($temp));
for ($i = 0; $i<count($temp); $i++)
{
    $current = explode("\"",$temp[$i]);
    if(count($current) >1)
    $rc_array[$i] = $current[1];
}
//finding and removing all dirs that not exist in rc.qmail
`ls -l /var/qmail/popboxes/|grep "^d" > /tmp/removePop.tmp`;
$lines = file("/tmp/removePop.tmp");
for ($i = 0; $i< count($lines); $i++)
{
    $temp = explode(" ",strrev(trim($lines[$i])) );
    $current = strrev($temp[0]);
    if(!in_array($current,$rc_array))
     { `rm -r /var/qmail/popboxes/$current 2>/tmp/myerr.tmp`;}
}
?>