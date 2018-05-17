#!/bin/bash

process=`ps auxx | grep  scp_check.sh | grep -v grep | wc -l`
if [ $process -gt 2 ] ; then
        exit 1
fi

echo -n > /tmp/scp_check.tmp
DATE=date

sql=`psql -d secure -U postgres -t -c "select host_ip from lb.vipn_details as L left join firewall.fw_hosts as F on L.ip::integer = F.host_id::integer"`;
echo -e "$sql" > /tmp/scp_ip_list.tmp

cat /tmp/scp_ip_list.tmp | while read -r IP; do
	echo -e "$DATE" > /tmp/$IP.tmp
	chown qmailq:qmail /tmp/$IP.tmp
	scp -P7022 -o ConnectTimeout=5 /tmp/$IP.tmp root@$IP:/tmp 1>/dev/null 2>/dev/null
	echo $?" - $IP" >> /tmp/scp_check.tmp
done

