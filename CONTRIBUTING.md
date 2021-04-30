# Contributing

When contributing to this repository, please first discuss the change you wish to make via issue,
email, forum post or GitHub Discussion before making a change. 

Please note we have a code of conduct, please follow it in all your interactions with the project.

## Pull Request Process

1. Ensure any install or build dependencies are removed before the end of the layer when doing a 
   build.
2. Update the README.md with details of changes to the interface, this includes new environment 
   variables, exposed ports, useful file locations and container parameters.
3. Increase the version numbers in any examples files and the README.md to the new version that this
   Pull Request would represent. The versioning scheme we use is [SemVer](http://semver.org/).
4. You may merge the Pull Request in once you have the sign-off of other developers, or if you 
   do not have permission to do that, you may request the second reviewer to merge it for you. 
   A passing CI test is required aswell.
   
   
## Deployment Process

When your contribution gets accepted and merged into master it is not immediately visible on tosdr.org.

We deploy from the master branch on a weekly basis, meaning somewhere during midnight on Saturday/Sunday
our [CI](https://ci.tosdr.org/tosdr/CrispCMS) automatically deploys changes from the latest approved and passed commit.

Critical security patches will be deployed manually upon merge. 
If your commit is between the latest deployment commit and security patch it will be deployed as well 

## [Code of Conduct](https://github.com/tosdr/CrispCMS/blob/master/CODE_OF_CONDUCT.md)

