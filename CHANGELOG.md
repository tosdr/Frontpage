# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Added pull request templates
- Add translation template
- Added Changelog (Me!)
- Added Dedicated API Domain
- Added v{version} prefix to API

### Changed
- Fix Generate API Files TTL

### Removed
- Ratelimit (temporarily)

## [1.0.2] - Jan 15, 2021, 12:18 AM GMT+1
### Added
- Add zip to drone
- Add wget to debian image
- Switch to libicu-dev
- Rebrand to privacy shields, add shield cdn
- Add docker-php-ext-install intl to CIdue to ratelimit lib
- Added ratelimit error message

### Changed
- Don't publish if tagged [NO CI] 
- Drop everything in a tmp dir
- Manual composer install
- Compile ext in seperate step
- Publish docker from CI
- RateLimiting behind RP

### Removed
- Removed cookies.js
- Remove flags to address #51 


## [1.0.1] - Jan 11, 2021, 11:50 PM GMT+1
### Added
- Added Ratelimit to API 100req/60s
- Added dockerfile to crisp
- Added POSTGRES_URI to .env
- Check if theme is not migrated
- Add theme dir to migrations and remove cookie notice
- Add cookie notice, fix #59, switch badges completely to IDs 
- Added unit tests and new CI
- Stuff and unit tests
- Create labeler.yml
- Create label.yml
- Added CI Badge to Readme
- Added apt-get confirm
- Added password to mysql pipeline
- Add Rack node
- Add env to drone file
- Add migration to CI
- Added some unit tests and drone CI
- Added new way to translate badges, cdn badges now
- Added CDN
- Preparation for partner api keys and new includeResource function
- Added margin left to grade badge. Fixes #48 
- Add embed generator
- Added additional links
- Add margin to service logos #48
- Add 3 new api endpoints as per reddit suggestion
- Add Presskit
- Add SVGS fix bugs and fix more bugs
- Custom badge renderer with logo
- New Badge Renderer
- Add core translations
- Add Dutch
- Add Translation Status
- Added language selector
- Add core translations
- Add LICENSE
- Add translation status
- Themes can now make use of seperate translation files by using a directory in createTranslationKeys
- Move translations to different file
- Add plugin migration TOS-48

### Changed
- Pushed Version to 1.0.1
- dump theme metadata
- Fix CLI stuff
- Fix $EnvFile["POSTGRES_URI"]
- Changes to Migration
- Only store cookies now if accepted. Woop
- Change Badges to ID, fixed #59 
- Fix empty queries
- Update issue templates
- Translated using Weblate (Spanish)
- Translated using Weblate (Dutch)
- Translated using Weblate (German)
- Translated using Weblate (Spanish (Mexico))
- Translated using Weblate (French)
- Git overwrote new ci file... Migrated to buster
- Changed MYSQL_PASSWORD
- Fixed mysql password
- Changed DB host to reflect service
- Increase Redis ping and change hostname
- CDN more stuff
- Updated API
- Updated embed generator to honor urlencoding
- Fixed urlencoding in service slugs
- Update submodule
- Update cli 
- Fixed nasty tooltip bug
- Service page title is now service name
- Fix cronjobs and add ExecuteOnce directive to crons
- Made plugin refresh over 30000% faster
- Translate badges on frontpage
- Badges are now translated using the l= parameter
- Fix unsafe html in translation
- Split legal page translations
- Fixed CLI echo
- German translations
- Fix locale bug in translation
- Fix https
- Update docs
- Fix plugin translations
- Catch throwables, fixes TOS-47
- Throw 404 only in 1 event TOS-47
- Fix 404 exception
- fix translation for StatusPage

### Removed
- Removed rack config
- Remove depends on
- Remove HTML and add more translations for classification

## [0.0.8-beta.RC3] - Dec 26, 2020, 6:13 AM GMT+1
### Added
- Add documentation for DB Migration TOS-45
- Add documentation for Errorreporter TOS-45
- Finished docs for phonix class TOS-45
- Add API Stats
- Add listAllByCount
- Add dropColumn to migrations TOS-43 

### Changed
- Protect migration functions TOS-44
- Update documentation for Languages TOS-45
- Update documentation for Languages TOS-45
- Update documentation for Phoenix TOS-45
- Fixes critical bug TOS-46
- Update docs for MySQL TOS-45
- Update docs for Plugin TOS-45
- Update docs for Plugins TOS-45
- Update docs for Postgres TOS-45
- Update docs for Redis TOS-45
- Update docs for Theme TOS-45
- Update docs for Theme TOS-45
- Update docs for Themes TOS-45
- Sort migrations by timestamp now.
- Update docs and submodule

### Removed


## [0.0.8-beta.RC2] - Dec 25, 2020, 4:32 PM GMT+1
### Added
- Added Database Migration System

## Changed

### Removed


## [0.0.8-beta.RC1] - Dec 25, 2020, 8:14 AM GMT+1
### Added
- Plugins have now the following new properties in their plugin.json available: testedVersion, incompatibleVersion, repository
- Added `\crisp\core\Plugins::testVersion` function
- Added `\crisp\core\Plugins::listTranslations` function
- Added `\crisp\core\Plugins::listConfig` function
- Added `\crisp\core\Plugins::listPlugins` function
- Added `\crisp\core::CRISP_VERSION` constant

### Changed
- When uninstalling, refreshing storage or translations Crisp will not iterate over the plugin.json anymore, it will retrieve all keys added by plugins and delete them instead. Now custom created configs and translations outside of the plugin.json will be cleaned up.

### Removed
- Nothing


[Unreleased]: https://github.com/tosdr/CrispCMS/compare/1.0.2...HEAD
[1.0.2]: https://github.com/tosdr/CrispCMS/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/tosdr/CrispCMS/compare/0.0.8-beta.RC3...1.0.1
[0.0.8-beta.RC3]: https://github.com/tosdr/CrispCMS/compare/0.0.8-beta.RC2...0.0.8-beta.RC3
[0.0.8-beta.RC2]: https://github.com/tosdr/CrispCMS/compare/0.0.8-beta.RC1...0.0.8-beta.RC2
[0.0.8-beta.RC1]: https://github.com/tosdr/CrispCMS/releases/tag/0.0.8-beta.RC1


<!-- Template

## [TAG] - TIME
### Added
-
-
-
-

### Changed
-
-
-
-

### Removed
-
-
-
-
-->
