#!/usr/bin/php
<?
header('Content-Type: text/html; charset=utf-8');
//************************************************************************************************//
//**************                SCRIPT send mail report to administartor                      ****//
//**************                                                                              ****//
//************************************************************************************************//

$DEBUG_LEVEL = 0;
$limit = 10;


// tcp server connect check
$error = null;
$hostip = "www.pineapp.com";
$port = 80;
$num=0;

if (fsockopen($hostip, $port, $num, $error, 5))
	$send_alert = 0;//echo "\n TRUE";
else
	$send_alert = 1;

//check if the box in scanner mode
/*
$CLUSTER_MODE = extract_rcparm_file ("/etc/rc.pineapp/rc.scanner","CLUSTER_MODE");
$CLUSTER_DIRECTOR = extract_rcparm_file ("/etc/rc.pineapp/rc.system","CLUSTER_DIRECTOR");
if ($CLUSTER_MODE == "yes" && $CLUSTER_DIRECTOR != "yes")
{
    die("\n It's scanner in director-scanner mode - script stopped.\n");
}
*/
//oem change: this option should be discased in the future
$send_alert = 0;

//echo "\n\ntcp_check =$tcp_check\n";die();


$mode = 1;  // for last day

if ($DEBUG_LEVEL>0)
	echo "\n mode = $mode\n";

if (file_exists("/var/qmail-sys/bin/qmail-inject"))
	$QMAIL_INJECT="/var/qmail-sys/bin/qmail-inject";
else
	$QMAIL_INJECT="/var/qmail/bin/qmail-inject";

$precision = 1;

if ($DEBUG_LEVEL>0)
	ini_set("display_errors",1);

require_once("/srv/www/htdocs/admin/avinfo.php");
require_once("/usr/local/httpd/htdocs/admin/utility/pg_conn.php");

$check_lang = extract_rcparm_file("/etc/rc.pineapp/rc.spam","DAILY_REPORT_LANG");
$tmp_lang = explode(",",$check_lang);
$lang_daily = trim($tmp_lang[0]);

$TMP_lang = $lang = "en";

if ($lang_daily == 'jp')
	$TMP_lang = $lang = "jp";
if ($lang_daily == 'ru')
	$TMP_lang = $lang = "ru";
else 
	$lang_daily == 'en';

$TMP_lang = $lang_daily;
$inner = true;
include_once("/usr/local/httpd/htdocs/admin/lang/global_lang.php"); 
require_once("/usr/local/httpd/htdocs/admin/sysinfo/includes/common_functions.php");
$_SERVER['DOCUMENT_ROOT'] = "/usr/local/httpd/htdocs";
require_once("/usr/local/httpd/htdocs/admin/reports/inc/ReportData.inc");
require_once ("/usr/local/httpd/htdocs/admin/utility/Timing.inc");

$timeing = new Timing();
$timeing->getTiming();
echo $timeing->getTiming()."0 START date ".exec(date);

### Select Owner details ##########
unset($r_);unset($result_m_logo);
$m_logo_query = "SELECT cust_logo,cust_company_name,cust_company_url,cust_notify_addr FROM cust.customer WHERE cust_id=1";
$result_m_logo =  pg_query($m_logo_query);
while ($r_=pg_fetch_array($result_m_logo)) {
		$temp_logo 			= $r_["cust_logo"];
		$company_name 		= $r_["cust_company_name"];
		$company_url 		= $r_["cust_company_url"];
		$company_notify 	= $r_["cust_notify_addr"];
}

$temp_logo_1 = explode(",",$temp_logo);
$reportmainlogo = base64_decode($temp_logo_1[1]);
$report_endlogo = $reportmainlogo;
unset($temp_logo);unset($temp_logo_1);
#echo $report_endlogo;
####################################

$res = pg_query("SELECT domain from config.local_domains where default_val=1");
$domain = pg_result($res,0,0);

$admin_email = exec("grep ^SCANNER_ADMIN /etc/rc.pineapp/rc.scanner |cut -d \\\" -f 2");
//echo $admin_email;

$file_css = "/usr/local/httpd/htdocs/admin/templates/ui/css/daily.css";

if ($trheadlite_color = get_pred_color($file_css,'trheadlite'))
	$trheadlite_color = '3D#'.$trheadlite_color;
else
	$trheadlite_color = '3D#cccccc';

if ($trhead_color = get_pred_color($file_css,'trhead'))
	$trhead_color = '3D#'.$trhead_color;
else
	$trhead_color = '3D#cccccc';



//$from = substr(pg_result(pg_query("SELECT NOW()-INTERVAL '24 HOURS' "),0,0),0,13).':00:00';
$yesterday = pg_result(pg_query("SELECT date(NOW()-INTERVAL '24 HOURS') "),0,0);

//$yesterday = '2007-03-01';


//$to = substr(pg_result(pg_query("SELECT NOW()"),0,0),0,10).' 00:00:00';

$curr_year = substr($yesterday,0,4);

$heder = "";

//$data_array = array($arr_lang["SPAM"]=>100,$arr_lang["CLEAN"]=>33,$arr_lang["VIRUS"]=>15,$arr_lang["POLICY"]=>25);

$data_array = array();
$data_array_con = array();

$table_totals = build_total_table();

$timestamp = date("Ymd");

/* Building graphs */
exec("rm /tmp/last_reporta*.gif");
echo "\n CALL create_image messages \n";
$last_reporta_con = "/tmp/last_reporta_con".$timestamp.".gif";
$last_reporta = "/tmp/last_reporta".$timestamp.".gif";
create_image($data_array,1);
$img_lr = imagecreatefromgif($last_reporta);
$width_lr = imagesx($img_lr);
$height_lr = imagesy($img_lr);

echo $timeing->getTiming()."last_reporta.gif created \n";

echo "\n CALL create_image connections \n";
create_image($data_array_cons,2);
$img_lr2 = imagecreatefromgif($last_reporta_con);
$width_lr2 = imagesx($img_lr2);
$height_lr2 = imagesy($img_lr2);

echo $timeing->getTiming()."last_reporta.gif created \n";


$pik_24 = "/tmp/lastday24".$timestamp.".gif";
$pik_24con = "/tmp/lastday24con".$timestamp.".gif";
//delete old images
exec("rm /tmp/lastday24*.gif");
//create24_image(1);
create24new(1);
echo "\n\n";
create24new(2);
echo "\n\n";

$img_lr24 = imagecreatefromgif($pik_24);
$width_lr24 = imagesx($img_lr24);
$height_lr24 = imagesy($img_lr24);

echo $timeing->getTiming()."lastday24.gif created \n";

$img_lr24con = imagecreatefromgif($pik_24con);
$width_lr24con = imagesx($img_lr24con);
$height_lr24con = imagesy($img_lr24con);

echo $timeing->getTiming()."$pik_24con created \n";




$table_totals.="</table>"."<br>
<img src=3D\"cid:last_reporta_con".$timestamp.".gif\" width=3D$width_lr2  height=3D$height_lr2 border=3D0>
<img src=3D\"cid:last_reporta".$timestamp.".gif\" width=3D$width_lr  height=3D$height_lr border=3D0>
<br>"."<br>
<img src=3D\"cid:lastday24con".$timestamp.".gif\" width=3D$width_lr24con  height=3D$height_lr24con border=3D0>
<br><br>
<img src=3D\"cid:lastday24".$timestamp.".gif\" width=3D$width_lr24  height=3D$height_lr24 border=3D0>
<br>";


/* domains/tops data */
if ($mode == 1)
$top_domains = "SELECT substring( lower(i.sender)  from '@(.+)' ) as address, COUNT(fr.grp_fate_id) AS val
FROM log.msg_info as i, log.msg_fate as f, log.msg_fate_recipients as fr
WHERE  i.msgid=f.msgid AND f.grp_fate_id=fr.grp_fate_id 
AND date(i.entry_time) = '$yesterday'  and i.origin=1  
GROUP  BY address  having substring( lower(i.sender)  from '@(.+)' )<>'' ORDER BY val desc  limit $limit";

if ($mode == 7)
$top_domains = "SELECT substring( lower(i.sender)  from '@(.+)' ) as address, COUNT(fr.grp_fate_id) AS val
FROM log.msg_info as i, log.msg_fate as f, log.msg_fate_recipients as fr
WHERE  i.msgid=f.msgid AND f.grp_fate_id=fr.grp_fate_id 
AND date(i.entry_time) > (date(now()) -  interval '8 days') and  date(i.entry_time) < date(now())  and i.origin=1  
GROUP  BY address  having substring( lower(i.sender)  from '@(.+)' )<>'' ORDER BY val desc  limit $limit";

$pg_result_tdom = pg_query($top_domains);

echo $timeing->getTiming()."Top sender Domains Selected \n";

$num_rows = pg_num_rows($pg_result_tdom);
for ($i = 0; $i < $num_rows; $i++)
{
    $tdom[]=pg_result($pg_result_tdom,$i,'address');
    $tdomains[]='\''.pg_result($pg_result_tdom,$i,'address').'\'';
}


