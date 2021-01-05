# CrispCMS - The new ToS;DR Frontpage

![](https://beta.tosdr.org/api/badge/service/tos;dr) [![Translation status](https://translate.jback.dev/widgets/crispcms/-/crispcms-theme/svg-badge.svg)](https://translate.jback.dev/engage/crispcms/) [![Build Status](https://ci.jback.dev/api/badges/tosdr/CrispCMS/status.svg)](https://ci.jback.dev/tosdr/CrispCMS)

This readme is still WIP but should cover basic requirements to install.

## Update submodules

```
git submodule update --init --recursive
git submodule foreach --recursive git fetch
git submodule foreach git merge origin master
```

## Requirements

Redis Server - To cache Phoenix requests

MySQL - Used for Crisp database

PHP7.4 - Self explainatory

Composer - To install required dependencies from composer.json

Shell Access - You'd need it to install plugins or setup crons

Apache2.4 - As of right now only apache is supported! Nginx may come as well

mod_rewrite - used for the .htaccess

[Phoenix](https://github.com/tosdr/edit.tosdr.org) - We recommend running your own Phoenix instance during development so you have control over the API and you don't get ratelimited

Discourse (Optional) - Crisp is also responsible for crisp webhooks to detect if a service has been added, this is entirely optional.


## Plugins

Crisp has a plugin system. More info about development can be found here:

https://forum.tosdr.org/t/374

## Installation

To install Crisp please make sure you have installed all requirements.

### Install Composer dependencies

from the root of Crisp execute `composer install`

### Configure Crisp

Copy `.env.example` to `.env` and edit it according to your settings.

The GITHUB_TOKEN property is required for private repos to access metadata

### Run Database Migrations

The database needs to be setup on initial clone, to do this run this command:

```bash
php bin/cli.php migrate
```

This will create all necessary tables, install plugins and themes.

<!--
No longer required ,managed by migrations

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

Your instance is ready now!