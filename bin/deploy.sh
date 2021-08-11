#!/bin/bash

git pull
php vendor/osmphp/framework/bin/console.php index

# full update

#osm http:down
#git pull
#composer install
#osmc Osm_Tools
#osmt config:npm
#npm install
#gulp
#osm migrate:up
#osm index
#osm http:down