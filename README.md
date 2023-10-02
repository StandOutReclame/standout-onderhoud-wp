# Standout Onderhoud WordPress plugin

This is a simple wordpress plugin mainly used with the Standout Wp onderhoud dashboard, but it could also be used for other projects.

The plugin adds 4 endpoints to remote update and test your wordpress websites.

<img src="https://media.tenor.com/Lh-0wgQBaL8AAAAC/it-crowd-roy-and-moss.gif" width="100%" height="auto" alt="the it crowd gif" />

## Running local development envoirment

We run a local wordpress testing envoirment using @wordpress/env. To start up a local envoirment first make sure you have docker installed, then run `composer install` and `npm install`.

After you have everything installed you can whip up a new envoirment with `npm run start-wp`.

> Heads up, on first booting a new envoirment we setup the permalinks via wp-cli. However for this to fully work you still have to visit the permalinks page.

If you need to fully reset the wordpress envoirment you can use the `npm run reset-wp` this will shut down and delete the docker volumes. And then run the start-wp command again.

To stop the local testing envoirment run `npm run stop-wp` or manually stop the docker containers.

### Changing php or wp version

You can change the php or wp version, and many other things for the local envoirment, in the `.wp-env.json` file located at the root of the project.

> At the time of writing changing the php version below 7.4 appearently destorys everything, so you know be carefull. or dont.

<img src="https://media.tenor.com/PRN-EHOCuHwAAAAd/the-it-crowd-moss-the-it-crowd.gif" width="100%" height="auto" alt="the it crowd gif" />

## Creating a new release

When creating a new release of the plugin first update the version in the `standout-onderhoud-wp.php` file located at the root of the project. And in the `StandoutOnderhoudUpdater.php` fier located at `src/updates`.

Then push / merge your changes into the master branch and create a zipfile from the files.

Then manually create a new github release with the same version number, adding the zipfile you just downloaded from the master branch.

Finally update the self-host plugin manifest located at https://server2.standoutwerkplaats.nl/standout-onderhoud/manifest.json with the new version and link to the zipfile attached to the new github release.

The update should now be made available.

<img src="https://media.tenor.com/3O6vWZ9kGWMAAAAd/kogama-new-update.gif" width="100%" height="500" alt="New update when gif" />
