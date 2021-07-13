# Contributor Covenant Code of Conduct - CrispCMS

<p align="center">
	<img src="https://tosdr-branding.s3.eu-west-2.jbcdn.net/tosdr-logo-128-w.svg">
</p>

In the ToS;DR Project, to ensure the safety of our users, aswell as to guarantee
the quality of our software, we have to commit ourselvs to certain standards,
and that includes some rules for people to understand.

Thus, let's begin.

## Table of Contents

1. Contributor Covenant Code of Conduct
* [Our Pledge](#our-pledge)
* [Our Standards](#our-responsibilities)
* [Our Responsibilities](#our-responsibilities)
* [Scope of These Guidelines](#scope-of-these-guidelines)
* [Enforcement](#enforcement)
2. [Attribution](#attribution)

## Our Pledge

In the interest of fostering an open and welcoming environment, we as
contributors and maintainers pledge to making participation in our project and
our community a harassment-free experience for everyone, regardless of age, body
size, disability, ethnicity, sex characteristics, gender identity and expression,
level of experience, education, socio-economic status, nationality, personal
appearance, race, religion, or sexual identity and orientation.

## Our Standards

Examples of behavior that contributes to creating a positive environment
include:

* Using welcoming and inclusive language
* Being respectful of differing viewpoints and experiences
* Gracefully accepting constructive criticism
* Focusing on what is best for the community
* Showing empathy towards other community members


### Code Standards

All Commits must be GPG or SMIME signed.

#### CrispCMS

We follow strict coding standards since migrating to PHP8.0 and your code must comply with the following conduct (The list is long):

- Code follow to the PSR-12 standard
- 'switch' expression must replaced with 'match' expression where applicable. [See Example](https://github.com/tosdr/CrispCMS/commit/3043fe78f2942f2bef370a7cbeeb465a54317c52#diff-3f4b79736bb8f16f9cbc502158eb95187e294b7487cf97e237ec89474e5f03d9)
- No php short tags
- Do NOT mix HTML with PHP, make use of the templating system
- The templating system exposes all config values, do not override the `config` variable.
- Make use of templates in translations. `{{ config.* }}` is exposed globally.
- Always use translations, never use untranslated strings.
- Multiple `if`s followed by a `throw` may be written without brackets, in other cases **ALWAYS** use brackets.
- Do not use `str_pos` for string searching, use `str_contains` instead.
- Each class must have a seperate file.
- **Always** use camelCase syntax.
- Make use of the `use` statement as much as possible, avoid using fully qualified names.
- ternary expressions **are** allowed
- Optional arguments **must always** be at the end of a function signature.
- Reference static methods with `self` in the own class, no need to reference the class name
- Avoid unnecessary type casts.
- **NEVER** use a PHP closing tag.
- Construct new array with the `[]` syntax rather than `array()`
- Do not override the `$_GET["route"]` or `$GLOBALS["route"]` variables.
- Always ensure non-GET and non-HEAD requests are CSRF secured!
- Database modifications **MUST** be edited by a migration file!
- Make use of `includeResource` and `generateLink` in templates as much as possible.
- Do **NOT** modify API files without further approval by ToS;DR Staff
- Do **NOT** modify CI files without further approval by ToS;DR Staff
- Write test files.
- Avoid double qoutes where applicable.

Examples of unacceptable behavior by participants include:

* The use of sexualized language or imagery and unwelcome sexual attention or
  advances
* Trolling, insulting/derogatory comments, and personal or political attacks
* Public or private harassment
* Publishing others' private information, such as a physical or electronic
  address, without explicit permission
* Other conduct which could reasonably be considered inappropriate in a
  professional setting

## Our Responsibilities

Project maintainers are responsible for clarifying the standards of acceptable
behavior and are expected to take appropriate and fair corrective action in
response to any instances of unacceptable behavior.

Project maintainers have the right and responsibility to remove, edit, or
reject comments, commits, code, wiki edits, issues, and other contributions
that are not aligned to this Code of Conduct, or to ban temporarily or
permanently any contributor for other behaviors that they deem inappropriate,
threatening, offensive, or harmful.

## Scope of These Guidelines

This Code of Conduct applies both within project spaces and in public spaces
when an individual is representing the project or its community. Examples of
representing a project or community include using an official project e-mail
address, posting via an official social media account, or acting as an appointed
representative at an online or offline event. Representation of a project may be
further defined and clarified by project maintainers.

## Enforcement

Instances of abusive, harassing, or otherwise unacceptable behavior may be
reported by contacting the project team at team@tosdr.org. All
complaints will be reviewed and investigated and will result in a response that
is deemed necessary and appropriate to the circumstances. The project team is
obligated to maintain confidentiality with regard to the reporter of an incident.
Further details of specific enforcement policies may be posted separately.

Project maintainers who do not follow or enforce the Code of Conduct in good
faith may face temporary or permanent repercussions as determined by other
members of the project's leadership.

# Attribution

This Code of Conduct is adapted from the [Contributor Covenant][homepage], version 1.4,
available at https://www.contributor-covenant.org/version/1/4/code-of-conduct.html

[homepage]: https://www.contributor-covenant.org

For answers to common questions about this code of conduct, see
https://www.contributor-covenant.org/faq
