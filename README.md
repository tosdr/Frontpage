# CrispCMS - The new ToS;DR Frontpage

![](https://beta.tosdr.org/api/badge/service/tos;dr) [![Translation status](https://translate.jback.dev/widgets/crispcms/-/crispcms-theme/svg-badge.svg)](https://translate.jback.dev/engage/crispcms/) [![Build Status](https://ci.jback.dev/api/badges/tosdr/CrispCMS/status.svg)](https://ci.jback.dev/tosdr/CrispCMS)

  ![](https://raw.githubusercontent.com/tosdr/CrispCMS/master/themes/crisp/img/tosdr-logo-128-w.png)

Welcome to the official repository for our frontpage, [tosdr.org](https://tosdr.org/).
This is a redo of our previous frontpage, which used JS.

If you wish to contribute, please check our [Code of Conduct](https://github.com/tosdr/CrispCMS/blob/master/CODE_OF_CONDUCT.md) before anything else.

_This readme is still a_ **WIP**_, but should cover basic requirements to install._

# Table of Contents

1. [Updating Submodules](#updating-submodules)
2. [Requirements](#requirements)
  1. [Plugins](#plugins)
3. [Installation](#installation)
  1. [Composer Dependencies](#installing-composer-dependencies)
  2. [Configuring Crisp](#configuring-crisp)
  3. [Running Database Migrations](#running-database-migrations)

## Updating Submodules

To update the submodules in this repository, simply run:

```bash
$ git submodule update --init --recursive
$ git submodule foreach --recursive git fetch
$ git submodule foreach git merge origin master
```

And you're set!

## Requirements

<!--TODO(?): Add Installation for (some) of these dependencies.-->

* `Redis Server` &mdash; Used to cache [Phoenix](https://edit.tosdr.org/) requests

* `MySQL` &mdash; Used for running the Crisp database.

  - To install on Debian-based Distros (Ubuntu, Mint, Pop OS, etc.), run:

  ```bash
  $ sudo apt-get update
  $ sudo apt-get install mysql-server     # Accept the installation.
  $ sudo mysql_secure_installation        # Configure and set-up the server.
  ```

  * *Additional instructions for server setup can be found [here](https://www.digitalocean.com/community/tutorials/how-to-install-mysql-on-ubuntu-20-04).*

  - For users running on Arch-based Distros (Arch Linux, Manjaro, etc.), check [this ArchWiki Article](https://wiki.archlinux.org/index.php/MySQL).

* `PHP-7.4` &mdash; Self explainatory.
  
  - To install on Debian-based Distros, run:

  ```bash
  $ sudo apt-get update
  $ sudo apt-get install php libapache2-mod-php     # Comes with Apache libraries for PHP.
  ```

  <!--
  TODO: Check for other required dependencies on Arch.
  
  - To install on Arch-based distros, run:

  ```bash
  $ sudo pacman -S php php-apache   # Installs PHP and modules for Apache.
  ```
  -->

* `Composer` &mdash; Used to install required dependencies from `composer.json`.

* `Shell Access` &mdash; You'll need it to install plugins or setup cron jobs.

* `Apache2.4` &mdash; As of right now only [Apache](https://httpd.apache.org/) is supported. [`Nginx`](https://nginx.org/en/) may come as well in the future, though!

  - To install on Debian-based distros, run:

  ```bash
  $ sudo apt-get update
  $ sudo apt-get install apache2    # Installs apache2
  ```

  * *Your configuration files are located on `/etc/apache2/`, and your Server Roots will (usually) be stored on `/var/www/`. For more info, check* [*this article*](https://linuxconfig.org/how-to-install-apache-on-ubuntu-20-04).

  - `mod_rewrite` - Used for the `.htaccess` instances to enable URL redirection and rewriting. [_More info_](https://httpd.apache.org/docs/current/mod/mod_rewrite.html)

* [Phoenix](https://github.com/tosdr/edit.tosdr.org) &mdash; We recommend running your own Phoenix instance during development so you have control over the API and you don't get ratelimited. Check its repository for more info.

* `Discourse Plugins` &mdash; Crisp is also responsible for webhooks on [**our forum**](https://forum.tosdr.org/) to, for example, detect if and/or when a service has been added on Phoenix. _(This is entirely optional)_


### Plugins

Crisp has a plugin system integrated. More info about development can be found within this topic on our forum:

https://forum.tosdr.org/t/374

## Installation

To install Crisp please make sure you have installed all the [requirements](#requirements) mentioned prior.

### Installing Composer Dependencies

From the repository root, execute the following command:

```bash
$ composer install
```

If no errors are returned, you're good to go!

### Configuring Crisp

From the repository root, copy `.env.example` to `.env` and edit it according to your settings.

```bash
$ cp .env.example .env
```

* *The* `$GITHUB_TOKEN` *variable is required for private repos to access metadata.*

### Running Database Migrations

The database needs to be setup after the initial clone. To do this, run this command from the root
of this repository:

```bash
$ php bin/cli.php migrate
```

This will create all necessary tables, aswell as install plugins and themes.

<!--
No longer required, managed by migrations

### Install crisp theme

To install the default theme and create necessary data run

```bash
php bin/cli.php theme install crisp
```

### Install core plugin

To install the core plugin you need shell access and execute the following commands in the bin folder:

```bash
php bin/cli.php plugin install core
```
-->

And so your instance is ready to run now!
