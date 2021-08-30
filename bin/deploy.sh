#!/bin/bash

# `osm`, `osmt` and other Bash aliases don't work in Bash files.
# The following variables are used instead of these Bash aliases
OSM="php vendor/osmphp/framework/bin/console.php"
OSMC="php vendor/osmphp/core/bin/compile.php"
OSMT="php vendor/osmphp/framework/bin/tools.php"

# Current Git branch
BRANCH=$(git rev-parse --abbrev-ref HEAD)

#git fetch

# Added, modified and deleted files overall and outside the `/data` directory
ALL_CHANGES=$(git diff --name-only $BRANCH..origin/$BRANCH)
CODE_CHANGES=$(git diff --name-only $BRANCH..origin/$BRANCH :^data/)

# If there are no code changes, update data files and the index, otherwise,
# do full update
if [[ $ALL_CHANGES ]] && ! [[ $CODE_CHANGES ]]; then
    git merge origin/$BRANCH
    $OSM index
    echo "DATA UPDATED!"
else
    $OSM http:down
    git merge origin/$BRANCH
    composer install
    $OSMC Osm_Tools
    $OSMT config:npm
    npm install
    gulp
    $OSM migrate:up
    $OSM index
    $OSM http:up
    echo "APP UPDATED!"
fi
