# Contributor Installation

Yesterday, I tested the instructions for installing Osm Admin locally as a contributor.

Details: 

{{ toc }}

### meta.abstract

Yesterday, I tested the instructions for installing Osm Admin locally as a contributor.

## Testing Installation Instructions

`README` states that in order to install the application, all you have to do is run `bin/install.sh`.

**Later**. On Windows, it will be `bin/install.bat`.

However, installation requires some manual actions on the user's behalf that may differ from one installation to another. 

Take, for example, creation of the database. Prior to installing the application, the user should create an empty MySql database, and put the MySql connection details into the `.env.Osm_App` file (for contributors, it's `.env.Osm_Admin_Samples` file).

**Later**. It's not the best possible installation experience. Most users are new to the platform, and they better be guided by a wizard. On the other hand, the current approach is OK when the application creation process is fully automated (for example, in a GitHub action).

I've tested project creation manually, fixed some glitches, added `bin/create-env.sh` script, and updated `README`.

## Need For Nginx Configuration Commands

I've also tested putting the project under Nginx, and, frankly, I'm not satisfied. There is too much manual labor, and a typo may result in significant time spent on finding what's wrong with the configuration.

There should be a better way.

**Later**. I've heard that for such a problem, [Docker](https://www.docker.com/) might be a solution. However, installing it locally should be easy, too. 

For now, there will be three commands:

    # create the `nginx_virtual_host.conf` file. Optionally, pass a domain name
    # as an argument. If it's omitted, `$NAME.local` is used.
    osmt config:nginx
    
    # add the domain name to the hosts file. Optionally, pass a domain name
    # as an argument. If it's omitted, `$NAME.local` is used. By default, the
    # domain name maps to the localhost, `127.0.0.1`, use `--ip=1.2.3.4` to
    # override that.
    sudo osmt config:host

    # copy the `nginx_virtual_host.conf` file to Nginx configuration, and 
    # restart Nginx. If the file already exists in the Nginx configuration, it
    # will be overwritten.    
    sudo osmt install:nginx
    
Actually, these commands are useful in any project based on Osm Framework, so I'll implement them in the `osmphp/framework` package.