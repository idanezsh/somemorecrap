#!/usr/bin/php
<?
// $Id: paquarclean.php,v 1.13 2018/02/05 09:39:03 idan Exp $

$debug=100;

function getparm($file, $parm) {
	$line=shell_exec("grep '^$parm=' $file | tail -n 1");
	$prm=preg_replace("/.*=\"/","",$line);
	$prm=preg_replace("/\".*/","",$prm);
	return chop($prm);
}

function addZeros($number,$zeros_upto,$suffix = false) {
	$str = '';
	for ($i=0;$i<($zeros_upto-strlen($number));$i++) {
		$str .= '0';
	}
	if ($suffix) {
		$str = $number.$str;
	}
	else {
		$str .= $number;
	}
	return $str;
}

function q_file_name($update_at, $msgid) {
	$time = preg_replace("!(\d+).(\d+).(\d+)\s(\d+).+!","\$1\$2\$3\$4",$update_at);
	$filename = $time."/999.".addZeros($msgid,17);
	return $filename;
}

// main()

$pg_conn = @pg_connect("dbname=secure user=postgres password=V1ew5oni(") or die ("Unable connect to PG DB\n"); //Open connection

$action ="= 2"; // 0 => Passed, 2 => Blocked , 3 => Deleted 
$quarantine_all=strtoupper(getparm("/etc/rc.pineapp/rc.system","QUARANTINE_ALL"));
if ($quarantine_all == 'YES') $action ="!= 3";


// What to clean according to day expiry (easiest)
$query="SELECT a.msgid,extract(day from now()-max(a.entry_time)) as daysold,max(e.days_to_keep) as maxdk,max(e.max_size),max(a.entry_time) as upat 
	FROM log.msg_info as a
	LEFT JOIN log.msg_fate as b using (msgid) 
	LEFT JOIN log.msg_fate_recipients as c using (grp_fate_id)
	LEFT JOIN pineapp.zones as d using (zone_id)
	LEFT JOIN pineapp.zones_quarantine as e using (zone_id)
	WHERE d.zone_type=1 AND b.action != 3
	GROUP BY (a.msgid) 
	ORDER by msgid";

echo "Getting list of messages to delete...";
$result=pg_query($query);

// this check is not relevant since we have padirector_sync now
/*if (!pg_num_rows($result)) {
	pg_free_result($result);
	pg_close($pg_conn);
	$cmode=strtoupper(getparm("/etc/rc.pineapp/rc.scanner","CLUSTER_MODE"));

	$director_str='';
	if ($cmode == 'YES') {
		$c_ip=strtoupper(getparm("/etc/rc.pineapp/rc.system","CLUSTER_DIRECTOR"));
		if ($c_ip) {
			echo "Director mode; working with director: ".$c_ip."\n";
			$director_str="host =".$c_ip;
		}
	}
	$pg_conn = @pg_connect($director_str." dbname=secure user=postgres password=V1ew5oni(") or die ("Unable connect to PG DB\n"); //Open connection
	$result=pg_query($query);
}*/
if (!pg_num_rows($result)) 
	die("Unable to fetch results from db. Aborted!");

