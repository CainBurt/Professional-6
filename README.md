# Crowd base build


## Now includes Crowd's gulpfile.js!

Crowd uses [Gulp](http://gulpjs.com/) for managing front-end build tooling and
workflow. Find out [more about
gulp](https://github.com/gulpjs/gulp/blob/master/docs/getting-started.md) and
the [plugins available for it](http://gulpjs.com/plugins/).

This version of the Crowd Base Build includes Gulp version 4.
This means you must be using the latest version of Node (or at lease version 10).

### Setup

1. Install [Node + NPM](https://www.npmjs.com/get-npm).
2. Run `npm install -g gulp` to install gulp globally.
3. `cd` into your themes directory.
4. Run `npm install`.
5. Have a kitkat 🍫

### Updating Node and NPM

If you installed Node with homebrew then just run `brew upgrade node`. If you
used a binary installer then download the [latest version of
Node](https://nodejs.org/en/) from the website.

To update NPM run `npm install npm@latest -g`.

To update packages from old gulpfiles run `npm outdated` to see local packages
that are out of date. Then run `npm install packageName@latest` to bring that
package up to date.

### Crowd's standard tasks

`gulp` Will run and then watch sass, js, and images tasks.

`gulp package` will product a package in the directory above that contains
whitelisted files. This is used to generate a 'package' version of the theme
that can be deployed to developement environments.

#### NPM scripts

Npm scripts are also setup, if you want to use them.

`npm start` basically just runs `gulp` (the standard dev / watch task).

`npm run build` runs `gulp package`.

### Flags

`--production` will run tasks configured to output for production. This often
means output files will be compressed.

`gulp package --production` will run the packaging task while also compressing
outputted files. The generated package should be deployed to development
environments and live environments.

`--pipeline` is for use only if the task is being run from a pipeline. All this does is change the name of the packaged folder to 'pipeline' so that bitbucket can save it as an artifact for automated deployment.


## Setting up CI (Bitbucket Pipelines) for your project

The Crowd base build now comes with a `bitbucket-pipelines.yml` file which is a
YAML config for Continuous Integration. It has some sensible defaults, and some
areas which will need to be uncommented + configured for your repository.

By default you'll need to add the following environment variables to your
bitbucket repo in the repository settings.

The environment variable `LFTP_infrastructure_user_string` holds the SFTP
connection details to your destination server. __It is very important to click
the 'padlock' icon when adding this environment variable to your repository.__
This will mask and encrypt the sensitive information detailed below (SFTP
usernames/passwords).


For TSO, this could look like the following:
```
sftp://sftp-username:sftp-password@shell.gridhost.co.uk
```

For SiteGround, LFTP does not quite work, so we use rsync.
This means using the THEME_DIR environment variable like so:
```
u278-z85k9umvk5y4@thisiscrowdlab.com:/home/customer/www/{ SITE FOLDER }/public_html/wp-content/themes/{ PACKAGE NAME }
```
Make sure that your Pipeline's SSH key (Settings -> SSH Keys) is imported to Siteground in SSH Keys Manager.

For Flywheel, it would look similar to the following:
```
sftp://crowdinfrastructure:crowd-infra-pass@sftp.flywheelsites.com
```

The flywheel deployment uses our `crowdinfrastructure` Flywheel account. You'll
need to contact Matt to get the password for this account.

### Branch Configuration

In the file there are build steps configured for two branches based on the Crowd
standards, `develop` and `master`. Feel free to rename these in the yml file if
you have a different branching structure in your repo.

#### Develop Branch CI Configuration

The develop branch has 1 automated step configured by default named `Build
Package`. You can see the actions this step will run in the `script` section of
this step in the `bitbucket-pipelines.yml` file. Essentially, it's similar to
building a package on your local environment with `gulp package --production`.

There is some __required configuration__. The second step, 'Deploy to UAT', is
commented out by default as it requires you to specifiy the path in the
destination filesystem you would like to deploy to. See the line below:

```
- "lftp $LFTP_infrastructure_user_string -e 'set sftp:auto-confirm yes; mirror -R --parallel=5 --delete --verbose=1 pipeline/ path/to/my/basebuild_theme-package; exit'"
```
This line references the mock path `path/to/my/basebuild_theme-package` where
`path/to/my/` is the destination path of the files, usually something like
`public_html/wp-content/themes/`. `basebuild_theme-package` is the name of the
folder which gets made when you run the `gulp package` task, __if you have
changed this name you will need to reflect this change here__.

'Deploy to UAT' is a manual step, which means when the build has run in
BitBucket, __you will have to go into the BitBucket pipelines area in the repo
and click a button marked 'Deploy'__ to actually deploy to UAT.

#### Master Branch CI Configuration

Master branch is largely the same as develop branch, so follow the steps above
to get mostly setup.

Master branch also has an additional manual step which allows you to 'Deploy to
Production'. Obviously __this should only be triggered when you've tested the
UAT environment thoroughly__.

You must adjust the lftp path like develop branch above, but this time put the
path of your production environment.


### Known Hosts

To run successful deployments, you'll need to add your destination SFTP servers
to the repo's "known hosts" area.

1. Go to your repo in bitbucket
2. Find the repo settings (cog icon)
3. Go to 'SSH Keys'
4. Enter the 'host address' (e.g. `sftp.flywheelsites.com` or
   `shell.gridhost.co.uk`) and click `Fetch`.
