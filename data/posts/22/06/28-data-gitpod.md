# GitPod

I waited for this day too long. Today, I've tried out [GitPod](https://www.gitpod.io/), and made it work for a project based on Osm Admin. 

Step by step: 

{{ toc }}

### meta.abstract

I waited for this day too long. Today, I've tried out *GitPod*, and made it work for a project based on Osm Admin.

## Getting Started

Let's start with some project based on Osm Admin, [`project1`](https://github.com/osmianski/project1). 

1. Download [JetBrains Gateway](https://www.jetbrains.com/remote-development/gateway/) and extract to `~/JetBrainsGateway-222.3153.1/`. Open its `bin/` directory in terminal and run `./gateway.sh`
2. On JetBrains Gateway welcome screen, install GitPod provider.
3. Select PhpStorm on the [GitPod Preferences](https://gitpod.io/preferences) page.
4. In JetBrains Gateway, connect to GitPod, select PhpStorm in the first dropdown field, paste GitHub Repo URL into the second input field, and press the New Workspace button.
5. Wait while it downloads and opens JetBrains Client with your project in it.

## What's Going On

The whole process looked a bit of a mystery to me, but if you think about it, it's not.

First, I created a *workspace* for my project. Simply put, a workspace is a virtual development machine hosted on a GitPod server. Workspace comes with preinstalled Ubuntu 20.04, PhpStorm, project files located in the `/workspace/project1` directory, the latest PHP, Composer and other useful tools.

Then, I opened JetBrains Client. This application is a thin UI layer for the PhpStorm that runs on the remote workspace. Every command I choose in the local Client is sent and executed on the remote PhpStorm, and its results are displayed back in the local Client.

Summing up: I've just got a remote development machine with my project on it, and a Client application displaying PhpStorm running there. 

## Running The Project

Out of the box, the workspace allows editing project files. It's nice, but not enough. I also want to run and debug the project there.

Let's refer to the [`README`](https://github.com/osmphp/admin) - the project installation should run automatically. Currently, it won't work. For example, `bin/install.sh` script fails as Gulp is not installed.   

After lots of back and forth, I've ended up with the following: 

1. Create `.gitpod.yml` that introduces custom Docker configuration file:

        image:
          file: .gitpod.dockerfile
        
        tasks:
          - name: Install And Run
            before: |
              npm install -g gulp-cli
            init: |
              mysql -u root -e "create database osm"
              bin/create-env.sh
              sed -i "s|MYSQL_USERNAME=\\.\\.\\.|MYSQL_USERNAME=root|g" .env.Osm_App
              sed -i "s|MYSQL_PASSWORD=\\.\\.\\.|MYSQL_PASSWORD=|g" .env.Osm_App
              sed -i "s|NAME=\\.\\.\\.|NAME=osm|g" .env.Osm_App
              echo "HTTPS=true" >> .env.Osm_App
              bin/install.sh
              php vendor/osmphp/core/bin/hint.php
            command: |
              sudo service elasticsearch start
              php -S 0.0.0.0:8000 -t public/Osm_App public/Osm_App/router.php
        
        ports:
          - name: Web App
            port: 8000
            onOpen: open-browser
          - name: Mysql
            port: 3306
            visibility: private
          - name: ElasticSearch
            port: 9200
            visibility: private
          - name: ElasticSearch Admin
            port: 9300
            visibility: private
        
        jetbrains:
          phpstorm:
            prebuilds:
              version: stable
 
2. Install Gulp CLI in the Docker configuration:

        FROM gitpod/workspace-mysql
        
        RUN curl -fsSL https://artifacts.elastic.co/GPG-KEY-elasticsearch | \
            sudo apt-key add -
        RUN echo "deb https://artifacts.elastic.co/packages/7.x/apt stable main" | \
            sudo tee -a /etc/apt/sources.list.d/elastic-7.x.list
        RUN sudo add-apt-repository ppa:ondrej/php
        RUN sudo apt update
        RUN sudo apt install -y elasticsearch php7.4-xdebug php8.1-xdebug
        
        RUN echo "alias osmc='php vendor/osmphp/core/bin/compile.php'"  >> $HOME/.bashrc
        RUN echo "alias osmh='php vendor/osmphp/core/bin/hint.php'"  >> $HOME/.bashrc
        RUN echo "alias osmt='php vendor/osmphp/framework/bin/tools.php'"  >> $HOME/.bashrc
        RUN echo "alias osm='php vendor/osmphp/framework/bin/console.php'"  >> $HOME/.bashrc
        
        RUN sudo echo "xdebug.mode=debug" | sudo tee -a /etc/php/8.1/mods-available/xdebug.ini
        RUN sudo echo "xdebug.discover_client_host = 1" | sudo tee -a /etc/php/8.1/mods-available/xdebug.ini
        RUN sudo echo "xdebug.client_port = 9000" | sudo tee -a /etc/php/8.1/mods-available/xdebug.ini
        RUN sudo echo "xdebug.max_nesting_level = 500" | sudo tee -a /etc/php/8.1/mods-available/xdebug.ini