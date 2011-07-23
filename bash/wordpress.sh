#!/bin/bash

# set variables
projectName=${1}
pathDev="/srv/www/${projectName}/dev"
pathTemp="/srv/www/services/prosty/temp/"
pathWPDefaults="/srv/www/services/wp-defaults/"
wpFile="${pathTemp}latest.tar.gz"

# remove old wordpress installations
rm ${wpFile}

# download latest wordpress
wget -P ${pathTemp} http://wordpress.org/latest.tar.gz

# untar wordpress to dev
tar -C ${pathDev} -zxf ${wpFile} --strip 1

# add default wp files to dev
cd ${pathWPDefaults} && git pull konscript master
rsync -az ${pathWPDefaults} ${pathDev} --exclude '.git'

# do initial commit
cd ${pathDev} && git add -A && git commit -m 'Automatic initial commit from Caesar'
cd ${pathDev} && git push konscript master
