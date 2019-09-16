# bienfpbx </br>
!Bien addition to the FusionPBX project </br>
</br>
#the directory bienfpbx is an addiition to the fusionpbx directory and holds the Bien proviosioning and application files </br>
</br>
#First clone the repository to the /var/www directory </br>
cd /var/www </br>
git clone https://github.com/willempoort/bienfpbx.git </br>
</br>
#Make sure the rights are correctly set </br> 
chown -R www-data.www-data /var/www/bienfpbx </br>
</br>
#Make symbolic links from the fusionpbx directory to these directories </br>
ln -s /var/www/bienfpbx/app/bien /var/www/fusionpbx/app/bien </br>
ln -s /var/www/bienfpbx/provision/bien /var/www/fusionpbx/resources/templates/provision/bien </br>
</br>
chown www-data.www-data /var/www/fusionpbx/app/bien </br>
chown www-data.www-data /var/www/fusionpbx/resources/templates/provision/bien </br>
</br>
