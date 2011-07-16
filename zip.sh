#!/bin/bash
pass="heyzan"
dbname="${1}"
path=${2}

# get last folder of path
lastFolder=$(basename $path)

# get path without last folder
pathWithoutLastFolder=${path%$lastFolder}

# debugging
#echo $lastFolder
#echo  $pathWithoutLastFolder

#sleep 10;

rm ./temp/export.sql
rm ./temp/folder.tar

# create mysql export and 
mysqldump -u root -p${pass} ${dbname} > ./temp/export.sql
tar -C /srv/www/${pathWithoutLastFolder} -cPf ./temp/folder.tar ${lastFolder}
tar -C ./temp/ --append --file=./temp/folder.tar export.sql


