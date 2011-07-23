#!/bin/bash

###########
# clone project: zip project and mysqldump
###########

# set variables
source "/srv/www/services/prosty/bash/config.sh"
projectName="${1}"
pathProject=${2}
dbname=${3}

pathTemp="/srv/www/services/prosty/temp/"
tarFile="${pathTemp}${projectName}.tar"
sqlFile="${pathTemp}export.sql"

# debugging
#echo "projectName: ${projectName}"
#echo "pathProject: ${pathProject}"
#echo "dbname: ${dbname}"

rm ${sqlFile}
rm ${tarFile}

# create tar archive with web files 
tar -C /srv/www -cpf ${tarFile} ${pathProject}

# create mysqldump and add to archive
mysqldump -u root -p${pass} ${dbname} > ${sqlFile}
tar -C ${pathTemp} --append --file=${tarFile} $(basename $sqlFile)
