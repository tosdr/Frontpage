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

To update the submodules in this repository, simply do:

```bash
$ git submodule update --init --recursive
$ git submodule foreach --recursive git fetch
$ git submodule foreach git merge origin master
```

And you're set!

## Requirements

<!--TODO(?): Add Installation for some of these dependencies.-->

* `Redis Server` &mdash; Used to cache [Phoenix](https://edit.tosdr.org/) requests

* `MySQL` &mdash; Used for the Crisp database.

* `PHP-7.4` &mdash; Self explainatory.

* `Composer` &mdash; Used to install required dependencies from `composer.json`.

* `Shell Access` &mdash; You'll need it to install plugins or setup cron jobs.

* `Apache2.4` &mdash; As of right now only [Apache](https://httpd.apache.org/) is supported. [`Nginx`](https://nginx.org/en/) may come as well in the future, though!

  * `mod_rewrite` - Used for the `.htaccess` instances, to enable URL redirection and rewriting. _[More info](https://httpd.apache.org/docs/current/mod/mod_rewrite.html)_

* [Phoenix](https://github.com/tosdr/edit.tosdr.org) &mdash; We recommend running your own Phoenix instance during development so you have control over the API and you don't get ratelimited. Check its repository for more info.

* `Discourse Plugins` &mdash; Crisp is also responsible for webhooks on [our Forum](https://forum.tosdr.org/), to detect if a service has been added on Phoenix. _(This is entirely optional)_


### Plugins

Crisp has a plugin system integrated. More info about development can be found here:

https://forum.tosdr.org/t/374

## Installation

To install Crisp please make sure you have installed all the [requirements](#requirements) mentioned prior.

### Installing Composer Dependencies

From the repository root, execute the following command:

```bash
$ composer install
```

### Configuring Crisp

Copy `.env.example` to `.env` and edit it according to your settings.

```bash
$ cp .env.example .env
```

* *The* `GITHUB_TOKEN` *property is required for private repos to access metadata.*

### Running Database Migrations

The database needs to be setup on initial clone, to do this run this command:

```bash
$ php bin/cli.php migrate
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