$tdomains_string = implode(",",$tdomains);

switch ($mode)
{
    case '1':
        $top_stats = "
SELECT   substring( lower(i.sender)  from '@(.+)' ) as address,f.modid AS modid,f.action as action, count(fr.grp_fate_id) AS count,mfr.action_id as mfr_action_id, SUM(i.size) as sum_size
FROM log.msg_info as i left join log.msg_fate as f on (i.msgid=f.msgid) left join  log.msg_fate_recipients as fr on (f.grp_fate_id=fr.grp_fate_id) 
left join log.msg_fate_rules as mfr on (f.grp_fate_id = mfr.group_id)
WHERE  i.origin=1
AND date(i.entry_time)= '$yesterday' AND substring( lower(i.sender)  from '@(.+)' ) in ($tdomains_string)
GROUP BY   address, modid, action, mfr_action_id ORDER BY    address, modid, action,mfr_action_id
";

        $sql = "SELECT substring( lower(i.sender)  from '@(.+)' ) as address, COUNT(fr.grp_fate_id) AS val
FROM log.msg_info as i, log.msg_fate as f, log.msg_fate_recipients as fr
WHERE  i.msgid=f.msgid AND f.grp_fate_id=fr.grp_fate_id
AND date(i.entry_time) = '$yesterday'  and i.origin=1  and f.modid=256 and f.action=2
GROUP  BY address  having substring( lower(i.sender)  from '@(.+)' )<>'' ORDER BY val desc limit $limit;";


        break;
    case '7':
        $top_stats = "
SELECT   substring( lower(i.sender)  from '@(.+)' ) as address,f.modid AS modid,f.action as action, count(fr.grp_fate_id) AS count,mfr.action_id as mfr_action_id, SUM(i.size) as sum_size
FROM log.msg_info as i left join log.msg_fate as f on (i.msgid=f.msgid) left join  log.msg_fate_recipients as fr on (f.grp_fate_id=fr.grp_fate_id) 
left join log.msg_fate_rules as mfr on (f.grp_fate_id = mfr.group_id)
WHERE  i.origin=1
AND date(i.entry_time) > (date(now()) -  interval '8 days') and  date(i.entry_time) < date(now())  AND substring( lower(i.sender)  from '@(.+)' ) in ($tdomains_string)
GROUP BY   address, modid, action, mfr_action_id ORDER BY    address, modid, action,mfr_action_id
";

        $sql = "SELECT substring( lower(i.sender)  from '@(.+)' ) as address, COUNT(fr.grp_fate_id) AS val
FROM log.msg_info as i, log.msg_fate as f, log.msg_fate_recipients as fr
WHERE  i.msgid=f.msgid AND f.grp_fate_id=fr.grp_fate_id
AND date(i.entry_time) > (date(now()) -  interval '8 days') and  date(i.entry_time) < date(now())  and i.origin=1  and f.modid=256 and f.action=2
GROUP  BY address  having substring( lower(i.sender)  from '@(.+)' )<>'' ORDER BY val desc limit $limit;";

        break;

}

//echo "\n\n$top_stats\n\n";


//echo "\n\n$sql\n\n";


$topa = build_top_data_array($top_stats,$tdom);




echo $timeing->getTiming()."Topa array has build \n";


$table_tdom = build_tdom_table($topa,$tdom);

/*  build top spam senders domain */

$res = pg_query($sql);
$num_rows = pg_num_rows($res);

echo $timeing->getTiming()."Top spam senders domains selected  \n";

if ($num_rows>0)
{
    $tabletopspam =
    "<table width=3D30% cellpadding=3D1 cellspacing=3D0 border=3D1 bgcolor=3Dblack>
<tr><td bgcolor=$trhead_color class=3Dtrhead colspan=3D2 align=3Dcenter>".$arr_lang["TOP_SPAM_SENDER_DOMAINS"]."</td></tr>
<tr bgcolor=$trheadlite_color class=3Dtrheadlite>
    <td>".$arr_lang["DOMAIN"]."</td>
    <td>".$arr_lang["SPAM"]."</td></tr>";
    for ($i = 0 ; $i < $num_rows; $i++)
    {
        $r = pg_fetch_array($res);
        $tabletopspam .="<tr bgcolor=3D#FFFFCC class=3Dpred>
        <td><NOBR>".$r['address']."</td><td>".$r['val']."</NOBR></td>";
    }
    $tabletopspam .="</table>";

}
else
$tabletopspam = "";





$table = build_sysinfo_table();

/* get logo dimensions */
$file = $reportmainlogo;
$img_logo = imagecreatefromgif($file);
$width_logo = imagesx($img_logo);
$height_logo = imagesy($img_logo);

$main_tr_color = "3D#cccccc";
$second_tr_color = "3D#ffffff";

$file_end = $report_endlogo;
$img_logo = imagecreatefromgif($file_end);
$width_logo_end = imagesx($img_logo);
$height_logo_end = imagesy($img_logo);


$end_pik = "<br><br><p class=3Dfooter ><a href=3D\"$company_url/\">$company_name</a> &#169; $curr_year, All Rights Reserved.</p>";

if ($mode == 1)
$arr_report = $arr_lang["DAILY_REPORT"];
if ($mode == 7)
$arr_report = $arr_lang["LAST_7_DAYS_REPORT"];



$style = file($file_css);
$style = implode("",$style);


$bodyopen =  'Content-Type: text/html; charset=UTF-8
Content-Transfer-Encoding: quoted-printable'."\n\n".
"<html><title>".$product_name." - $arr_report</title><body bgcolor=3D#FFFFFF>
<head>
<style type=3D\"text/css\">
$style     
</style>
</head>
<tables width=3D100% height=3D64 border=3D0>
<tr>
<td align=center><img src=3D\"cid:reportmainlogo.gif\" width=3D$width_logo  height=3D$height_logo border=3D0>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$heder</td>
</tr>
</table><br><br>

";
$bodyclose =  "</body></html>
";


$mail_body = $bodyopen.$total_table.$table.$mail_body."</table><br>".$table_totals."<br>".$table_tdom."<br>$tabletopspam<br>".$end_pik.$bodyclose;
/**** SEND MAIL  *****/
$to = "To: $admin_email\n";
if ($DEBUG_LEVEL > 0)
$to = "To:shlomo@pineapp.com \n";
if($DEBUG_LEVEL == 777)
$to = "To:alex@pineapp.com \n";
// $cc = "CC: shlomo@pineapp.com;\n";
$from ="From:$company_notify\n";
$from_header = "$company_notify\n";
$start_date_ = substr($start_date,0,10);
if ($mode == 1)
$subject = "Subject:  Daily Report for $yesterday \n";
if ($mode == 7)
$subject = "Subject:  Last 7 Days Report $yesterday \n";

$content = "Content-type: text/html; charset=UTF-8\n";
$boundary = "------------" . md5(uniqid(time()));
$content = "MIME-Version: 1.0\n" .
"Content-Type: multipart/related;\n" .
" boundary=\"" . $boundary . "\"\n" ;




$msg_body = build_msg_body();

/*
$fp = fopen("/tmp/last.eml","w");
fwrite($fp,$from.$to.$subject.$content.$msg_body);
fclose($fp);
exit();
*/


if($fp = popen("$QMAIL_INJECT -f$from_header",'w'))
{
    if(!fwrite($fp,$from.$to.$subject.$content.$msg_body))
    {
        $str_error = "Can't write  : fwrite($fp,$from.$to) \n";
        pclose($fp);
        break;
    }
    pclose($fp);
    $array_sent[$mail_sent] = $oid_prev;
    $mail_sent++;
    if ($DEBUG_LEVEL > 0)
    echo " \n URA tovarischi $to !!!\n";
}
else
{
    $str_error =  "Can't popen $QMAIL_INJECT -f$from.....  \n";
    echo $str_error;
    break;
}


if ($send_alert == 1)
{
    //$mail_body = 'Alert: please note that there is a problem to connect to PineApp center.';
    $alert_txt = "<table><tr><td bgcolor=3D#E43117 class=3Dalert>".$arr_lang["ALERT_TCP_CONNECT_TO_PINEAPP_FAILED"]."</td></tr></table>";
    $mail_body = $bodyopen.$alert_txt.$end_pik.$bodyclose;
    $subject = "Subject: Alert: TCP connect failed from $yesterday \n";

    $file = $reportmainlogo;
    $data = $file;
    $part_i = "Content-Type: image/gif
Content-Transfer-Encoding: base64
Content-Disposition: attachment
Content-Location: reportmainlogo.gif
Content-Id:<reportmainlogo.gif>\n" .
"\n" .
chunk_split( base64_encode($data), 68, "\n");

$msg_body ="\n"."--" . $boundary . "\n".$mail_body."\n";   /// $bodyopen


$msg_body .="--" . $boundary . "\n".$part_i;

$file = $report_endlogo;
$data = $file;
$part_r = "Content-Type: image/gif
Content-Transfer-Encoding: base64
Content-Disposition: attachment
Content-Location: reportendlogo.gif
Content-Id:<reportendlogo.gif>\n" .
"\n" .
chunk_split(base64_encode($data), 68, "\n");
$msg_body .="--" . $boundary . "\n".$part_r;
$msg_body .="--" . $boundary.'--'."\n";


/**** SEND MAIL  *****/
$to = "To: $admin_email\n";
if ($DEBUG_LEVEL > 0)
$to = "To:shlomo@pineapp.com \n";




if($fp = popen("$QMAIL_INJECT -f$from_header",'w'))
{
    if(!fwrite($fp,$from.$to.$subject.$content.$msg_body))
    {
        $str_error = "Can't write  : fwrite($fp,$from.$to) \n";
        echo $str_error;
        pclose($fp);
        break(2);
    }
    pclose($fp);
    $array_sent[$mail_sent] = $oid_prev;
    $mail_sent++;
    if ($DEBUG_LEVEL > 0)
    echo " \n URA tovarischi $to !!!\n";
}
else
{
    $str_error =  "Can't popen $QMAIL_INJECT -f$from.....  \n";
    break;
}
}



