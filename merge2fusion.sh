#set user and owner to www-data
chown -R www-data.www-data /var/www/bienfpbx

#make the symbolic links from the fusionpbx directory to these bien directories
ln -s /var/www/bienfpbx/app/bien /var/www/fusionpbx/app/bien
ln -s /var/www/bienfpbx/provision/bien /var/www/fusionpbx/resources/templates/provision/bien

#set user and owner to www-data
chown www-data.www-data /var/www/fusionpbx/app/bien
chown www-data.www-data /var/www/fusionpbx/resources/templates/provision/bien
