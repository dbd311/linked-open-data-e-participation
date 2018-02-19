#ssh to lod dk

ssh lod-dk

# check out from svn
# skip the .env file at the root folder because it contains information about the DBA user name and password.

# from outside
# using git svn
git svn clone https://webgate.ec.europa.eu/publications/svn/LODEPART/trunk/src/

# using svn
svn co --username={USERNAME} https://webgate.ec.europa.eu/publications/svn/LODEPART/trunk/src/
svn co --username={USERNAME} https://webgate.ec.europa.eu/publications/svn/LODEPART/branches/front-end/

# from inside, lod-dk
svn co --username={USERNAME} http://svn-int.opoce.cec.eu.int:8080/publications/svn/LODEPART/trunk/src/


# update
svn update


#To deploy a Laravel website:
sush lod
cd laravel/lod/src

# do a first migration and seeding (only run this if you are sure that you can qran up the whole database)

php artisan migrate:refresh --seed

# refresh laravel (generate autoload file)
sudo composer dumpautoload

# refresh everything (clean up) in database
php artisan migrate:refresh --seed


# to deploy the website
php artisan serve --host=lod-dk --port=8000

# install Elastic search
#apt-get install elasticsearch
# Manually download ElasticSearch at https://www.elastic.co/downloads/elasticsearch
# install it using .deb file


# Virtuoso configuration

http://localhost:8890/conductor/
http://localhost:8890/sparql

using ISQL (interactive)
=======================================================
SPARQL

clear graph <http://lodepart/graph>


#------------------------------
To clean up Virtuoso

RDF_GLOBAL_RESET();

=======================================================

# temporarily set rights for SPARQL

System admin -> User accounts -> SPARQL -> Edit grants (SELECT,INSERT,UPDATE)

# on the command line iSQL
grant execute on SPARQL_INSERT_DICT_CONTENT to "SPARQL";
grant execute on DB.DBA.L_O_LOOK TO "SPARQL"; 
grant execute on DB.DBA.SPARQL_DELETE_DICT_CONTENT to "SPARQL";


# To refresh the website after several updates ... 
sudo composer dumpautoload

# To run the migration and seeding for Users and Nationalities Table:

php artisan db:seed class=UserTableSeeder
php artisan db:seed class=NationalitiesTableSeeder



# install git svn
sudo apt-get install git-svn

git svn clone https://webgate.ec.europa.eu/publications/svn/LODEPART/trunk/src/


# Clone the collection for a different environment
bash setup/clone-collection.sh /home/duy/NetBeansProjects/lodepart/src/public/collection/lod-dk-formex-documents  "localhost" "lod-dk"

# replace a string in files / folder
sed -i -e 's/class="media-object" src/class="media-object" ng-src/g' /*/*.html