echo $timeing->getTiming()."Finish . . . .   \n";


function percent($total,$value,$precision)
{
    global $precision;
    return round(($value/$total*100),$precision);
}

function show_total_tr($label,$param,$total_param)
{
    global $trheadlite_color, $trhead_color;
    $ret_val = "
        <tr>
		<td bgcolor=$trheadlite_color class=3Dtrheadlite width=3D10% align=3Dcenter>
		  $label 
		</td>";


    for ($i = 0; $i < 3; $i++)
    {
        $perc = percent($total_param[$i],$param[$i]);
        $ret_val .="<td bgcolor=3D#FFFFCC class=3Dpred width=3D30% align=3Dcenter>&nbsp;";
        if(!isset($param[$i]) || $param[$i] == 0) $ret_val .= '0';
        elseif($perc != 100) $ret_val .= $param[$i]." (".$perc."%)";
        else
        $ret_val .= $param[$i];
        $ret_val .="
		</td>";
    }
    $ret_val .="
	</tr>";
    return $ret_val;
}


function create_image($data_array,$graph_type)
{
    global $trheadlite_color, $trhead_color, $last_reporta, $last_reporta_con;
    global $arr_lang,$lang,$yesterday,$mode;
    //$graph_type = 1;

    switch($graph_type)
    {
        case '1':
            $target_file = $last_reporta;
            break;
        case '2':
            $target_file = $last_reporta_con;
            break;
    }

    //  print_r($data_array);

    require_once("/usr/local/httpd/htdocs/admin/confvariables.php");


    DEFINE("TTF_DIR","/usr/X11R6/lib/X11/fonts/");
    DEFINE("MBTTF_DIR","/usr/X11R6/lib/X11/fonts/");

    require_once("/usr/local/httpd/htdocs/admin/jpgraph/src/jpgraph.php");
    require_once("/usr/local/httpd/htdocs/admin/jpgraph/src/jpgraph_pie.php");
    require_once("/usr/local/httpd/htdocs/admin/jpgraph/src/jpgraph_pie3d.php");


    if ($lang=="jp" || $lang=="ru") {
        mb_http_output("pass");
        DEFINE("JPFONT",FF_MINCHO);
    } else
    DEFINE("JPFONT",FF_VERASERIF);

    // $graph = new PieGraph(650,480/1.4);
    $graph = new PieGraph(700,580/1.3);

    $legends = array();

    $labels  = array();

    $slice_colors  = array();
    $color_types  = array($arr_lang["SPAM"]=>'darkgoldenrod1',$arr_lang["CLEAN"]=>'green',$arr_lang["VIRUS"]=>'red',$arr_lang["POLICY"]=>'yellow',$arr_lang["BACKSCATTER"]=>'brown',$arr_lang["USER_NOT_EXIST"]=>'khaki3',$arr_lang["RBL"]=>'lightslateblue',$arr_lang["POLICY_BADMAILFROM"]=>'lightsalmon',$arr_lang["POLICY_BADRCPTTO"]=>'lightcyan2',$arr_lang["POLICY_RELAY_DENIED"]=>'magenta1',$arr_lang["POLICY_NON_EXDOMAIN"]=>'mediumred',$arr_lang["POLICY_RATELIMIT"]=>'lightpink1',$arr_lang["POLICY_FAKE_DELAY"]=>'ivory2',$arr_lang["POLICY_IPREP"]=>'khaki2',$arr_lang["POLICY_SPOOFING_DENIED"]=>'mistyrose',$arr_lang["PASSED"]=>'green');


    $data = array();
    if ($lang=="jp" || $lang=="ru") {
        $graph->title->SetFont(JPFONT);
    } else
    $graph->title->SetFont(JPFONT,FS_BOLD);

    $max_lenl = $max_lenr = 0;


    // total
    //$graph->title->Set($arr_lang["GRAND_TOTAL"]);
    $i = 0;
    switch ($graph_type)
    {
        case '1':
        case 1:
            // total
            $graph->title->Set($arr_lang["GRAND_TOTAL"]);
            $height_legend = 0.82;
            foreach ($data_array as $key => $value) {
                if ($value != 0) {
                    $data[] 					= $value;
                    //$labels[] 				= $key."\n%.2f%%";
                    $labels[] 				= "";
                    $legends[] 				= $key."%.2f%%";
                    $slice_colors[]		= $color_types[$key];
                    if (fmod($i,2) == 0)
                    {
                        $cur = strlen($key." %.2f%%aa");
                        if ($max_lenl<$cur)
                        $max_lenl = $cur;
                    }
                    else
                    {
                        $cur = strlen($key." %.2f%%bbbbbbcc");
                        if ($max_lenr<$cur)
                        $max_lenr = $cur;
                    }
                    $i++;
                }
            }
            break;
            break;
        case '2':
            $graph->title->Set($arr_lang["CONNECTIONS"]);
            $height_legend = 0.78;

            foreach ($data_array as $key => $value) {
                if ($value != 0) {
                    $data[] 		= $value;
                    //$labels[] 	= $key."\n%.2f%%";
                    $labels[] 				= "";
                    //$legends[] 	= $key."\n%.2f%%";
                    //$legends[] 	= $key."\n%.2f%%";

                    $tmp = $key." %.2f%%";

                    if (fmod($i,2) == 0)
                    {
                        $cur = strlen($tmp);
                        if ($max_lenl<$cur)
                        $max_lenl = $cur;
                    }
                    else
                    {
                        $cur = strlen($tmp);
                        if ($max_lenr<$cur)
                        $max_lenr = $cur;
                    }
                    // $legends[] 	= $key." %.2f%%bcc$max_lenl-$max_lenr";
                    $legends[] = $tmp;

                    $slice_colors[]		= $color_types[$key];
                    $i++;
                }

            }

            break;
    }

    $max_len = $max_lenr+$max_lenl;



    if (count($data) == 0) {
        echo "\n GR no data";
        exec("cp /usr/local/httpd/htdocs/admin/images/null.gif $target_file");
    } else {
        $p1 = new PiePlot3D($data);

        switch($graph_type)
        {
            case '1':
                switch($mode)
                {
                    case '1':
                        $graph->title->Set($arr_lang["TOTAL_DAILY_TRAFFIC_FOR"].' '.$yesterday);
                        break;
                    case '7':
                        $graph->title->Set($arr_lang["TOTAL_DAILY_TRAFFIC_FOR_LAST_7_DAYS"]);
                        break;
                }
                break;
            case '2':
                switch($mode)
                {
                    case '1':
                        $graph->title->Set($arr_lang["CONNECTIONS"].' '.$yesterday);
                        break;
                    case '7':
                        $graph->title->Set($arr_lang["TOTAL_DAILY_TRAFFIC_FOR_LAST_7_DAYS"]);
                        break;
                }
                break;
        }


        $graph->legend->SetFont(JPFONT);

        $graph->img->SetImgFormat("gif") ;
        $graph->img->SetQuality(100);
        $p1->SetSliceColors($slice_colors);
        $p1->SetLegends($legends);

        $p1->SetCenter(0.5,0.4);
        $p1->ExplodeAll();
        $p1->SetLabelType(PIE_VALUE_PER);
        $p1->SetLabels($labels);
        //$p1->SetLabelType(PIE_VALUE_PER);
        // $p1->SetLabels($labels);

        $p1->SetAngle(55);

        $tmp = (76-$max_len) * 0.01;
        //echo "\n TMP=$tmp\n";
        //$graph->legend->Pos(0.05,0.78);
        $graph->legend->Pos($tmp,0.78);
        $graph->legend->SetLayout(LEGEND_SPESIAL);

        $graph->Add($p1);

        $graph->Stroke($target_file);
    }


}