echo "\nDeleting expired messages...";
$my_maxdk=0;
$deleted=0;
$kept=0;
if (pg_num_rows($result)>0) {
	$msg=array();
	$query="START TRANSACTION";
	$r=pg_query($query);
	$tsize=0;
	while ($row = pg_fetch_array($result)) {
		if ($debug>10) echo $row['msgid'].": ";
		if ($row['daysold']>$row['maxdk']) {
			//$msg[$row['id']]='d';
			$filename=q_file_name($row['upat'],$row['msgid']);
			@unlink ("/var/data/quarantine/".$filename);
			@unlink ("/var/data/quarantine/".$filename.".env");
			if ($debug>10) echo " $filename Deleted.\n";
			$tsize++;
			$query="UPDATE log.msg_fate SET action=3
				WHERE grp_fate_id IN 
				( SELECT grp_fate_id FROM log.msg_fate WHERE msgid=".$row['msgid']." );"; 
			$r=pg_query($query);
			$query="UPDATE log.msg_fate_recipients SET last_action=3
				WHERE grp_fate_id IN 
				( SELECT grp_fate_id FROM log.msg_fate WHERE msgid=".$row['msgid']." );"; 
			$r=pg_query($query);
			$deleted++;
			if ($tsize==100) { 
				echo "+";
				$tsize=0;
				$query="COMMIT";
				$r=pg_query($query);
				$query="START TRANSACTION";
				$r=pg_query($query);
			}
		} else {
			//$msg[$row['id']]='k';
			if ($debug>10) echo "Keep\n";
			$kept++;
		}	
		if ($my_maxdk<$row['maxdk'])
			$my_maxdk=$row['maxdk'];
	}
	echo "Deleted: ".$deleted.", Kept: ".$kept."\n";
	echo "Commiting update of DB...";
	$query="COMMIT";
	$r=pg_query($query);
	echo "\n";
}
if ($my_maxdk==0) {
	$my_maxdk = 14;
}
echo "Done.\nGlobal maximum days to keep: ".$my_maxdk."\n";
chdir ("/var/data/quarantine");
echo "Getting quarantine directory structure...";
$dirs=explode("\n",shell_exec("/bin/ls -tr"));
echo "\nCleaning outdated directories...\n";
$keepdir=date("Ymd",mktime(0, 0, 0, date("m")  , date("d")-$my_maxdk, date("Y")));
foreach ($dirs as $dir) 
	if ($dir && is_dir("/var/data/quarantine/".$dir)) {
		$datedir=substr ($dir,0,8);
		if ($datedir<$keepdir) {
			echo $dir.": Deleting...";
			system ("/bin/rm -rf /var/data/quarantine/$dir");
			echo "Done\n";
		} else
			if ($debug>10) echo $dir.": Skipping\n";
	}
echo "Clean outdated directories done.\n";

