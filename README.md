# Standout Onderhoud WordPress plugin

This is a simple wordpress plugin mainly used with the Standout Wp onderhoud dashboard, but it could also be used for other projects.

The plugin adds 4 endpoints to remote update and test your wordpress websites.

## Running local development envoirment

We run a local wordpress testing envoirment using @wordpress/env. To start up a local envoirment first make sure you have docker installed, then run `composer install` and `npm install`.

After you have everything installed you can whip up a new envoirment with `npm run start-wp`.

> Heads up, on first booting a new envoirment we setup the permalinks via wp-cli. However for this to fully work you still have to visit the permalinks page.

If you need to fully reset the wordpress envoirment you can use the `npm run reset-wp` this will shut down and delete the docker volumes. And then run the start-wp command again.

To stop the local testing envoirment run `npm run stop-wp` or manually stop the docker containers.

### Changing php version

Inside the project directory are 2 .wp-env.json files, one will have the .nonactive suffix, needless to say this is the non active one.

Look inside the file for properties like php version & wordpress / plugin versions used while running the @wordpress/env commands.

## Creating a new release

When creating a new release of the plugin first update the version in the `standout-onderhoud-wp.php` file located at the root of the project.

Then push / merge your changes into the master branch and manually create a new github release with the same version number.

The update should now be made available.

![the office gif]: (https://media.tenor.com/Lh-0wgQBaL8AAAAC/it-crowd-roy-and-moss.gif)