function CreateBarPlot($array_data,$start_date,$color,$label)
{
    global $trheadlite_color, $trhead_color;
    global $arr_lang;
    $bplot = new BarPlot($array_data);

    return $bplot;

    if ($start_date != '24hours')
    {
        $bplot->SetFillColor("$color@0.9");
        $bplot->value->Show();
        $bplot->value->SetFormat('%d');
        $bplot->value->SetFont(FF_FONT1,FS_BOLD,6);
        $bplot->SetColor("$color@0.9");
    }
    if ($start_date == '24hours')
    {
        // $bplot->SetFillColor($color);
        // $bplot->SetLegend($label);
    }
    return $bplot;
}


function CreateLinePlot($array_data,$color,$label)
{
    global $arr_lang;
    $dplot = new LinePlot($array_data);
    $dplot->SetFillColor("$color");
    $dplot->SetLegend($label);
    // $dplot->legend->Pos(0.05,0.5,"right","center");

    return($dplot);
}




function getData($groupby,$origin,$start_date,$mode,$graph_type) {
    global $trheadlite_color, $trhead_color;

    // selecting the desired fields according to origin
    switch ($graph_type) {
        case '1':
            $select = "SUM(total_sum_in) as total, SUM(spam_sum) as spam,
		 				 SUM(virus_sum_in) as virus, SUM(policy_sum_in) as policy ,
		 				 SUM(backscatter_sum) as backscatter  ,SUM(nonexist_sum) as nonexist";
            switch($mode){
                case '7' :
                    $where = " where general.datetime > (date(now())- interval '7 days') and general.datetime < date(now()) ";
                    $sql_date = "  date_trunc('day'::text, general.datetime) ";
                    break;
                case '1' :
                    $where = " where general.datetime > (date(now())- interval '25 hours') and general.datetime < date(now())";
                    $sql_date = "  date_trunc('hour'::text, general.datetime) ";
                    break;
                case '12months' :
                    $where = " where general.datetime > (now()- interval '11 months') ";
                    break;
            }
            break;
        case '2':
            $select = "SUM(cons_all) as cons_all,
		 				 SUM(rbl_sum) as rbl_sum,
		 				 SUM(cpolicy_badmailfrom) as cpolicy_badmailfrom,
		 				 SUM(cpolicy_badrcptto) as cpolicy_badrcptto,
		 				 SUM(cpolicy_relay_denied) as cpolicy_relay_denied,
		 				 SUM(cpolicy_non_exdomain) as cpolicy_non_exdomain,
		 				 SUM(cpolicy_ratelimit) as cpolicy_ratelimit,
		 				 SUM(cpolicy_fake_delay) as cpolicy_fake_delay,
		 				 SUM(cpolicy_iprep) as cpolicy_iprep,
		 				 SUM(cpolicy_spoofing_denied) as cpolicy_spoofing_denied ";
            switch($mode){
                case '7' :
                    $where = " where general.datetime > (date(now())- interval '7 days') and general.datetime < date(now()) ";
                    $sql_date = "  date_trunc('day'::text, general.datetime) ";
                    break;
                case '1' :
                    $where = " where general.datetime > (date(now())- interval '25 hours') and general.datetime < date(now())";
                    $sql_date = "  date_trunc('hour'::text, general.datetime) ";
                    break;
                case '12months' :
                    $where = " where general.datetime > (now()- interval '11 months') ";
                    break;
            }
            break;
            break;
    }

    $sql = "SELECT $sql_date as date, $select FROM reports.general $where GROUP BY date order by date";

    return $sql;
}


function build_top_data_array($top_stats,$tdom)
{
    global $mode;
    $topa = array();
    for ($i = 0; $i< count($tdom); $i++)
    {
        $topstat_array_tmp = array( $tdom[$i] => array('clean'=>0,'spam'=>0,'nonexist'=>0,'policy'=>0,'virus'=>0,'backscatter'=>0,'all'=>0));
        $topa = $topa + $topstat_array_tmp;
    }

    $res_top_stats = pg_query($top_stats);
    $num_rows = pg_num_rows($res_top_stats);
    for ($i = 0; $i < $num_rows; $i++)
    {
        $r = pg_fetch_array($res_top_stats);
        $index = $r['address'];

        $action = $r['action'];
        //	echo "\n$i = ".$r['count'].' '.$index.' modid='.$r['modid'].' action='.$r['action'];

        if (($action == 0 && $r['modid'] != 256 ) && ($action == 0 && $r['modid'] != 768))
        $topa[$index]['clean'] += $r['count'];
        else
        switch ($r['modid'])
        {

            case '0':
                $topa[$index]['clean'] += $r['count'];
                break;
            case '256':
                if ($action == 0)
                {
                    if (isset($r['mfr_action_id']) && $r['mfr_action_id'] == 65792) // tagged spam
                    {
                        $topa[$index]['spam'] += $r['count'];
                        $topa[$index]['clean'] -= $r['count'];
                    }
                    else
                    $topa[$index]['clean'] += $r['count'];
                }
                else
                {
                    $topa[$index]['spam'] += $r['count'];
                }
                break;
            case '768': // attachment
            if (isset($r['mfr_action_id']) && $r['mfr_action_id'] == 66304)  // striped attach
            ;
            else
            $topa[$index]['policy'] += $r['count'];
            break;
            case '1024':
                $topa[$index]['virus'] += $r['count'];
                break;
            case '1025':
                $topa[$index]['backscatter'] = $r['count'];
                break;
            case '1027':
                $topa[$index]['nonexist'] += $r['count'];
                break;
            case '1028':
                $topa[$index]['policy'] += $r['count'];
                break;
            case '1030':
                $topa[$index]['policy'] += $r['count'];
                break;
            case '1280':  //black list
            $topa[$index]['policy'] += $r['count'];
            break;
        }
    }


    for ($i = 0; $i < count($tdom); $i++)
    {
        $index = $tdom[$i];
        $topa[$index]['all'] = $topa[$index]['clean'] + $topa[$index]['spam'] + $topa[$index]['virus'] + $topa[$index]['policy'] + $topa[$index]['backscatter'] + $topa[$index]['nonexist'];
        $topa[$index]['blocked'] = $topa[$index]['spam'] + $topa[$index]['virus'] + $topa[$index]['policy'] + $topa[$index]['backscatter'] + $topa[$index]['nonexist'];
        $topa[$index]['passed'] = $topa[$index]['clean'];

        $topa['sum']['all'] += $topa[$index]['all'];
        $topa['sum']['clean'] += $topa[$index]['clean'];
        $topa['sum']['spam'] += $topa[$index]['spam'];
        $topa['sum']['nonexist'] += $topa[$index]['nonexist'];
        $topa['sum']['virus'] += $topa[$index]['virus'];
        $topa['sum']['policy'] += $topa[$index]['policy'];
        $topa['sum']['backscatter'] += $topa[$index]['backscatter'];
        $topa['sum']['blocked'] += $topa[$index]['blocked'];
        $topa['sum']['passed'] += $topa[$index]['passed'];

    }
    $tdom[] = 'sum';
    for ($i = 0; $i < count($tdom); $i++)
    {
        $index = $tdom[$i];
        if ($topa[$index]['blocked'] == 0)
        ;
        else
        {
            $tmp = $topa[$index]['blocked']*100/$topa[$index]['all']*2;
            $topa[$index]['blocked'] =  round($tmp);
        }
        if ($topa[$index]['passed'] == 0)
        ;
        else
        {
            $tmp = $topa[$index]['passed']*100/$topa[$index]['all']*2;
            $topa[$index]['passed'] =  round($tmp);
        }
    }

    return $topa;
}

