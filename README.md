# CrispCMS - The new ToS;DR Frontpage

This readme is still WIP but should cover basic requirements to install.




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