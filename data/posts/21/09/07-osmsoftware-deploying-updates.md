# Deploying Updates

After pushing changes to GitHub, [osm.software](https://osm.software/) website
is updated **without any additional manual action**. On push, GitHub executes the deployment script on the production server. Most changes are done to content only, and in this case, the deployment script only updates the search index. Otherwise, with the website being on maintenance, it additionally updates Composer and Node dependencies, builds JS and CSS assets, and runs the database migrations.    

Details:

{{ toc }}

### meta.abstract

After pushing changes to GitHub, *osm.software* website
is updated **without any additional manual action**. On push, GitHub executes the
deployment script on the production server. Most changes are done to content
only, and in this case, the deployment script only updates the search index.
Otherwise, with the website being on maintenance, it additionally updates
Composer and Node dependencies, builds JS and CSS assets, and runs the database
migrations.  

## GitHub `deploy` Action

The [`.github/workflows/deploy.yml`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/.github/workflows/deploy.yml) is a [GitHub action](https://docs.github.com/en/actions). It runs whenever changes are pushed, or a pull request is merged into the production branch (at the moment of writing, `v0.2`):

    name: deploy
    on:
        push:
            branches: [ "v0.2" ]
        pull_request:
            branches: [ "v0.2" ]
    ...
    
The `deploy` action creates a "container" (similar to virtual machine) with Ubuntu operating system, and from that machine it connects to the production server via SSH, and executes
the `bin/deploy.sh` script in the project directory:

    jobs:
        ubuntu:
            runs-on: ubuntu-latest
            steps:
                -   name: Run deploy script on the production server
                    uses: garygrossgarten/github-action-ssh@release
                    with:
                        command: cd ~/www && bash bin/deploy.sh
                        host: ${{ secrets.DEPLOY_HOST }}
                        username: ${{ secrets.DEPLOY_USER }}
                        passphrase: ${{ secrets.DEPLOY_PASSPHRASE }}
                        privateKey: ${{ secrets.DEPLOY_PRIVATE_KEY}}

`secrets.*` variables, mentioned in the GitHub action, are defined in the `Secrets` section of the `osmphp/osmsoftware-website` GitHub repository:

![`DEPLOY_*` Secrets](deploy-secrets.png)

## SSH Key Pair

GitHub connects to the production server via SSH using public key authentication.

Both SSH private and public keys for this connection are generated using `ssh-keygen`
command.
 
SSH private key is copied into the `DEPLOY_PRIVATE_KEY` GitHub repository secret variable, and it's used to connect via SSH. 

SSH public key is added to project user's `~/.ssh/authorized_keys` file on the production server, and it's used to verify that matching SSH private key is allowed to log in:

    ...
    ssh-rsa AAAAB3Nza...qI32q/zG+M= GitHub deploy action in osmphp/osmsoftware-website repo

## Deployment Script

The [`bin/deploy.sh`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/bin/deploy.sh) script, initiated by the GitHub action:

1. As [Bash aliases](../08/10-framework-command-line-aliases.md) don't work in Bash scripts, it defines variables to be used instead of aliases: 

        OSM="php vendor/osmphp/framework/bin/console.php"
        OSMC="php vendor/osmphp/core/bin/compile.php"
        OSMT="php vendor/osmphp/framework/bin/tools.php"

2. Then it detects the production branch, and fetches new commits from GitHub:

        BRANCH=$(git rev-parse --abbrev-ref HEAD)
        git fetch

3. It also detects if there were any file changes, and if there were non-content file changes: 

        ALL_CHANGES=$(git diff --name-only $BRANCH..origin/$BRANCH)
        CODE_CHANGES=$(git diff --name-only $BRANCH..origin/$BRANCH :^data/)

4. If only blog content has changed, it pulls the changes from GitHub, and
   runs `osm index` command in order to update the database and the
   ElasticSearch index:

        git merge origin/$BRANCH
        $OSM index

5. Otherwise, that is, if either code files have been changes, or no files have been changed at all, with the website being on maintenance, it additionally updates Composer and Node dependencies, builds JS and CSS assets, and runs the database migrations:

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