//--------------- Check zones now ---------------
echo "Checking Zone sizes...\n";
$global_exc=0;
$zones=array();
$zones_ex=array();
$zones_ok=array();
$query="select zone_id,max_size from pineapp.zones_quarantine;";
if ($debug) echo "q=".$query."\n";
$res=pg_query($query);
if (pg_num_rows($res)>0) {
	while ($r = pg_fetch_array($res)) {
		$query="
			select zone_id,sum(size) from ( 
				select distinct(msgid),size,zone_id,max_size from log.msg_info as a 
				LEFT JOIN log.msg_fate as b using (msgid) 
				LEFT JOIN log.msg_fate_recipients as c using (grp_fate_id)  
				LEFT JOIN pineapp.zones as d using (zone_id)  
				LEFT JOIN pineapp.zones_quarantine as e using (zone_id) 
				WHERE d.zone_type=1 AND b.action ".$action." and zone_id=".$r['zone_id']."
			) as qc group by zone_id;";
		if ($debug) echo "q=".$query."\n";
		$result=pg_query($query);
		while ($row = pg_fetch_array($result)) {
			echo "\nZone ".$row['zone_id'].": (",number_format(round($row['sum'] / 1024)),"KB/",number_format($r['max_size']),"KB): ";
			$exceed=false;
			$zones[$row['zone_id']]['esum']=$row['sum'];
			$zones[$row['zone_id']]['max']=$r['max_size']*1024;
			$zones[$row['zone_id']]['sum']=0;
			if ($row['sum']>($r['max_size']*1024)) {
				echo "Size exceeds, needs clean...\n";
				$exceed=true;
				$global_exc++;
				$zones_ex[$row['zone_id']]=$row['zone_id'];
			} else {
				$zones_ok[$row['zone_id']]=$row['zone_id'];
				echo "Size OK.\n";
			}
		}
	}
}
if (sizeof($zones_ex)>0) {
	if (sizeof($zones_ok)>0) {
		$query_filter=" AND c.zone_id IN (";
		foreach ($zones_ok as $zone_ok)
			$query_filter.=$zone_ok.",";
		$query_filter=preg_replace('/,$/',")",$query_filter);
		echo "\nRecalculating Zones...";
		$query="select zone_id,sum(size),max(maxsize) as maxsize from (
			select distinct(msgid),zone_id,a.size,e.max_size as maxsize from log.msg_info as a 
			LEFT JOIN log.msg_fate as b using (msgid) 
			LEFT JOIN log.msg_fate_recipients as c using (grp_fate_id) 
			LEFT JOIN pineapp.zones as d using (zone_id) 
			LEFT JOIN pineapp.zones_quarantine as e using (zone_id) 
			WHERE d.zone_type=1 AND b.action ".$action."  
			EXCEPT select distinct(msgid),zone_id,a.size,e.max_size as maxsize from log.msg_info as a 
			LEFT JOIN log.msg_fate as b using (msgid) 
			LEFT JOIN log.msg_fate_recipients as c using (grp_fate_id) 
			LEFT JOIN pineapp.zones as d using (zone_id) 
			LEFT JOIN pineapp.zones_quarantine as e using (zone_id) 
			WHERE d.zone_type=1 AND b.action ".$action." ".$query_filter."
		) as qc group by zone_id;";
		if ($debug) echo "q=".$query."\n";
		$result=pg_query($query);
		echo "Done\n";
		while ($row = pg_fetch_array($result)) {
			echo "Rechecking Zone ".$row['zone_id'].": (",number_format(round($row['sum'] / 1024)),"KB/",number_format($row['maxsize']),"KB): ";
			if ($row['sum']>($row['maxsize']*1024)) {
				echo "Size exceeds, needs clean...\n";
				$exceed=true;
				$zones_ex[$row['zone_id']]=$row['zone_id'];
				$zones[$row['zone_id']]['esum']=$row['sum'];
			} else {
				$global_exc--;
				$zones_ok[$row['zone_id']]=$row['zone_id'];
				unset($zones_ex[$row['zone_id']]);
				echo "Size OK.\n";
			}
		}
		$query_filter="
			EXCEPT select distinct(msgid),zone_id,a.size,e.max_size as maxsize, entry_time from log.msg_info as a 
			LEFT JOIN log.msg_fate as b using (msgid) 
			LEFT JOIN log.msg_fate_recipients as c using (grp_fate_id) 
			LEFT JOIN pineapp.zones as d using (zone_id) 
			LEFT JOIN pineapp.zones_quarantine as e using (zone_id) 
			WHERE d.zone_type=1 AND b.action ".$action." ".$query_filter;
	} else
		$query_filter="";
}
foreach ($zones_ex as $zone) {
	$query="select msgid,size ,entry_time from ( 
		select  distinct(msgid),zone_id,a.size,e.max_size as maxsize,entry_time from log.msg_info as a
		LEFT JOIN log.msg_fate as b using (msgid) 
		LEFT JOIN log.msg_fate_recipients as c using (grp_fate_id) 
		LEFT JOIN pineapp.zones as d using (zone_id) 
		LEFT JOIN pineapp.zones_quarantine as e using (zone_id) 
		WHERE d.zone_type=1 AND b.action ".$action." and c.zone_id=".$zone.$query_filter."
	) as qc order by msgid;";
	if ($debug) echo "q=".$query."\n";
	$result=pg_query($query);
	$deleted=0;
	$tsize=0;
	$query="START TRANSACTION";
	$r=pg_query($query);
	echo "Cleaning Zone ".$zone.": ";
	while (($row = pg_fetch_array($result)) && $zones[$zone]['esum']>$zones[$zone]['max']) {
		$deleted++;
		$zones[$zone]['esum']-=$row['size'];
		$filename=q_file_name($row['entry_time'],$row['msgid']);
		@unlink ("/var/data/quarantine/".$filename);
		@unlink ("/var/data/quarantine/".$filename.".env");
		if ($debug>10) echo " $filename Deleted.\n";
		$query="UPDATE log.msg_fate SET action = 3
			WHERE grp_fate_id IN  
			( SELECT grp_fate_id FROM log.msg_fate WHERE msgid=".$row['msgid']." );"; 
		//echo $query."\n";
		$r=pg_query($query);
		$tsize++;
		if ($tsize==100) { 
			echo "+";
			$tsize=0;
			$query="COMMIT";
			$r=pg_query($query);
			$query="START TRANSACTION";
			$r=pg_query($query);
		}
	}
	$query="COMMIT";
	$r=pg_query($query);
	echo "Done, Deleted: ".$deleted." - New Size: ".number_format(round($zones[$zone]['esum'] / 1024)),"KB\n";
}
?>