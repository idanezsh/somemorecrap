#!/bin/bash

# This script will delete all files olders than given in the
# command line.
# Copyright (c) 2001-2006, PineApp Ltd.


CURDIR=`pwd`
DIRHANDLE=$2
DAYS=$1
MASK=$3
TARGETDIR="/var/data/tmp/expired_messages/"

if [ ! "$MASK" ]; then
       echo "usage: expirationclean.sh <days> <directory> <mask>"
       exit 1
fi

cd $DIRHANDLE

for file in `find . -maxdepth 1 -mtime +$DAYS -name "$MASK" -prune -type f` ;
   do
      mv $file $TARGETDIR
   done


cd $CURDIR

