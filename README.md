# bienfpbx
!Bien addition to the FusionPBX project

#the directory bienfpbx is an addiition to the fusionpbx directory and holds the Bien 
#proviosioning and application files
#
#First clone the repository to the /var/www directory
cd /var/www
git clone ssh://github.com/willempoort/bienfpbx

#Make sure the rights are correctly set
chown -R www-data.www-data /var/www/bienfpbx

#Make symbolic links from the fusionpbx directory to these directories
ln -s /var/www/bienfpbx/app/bien /var/www/fusionpbx/app/bien
ln -s /var/www/bienfpbx/provision/bien /var/www/fusionpbx/resources/templates/provision/bien

chown www-data.www-data /var/www/fusionpbx/app/bien
chown www-data.www-data /var/www/fusionpbx/resources/templates/provision/bien
root@fpbx:/var/www# 

