#!/bin/bash

###########
# clone project: zip project and mysqldump
###########

# set variables
source "/srv/www/services/prosty/bash/config.sh"
project_id="${1}"
folderToZip=$(basename ${2})
cwdToFolder=$(dirname ${2})
dbname=${3}
error=0

pathTemp="/srv/www/services/prosty/temp/"
tarFile="${pathTemp}${project_id}.tar"
sqlFile="${pathTemp}export.sql"

rm ${sqlFile}
rm ${tarFile}

# create mysqldump
dump=$(mysqldump -u $SQLUser -p$SQLPass $dbname)

# if successfully opened database
if [ $? != 0 ]; then {
	error=1	
} else {
	# write dump to file
	echo $dump > $sqlFile		
} fi
	
# create tar archive with web files and mysqldump
tar --create --file=${tarFile} -C ${cwdToFolder} ${folderToZip} -C ${pathTemp} export.sql

if [ $? != 0 ]; then {
	error=1
} fi
	
if [ $error == 0 ]; then {
	echo "success"
} fi




# debugging tar
#echo "project_id: ${project_id}"
#echo "Tarfile: ${tarFile}"
#echo "folderToZip: ${folderToZip}"
#echo "cwdToFolder: ${cwdToFolder}"

# debugging sql
#echo "mysqldump -u ${SQLUser} -p${SQLPass} ${dbname} > ${sqlFile}"
#echo "dbname: ${dbname}"
