# Project Structure

The project repository, [`osmphp/admin`](https://github.com/osmphp/admin), follows a typical Osm Framework-based [project structure](https://osm.software/docs/framework/getting-started/project-structure.html). However, this repository is going to be a reusable Composer package, and has important structural differences presented in this article. 

More details:

{{ toc }}

### meta.abstract

The project repository, *`osmphp/admin`*, follows a typical Osm Framework-based *project structure*. However, this repository is going to be a reusable Composer package, and has important structural differences presented in this article. 

## Project Namespace (`Osm\Admin`)

By default, project modules are defined under `My` namespace. In this project, the root project namespace is `Osm\Admin`. It's configured in the `composer.json`:

    ...
    "autoload": {
        "psr-4": {
            "Osm\\Admin\\": "src/",
            "Osm\\Admin\\Tools\\": "tools/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Osm\\Admin\\Tests\\": "tests/",
            "Osm\\Admin\\TestsMigrations\\": "tests_migrations/",
            "Osm\\Admin\\Samples\\": "samples/"
        }
    }
    ...
    
## Reusable Modules (`src/`)

The `src/` directory contains reusable modules for any data-intensive application.

These modules are not included into any application. You can require them individually, or require `Osm\Admin\All\Module` module that requires them all.

## Sample Application (`samples/`)

In the `samples/` directory, I create a hypothetical e-commerce application. This sample application `Osm_Admin_Samples`, is executed in unit tests. 

## Viewing Sample Application In Browser

The sample application defines the HTTP entry point, `public/Osm_Admin_Samples/index.php`. Execute it under the native PHP Web server:

    php -S 0.0.0.0:8000 -t public/Osm_Admin_Samples public/Osm_Admin_Samples/router.php
    
Alternatively, put it under Nginx (mine configuration below):

    server {
        listen 127.0.0.1:80;
        listen [::1]:80;
        server_name admin.local;
    
        root /home/vo/projects/admin/public/Osm_Admin_Samples;
    
        index index.html index.php;
    
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }
    
        location = /favicon.ico { access_log off; log_not_found off; }
        location = /robots.txt  { access_log off; log_not_found off; }
    
        access_log /var/log/nginx/admin.local-access.log combined;
        error_log  /var/log/nginx/admin.local-error.log error;
    
        sendfile off;
    
        client_max_body_size 100m;
    
        location ^~ /_ {
            expires 30d;
        }
    
        location ~ \.php$ {
            fastcgi_split_path_info ^(.+\.php)(/.+)$;
            fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
            fastcgi_index index.php;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    
            fastcgi_intercept_errors off;
            fastcgi_buffer_size 16k;
            fastcgi_buffers 4 16k;
            fastcgi_connect_timeout 300;
            fastcgi_send_timeout 300;
            fastcgi_read_timeout 300;
        }
    
        location ~ /\.ht {
            deny all;
        }
    } 
    
And a line added to `/etc/hosts`:

    127.0.0.1		admin.local

## Two Test Suites

### Migration Test Suite

Creating tables based on PHP class reflection is a big part of the project, and it is covered with a separate test suite:

* `phpunit_migrations.xml` - PHPUnit configuration file
* `tests_migrations/bootstrap.php` - bootstrap script that run before the test suite
* `tests_migrations/test_*.php` - unit tests

### Main Test Suite

Once I have confidence in the migration code, the main test suite will just run the migrations in its bootstrap script. The main test suite covers all the other logic:

* `phpunit.xml` - PHPUnit configuration file
* `tests/bootstrap.php` - bootstrap script that run before the test suite
* `tests/test_*.php` - unit tests
 