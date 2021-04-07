# CrispCMS - The new ToS;DR Frontpage

![](https://shields.tosdr.org/tosdr.svg) [![Translation status](https://translate.tosdr.org/widgets/crispcms/-/svg-badge.svg)](https://translate.tosdr.org/engage/crispcms/) [![Build Status](https://ci.tosdr.org/api/badges/tosdr/CrispCMS/status.svg)](https://ci.tosdr.org/tosdr/CrispCMS)

  ![](https://tosdr-branding.s3.eu-west-2.jbcdn.net/tosdr-logo-128.svg)

Welcome to the official repository for our frontpage, [tosdr.org](https://tosdr.org/).
This is a redo of our previous frontpage, which used JS.

If you wish to contribute, please check our [Code of Conduct](https://github.com/tosdr/CrispCMS/blob/master/CODE_OF_CONDUCT.md) before anything else.

_This readme is still a **Work in Progress**, but should cover basic requirements to install._

# Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
  * [Composer Dependencies](#installing-composer-dependencies)
3. [Configuring Crisp](#configuring-crisp)
  * [Updating Submodules](#updating-submodules)
  * [Running Database Migrations](#running-database-migrations)
4. [Plugins](#plugins)

## Requirements

You will need these requirements to run your instance of Crisp.

### [Redis Server](https://redis.io/)

This is used to cache [Phoenix](https://edit.tosdr.org/) requests.

- For users running on Debian-based distros, check [this article](https://bitlaunch.io/blog/installing-redis-server-on-ubuntu-20-04-lts/)

### Postgres
This is used for running the Crisp database.

- To install on Debian-based Distros, run:

```bash
$ sudo apt-get update
$ sudo apt-get install postgresql     # Accept the installation.
```

*Additional instructions for server setup can be found [here](https://www.digitalocean.com/community/tutorials/how-to-install-mysql-on-ubuntu-20-04).*

- For users running on Arch-based Distros, check [this ArchWiki Article](https://wiki.archlinux.org/index.php/MySQL).

### Apache vs Nginx

For testing we recommend using apache, however we use nginx on our production servers to combat the high volume

#### Nginx

Frontpage config:
```nginx
server {
    server_name tosdr.org;

    root   PATH_TO_YOUR_GITHUB_REPO;
    index  index.php index.html index.htm;

    location ~ /\. {
        deny all;
    }

    location / {
        try_files $uri /index.php?route=$uri$is_args$args;
        if ($request_uri ~ ^/(.*)\.html$) {
            return 302 /$1;
        }
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass   unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }

    listen 80 default_server;
}
```

API Config:
```nginx
server {
    server_name api.tosdr.org;

    location ~ /\. {
        deny all;
    }

    location / {
        proxy_set_header X-Real-IP $remote_addr;
        proxy_pass https://$VLAN_CONN_DC_14/api/;
    }

    listen 80;
}
```

#### Apache

Apache only requires mod rewrite and headers to be enabled as everything is handled by the htaccess files.

### PHP-7.4
This one is quite self explainatory.

#### Apache

- To install on Debian-based Distros, run:

```bash
$ sudo apt-get update
$ sudo apt-get install apache2 php7.4 libapache2-mod-php     # Comes with Apache libraries for PHP.
$ sudo apt-get install php7.4-apcu php7.4-cli php7.4-curl php7.4-gd php7.4-gmp php7.4-intl php7.4-json php7.4-mbstring php7.4-pgsql php7.4-redis php7.4-xml php7.4-zip # The dependencies
```

#### Nginx

- To install on Debian-based Distros, run:

```bash
$ sudo apt-get update
$ sudo apt install php7.4-fpm nginx
$ sudo apt-get install php7.4-apcu php7.4-cli php7.4-curl php7.4-gd php7.4-gmp php7.4-intl php7.4-json php7.4-mbstring php7.4-pgsql php7.4-redis php7.4-xml php7.4-zip # The dependencies
```

See Nginx configuration above on how to connect to your FPM socket.

### [Composer](https://getcomposer.org/)
This is used to install required dependencies from `composer.json`.

- To install on Debian-based distros, follow [this tutorial](https://www.digitalocean.com/community/tutorials/how-to-install-composer-on-ubuntu-20-04-quickstart).

### Shell Access
You'll need it to install plugins or setup cron jobs.

### [Phoenix](https://github.com/tosdr/edit.tosdr.org)
We recommend running your own Phoenix instance during development so you have control over the API and you don't get ratelimited. Check its repository for more info.

### Discourse Plugins
Crisp is also responsible for webhooks on [**our forum**](https://forum.tosdr.org/) to, for example, detect if and/or when a service has been added on Phoenix. _(This is entirely optional)_

## Installation

***To install Crisp please make sure you have installed all the [requirements](#requirements) mentioned prior.***

Run the following commands on your terminal:

```bash
$ git clone --recursive https://github.com/tosdr/CrispCMS.git
$ cd CrispCMS
```

And you're ready to set it up!

### Installing Composer Dependencies

From the repository root, execute the following command:

```bash
$ composer install
```

If no errors are returned, you're good to go!

## Configuring Crisp

From the repository root, copy `.env.example` to `.env`:

```bash
$ cp .env.example .env
```

Then edit it accordingly.

* _The `$GITHUB_TOKEN` variable is required for private repos to access metadata._ [More Info](https://docs.github.com/en/github/authenticating-to-github/creating-a-personal-access-token)

### Updating Submodules

To update the submodules in this repository, simply run these commands in order:

```bash
$ git submodule update --init --recursive
$ git submodule foreach --recursive git fetch
$ git submodule foreach git merge origin master
```

And you're set!

### Running Database Migrations

The database needs to be setup after the initial clone. To do this, run this command from the root
of this repository:

```bash
$ php bin/cli.php migrate
$ php bin/cli.php theme install crisp
```

This will create all necessary tables, aswell as install plugins and themes.

And so your instance is ready to run now!

## Plugins

Crisp has a plugin system integrated. More info about development can be found within this topic on our forum:

https://forum.tosdr.org/t/374