function build_total_table()
{
    global $trheadlite_color, $trhead_color;
    global $arr_lang, $data_array_cons, $data_array, $timeing,$yesterday, $mode;


    $params = array ($total_param,$clean_param,$policy_param,$virus_param,$spam_param,$nonexist_param,$rbl_param,$backscatter_param);
    $label = array("Total","Clean","Policy","Virus","Spam","User not exist","RBL","Backscatter");

    switch ($mode)
    {
        case '1':
            $where = " WHERE date(datetime) = '$yesterday' ";
            break;
        case '7':
            $where = " WHERE date(datetime) > (date(now()) -  interval '8 days') and  date(datetime) < date(now())";
            break;
    }

    /*
    cons_all bigint,
    cons_false bigint,
    cpolicy_badmailfrom bigint,
    cpolicy_badrcptto bigint,
    cpolicy_relay_denied bigint,
    cpolicy_non_exdomain bigint,
    cpolicy_ratelimit bigint,
    cpolicy_fake_delay bigint,
    cpolicy_iprep bigint,
    cpolicy_spoofing_denied
    */

    $sql = "SELECT SUM(total_sum_in) as total_sum_in,  SUM(total_sum_out) as total_sum_out,
SUM(virus_sum_in) as virus_sum_in, SUM(virus_sum_out) as virus_sum_out,
SUM(policy_sum_in) as policy_sum_in, SUM(policy_sum_out) as policy_sum_out,
SUM(spam_sum) as spam_sum,SUM(backscatter_sum) as backscatter_sum,
SUM(rbl_sum) as rbl_sum, SUM(nonexist_sum) as nonexist_sum,
SUM(cons_all) as cons_all, SUM(cpolicy_badmailfrom) as cpolicy_badmailfrom,
SUM(cpolicy_badrcptto) as cpolicy_badrcptto, SUM(cpolicy_relay_denied) as cpolicy_relay_denied,
SUM(cpolicy_non_exdomain) as cpolicy_non_exdomain, SUM(cpolicy_ratelimit) as cpolicy_ratelimit,
SUM(cpolicy_fake_delay) as cpolicy_fake_delay, SUM(cpolicy_iprep) as cpolicy_iprep,
SUM(cpolicy_spoofing_denied) as cpolicy_spoofing_denied 
FROM reports.general $where ";

    //echo "\n\n$sql\n\n";
    $result = pg_query($sql);

    echo $timeing->getTiming()."Total Data Selected \n";


    $row = pg_fetch_array($result);
    $total_sum_in = $row['total_sum_in'];
    $total_sum_out = $row['total_sum_out'];
    $value[2] = $total_sum_out;
    $virus_sum_in = $row['virus_sum_in'];
    $value[10] = $virus_sum_in;
    $virus_sum_out = $row['virus_sum_out'];
    $value[11] = $virus_sum_out;
    $policy_sum_in = $row['policy_sum_in'];
    $value[13] = $policy_sum_in;
    $policy_sum_out = $row['policy_sum_out'];
    $value[14] = $policy_sum_out;
    $spam_sum = $row['spam_sum'];
    $backscatter_sum = $row['backscatter_sum'];
    $value[7] = $backscatter_sum;

    $nonexist_sum = $row['nonexist_sum'];
    $value[16] = $nonexist_sum;



    //$total_sum_in = $total_sum_in + $rbl_sum   ;
    $value[1] = $total_sum_in;

    $spam = $spam_sum;
    $value[6] = $spam;
    $total = $total_sum_in + $total_sum_out ;
    $value[0] = $total;
    $virus = $virus_sum_in + $virus_sum_out;
    $value[9] = $virus;
    $policy = $policy_sum_in + $policy_sum_out;
    $value[12] = $policy;
    //$clean = $total - $virus - $policy - $spam - $backscatter_sum - $nonexist_sum - $rbl_sum;
    $clean = $total - $virus - $policy - $spam - $backscatter_sum - $nonexist_sum;
    $value[3] = $clean;
    //$clean_in = $total_sum_in - $spam - $virus_sum_in - $policy_sum_in - $backscatter_sum - $nonexist_sum - $rbl_sum;
    $clean_in = $total_sum_in - $spam - $virus_sum_in - $policy_sum_in - $backscatter_sum - $nonexist_sum;
    //echo "\n$clean_in = $total_sum_in - $spam - $virus_sum_in - $policy_sum_in - $backscatter_sum - $nonexist_sum;\n";
    $value[4] = $clean_in;
    $clean_out = $total_sum_out - $virus_sum_out - $policy_sum_out;
    $value[5] = $clean_out;

    /*  building param_arrays  */
    $total_param = array($value[1],$value[2],$value[0]);
    $clean_param = array($value[4],$value[5],$value[3]);
    $virus_param = array($value[10],$value[11],$value[9]);
    $policy_param = array($value[13],$value[14],$value[12]);
    $spam_param = array($value[6],"None",$value[6]);
    $nonexist_param = array($value[16],"None",$value[16]);

    $backscatter_param = array($value[7],"None",$value[7]);



    /* connection data */
    $cons_all = $row['cons_all'];
    $cpolicy_badmailfrom = $row['cpolicy_badmailfrom'];
    $cpolicy_badrcptto = $row['cpolicy_badrcptto'];
    $cpolicy_relay_denied = $row['cpolicy_relay_denied'];
    $cpolicy_non_exdomain = $row['cpolicy_non_exdomain'];
    $cpolicy_ratelimit = $row['cpolicy_ratelimit'];
    $cpolicy_fake_delay = $row['cpolicy_fake_delay'];
    $cpolicy_iprep = $row['cpolicy_iprep'];
    $cpolicy_spoofing_denied = $row['cpolicy_spoofing_denied'];
    $rbl_sum = $row['rbl_sum'];

    $cons_block = $cpolicy_badmailfrom + $cpolicy_badrcptto + $cpolicy_relay_denied + $cpolicy_non_exdomain + $cpolicy_ratelimit + $cpolicy_fake_delay + $cpolicy_iprep + $cpolicy_spoofing_denied + $rbl_sum;
    $cons_passed = $cons_all - $cons_block;


    $data_array_cons = array($arr_lang["PASSED"]=>$cons_passed,$arr_lang["RBL"]=>$rbl_sum,$arr_lang["POLICY_BADMAILFROM"]=>$cpolicy_badmailfrom,$arr_lang["POLICY_BADRCPTTO"]=>$cpolicy_badrcptto,$arr_lang["POLICY_RELAY_DENIED"]=>$cpolicy_relay_denied,$arr_lang["POLICY_NON_EXDOMAIN"]=>$cpolicy_non_exdomain,$arr_lang["POLICY_RATELIMIT"]=>$cpolicy_ratelimit,$arr_lang["POLICY_FAKE_DELAY"]=>$cpolicy_fake_delay,$arr_lang["POLICY_IPREP"]=>$cpolicy_iprep,$arr_lang["POLICY_SPOOFING_DENIED"]=>$cpolicy_spoofing_denied);


    $params = array ($total_param,$clean_param,$policy_param,$virus_param,$spam_param,$nonexist_param,$backscatter_param);
    $label = array("Total","Clean","Policy","Virus","Spam","User not exist",  "Backscatter");

    $data_array = array($arr_lang["SPAM"]=>$spam,$arr_lang["USER_NOT_EXIST"]=>$nonexist_sum,$arr_lang["CLEAN"]=>$clean,$arr_lang["VIRUS"]=>$virus,$arr_lang["POLICY"]=>$policy, $arr_lang["BACKSCATTER"]=>$backscatter_sum);



    $table_cons = "
    <table width=3D60% cellpadding=3D1 cellspacing=3D0 border=3D1 bgcolor=3Dblack>
<tr bgcolor=$trhead_color class=3Dtrhead>
		<td colspan=3D2 align=3Dcenter>
		".$arr_lang["CONNECTIONS"]."
		</td>
		</tr>";

    foreach ($data_array_cons as $key => $value)
    {
        if ($value > 0)
        $table_cons .="<tr>
		  <td bgcolor=$trheadlite_color class=3Dtrheadlite width=3D20%>$key</td>
		  <td bgcolor=3D#FFFFCC class=3Dpred width=3D25% align=3Dcenter>$value</td>
		  </tr>
		  ";
    }
    $table_cons .="</table>";

    $table_totals = $table_cons."<br>";




    $table_totals .= "
    <table width=3D60% cellpadding=3D1 cellspacing=3D0 border=3D1 bgcolor=3Dblack>
<tr bgcolor=$trhead_color class=3Dtrhead>
		<td colspan=3D4 align=3Dcenter>
		".$arr_lang["TOTAL_RESULTS"]."
		</td>
		</tr>
		
    <tr>
		  <td bgcolor=$trheadlite_color class=3Dtrheadlite width=3D20%>&nbsp;</td>
		  <td bgcolor=$trheadlite_color class=3Dtrheadlite width=3D25% align=3Dcenter>
		      ".$arr_lang["INBOUND"]."
		  </td>
		  <td bgcolor=$trheadlite_color class=3Dtrheadlite width=3D25% align=3Dcenter>
		      ".$arr_lang["OUTBOUND"]."
		  </td>
		  <td bgcolor=$trheadlite_color class=3Dtrheadlite width=3D30% align=3Dcenter>
		      ".$arr_lang["TOTAL"]."
		  </td>
	</tr>	";





    for ($i = 0; $i < count($params); $i++)
    {
        $table_totals.=show_total_tr($label[$i],$params[$i],$total_param);
    }


    return $table_totals;

}

