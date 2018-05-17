#!/bin/bash
#SER_EXT=$1
#SER_FILETYPE=$2
#SER_GRP=$3

DBselect='psql secure postgres -t -c'
DBinsert='psql secure postgres -q -c'

display_help() {
    echo "Usage: $0 [option...]" >&2
    echo
    echo "   -h, show this help"
    echo "   -e, chose extenstion for *.exe write : -e exe "
    echo "   -f, Enter Filetype in '' : -f 'MS Dos Executable' "
    echo "   -g, (optional)  chose group number from table policy.ext_groups: -g 1 "
    echo "   -m, (optional)  checkmimetype for file in FTP"
    echo
    echo "   Group list Below:"
    echo 
    echo
    $DBselect  "select * from policy.ext_groups;"
    exit 1
}

[ -z "$1" ] && {
	echo "-h for help"
	exit 0
}

[ "$1" = "-h" ] && {
	display_help
	exit 0
}



#heck_md5sum(){
#md5sum * > /tmp/workingdir/md5
#cat /tmp/workingdir/md5 | cut -d ' ' -f1
#insert into log.hash (md5,filename) VALUES ('f529d7462e52a11134c524ef685817f7','На пароле.docx');
	
	
#




get_file_mime(){
        mkdir -p /tmp/workingdir
        cd /tmp/workingdir
        wget ftp://qatest:12345@192.168.7.2:21/*

        for f in `ls`; do
		md5_hash=`md5sum $f | cut -d ' ' -f1`
		filetype=`file $f | cut -d ',' -f1 | cut -d ':' -f2 |  sed -e 's/^[[:space:]]*//'`
		echo -n "filename: "$f"			"
		echo "filetype:"" '"$filetype"'"
	       	$DBinsert "insert into log.hash (md5,filename,filetype) VALUES ('$md5_hash','$f','$filetype')"
        done

        rm -rf /tmp/workingdir

}



[ "$1" = "-m" ] && {
	get_file_mime
	exit 0
}

while getopts e:f:g: option
do
 case "${option}"
	 in
	 e) USER_EXT=${OPTARG};;
	 f) USER_FILETYPE=${OPTARG};;
	 g) USER_GRP=${OPTARG};;
	 *) echo "press -h for help"; exit 1 ;;
 esac
done


#echo "write extension (example for *.jpg write jpg ):"
#read USER_EXT

#echo "write filetype (run: file command on the file and copy everything up to the 1st comma:"
#read USER_FILETYPE

#echo "to what group to add? (number from chose number)"
#$DBselect "select * from policy.ext_groups;"
#read USER_GRP

check_if_exists(){
	results=`$DBselect "select count(type) from policy.file_types where type='$USER_FILETYPE'"`;
	if [[ "$results" -gt 0 ]]; then
		ret=1
		echo "already exists"
	else 
		ret=0
		echo "no results"
	fi
	
}


add_to_db(){
	echo "adding to DB EXT:'$USER_EXT' with filetype:'$USER_FILETYPE' group:$USER_GRP"
	
	$DBinsert "insert into policy.file_types (type) VALUES ('$USER_FILETYPE')";

	ftid=`$DBselect "select last_value from policy.file_types_ftid_seq";`
	$DBinsert "insert into policy.extensions (name,description,ftid) VALUES ('*.$USER_EXT','$USER_FILETYPE',$ftid)";


	eid=`$DBselect "select last_value from policy.extensions_eid_seq ";`
	$DBinsert "insert into policy.ext_to_grp (eid,gid) VALUES ($eid,$USER_GRP)";

	echo "done"
}



add_to_db_wo_grp(){
        echo "adding to DB EXT:'$USER_EXT' with filetype:'$USER_FILETYPE'"

        $DBinsert "insert into policy.file_types (type) VALUES ('$USER_FILETYPE')";

        ftid=`$DBselect "select last_value from policy.file_types_ftid_seq";`
        $DBinsert "insert into policy.extensions (name,description,ftid) VALUES ('*.$USER_EXT','$USER_FILETYPE',$ftid)";

        echo "done"
}
							


#Actually doing something here:
check_if_exists
	if [ "$ret" = "0" ]; then
		[ -z "$USER_GRP" ] && {
			add_to_db_wo_grp
		}
		[ -n "$USER_GRP" ] && {
	 	      	add_to_db
		}
	
	else
		echo "FileType Already in DB"
		$DBselect "select * from policy.file_types where type='$USER_FILETYPE'" || exit 5
		
	fi
