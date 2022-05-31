# Automating Nginx Configuration

I've implemented the commands as [specified](27-data-contributor-installation.md#need-for-nginx-configuration-commands).

One thing that is different is under `sudo`, `osmt` command alias is no longer defined, and you have to write full unaliased command, for example:

    # sudo osmt config:host doesn't work 
    sudo php vendor/osmphp/framework/bin/tools.php config:host
    
It's not pretty, but works. If you have a suggestion on making it more elegant, let's discuss.

Overall, the installation experience is a lot smoother!

Typical usage:

    osmt config:nginx
    sudo php vendor/osmphp/framework/bin/tools.php config:host
    sudo php vendor/osmphp/framework/bin/tools.php install:nginx

For more details, see [documentation](https://osm.software/docs/framework/0.15/getting-started/web-server.html#nginx).

### meta.abstract

Use `config:nginx`, `config:host` and `install:nginx` commands to automate Nginx configuration of your project. Now, it's fully automated!