function build_tdom_table($topa,$tdom)
{
    global $trheadlite_color, $trhead_color;
    global $arr_lang;

    $table_tdom =
    "<table width=3D60% cellpadding=3D1 cellspacing=3D0 border=3D1 bgcolor=3Dblack>
<tr><td bgcolor=$trhead_color class=3Dtrhead colspan=3D9 align=3Dcenter>".$arr_lang["TOP_SENDER_DOMAINS_DETAILS"]." </td></tr>
<tr bgcolor=$trheadlite_color class=3Dtrheadlite>
    <td>".$arr_lang["DOMAIN"]."</td>
    <td>".$arr_lang["ALL"]."</td>
    <td>".$arr_lang["PASSED"]."/".$arr_lang["BLOCKED"]."(%)</td>
    <td>".$arr_lang["CLEAN"]."</td>
    <td>".$arr_lang["SPAM"]."</td>
    <td>".$arr_lang["USER_NOT_EXIST"]."</td>
    <td>".$arr_lang["POLICY"]."</td>
    <td>".$arr_lang["VIRUS"]."</td>
    <td>".$arr_lang["BACKSCATTER"]."</td>
</tr>";


    for ($i = 0; $i < count($tdom); $i++)
    {
        $index = $tdom[$i];
        $tr =
        "<tr bgcolor=3D#FFFFCC class=3Dpred>
    <td><NOBR>$tdom[$i]</NOBR></td>
    <td>".$topa[$index]['all']."</td>
    <td bgcolor=$trheadlite_color class=3Dtrheadlite>";
        if ($topa[$index]['passed']>0)
        $tr .= "<img src=3D\"cid:green1x1.gif\" width=3D".$topa[$index]['passed']."  height=3D14 border=3D0>";
        if ($topa[$index]['blocked']>0)
        $tr .= "<img src=3D\"cid:red1x1.gif\" width=3D".$topa[$index]['blocked']."  height=3D14 border=3D0>";

        $tr .= "</td>
    <td>".$topa[$index]['clean']."</td>
    <td>".$topa[$index]['spam']."</td>
    <td>".$topa[$index]['nonexist']."</td>
    <td>".$topa[$index]['policy']."</td>
    <td>".$topa[$index]['virus']."</td>
    <td>".$topa[$index]['backscatter']."</td>
    </tr>";
        $table_tdom .=$tr;
    }


    $table_tdom .="</table>";

    return $table_tdom;
}

function build_sysinfo_table() {
    global $trheadlite_color, $trhead_color,$product_name;
    /*  getting general system info */
    /* $av_db_vers = exec("/usr/local/fsav/fsav --v|grep 'Database version'|cut -d : -f 2"); */
    $AVName = AVInfo::AVName();
    $AVDBVersion = str_replace("_"," #",AVInfo::AVDBVersion());
	$AVDBVersion = str_replace("/"," / ",$AVDBVersion);
    
    $version_file = "/etc/rc.pineapp/rc.version";
    $product_model    =   exec("grep ^PINEAPP_MAIL_RELAY_PRODNUM /etc/rc.pineapp/rc.version|cut -d \\\" -f 2");
    $product_version  =   exec("grep ^PINEAPP_MAIL_RELAY_VERSION /etc/rc.pineapp/rc.version|cut -d \\\" -f 2");
    $product_revision =   exec("grep ^PINEAPP_MAIL_RELAY_DATE /etc/rc.pineapp/rc.version|cut -d \\\" -f 2");
    $hostname         =   exec("grep ^HOSTNAME /etc/rc.pineapp/rc.system|cut -d \\\" -f 2");

    $product_serial = exec("/bin/cat /usr/local/etc/PA_SN");
    $lisence = exec("/bin/cat /usr/local/etc/PA_EXPIRE");

    //echo "$av_db_vers\n$lisence\n$product_serial\n $product_model\n $product_version\n $product_revision\n";
    $table = "<table width=3D40% cellpadding=3D1 cellspacing=3D0 border=3D1 bgcolor=3Dblack>
<tr><td bgcolor=$trhead_color class=3Dtrhead colspan=3D2 align=3Dcenter>$product_name </td></tr>
<tr><td bgcolor=$trheadlite_color class=3Dtrheadlite>Hostname</td><td bgcolor=3D#FFFFCC class=3Dpred>&nbsp;$hostname</td></tr>
<tr><td bgcolor=$trheadlite_color class=3Dtrheadlite>License</td><td bgcolor=3D#FFFFCC class=3Dpred>&nbsp;Expires in $lisence days</td></tr>
<tr><td bgcolor=$trheadlite_color class=3Dtrheadlite>Anti-Virus Database version</td><td bgcolor=3D#FFFFCC class=3Dpred>&nbsp;$AVName $AVDBVersion</td></tr>
<tr><td bgcolor=$trheadlite_color class=3Dtrheadlite>Product model</td><td bgcolor=3D#FFFFCC class=3Dpred>&nbsp;$product_model</td></tr>
<tr><td bgcolor=$trheadlite_color class=3Dtrheadlite>Product S/N</td><td bgcolor=3D#FFFFCC class=3Dpred>&nbsp;$product_serial</td></tr>
<tr><td bgcolor=$trheadlite_color class=3Dtrheadlite>Product version</td><td bgcolor=3D#FFFFCC class=3Dpred>&nbsp;$product_version</td></tr>
<tr><td bgcolor=$trheadlite_color class=3Dtrheadlite>Product revision</td><td bgcolor=3D#FFFFCC class=3Dpred>&nbsp;$product_revision</td></tr>
";

    return $table;

}


function build_msg_body() {
    global $trheadlite_color, $trhead_color, $pik_24con, $pik_24, $last_reporta, $last_reporta_con, $timestamp;
    global $mail_body,$boundary, $company_name, $company_url, $product_name, $company_notify,$reportmainlogo,$report_endlogo;

    $file = $reportmainlogo;
    $data = $file;
    $part_i = "Content-Type: image/gif
Content-Transfer-Encoding: base64
Content-Disposition: attachment
Content-Location: reportmainlogo.gif
Content-Id:<reportmainlogo.gif>\n" .
"\n" .
chunk_split( base64_encode($data), 68, "\n");

$msg_body ="\n"."--" . $boundary . "\n".$mail_body."\n";   /// $bodyopen
$msg_body .="--" . $boundary . "\n".$part_i;

$file = $report_endlogo;
$data = $file;
$part_r = "Content-Type: image/gif
Content-Transfer-Encoding: base64
Content-Disposition: attachment
Content-Location: reportendlogo.gif
Content-Id:<reportendlogo.gif>\n" .
"\n" .
chunk_split(base64_encode($data), 68, "\n");
$msg_body .="--" . $boundary . "\n".$part_r;

$file=$last_reporta;
$data = file_get_contents($file);
$part_r = "Content-Type: image/gif
Content-Transfer-Encoding: base64
Content-Disposition: attachment
Content-Location: last_reporta".$timestamp.".gif
Content-Id:<last_reporta".$timestamp.".gif>\n" .
"\n" .
chunk_split(base64_encode($data), 68, "\n");
$msg_body .="--" . $boundary . "\n".$part_r;

$file=$last_reporta_con;
$data = file_get_contents($file);  //last_reporta_con".$timestamp.".gif 
$part_r = "Content-Type: image/gif
Content-Transfer-Encoding: base64
Content-Disposition: attachment
Content-Location: last_reporta_con".$timestamp.".gif
Content-Id:<last_reporta_con".$timestamp.".gif>\n" .
"\n" .
chunk_split(base64_encode($data), 68, "\n");
$msg_body .="--" . $boundary . "\n".$part_r;

$file="/usr/local/httpd/htdocs/admin/images/green1x1.gif";
$data = file_get_contents($file);
$part_r = "Content-Type: image/gif
Content-Transfer-Encoding: base64
Content-Disposition: attachment
Content-Location: green1x1.gif
Content-Id:<green1x1.gif>\n" .
"\n" .
chunk_split(base64_encode($data), 68, "\n");
$msg_body .="--" . $boundary . "\n".$part_r;


$file="/usr/local/httpd/htdocs/admin/images/red1x1.gif";
$data = file_get_contents($file);
$part_r = "Content-Type: image/gif
Content-Transfer-Encoding: base64
Content-Disposition: attachment
Content-Location: red1x1.gif
Content-Id:<red1x1.gif>\n" .
"\n" .
chunk_split(base64_encode($data), 68, "\n");
$msg_body .="--" . $boundary . "\n".$part_r;


$file=$pik_24;
$data = file_get_contents($file);
$part_r = "Content-Type: image/gif
Content-Transfer-Encoding: base64
Content-Disposition: attachment
Content-Location: lastday24".$timestamp.".gif
Content-Id:<lastday24".$timestamp.".gif>\n" .
"\n" .
chunk_split( base64_encode($data), 68, "\n");
$msg_body .="--" . $boundary . "\n".$part_r;

$file=$pik_24con;
$data = file_get_contents($file);
$part_r = "Content-Type: image/gif
Content-Transfer-Encoding: base64
Content-Disposition: attachment
Content-Location: lastday24con".$timestamp.".gif
Content-Id:<lastday24con".$timestamp.".gif>\n" .
"\n" .
chunk_split( base64_encode($data), 68, "\n");
$msg_body .="--" . $boundary . "\n".$part_r;

$msg_body .="--" . $boundary.'--'."\n";

return $msg_body;
}

function extract_rcparm_file ($rcfile,$parm) {
    $rcdata = file($rcfile);
    $tmp=preg_grep("/^$parm=/",$rcdata);
    foreach ($tmp as $v)
    $t=explode("\"",$v);
    if (isset($t[1]))
    return $t[1];
    else
    return "";
}

