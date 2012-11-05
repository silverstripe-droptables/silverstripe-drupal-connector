# SilverStripe Drupal Connector Module

The Drupal connector module allows you to link to and import pages and hierarchies
from an external Drupal site.

The module supports importing hierarchies by menu and by taxonomy, and also can import
taxonomies into tags.

## Maintainer Contacts
*  Robert Curry (<robert@silverstripe.com>)

## Requirements
*  SilverStripe 3.0+
*  The External Content module.

## Installation Instructions
*  Ensure the External Content module is installed
*  Place this directory in the root of your SilverStripe site, making sure it
   is named "drupal-connector".
*  Run example.com/dev/build on your site to re-build the database.

## Setting up your Drupal site
To prepare a Drupal site to be connected to by this module, you'll need to do the following:
* Install and enable the services module (http://drupal.org/node/109640/release).
* Enable the node, taxonomy and user services.
* Enable the XMLRPC Server.

There are extra steps for a Drupal 5.x and 6.x site:
* Create an API key for the connector by visiting /build/services/keys. After creating a new key, you'll need to enter in the Key and Domain into the connector settings.
* Visit /admin/content/rss-publishing and increase the "Number of items per feed:" to the maximum possible. This value is used by the XMLRPC taxonomy functions as a limit of the number of nodes that can be retrieved from a query.

You can also fetch from menus if are you are on Drupal version 7.x. You'll need to install the services menu module (http://drupal.org/project/services_menu) and enable the menu service.

You can also create a new user just for the connector to use. If you do so, make sure it has all the appropriate permissions to use the services (and services menu) module.

## Project Links
*  [GitHub Project Page](https://github.com/silverstripe-droptables/silverstripe-drupal-connector)
*  [Issue Tracker](https://github.com/silverstripe-droptables/silverstripe-drupal-connector/issues)