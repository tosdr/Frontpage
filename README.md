# CrispCMS - The new ToS;DR Frontpage



<!--suppress HtmlDeprecatedAttribute -->
<p align="center">
  <a href="https://tosdr.org/en/service/596" title="Privacy Grade">
    <img alt="ToS;DR Privacy Shield" src="https://shields.tosdr.org/tosdr.svg">
  </a>
  <a href="https://discord.gg/tosdr" title="Join the Discord chat at https://discord.gg/tosdr">
    <img alt="Discord Member count" src="https://img.shields.io/discord/324969783508467715.svg">
  </a>
  <a href="https://translate.tosdr.org/engage/crispcms/" title="Translations">
    <img alt="Translation Status" src="https://translate.tosdr.org/widgets/crispcms/-/svg-badge.svg">
  </a>
  <a href="https://github.com/tosdr/CrispCMS/releases/latest" title="GitHub release">
    <img alt="Release" src="https://img.shields.io/github/release/tosdr/CrispCMS.svg">
  </a>
  <a href="https://opencollective.com/tosdr" title="Become a backer/sponsor of ToS;DR">
    <img alt="Opencollective" src="https://opencollective.com/tosdr/tiers/backers/badge.svg?label=backers&color=brightgreen">
  </a>
  <a href="https://opensource.org/licenses/GPL-3.0" title="License: GPL-3.0">
    <img alt="License" src="https://img.shields.io/badge/License-GPL%203.0-blue.svg">
  </a>
  <a href="https://ci.tosdr.org/tosdr/CrispCMS" title="Build Status">
    <img alt="CI" src="https://ci.tosdr.org/api/badges/tosdr/CrispCMS/status.svg">
  </a>
</p>

<p align="center">
	<img alt="ToS;DR Logo" src="https://tosdr-branding.s3.eu-west-2.jbcdn.net/tosdr-logo-128.svg">
</p>
Welcome to the official repository for our frontpage, [tosdr.org](https://tosdr.org/).
This is a redo of our previous frontpage, which used JS.

If you wish to contribute, please check our [Code of Conduct](https://github.com/tosdr/CrispCMS/blob/master/CODE_OF_CONDUCT.md) before anything else.

_This readme is still a **Work in Progress**, but should cover basic requirements to install._

# Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
  * [Composer Dependencies](#installing-composer-dependencies)
3. [Configuring Crisp](#configuring-crisp)
  * [Running Database Migrations](#running-database-migrations)
4. [Plugins](#plugins)

## Requirements

You will need these requirements to run your instance of Crisp.

### [Redis Server](https://redis.io/)

This is used to cache [Phoenix](https://edit.tosdr.org/) requests.

### Postgres
This is used for running the Crisp database.

- To install on Debian-based Distros, run:

```bash
$ sudo apt-get update
$ sudo apt-get install postgresql     # Accept the installation.
```


### Apache vs Nginx

Apache is no longer supported. Use NGINX instead.

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
        try_files $uri /index.php?route=$uri$is_args$args;
        if ($request_uri ~ ^/(.*)\.html$) {
            return 302 /$1;
        }
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass   unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  IS_API_ENDPOINT true
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }


    listen 80;
}
```

### PHP-8.0
This one is quite self explainatory.


#### Nginx

- To install on Debian-based Distros, run:

```bash
$ sudo apt-get update
$ sudo apt install php8.0-fpm nginx
$ sudo apt-get install php8.0-{apcu,cli,curl,gd,gmp,intl,json,mbstring,pgsql,redis,xml,zip} # The dependencies
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

And you're set!

### Running Database Migrations

The database needs to be setup after the initial clone. To do this, run this command from the root
of this repository:

```bash
$ php bin/cli.php migrate
$ php bin/cli.php theme install crisp
$ php bin/cli.php plugin install management
```

This will create all necessary tables, as well as install plugins and themes.

Now your instance is ready to run!

## Plugins

Crisp has a plugin system integrated. More info about development can be found within this topic on our forum:

https://forum.tosdr.org/t/374