function create24new($graph_type) {
    global $pik_24,$pik_24con;
    global $arr_lang,$yesterday;
    global $lang, $mode, $arr_DAYS;
    echo "\n Function call create24new($graph_type)\n";
    switch($graph_type) {
        case '1':
            $target_file = $pik_24;
            switch($mode) {
                case '1':
                    $groupby=1;
                    $start_date='24hours@1@1@1@1@1@1@1';
                    break;
                case '7':
                    $groupby=2;
                    $start_date='7day@1@1@1@1@1@1@1';
                    break;
            }
            break;
        case '2':
            $target_file = $pik_24con;
            switch($mode) {
                case '1':
                    $groupby=1;
                    $start_date='24hours@1@1@1@1@1@1@1';
                    break;
                case '7':
                    $groupby=2;
                    $start_date='7day@1@1@1@1@1@1@1';
                    break;
            }
            break;
    }
    $start_date_orig = $start_date;
    unlink($target_file);
    $origin=1;
    DEFINE("TTF_DIR","/usr/X11R6/lib/X11/fonts/");
    DEFINE("MBTTF_DIR","/usr/X11R6/lib/X11/fonts/");

    require_once ("/usr/local/httpd/htdocs/admin/jpgraph/src/jpgraph.php");
    require_once ("/usr/local/httpd/htdocs/admin/jpgraph/src/jpgraph_bar.php");
    require_once ("/usr/local/httpd/htdocs/admin/jpgraph/src/jpgraph_line.php");

    if ($lang=="jp" || $lang=="ru") {
        mb_http_output("pass");
        DEFINE("JPFONT",FF_MINCHO);
    } else
    DEFINE("JPFONT",FF_VERASERIF);

    $big_array['clean']['color'] = 'green';
    $big_array['clean']['label'] = $arr_lang["CLEAN"];
    $big_array['policy']['color'] = 'yellow';
    $big_array['policy']['label'] = $arr_lang["POLICY"];
    $big_array['virus']['color'] = 'red';
    $big_array['virus']['label'] = $arr_lang["VIRUS"];
    $big_array['spam']['color'] = 'darkgoldenrod1';
    $big_array['spam']['label'] = $arr_lang["SPAM"];
    $big_array['backscatter']['color'] = 'brown';
    $big_array['backscatter']['label'] = $arr_lang["BACKSCATTER"];
    $big_array['user_non_exist']['color'] = 'khaki3';
    $big_array['user_non_exist']['label'] = $arr_lang["USER_NOT_EXIST"];



    // cons_graph values
    $big_array['cons_passed']['color'] = 'green';
    $big_array['cons_passed']['label'] = $arr_lang["PASSED"];
    $big_array['rbl_cons']['color'] = 'lightslateblue';
    $big_array['rbl_cons']['label'] = $arr_lang["RBL"];
    $big_array['badmailfrom_cons']['color'] = 'lightsalmon';
    $big_array['badmailfrom_cons']['label'] = $arr_lang["POLICY_BADMAILFROM"];
    $big_array['badrcptto_cons']['color'] = 'lightcyan2';
    $big_array['badrcptto_cons']['label'] = $arr_lang["POLICY_BADRCPTTO"];
    $big_array['relay_denied']['color'] = 'magenta1';
    $big_array['relay_denied']['label'] = $arr_lang["POLICY_RELAY_DENIED"];
    $big_array['non_exdomain_cons']['color'] = 'mediumred';
    $big_array['non_exdomain_cons']['label'] = $arr_lang["POLICY_NON_EXDOMAIN"];
    $big_array['ratelimit_cons']['color'] = 'lightpink1';
    $big_array['ratelimit_cons']['label'] = $arr_lang["POLICY_RATELIMIT"];
    $big_array['fake_delay_cons']['color'] = 'ivory2';
    $big_array['fake_delay_cons']['label'] = $arr_lang["POLICY_FAKE_DELAY"];
    $big_array['iprep_cons']['color'] = 'khaki2';
    $big_array['iprep_cons']['label'] = $arr_lang["POLICY_IPREP"];
    $big_array['spoofing_denied_cons']['color'] = 'mistyrose';
    $big_array['spoofing_denied_cons']['label'] = $arr_lang["POLICY_SPOOFING_DENIED"];


    if (!isset($origin)) {
        $origin = 'in';
        $origin_title = $arr_lang["IN"];
    }


    $sql = getData($groupby,$origin,$start_date,$mode,$graph_type);
    //echo "\n\n\n $sql\n\n";

    $result = @pg_query ($sql);
    $num_rows = @pg_num_rows($result);

    //************ CREATING DATA-ARRAYS FOR GRAPHS  **********************************//
    switch($graph_type) {
        case '1':
            $param_indexes = array('clean','policy','virus','spam','backscatter','user_non_exist');
            for ($i=0; $i<$num_rows; $i++) {
                $row = pg_fetch_array($result, $i) ;
                $date[$i] = $row['date'];
                $total[$i] = $row['total'];
                $policy[$i] = $row['policy'];
                $virus[$i] = $row['virus'];

                $nonexist[$i] = $row['nonexist'];
                $spam[$i] = $row['spam'];
                $backscatter[$i] = $row['backscatter'];
                $clean[$i] =$row['total'] - $spam[$i] - $virus[$i] - $policy[$i] - $backscatter[$i] - $nonexist[$i];

                switch($groupby) {
                    case '1':
                        $labels[$i] = substr($row['date'],11,2);
                        $graph_title = $arr_lang["INCOMING_TRAFFIC_HOURLY_DETAILS_FOR"].' '.$yesterday;
                        break;
                    case '2':
                        $labels[$i] = substr($row['date'],5,5);
                        $graph_title =  $arr_lang["MAIL"]." $origin_title ".$arr_lang["FOR_LAST_7_DAYS"];
                }
            }


            if ($groupby != 1) {
                $start_date = $yesterday;
                $count = 7;
                for ($i=0; $i<$count; $i++) {
                    $temp = mktime(0, 0, 0, date("m")  , date("d")-$i-1, date("Y"));
                    $start_date = date("Y-m-d G:i:s",$temp);
                    $labels_date[$count-1-$i] = substr($start_date,0,10);
                    $labels[$count-1-$i] = $arr_DAYS[date("w",$temp)];
                }


                $j = 0;
                for ($i=0; $i<$count; $i++) {
                    if ($labels_date[$i] == substr($date[$j],0,10)) {
                        //$total[$i] = $total[$j];
                        $policy_temp[$i] = $policy[$j];
                        $virus_temp[$i] = $virus[$j];
                        $clean_temp[$i] =$clean[$j];
                        if ($origin == 'in' || $origin == 'all') {
                            $spam_temp[$i] = $spam[$j];
                            $nonexist_temp[$i] = $nonexist[$j];
                            $backscatter_temp[$i] = $backscatter[$j];
                        }
                        $j++;
                    } else {
                        $policy_temp[$i] = 0;
                        $virus_temp[$i] = 0;
                        $spam_temp[$i] = 0;
                        $nonexist_temp[$i] = 0;
                        $clean_temp[$i] = 0;
                        $backscatter_temp[$i] = 0;
                    }
                }
                $policy = $policy_temp;
                $virus = $virus_temp;
                $spam = $spam_temp;
                $clean = $clean_temp;
                $backscatter = $backscatter_temp;
                $nonexist = $nonexist_temp;
            }


            $big_array['clean']['data'] = $clean;
            $big_array['policy']['data'] = $policy;
            $big_array['virus']['data'] = $virus;
            $big_array['spam']['data'] = $spam;
            $big_array['backscatter']['data'] = $backscatter;
            $big_array['user_non_exist']['data'] = $nonexist;

            break;
        case '2':
            $param_indexes = array('cons_passed','rbl_cons','badmailfrom_cons','badrcptto_cons','relay_denied','non_exdomain_cons','ratelimit_cons','fake_delay_cons','iprep_cons','spoofing_denied_cons');

            for ($i=0; $i<$num_rows; $i++)
            {
                $row = pg_fetch_array($result, $i) ;
                $date[$i] = $row['date'];
                $cons_all[$i] = (isset($row['cons_all'])) ? $row['cons_all'] : 0;
                $rbl[$i] = isset($row['rbl_sum']) ? $row['rbl_sum'] : 0;
                $cpolicy_badmailfrom[$i] = isset($row['cpolicy_badmailfrom']) ? $row['cpolicy_badmailfrom'] : 0;
                $cpolicy_badrcptto[$i] = isset($row['cpolicy_badrcptto']) ? $row['cpolicy_badrcptto'] : 0;
                $cpolicy_relay_denied[$i] = isset($row['cpolicy_relay_denied']) ? $row['cpolicy_relay_denied'] : 0;
                $cpolicy_non_exdomain[$i] = isset($row['cpolicy_non_exdomain']) ? $row['cpolicy_non_exdomain'] : 0;
                $cpolicy_ratelimit[$i] = isset($row['cpolicy_ratelimit']) ? $row['cpolicy_ratelimit'] : 0;
                $cpolicy_fake_delay[$i] = isset($row['cpolicy_fake_delay']) ? $row['cpolicy_fake_delay'] : 0;
                $cpolicy_iprep[$i] = isset($row['cpolicy_iprep']) ? $row['cpolicy_iprep'] : 0;
                $cpolicy_spoofing_denied[$i] = isset($row['cpolicy_spoofing_denied']) ? $row['cpolicy_spoofing_denied'] : 0;


                $passed[$i] = $cons_all[$i] - $rbl[$i] - $cpolicy_badmailfrom[$i] - $cpolicy_badrcptto[$i] - $cpolicy_relay_denied[$i] - $cpolicy_non_exdomain[$i] - $cpolicy_ratelimit[$i] - $cpolicy_fake_delay[$i] - $cpolicy_iprep[$i] - $cpolicy_spoofing_denied[$i];
                switch($groupby)
                {
                    case '1':

                        $labels[$i] = substr($row['date'],11,2);
                        $graph_title = $arr_lang["CONNECTIONS"]." - ".$arr_lang["DETAILS"].' '.$yesterday;;
                        //$graph_title = $arr_lang["INCOMING_TRAFFIC_HOURLY_DETAILS_FOR"].' '.$yesterday;
                        break;
                    case '2':

                        $labels[$i] = substr($row['date'],5,5);
                        $graph_title =  $arr_lang["CONNECTIONS"]." - ".$arr_lang["DETAILS"].' '.$arr_lang["FOR_LAST_7_DAYS"];
                        break;

                }
            }

            if ($groupby == 2)
            {

                $count = 7;

                for ($i=0; $i<$count; $i++)
                {

                    $temp = mktime(0, 0, 0, date("m")  , date("d")-$i, date("Y"));

                    $start_date = date("Y-m-d G:i:s",$temp);
                    $labels_date[$count-1-$i] = substr($start_date,0,10);

                    $labels[$count-1-$i] = $arr_DAYS[date("w",$temp)];
                }

                $date_border = 10;


            }

            if ($groupby != 1)
            {
                $j = 0;
                for ($i=0; $i<$count; $i++)
                {
                    if ($labels_date[$i] == substr($date[$j],0,$date_border))
                    {
                        $rbl_temp[$i] = $rbl[$j];
                        $cpolicy_badmailfrom_temp[$i] = $cpolicy_badmailfrom[$j];
                        $cpolicy_badrcptto_temp[$i] = $cpolicy_badrcptto[$j];
                        $cpolicy_relay_denied_temp[$i] = $cpolicy_relay_denied[$j];
                        $cpolicy_non_exdomain_temp[$i] = $cpolicy_non_exdomain[$j];
                        $cpolicy_ratelimit_temp[$i] = $cpolicy_ratelimit[$j];
                        $cpolicy_fake_delay_temp[$i] = $cpolicy_fake_delay[$j];
                        $cpolicy_iprep_temp[$i] = $cpolicy_iprep[$j];
                        $cpolicy_spoofing_denied_temp[$i] = $cpolicy_spoofing_denied[$j];
                        $passed_temp[$i] = $passed[$j];

                        $j++;

                        //echo "<br>j=$j";
                    }
                    else
                    {
                        $rbl_temp[$i] = 0;
                        $cpolicy_badmailfrom_temp[$i] = 0;
                        $cpolicy_badrcptto_temp[$i] = 0;
                        $cpolicy_relay_denied_temp[$i] = 0;
                        $cpolicy_non_exdomain_temp[$i] = 0;
                        $cpolicy_ratelimit_temp[$i] = 0;
                        $cpolicy_fake_delay_temp[$i] = 0;
                        $cpolicy_iprep_temp[$i] = 0;
                        $cpolicy_spoofing_denied_temp[$i] = 0;
                        $passed_temp[$i] = 0;
                    }
                }

                $rbl = $rbl_temp;
                $cpolicy_badmailfrom = $cpolicy_badmailfrom_temp;
                $cpolicy_badrcptto = $cpolicy_badrcptto_temp;
                $cpolicy_relay_denied = $cpolicy_relay_denied_temp;
                $cpolicy_non_exdomain = $cpolicy_non_exdomain_temp;
                $cpolicy_ratelimit = $cpolicy_ratelimit_temp;
                $cpolicy_fake_delay = $cpolicy_fake_delay_temp;
                $cpolicy_iprep = $cpolicy_iprep_temp;
                $cpolicy_spoofing_denied = $cpolicy_spoofing_denied_temp;
                $passed = $passed_temp;
            }

            $big_array['cons_passed']['data'] = $passed;
            $big_array['rbl_cons']['data'] = $rbl;
            $big_array['badmailfrom_cons']['data'] = $cpolicy_badmailfrom;
            $big_array['badrcptto_cons']['data'] = $cpolicy_badrcptto;
            $big_array['relay_denied']['data'] = $cpolicy_relay_denied;
            $big_array['non_exdomain_cons']['data'] = $cpolicy_non_exdomain;
            $big_array['ratelimit_cons']['data'] = $cpolicy_ratelimit;
            $big_array['fake_delay_cons']['data'] = $cpolicy_fake_delay;
            $big_array['iprep_cons']['data'] = $cpolicy_iprep;
            $big_array['spoofing_denied_cons']['data'] = $cpolicy_spoofing_denied;
            break;
    }

if (array_sum($cons_all) == 0 && $graph_type == 2) {
        echo  "\n\n24 pik(connection) - NO DATA\n\n";
        exec("cp /usr/local/httpd/htdocs/admin/images/null.gif /tmp/lastday24con.gif"); 
        return(1) ;
}
// print_r($big_array);echo "<br><br>";
$params_on = $param_indexes;
//print_r($params_on);



//****************************  CREATING  GRAPHS  ********************************//

    if(!isset($date[0]) ) {

        echo  "\n\n24 pik - NO DATA\n\n";
        exec("cp /usr/local/httpd/htdocs/admin/images/null.gif /tmp/lastday24.gif");
		
    } else {
        //creating graph

        if ($graph_type == 1) {
            $graph = new Graph(700,420,"auto");
            $graph->img->SetMargin(60,40,60,110);
            $legend_height = 0.82;
        } else {
            $graph = new Graph(700,450,"auto");
            $graph->img->SetMargin(60,40,60,140);
            $legend_height = 0.78;
        }
        $graph->SetScale("textlin");
        $graph->SetMarginColor('white');

        // }




        $i = 0;
        $max_lenl = $max_lenr = 0;

        for ($x = 0; $x < count($params_on); $x++)
        if (array_sum($big_array[$params_on[$x]]['data'])>0) {
            $dplot[] = CreateLinePlot($big_array[$params_on[$x]]['data'],$big_array[$params_on[$x]]['color'],$big_array[$params_on[$x]]['label']);

            $tmp = $big_array[$params_on[$x]]['label']."azazazaz";

            if (fmod($i,2) == 0)
            {
                $cur = strlen($tmp);
                if ($max_lenl<$cur)
                $max_lenl = $cur;
            }
            else
            {
                $cur = strlen($tmp);
                if ($max_lenr<$cur)
                $max_lenr = $cur;
            }

        }
        $max_len = $max_lenr+$max_lenl;

        $accplot = new AccLinePlot($dplot);
        // Add the plot to the graph
        $graph->Add($accplot);

        //}

        // Create the bar plots
        for ($x = 0; $x < count($params_on); $x++)
        	if (array_sum($big_array[$params_on[$x]]['data'])>0)
        		$bplot[] = CreateBarPlot($big_array[$params_on[$x]]['data'],$start_date,$big_array[$params_on[$x]]['color'],$big_array[$params_on[$x]]['label']);

	     // Create the grouped bar plot
	     if (!is_array($bplot) || sizeof(@$bplot) == 0)
	     	return(1);
	     $gbplot = new AccBarPlot($bplot);
	
	     $gbplot->SetAbsWidth(0);
	
	     $graph->title->Set($graph_title);
	     $graph->xaxis->SetTickLabels($labels);
	     if ($lang=="jp" || $lang=="ru") {
	     	$graph->title->SetFont(JPFONT);
	     } else
	        $graph->title->SetFont(JPFONT,FS_BOLD);
	        
	     $graph->legend->SetFont(JPFONT);
	     $graph->xaxis->SetFont(JPFONT);
	     $graph->yaxis->SetFont(JPFONT);
	     $graph->xaxis->scale->ticks->SetXLabelOffset(9.5,14);
	
	     $graph->Add($gbplot);
	     //$graph->legend->Pos(0.1,0.78);
	     $tmp = (60-$max_len) * 0.01;
	     $graph->legend->Pos($tmp,$legend_height);
	
	     $graph->legend->SetLayout(LEGEND_SPESIAL);
	     $graph->img->SetImgFormat("gif") ;
	     $graph->img->SetQuality(100);
	     $graph->Stroke($target_file);
    }
}


function get_pred_color($file_css,$style)
{
    if (!is_file($file_css))
    return false;

    $lines = file($file_css);
    $color_search = 0;
    for ($i = 0; $i < count($lines); $i++)
    {
        if ($color_search == 0)
        if (preg_match("/.$style /",$lines[$i]))
        $color_search = 1;

        if ($color_search == 1)
        if (preg_match("/background-color/",$lines[$i]))
        {
            $tmp = explode("#",$lines[$i]);
            return (substr($tmp[1],0,6));
        }
    }
}

?>
